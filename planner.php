
<?php
/* =======================================================================
   index.php — Single-file app with NO AJAX, DB-backed persistence (XAMPP)
   Drop into:  C:\xampp\htdocs\study_planner\index.php
   Open:       http://localhost/study_planner/
   ======================================================================= */

session_start();
header('X-Content-Type-Options: nosniff');

$host = "localhost";
$user = "root";    // XAMPP default
$pass = "";        // XAMPP default
$db   = "study_planner";

/* ---------- DB bootstrap ---------- */
function db() {
  static $conn = null;
  global $host, $user, $pass, $db;
  if ($conn) return $conn;

  $conn = @new mysqli($host, $user, $pass);
  if ($conn->connect_error) {
    http_response_code(500);
    die("DB Connection failed: " . $conn->connect_error);
  }

  // Create DB if not exists
  $conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $conn->select_db($db);

  // Create tables if not exists
  $conn->query("CREATE TABLE IF NOT EXISTS semester (
      semester_id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(50) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )");

  $conn->query("CREATE TABLE IF NOT EXISTS subjects (
      subject_id INT AUTO_INCREMENT PRIMARY KEY,
      semester_id INT,
      name VARCHAR(100) NOT NULL,
      FOREIGN KEY (semester_id) REFERENCES semester(semester_id) ON DELETE CASCADE,
      INDEX(semester_id), INDEX(name)
  )");

  $conn->query("CREATE TABLE IF NOT EXISTS tasks (
      task_id INT AUTO_INCREMENT PRIMARY KEY,
      subject_id INT NULL,
      description VARCHAR(255) NOT NULL,
      completed TINYINT(1) DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE SET NULL,
      INDEX(subject_id), INDEX(completed)
  )");

  $conn->query("CREATE TABLE IF NOT EXISTS goals (
      goal_id INT AUTO_INCREMENT PRIMARY KEY,
      description VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )");

  $conn->query("CREATE TABLE IF NOT EXISTS daily_schedule (
      schedule_id INT AUTO_INCREMENT PRIMARY KEY,
      hour INT NOT NULL,      -- 0-23
      subject_id INT NULL,
      semester_id INT,
      FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE SET NULL,
      FOREIGN KEY (semester_id) REFERENCES semester(semester_id) ON DELETE CASCADE,
      UNIQUE KEY uniq_sem_hour (semester_id, hour),
      INDEX(semester_id), INDEX(hour)
  )");

  $conn->query("CREATE TABLE IF NOT EXISTS exam (
      exam_id INT AUTO_INCREMENT PRIMARY KEY,
      semester_id INT,
      exam_date DATE NOT NULL,
      FOREIGN KEY (semester_id) REFERENCES semester(semester_id) ON DELETE CASCADE,
      UNIQUE KEY uniq_sem (semester_id)
  )");

  $conn->query("CREATE TABLE IF NOT EXISTS study_files (
      file_id INT AUTO_INCREMENT PRIMARY KEY,
      file_name VARCHAR(255) NOT NULL,
      file_size_kb DECIMAL(12,2),
      uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )");

  return $conn;
}

/* ---------- Helpers ---------- */
function prg_redirect($msg = '') {
  if ($msg !== '') $_SESSION['flash'] = $msg;
  header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
  exit;
}

// Resolve or create a semester by name; return semester_id
function ensure_semester($name) {
  $c = db();
  $nameEsc = $c->real_escape_string($name);
  $res = $c->query("SELECT semester_id FROM semester WHERE name='$nameEsc' ORDER BY semester_id DESC LIMIT 1");
  if ($res && $row = $res->fetch_assoc()) return (int)$row['semester_id'];
  $c->query("INSERT INTO semester (name) VALUES ('$nameEsc')");
  return (int)$c->insert_id;
}

// Map subject name -> subject_id for a semester (create if missing)
function map_subject_ids($semester_id, $subject_names) {
  $c = db();
  $map = [];
  // Load existing
  $res = $c->query("SELECT subject_id, name FROM subjects WHERE semester_id=".$semester_id);
  while ($res && $row = $res->fetch_assoc()) {
    $map[$row['name']] = (int)$row['subject_id'];
  }
  // Ensure all names exist
  foreach ($subject_names as $nm) {
    if (!isset($map[$nm])) {
      $nmEsc = $c->real_escape_string($nm);
      $c->query("INSERT INTO subjects (semester_id, name) VALUES ($semester_id, '$nmEsc')");
      $map[$nm] = (int)$c->insert_id;
    }
  }
  return $map;
}

/* ---------- Handle POST: Save all data (NO AJAX) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_all') {
  $c = db();

  // 1) Semester
  $semester_name = trim($_POST['semester'] ?? '');
  if ($semester_name === '') {
    prg_redirect("Semester name required to save.");
  }
  $semester_id = ensure_semester($semester_name);

  // 2) Subjects (JSON array of strings)
  $subjects = [];
  if (!empty($_POST['subjects_json'])) {
    $subjects = json_decode($_POST['subjects_json'], true) ?: [];
  }
  // Replace subjects for current semester with posted set (idempotent)
  $c->query("DELETE FROM subjects WHERE semester_id=".$semester_id);
  foreach ($subjects as $s) {
    $sEsc = $c->real_escape_string($s);
    $c->query("INSERT INTO subjects (semester_id, name) VALUES ($semester_id, '$sEsc')");
  }
  // Build name->id map after insert
  $subj_map = [];
  $res = $c->query("SELECT subject_id, name FROM subjects WHERE semester_id=".$semester_id);
  while ($res && $row = $res->fetch_assoc()) $subj_map[$row['name']] = (int)$row['subject_id'];

  // Helper to parse "SUBJ: rest" prefix
  $resolveSubjectFromTaskText = function($text) use ($subj_map) {
    if (strpos($text, ':') !== false) {
      $maybe = trim(substr($text, 0, strpos($text, ':')));
      if (isset($subj_map[$maybe])) return $subj_map[$maybe];
    }
    // Also match if text starts with "SUBJ " (no colon)
    $firstWord = strtok($text, " ");
    if ($firstWord && isset($subj_map[$firstWord])) return $subj_map[$firstWord];
    return null;
  };

  // 3) Tasks (JSON array of {text, completed})
  $tasks = [];
  if (!empty($_POST['tasks_json'])) {
    $tasks = json_decode($_POST['tasks_json'], true) ?: [];
  }
  // Replace all tasks with the submitted snapshot (keeps client authoritative)
  $c->query("DELETE FROM tasks");
  foreach ($tasks as $t) {
    if (!isset($t['text'])) continue;
    $text = trim($t['text']);
    $completed = !empty($t['completed']) ? 1 : 0;
    $sid = $resolveSubjectFromTaskText($text);
    $textEsc = $c->real_escape_string($text);
    $sidSql = is_null($sid) ? "NULL" : (int)$sid;
    $c->query("INSERT INTO tasks (subject_id, description, completed) VALUES ($sidSql, '$textEsc', $completed)");
  }

  // 4) Goals (JSON array of strings)
  $goals = [];
  if (!empty($_POST['goals_json'])) {
    $goals = json_decode($_POST['goals_json'], true) ?: [];
  }
  $c->query("DELETE FROM goals");
  foreach ($goals as $g) {
    $gEsc = $c->real_escape_string($g);
    $c->query("INSERT INTO goals (description) VALUES ('$gEsc')");
  }

  // 5) Daily schedule (JSON object {hour: subjectName})
  $daily = [];
  if (!empty($_POST['daily_json'])) {
    $daily = json_decode($_POST['daily_json'], true) ?: [];
  }
  $c->query("DELETE FROM daily_schedule WHERE semester_id=".$semester_id);
  foreach ($daily as $hourStr => $subjName) {
    $hour = (int)$hourStr;
    if ($hour < 0 || $hour > 23) continue;
    $subject_id = null;
    if ($subjName !== '') {
      if (!isset($subj_map[$subjName])) {
        // If user typed new subject directly in planner, create it
        $nmEsc = $c->real_escape_string($subjName);
        $c->query("INSERT INTO subjects (semester_id, name) VALUES ($semester_id, '$nmEsc')");
        $subject_id = (int)$c->insert_id;
        $subj_map[$subjName] = $subject_id;
      } else {
        $subject_id = $subj_map[$subjName];
      }
    }
    $sidSql = is_null($subject_id) ? "NULL" : (int)$subject_id;
    $c->query("INSERT INTO daily_schedule (semester_id, hour, subject_id) VALUES ($semester_id, $hour, $sidSql)");
  }

  // 6) Exam date
  $exam_date = trim($_POST['exam_date'] ?? '');
  if ($exam_date !== '') {
    $edEsc = $c->real_escape_string($exam_date);
    $c->query("INSERT INTO exam (semester_id, exam_date) VALUES ($semester_id, '$edEsc')
               ON DUPLICATE KEY UPDATE exam_date=VALUES(exam_date)");
  } else {
    // If empty, clear exam for this semester
    $c->query("DELETE FROM exam WHERE semester_id=".$semester_id);
  }

  // 7) Study files (if any)
  if (!empty($_FILES) && isset($_FILES['files'])) {
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads";
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

    $names = $_FILES['files']['name'] ?? [];
    $tmps  = $_FILES['files']['tmp_name'] ?? [];
    $sizes = $_FILES['files']['size'] ?? [];

    for ($i = 0; $i < count($names); $i++) {
      if (!is_uploaded_file($tmps[$i])) continue;
      $base = basename($names[$i]);
      $dest = $uploadDir . DIRECTORY_SEPARATOR . $base;
      if (@move_uploaded_file($tmps[$i], $dest)) {
        $sizeKB = round(($sizes[$i] ?? 0) / 1024, 2);
        $baseEsc = $c->real_escape_string($base);
        $c->query("INSERT INTO study_files (file_name, file_size_kb) VALUES ('$baseEsc', $sizeKB)");
      }
    }
  }

  prg_redirect("Saved successfully.");
}

/* ---------- Fetch DB state for initial hydration ---------- */
$c = db();
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Choose latest semester (or none)
$semesterRow = null;
$res = $c->query("SELECT * FROM semester ORDER BY semester_id DESC LIMIT 1");
if ($res && $res->num_rows) $semesterRow = $res->fetch_assoc();
$currentSemId = $semesterRow['semester_id'] ?? null;

// Pull data
$db_semester_name = $semesterRow['name'] ?? '';
$db_subjects = [];
$db_tasks = [];
$db_goals = [];
$db_daily = []; // hour => subjectName
$db_exam_date = '';

if ($currentSemId) {
  $res = $c->query("SELECT name FROM subjects WHERE semester_id=".(int)$currentSemId." ORDER BY subject_id ASC");
  while ($res && $row=$res->fetch_assoc()) $db_subjects[] = $row['name'];

  $res = $c->query("SELECT t.description, t.completed, s.name AS subject_name
                    FROM tasks t LEFT JOIN subjects s ON t.subject_id = s.subject_id
                    ORDER BY t.task_id ASC");
  while ($res && $row=$res->fetch_assoc()) {
    // Keep your original 'text' format; if subject is known and not already in text, we can retain as-is
    $db_tasks[] = [
      'text' => $row['description'],
      'completed' => (bool)$row['completed'],
    ];
  }

  $res = $c->query("SELECT description FROM goals ORDER BY goal_id ASC");
  while ($res && $row=$res->fetch_assoc()) $db_goals[] = $row['description'];

  $res = $c->query("SELECT ds.hour, s.name AS subject_name FROM daily_schedule ds
                    LEFT JOIN subjects s ON s.subject_id = ds.subject_id
                    WHERE ds.semester_id=".(int)$currentSemId);
  while ($res && $row=$res->fetch_assoc()) {
    $db_daily[(int)$row['hour']] = $row['subject_name'] ?? '';
  }

  $res = $c->query("SELECT exam_date FROM exam WHERE semester_id=".(int)$currentSemId." LIMIT 1");
  if ($res && $row=$res->fetch_assoc()) $db_exam_date = $row['exam_date'];
}

// JSON for client hydration if localStorage is empty
$HYDRATE = [
  'semester' => $db_semester_name,
  'subjects' => $db_subjects,
  'tasks'    => $db_tasks,
  'goals'    => $db_goals,
  'daily'    => $db_daily,
  'examDate' => $db_exam_date,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>College Study Planner</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background: #f0f2f5; }
    .card { border-radius: 1rem; box-shadow: 0 4px 10px rgba(0,0,0,.08); }
    #timer { font-size: 1.8rem; font-weight: bold; }
    .file-list li { font-size: 0.9rem; }
    .list-group-item.done label { text-decoration: line-through; color: gray; }
    #taskList { max-height: 300px; overflow-y: auto; }
  </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <span class="navbar-brand">📘 College Study Planner</span>
  </div>
</nav>

<div class="container">

  <?php if ($flash): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div>
  <?php endif; ?>

  <!-- Semester Configuration -->
  <div class="card p-3 mb-3">
    <h5>🎓 Semester Setup</h5>
    <input type="text" id="semesterInput" class="form-control mb-2" placeholder="Enter current semester (e.g. Sem 5)">
    <button class="btn btn-primary mb-2" onclick="setupSemester()">Set Semester</button>
    <div id="semesterStatus" class="text-success fw-bold"></div>
  </div>

  <!-- Subject Input -->
  <div class="card p-3 mb-3">
    <h5>📚 Subjects</h5>
    <input type="number" class="form-control mb-2" id="numSubjects" placeholder="Number of subjects" min="0">
    <div id="subjectInputs" class="mb-2"></div>
    <button class="btn btn-success" onclick="saveSubjects()">Save Subjects</button>
    <ul class="list-group mt-3" id="subjectList"></ul>
  </div>

  <!-- Filter -->
  <div class="card p-3 mb-3">
    <h5>🔍 Filter Tasks by Subject</h5>
    <select class="form-select" onchange="filterTasks(this.value)" id="subjectFilter">
      <option value="">All Subjects</option>
    </select>
  </div>

  <!-- Task and Goal -->
  <div class="row g-3">
    <div class="col-md-6 d-flex flex-column">
      <div class="card p-3 flex-grow-1 d-flex flex-column">
        <h5>📝 Tasks</h5>
        <ul class="list-group flex-grow-1 overflow-auto" id="taskList"></ul>
        <div class="input-group mt-3">
          <input type="text" id="taskInput" class="form-control" placeholder="Add task (e.g. DBMS: Assignment)" />
          <button class="btn btn-primary" id="addTaskBtn">Add Task</button>
        </div>
        <div class="mt-2 d-flex gap-2 flex-wrap">
          <button class="btn btn-success flex-grow-1" id="markCompletedBtn">Mark Selected Completed</button>
          <button class="btn btn-warning flex-grow-1" id="markUncompletedBtn">Mark Selected Uncompleted</button>
          <button class="btn btn-danger flex-grow-1" id="removeSelectedBtn">Remove Selected</button>
        </div>

        <!-- Upload Completed Tasks -->
        <div class="mt-4">
          <h6>Upload Completed Tasks</h6>
          <input type="file" class="form-control" id="uploadCompletedTasks" accept=".txt,.csv" />
          <small class="text-muted">Upload a text file with each completed task on a new line.</small>
        </div>

      </div>
    </div>
    <div class="col-md-6 d-flex flex-column">
      <div class="card p-3 flex-grow-1 d-flex flex-column">
        <h5>🎯 Goals</h5>
        <ul class="list-group list-group-flush flex-grow-1 overflow-auto" id="goalList"></ul>
        <input type="text" class="form-control mt-2" placeholder="Add goal..." 
               onkeydown="if(event.key==='Enter'){addGoal(this)}">
      </div>
    </div>

    <!-- Timer -->
    <div class="col-md-6">
      <div class="card p-3 text-center">
        <h5>⏱ Study Timer</h5>
        <div id="timer">25:00</div>
        <div class="mt-2">
          <button class="btn btn-primary btn-sm" onclick="startTimer()">Start</button>
          <button class="btn btn-secondary btn-sm" onclick="pauseTimer()">Pause</button>
          <button class="btn btn-danger btn-sm" onclick="resetTimer()">Reset</button>
        </div>
        <select class="form-select mt-2" onchange="setTimerPreset(this.value)">
          <option value="25">Pomodoro (25 min)</option>
          <option value="50">Deep Focus (50 min)</option>
          <option value="custom">Custom</option>
        </select>
      </div>
    </div>

    <!-- Files -->
    <div class="col-md-6">
      <div class="card p-3">
        <h5>📂 Study Materials</h5>
        <input type="file" class="form-control mb-2" multiple onchange="previewFiles(this,'fileList')">
        <ul class="list-group file-list" id="fileList"></ul>
      </div>
    </div>

    <!-- Progress -->
    <div class="col-md-6">
      <div class="card p-3">
        <h5>📈 Progress</h5>
        <div class="progress mb-2">
          <div id="progressBar" class="progress-bar" style="width: 0%">0%</div>
        </div>
      </div>
    </div>

    <!-- Exam Countdown -->
    <div class="col-md-6">
      <div class="card p-3">
        <h5>📅 Exam Countdown</h5>
        <input type="date" class="form-control mb-2" onchange="setExamDate(this.value)">
        <div id="examCountdown" class="fw-bold text-danger"></div>
      </div>
    </div>
  </div>

  <!-- Daily Planner -->
  <div class="card p-3 mt-4">
    <h5>📆 Daily Planner</h5>
    <div id="dailyTimeline" class="list-group"></div>
  </div>

  <!-- Export -->
  <div class="text-end mt-3">
    <button class="btn btn-secondary btn-sm" onclick="alert('uploading')">submit</button>
    <a href="index.php" class="btn btn-secondary btn-sm">back to home</a>
  </div>
</div>

<!-- Hidden form for full save (NO AJAX) -->
<form id="persistForm" method="POST" enctype="multipart/form-data" style="display:none">
  <input type="hidden" name="action" value="save_all">
  <input type="hidden" name="semester" id="pf_semester">
  <input type="hidden" name="subjects_json" id="pf_subjects">
  <input type="hidden" name="tasks_json" id="pf_tasks">
  <input type="hidden" name="goals_json" id="pf_goals">
  <input type="hidden" name="daily_json" id="pf_daily">
  <input type="hidden" name="exam_date" id="pf_exam">
  <!-- file input will be moved here dynamically just before submit -->
</form>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const $ = id => document.getElementById(id);

// --- Semester Setup ---
function setupSemester() {
  const sem = $("semesterInput").value.trim();
  if (!sem) return alert("Please enter a valid semester name.");
  const currentSem = localStorage.getItem("semester");
  if (sem !== currentSem) {
    localStorage.setItem("semester", sem);
    localStorage.removeItem("subjects");
    localStorage.removeItem("tasks");
    localStorage.removeItem("dailySchedule");
    alert("New semester detected. Subjects, tasks, and daily planner reset.");
  }
  $("semesterStatus").textContent = `Current semester: ${sem}`;
  loadSubjects();
  renderTasks();
}

// --- Subjects ---
$("numSubjects").addEventListener("input", function () {
  const count = Math.max(0, parseInt(this.value) || 0);
  const container = $("subjectInputs");
  container.innerHTML = "";
  for (let i = 0; i < count; i++) {
    const input = document.createElement("input");
    input.className = "form-control mb-2";
    input.placeholder = `Subject ${i + 1}`;
    container.appendChild(input);
  }
});

function saveSubjects() {
  const inputs = document.querySelectorAll("#subjectInputs input");
  const subjects = Array.from(inputs).map(i => i.value.trim()).filter(Boolean);
  if (!subjects.length) return alert("Enter at least one subject.");
  localStorage.setItem("subjects", JSON.stringify(subjects));
  renderSubjects(subjects);
  updateSubjectDropdown(subjects);
  reloadDailyPlanner(); // Refresh daily planner dropdowns when subjects change
}

function loadSubjects() {
  const subjects = JSON.parse(localStorage.getItem("subjects")) || [];
  renderSubjects(subjects);
  updateSubjectDropdown(subjects);
  reloadDailyPlanner(); // Refresh daily planner dropdowns when subjects change or on page load
}

function renderSubjects(subjects) {
  $("subjectList").innerHTML = subjects.map(s => `<li class="list-group-item">${s}</li>`).join("");
}
function updateSubjectDropdown(subjects) {
  $("subjectFilter").innerHTML = `<option value="">All Subjects</option>` +
    subjects.map(s => `<option value="${s}">${s}</option>`).join("");
}

// --- Tasks ---
function getTasks() {
  return JSON.parse(localStorage.getItem("tasks")) || [];
}
function saveTasks(tasks) {
  localStorage.setItem("tasks", JSON.stringify(tasks));
}
function renderTasks() {
  const tasks = getTasks();
  const filterVal = $('subjectFilter').value.toLowerCase();
  const ul = $('taskList');
  ul.innerHTML = '';

  if (tasks.length === 0) {
    ul.innerHTML = '<li class="list-group-item text-muted">No tasks added yet.</li>';
    updateProgress();
    return;
  }

  tasks.forEach((task, idx) => {
    if (filterVal && !task.text.toLowerCase().startsWith(filterVal)) return;
    const li = document.createElement('li');
    li.className = `list-group-item d-flex align-items-center ${task.completed ? 'done' : ''}`;
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.className = 'form-check-input me-2';
    cb.dataset.idx = idx;
    li.appendChild(cb);
    const label = document.createElement('label');
    label.textContent = task.text;
    li.appendChild(label);
    ul.appendChild(li);
  });
  updateProgress();
}

function addTask() {
  const input = $("taskInput");
  const text = input.value.trim();
  if (!text) return;
  const tasks = getTasks();
  tasks.push({ text, completed: false });
  saveTasks(tasks);
  input.value = "";
  renderTasks();
}
$("addTaskBtn").addEventListener("click", addTask);
$("taskInput").addEventListener("keydown", e => {
  if (e.key === 'Enter') addTask();
});

function filterTasks() {
  renderTasks();
}

function markSelectedCompleted(completed = true) {
  const tasks = getTasks();
  const checkboxes = document.querySelectorAll("#taskList input[type=checkbox]");
  checkboxes.forEach(cb => {
    if (cb.checked) {
      tasks[cb.dataset.idx].completed = completed;
    }
  });
  saveTasks(tasks);
  renderTasks();
}
$("markCompletedBtn").addEventListener("click", () => markSelectedCompleted(true));
$("markUncompletedBtn").addEventListener("click", () => markSelectedCompleted(false));

function removeSelectedTasks() {
  const tasks = getTasks();
  const checkboxes = [...document.querySelectorAll("#taskList input[type=checkbox]")];
  const keep = [];
  checkboxes.forEach(cb => {
    if (!cb.checked) keep.push(tasks[cb.dataset.idx]);
  });
  saveTasks(keep);
  renderTasks();
}
$("removeSelectedBtn").addEventListener("click", removeSelectedTasks);

// Upload completed tasks file handler
$("uploadCompletedTasks").addEventListener("change", function () {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function () {
    const lines = reader.result.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
    const tasks = getTasks();
    lines.forEach(line => {
      for (let t of tasks) {
        if (t.text.toLowerCase() === line.toLowerCase()) {
          t.completed = true;
          break;
        }
      }
    });
    saveTasks(tasks);
    renderTasks();
    alert("Completed tasks updated from file.");
  };
  reader.readAsText(file);
});

// --- Goals ---
function getGoals() {
  return JSON.parse(localStorage.getItem("goals")) || [];
}
function saveGoals(goals) {
  localStorage.setItem("goals", JSON.stringify(goals));
}
function renderGoals() {
  const goals = getGoals();
  const ul = $("goalList");
  ul.innerHTML = "";
  goals.forEach((g, idx) => {
    const li = document.createElement("li");
    li.className = "list-group-item d-flex justify-content-between align-items-center";
    li.textContent = g;
    const btn = document.createElement("button");
    btn.className = "btn btn-sm btn-danger";
    btn.textContent = "✖";
    btn.onclick = () => {
      goals.splice(idx, 1);
      saveGoals(goals);
      renderGoals();
    };
    li.appendChild(btn);
    ul.appendChild(li);
  });
}
function addGoal(input) {
  const val = input.value.trim();
  if (!val) return;
  const goals = getGoals();
  goals.push(val);
  saveGoals(goals);
  input.value = "";
  renderGoals();
}

// --- Timer ---
let timerInterval = null;
let timerSeconds = 25 * 60;
let timerRunning = false;
const timerDisplay = $("timer");

function updateTimerDisplay() {
  const m = Math.floor(timerSeconds / 60);
  const s = timerSeconds % 60;
  timerDisplay.textContent = `${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
}
function startTimer() {
  if (timerRunning) return;
  timerRunning = true;
  timerInterval = setInterval(() => {
    if (timerSeconds <= 0) {
      clearInterval(timerInterval);
      timerRunning = false;
      alert("Time's up!");
      return;
    }
    timerSeconds--;
    updateTimerDisplay();
  }, 1000);
}
function pauseTimer() {
  if (!timerRunning) return;
  clearInterval(timerInterval);
  timerRunning = false;
}
function resetTimer() {
  pauseTimer();
  timerSeconds = 25 * 60;
  updateTimerDisplay();
}
function setTimerPreset(val) {
  if (val === 'custom') {
    const customMins = prompt("Enter timer minutes:", "25");
    const mins = parseInt(customMins);
    if (!isNaN(mins) && mins > 0) {
      timerSeconds = mins * 60;
      updateTimerDisplay();
    }
  } else {
    timerSeconds = parseInt(val) * 60;
    updateTimerDisplay();
  }
}
// --- Files ---
function previewFiles(input, listId) {
  const files = input.files;
  const ul = $(listId);
  ul.innerHTML = '';
  for (const f of files) {
    const li = document.createElement('li');
    li.className = 'list-group-item';
    li.textContent = `${f.name} (${(f.size / 1024).toFixed(1)} KB)`;
    ul.appendChild(li);
  }
}

// --- Progress ---
function updateProgress() {
  const tasks = getTasks();
  const bar = $("progressBar");
  if (!tasks.length) {
    bar.style.width = "0%";
    bar.textContent = "0%";
    return;
  }
  const doneCount = tasks.filter(t => t.completed).length;
  const pct = Math.round((doneCount / tasks.length) * 100);
  bar.style.width = pct + "%";
  bar.textContent = pct + "%";
}

// --- Exam Countdown ---
function setExamDate(dateStr) {
  if (!dateStr) {
    $("examCountdown").textContent = "";
    localStorage.removeItem("examDate");
    return;
  }
  localStorage.setItem("examDate", dateStr);
  updateExamCountdown();
}
function updateExamCountdown() {
  const dateStr = localStorage.getItem("examDate");
  if (!dateStr) {
    $("examCountdown").textContent = "No exam date set.";
    return;
  }
  const examDate = new Date(dateStr);
  const now = new Date();
  const diffMs = examDate - now;
  if (diffMs < 0) {
    $("examCountdown").textContent = "Exam date passed!";
    return;
  }
  const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  $("examCountdown").textContent = `${days} day(s) until exams`;
}

// --- Daily Planner ---
function reloadDailyPlanner() {
  generateDailyTimeline();
}
function saveDailySchedule(hour, subj) {
  const dailySchedule = JSON.parse(localStorage.getItem("dailySchedule") || "{}");
  if (subj === '') {
    delete dailySchedule[hour];
  } else {
    dailySchedule[hour] = subj;
  }
  localStorage.setItem("dailySchedule", JSON.stringify(dailySchedule));
}
function generateDailyTimeline() {
  const container = $("dailyTimeline");
  container.innerHTML = '';

  const subjects = JSON.parse(localStorage.getItem("subjects")) || [];
  const dailySchedule = JSON.parse(localStorage.getItem("dailySchedule") || "{}");

  // Reduced hours: 9 AM to 5 PM
  for (let h = 9; h <= 17; h++) {
    const hour = h % 24;
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = ((hour + 11) % 12 + 1);

    const div = document.createElement('div');
    div.className = 'list-group-item d-flex align-items-center justify-content-between';

    const timeLabel = document.createElement('span');
    timeLabel.textContent = `${displayHour} ${ampm}`;
    div.appendChild(timeLabel);

    // Create subject select dropdown
    const select = document.createElement('select');
    select.className = 'form-select form-select-sm w-auto ms-3';
    select.dataset.hour = hour;

    // Option for no subject
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = '-- No Subject --';
    select.appendChild(defaultOption);

    // Add subjects options
    subjects.forEach(subj => {
      const option = document.createElement('option');
      option.value = subj;
      option.textContent = subj;
      select.appendChild(option);
    });

    // Set saved value if exists
    if (dailySchedule[hour]) {
      select.value = dailySchedule[hour];
    }

    // Save on change
    select.addEventListener('change', e => {
      saveDailySchedule(e.target.dataset.hour, e.target.value);
    });

    div.appendChild(select);
    container.appendChild(div);
  }
}

// --- Initialization ---
function init() {
  const currentSem = localStorage.getItem("semester");
  if (currentSem) {
    $("semesterInput").value = currentSem;
    $("semesterStatus").textContent = `Current semester: ${currentSem}`;
  }
  loadSubjects();
  renderTasks();
  renderGoals();
  updateProgress();
  updateExamCountdown();
  generateDailyTimeline();
  updateTimerDisplay();
}
window.onload = init;
</script>

<!-- ====== ADDITIONS (NO CHANGES to your code above) ====== -->
<script>
/* Hydrate from DB only if localStorage is empty */
(function(){
  const hasAny =
    localStorage.getItem('semester') ||
    localStorage.getItem('subjects') ||
    localStorage.getItem('tasks') ||
    localStorage.getItem('goals') ||
    localStorage.getItem('dailySchedule') ||
    localStorage.getItem('examDate');

  const HYDRATE = <?php echo json_encode($HYDRATE, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;

  if (!hasAny && HYDRATE) {
    if (HYDRATE.semester) localStorage.setItem('semester', HYDRATE.semester);
    if (Array.isArray(HYDRATE.subjects)) localStorage.setItem('subjects', JSON.stringify(HYDRATE.subjects));
    if (Array.isArray(HYDRATE.tasks)) localStorage.setItem('tasks', JSON.stringify(HYDRATE.tasks));
    if (Array.isArray(HYDRATE.goals)) localStorage.setItem('goals', JSON.stringify(HYDRATE.goals));
    if (HYDRATE.daily && typeof HYDRATE.daily === 'object') localStorage.setItem('dailySchedule', JSON.stringify(HYDRATE.daily));
    if (HYDRATE.examDate) localStorage.setItem('examDate', HYDRATE.examDate);
  }
})();

/* Turn your existing "submit" button into a full-page save WITHOUT AJAX */
(function(){
  const persistForm = document.getElementById('persistForm');

  function collectAndSubmit() {
    const semester = localStorage.getItem('semester') || (document.getElementById('semesterInput').value || '').trim();
    const subjects = JSON.parse(localStorage.getItem('subjects') || '[]');
    const tasks    = JSON.parse(localStorage.getItem('tasks') || '[]');
    const goals    = JSON.parse(localStorage.getItem('goals') || '[]');
    const daily    = JSON.parse(localStorage.getItem('dailySchedule') || '{}');
    const examDate = localStorage.getItem('examDate') || '';

    document.getElementById('pf_semester').value = semester;
    document.getElementById('pf_subjects').value = JSON.stringify(subjects);
    document.getElementById('pf_tasks').value    = JSON.stringify(tasks);
    document.getElementById('pf_goals').value    = JSON.stringify(goals);
    document.getElementById('pf_daily').value    = JSON.stringify(daily);
    document.getElementById('pf_exam').value     = examDate;

    // Move the "Study Materials" input (just above #fileList) into the hidden form
    const fileList = document.getElementById('fileList');
    if (fileList && fileList.previousElementSibling && fileList.previousElementSibling.type === 'file') {
      const fileInput = fileList.previousElementSibling;
      fileInput.setAttribute('name', 'files[]'); // ensure files are posted
      persistForm.appendChild(fileInput);        // move into form so browser includes files
    }

    persistForm.submit();
  }

  // Attach to your existing bottom "submit" button (no id given originally)
  const submitBtn = document.querySelector('.text-end .btn.btn-secondary.btn-sm');
  if (submitBtn) {
    submitBtn.addEventListener('click', function(e){
      // keep your alert
      // after alert closes, collect data and submit
      setTimeout(collectAndSubmit, 0);
    });
  }
})();
</script>

</body>
</html>

