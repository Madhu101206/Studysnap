<?php 
// ---------- DATABASE CONNECTION ----------
$host = "localhost";
$user = "root";   // default in XAMPP
$pass = "";
$db   = "doubt_forum";   // ✅ Changed DB name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// Show all errors during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------- HANDLE POST REQUESTS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add new doubt
    if (isset($_POST['new_doubt'])) {
        $subject = $conn->real_escape_string($_POST['subject']);
        $title   = $conn->real_escape_string($_POST['title']);
        $text    = $conn->real_escape_string($_POST['text']);
        $author  = $conn->real_escape_string($_POST['author']);

        $sql = "INSERT INTO doubts (subject, title, text, author) 
                VALUES ('$subject','$title','$text','$author')";
        if (!$conn->query($sql)) die("Insert doubt failed: " . $conn->error);
    }

    // Add new reply
    if (isset($_POST['new_reply'])) {
        $doubt_id = (int)$_POST['doubt_id'];
        $author   = $conn->real_escape_string($_POST['author']);
        $text     = $conn->real_escape_string($_POST['text']);
        
        $sql = "INSERT INTO replies (doubt_id, author, text) 
                VALUES ($doubt_id, '$author', '$text')";
        if (!$conn->query($sql)) die("Insert reply failed: " . $conn->error);
    }

    // Like
    if (isset($_POST['like'])) {
        $id = (int)$_POST['doubt_id'];
        if (!$conn->query("UPDATE doubts SET likes = likes + 1 WHERE id=$id")) {
            die("Like failed: " . $conn->error);
        }
    }

    // Unlike
    if (isset($_POST['unlike'])) {
        $id = (int)$_POST['doubt_id'];
        if (!$conn->query("UPDATE doubts SET unlikes = unlikes + 1 WHERE id=$id")) {
            die("Unlike failed: " . $conn->error);
        }
    }

    // Solve / Unsolve toggle
    if (isset($_POST['toggle_solve'])) {
        $id = (int)$_POST['doubt_id'];
        $result = $conn->query("SELECT solved FROM doubts WHERE id=$id");
        if ($result && $row = $result->fetch_assoc()) {
            $new_solved = $row['solved'] ? 0 : 1;
            if (!$conn->query("UPDATE doubts SET solved=$new_solved WHERE id=$id")) {
                die("Toggle solve failed: " . $conn->error);
            }
        }
    }

    // Edit reply
    if (isset($_POST['edit_reply'])) {
        $reply_id = (int)$_POST['reply_id'];
        $text     = $conn->real_escape_string($_POST['text']);
        if (!$conn->query("UPDATE replies SET text='$text', edited=1 WHERE id=$reply_id")) {
            die("Edit reply failed: " . $conn->error);
        }
    }

    // Delete reply
    if (isset($_POST['delete_reply'])) {
        $reply_id = (int)$_POST['reply_id'];
        if (!$conn->query("DELETE FROM replies WHERE id=$reply_id")) {
            die("Delete reply failed: " . $conn->error);
        }
    }

    // Clear all doubts
    if (isset($_POST['clear_all'])) {
        if (!$conn->query("DELETE FROM replies") || !$conn->query("DELETE FROM doubts")) {
            die("Clear all failed: " . $conn->error);
        }
    }

    // Redirect after POST to prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ---------- FETCH DOUBTS + REPLIES ----------
$doubts = [];
$res_doubts = $conn->query("SELECT * FROM doubts ORDER BY time DESC");
if ($res_doubts) {
    while ($d = $res_doubts->fetch_assoc()) {
        $doubt_id = $d['id'];
        $d['replies'] = [];
        $res_replies = $conn->query("SELECT * FROM replies WHERE doubt_id=$doubt_id ORDER BY time ASC");
        while ($r = $res_replies->fetch_assoc()) {
            $d['replies'][] = $r;
        }
        $doubts[] = $d;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Doubt Exchange Forum</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background:#f0f2f5; }
    .card { border-radius:1rem; box-shadow:0 4px 10px rgba(0,0,0,.08); }
    .doubt-item { border-bottom:1px solid #e8e8e8; padding:14px 8px; }
    .doubt-item:last-child { border-bottom:none; }
    .subject-badge { font-size:.78rem; }
    .reply-box { background:#fff; border:1px solid #eee; border-radius:.6rem; padding:.6rem .8rem; }
    .reply-item { border-left:3px solid #e9ecef; padding-left:.7rem; margin:.4rem 0; }
    .solved { border-left:5px solid #28a745; }
  </style>
</head>
<body>
  <nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container-fluid">
      <span class="navbar-brand">❓ Doubt Forum</span>
    </div>
  </nav>

  <div class="container">

    <!-- Your name -->
    <div class="card p-3 mb-3">
      <h5>👤 Your Name</h5>
      <input id="currentUser" class="form-control" placeholder="Enter your name / roll no." />
      <button class="btn btn-outline-primary mt-2" onclick="saveCurrentUser()">Save Name</button>
    </div>

    <!-- Post a new doubt -->
    <div class="card p-3 mb-3">
      <h5>📝 Post a New Doubt</h5>
      <form id="doubtForm" method="POST">
        <input type="hidden" name="new_doubt" value="1" />
        <div class="row g-2">
          <div class="col-md-4">
            <input name="subject" id="newSubject" class="form-control" placeholder="Subject" required />
          </div>
          <div class="col-md-8">
            <input name="title" id="newTitle" class="form-control" placeholder="Enter doubt title (optional)" />
          </div>
        </div>
        <textarea name="text" id="newDoubt" class="form-control mt-2" placeholder="Describe your doubt..." rows="3" required></textarea>
        <input name="author" id="authorInput" type="hidden" required />
        <div class="mt-2 d-flex gap-2">
          <button class="btn btn-primary" type="submit">Post Doubt</button>
          <button type="button" class="btn btn-outline-secondary" onclick="clearForm()">Clear</button>
        </div>
      </form>
    </div>

    <!-- Filters -->
    <div class="card p-3 mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1">🔍 Subject</label>
          <select class="form-select" id="subjectFilter" onchange="renderDoubts()">
            <option value="">-- All Subjects --</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label mb-1">Search</label>
          <input id="searchBox" class="form-control" placeholder="Search…" oninput="renderDoubts()" />
        </div>
        <div class="col-md-4">
          <label class="form-label mb-1">Sort by</label>
          <select id="sortBy" class="form-select" onchange="renderDoubts()">
            <option value="latest">Latest</option>
            <option value="likes">Most Likes</option>
            <option value="replies">Most Replies</option>
            <option value="oldest">Oldest</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Doubt list -->
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0">📋 Doubts</h5>
        <form method="POST" onsubmit="return confirm('Clear all doubts?');">
          <input type="hidden" name="clear_all" value="1" />
          <button type="submit" class="btn btn-sm btn-outline-danger">Clear All Data</button>
        </form>
      </div>
      <hr/>
      <div id="doubtList"></div>
    </div>
  </div>
    <div class="text-end mt-3">
    <a href="index.php" class="btn btn-secondary btn-sm">back to home</a>
</div>
<script>
  const disputesData = <?= json_encode($doubts) ?>;
  const $ = id => document.getElementById(id);
  let currentUser = localStorage.getItem('currentUser') || '';

  function saveCurrentUser() {
    const name = $('currentUser').value.trim();
    if (!name) { alert('Please enter your name.'); return; }
    currentUser = name;
    localStorage.setItem('currentUser', currentUser);
    $('authorInput').value = currentUser;
    renderDoubts();
  }

  function clearForm() {
    $('newSubject').value = '';
    $('newTitle').value = '';
    $('newDoubt').value = '';
  }

  function getSubjects() {
    let subs = new Set();
    disputesData.forEach(d => subs.add(d.subject));
    return Array.from(subs);
  }

  function renderDoubts() {
    $('authorInput').value = currentUser;
    $('currentUser').value = currentUser;

    const query = $('searchBox').value.toLowerCase();
    const filter = $('subjectFilter').value;
    const sortBy = $('sortBy').value;

    let doubts = [...disputesData];
    if (filter) doubts = doubts.filter(d => d.subject === filter);
    if (query) {
      doubts = doubts.filter(d =>
        (d.title || '').toLowerCase().includes(query) ||
        (d.text || '').toLowerCase().includes(query) ||
        (d.author || '').toLowerCase().includes(query)
      );
    }

    if (sortBy === 'latest') doubts.sort((a, b) => new Date(b.time) - new Date(a.time));
    else if (sortBy === 'oldest') doubts.sort((a, b) => new Date(a.time) - new Date(b.time));
    else if (sortBy === 'likes') doubts.sort((a, b) => b.likes - a.likes);
    else if (sortBy === 'replies') doubts.sort((a, b) => (b.replies.length || 0) - (a.replies.length || 0));

    const container = $('doubtList');
    container.innerHTML = '';
    if (doubts.length === 0) { container.innerHTML = '<p class="text-muted">No doubts yet.</p>'; return; }

    for (const d of doubts) {
      const solvedClass = d.solved ? 'solved' : '';
      const subjectBadge = `<span class="badge bg-primary subject-badge">[${escapeHTML(d.subject)}]</span>`;
      const solvedBadge = d.solved ? `<span class="badge bg-success ms-2">Solved</span>` : '';
      const title = d.title ? `<div class="fw-semibold">${escapeHTML(d.title)}</div>` : '';

      let html = `
        <div class="doubt-item ${solvedClass}">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              ${subjectBadge} ${solvedBadge} ${title}
              <div class="mt-1">${escapeHTML(d.text)}</div>
              <small class="text-muted">Asked by ${escapeHTML(d.author || 'Anonymous')} · ${formatTime(d.time)}</small>
            </div>
            <div class="text-nowrap ms-2">
              <form method="POST" class="d-inline me-1">
                <input type="hidden" name="doubt_id" value="${d.id}" />
                <button type="submit" name="toggle_solve" class="btn btn-sm btn-outline-success">
                  ${d.solved ? 'Unmark' : 'Mark Solved'}
                </button>
              </form>
              <form method="POST" class="d-inline me-1">
                <input type="hidden" name="doubt_id" value="${d.id}" />
                <button type="submit" name="like" class="btn btn-sm btn-outline-primary">👍 Like (${d.likes})</button>
              </form>
              <form method="POST" class="d-inline">
                <input type="hidden" name="doubt_id" value="${d.id}" />
                <button type="submit" name="unlike" class="btn btn-sm btn-outline-danger">👎 Unlike (${d.unlikes})</button>
              </form>
            </div>
          </div>
          <div class="mt-3 reply-box"><div>`;

      for (const r of d.replies) {
        html += `
          <div class="reply-item">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div>${escapeHTML(r.text)}</div>
                <small class="text-muted">by ${escapeHTML(r.author)} · ${formatTime(r.time)}${r.edited ? ' · edited' : ''}</small>
              </div>
              <div class="ms-2">
                ${ r.author === currentUser ? `
                <button class="btn btn-sm btn-outline-secondary me-1" onclick="editReply(${d.id}, ${r.id})">Edit</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteReply(${d.id}, ${r.id})">Delete</button>` : ''}
              </div>
            </div>
          </div>`;
      }

      html += `
        </div>
        <form method="POST" class="mt-2 reply-form" onsubmit="return validateReply(event, ${d.id})">
          <input type="hidden" name="new_reply" value="1" />
          <input type="hidden" name="doubt_id" value="${d.id}" />
          <input type="hidden" name="author" value="${escapeHTML(currentUser)}" />
          <div class="d-flex align-items-center gap-2">
            <input name="text" class="form-control" placeholder="Write an answer..." required />
            <button class="btn btn-primary btn-sm" type="submit">Reply</button>
          </div>
        </form>
        </div>
      `;
      container.insertAdjacentHTML('beforeend', html);
    }
  }

  function escapeHTML(str = '') {
    return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function formatTime(ts) { return new Date(ts).toLocaleString(); }

  function editReply(doubtId, replyId) {
    const newText = prompt("Edit your reply:"); if (newText === null) return;
    const form = document.createElement('form');
    form.method = 'POST'; form.style.display = 'none';
    form.innerHTML = `
      <input type="hidden" name="edit_reply" value="1" />
      <input type="hidden" name="reply_id" value="${replyId}" />
      <input type="hidden" name="text" value="${escapeHTML(newText.trim())}" />
    `;
    document.body.appendChild(form); form.submit();
  }

  function deleteReply(doubtId, replyId) {
    if (!confirm("Are you sure you want to delete this reply?")) return;
    const form = document.createElement('form');
    form.method = 'POST'; form.style.display = 'none';
    form.innerHTML = `
      <input type="hidden" name="delete_reply" value="1" />
      <input type="hidden" name="reply_id" value="${replyId}" />
    `;
    document.body.appendChild(form); form.submit();
  }

  function validateReply(event, doubtId) {
    const textInput = event.target.querySelector('input[name="text"]');
    if (textInput.value.trim() === '') { alert("Reply cannot be empty."); event.preventDefault(); return false; }
    return true;
  }

  window.onload = () => {
    $('currentUser').value = currentUser;
    $('authorInput').value = currentUser;
    const subjects = getSubjects();
    const subjectFilter = $('subjectFilter');
    subjectFilter.innerHTML = '<option value="">-- All Subjects --</option>';
    for (const s of subjects) subjectFilter.innerHTML += `<option value="${s}">${s}</option>`;
    renderDoubts();
  }
</script>
</body>
</html>
