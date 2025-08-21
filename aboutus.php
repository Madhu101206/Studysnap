<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About Us - Study Planner & Doubt Exchange</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #e6f0ff, #ffffff);
      font-family: 'Segoe UI', sans-serif;
    }
    .navbar {
      background-color: #0d6efd;
    }
    .navbar-brand, .nav-link {
      color: white !important;
      font-weight: bold;
    }
    .nav-link:hover {
      text-decoration: underline;
    }
    .navbar-brand img {
      height: 40px;
      margin-right: 10px;
    }
    .section-title {
      color: #0d6efd;
      font-weight: bold;
      text-align: center;
      margin-bottom: 20px;
    }
    .about-card {
      background: white;
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0px 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .about-icon {
      font-size: 40px;
      color: #0d6efd;
    }
    footer {
      background-color: #004aad;
      color: white;
      padding: 20px 0;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
        <img src="https://sdmntprnorthcentralus.oaiusercontent.com/files/00000000-7180-622f-a7c4-5ab3a1f8591c/raw?se=2025-08-13T10%3A07%3A06Z&sp=r&sv=2024-08-04&sr=b&scid=08eecb88-a43c-53bc-a32e-2ada9dc3dc3f&skoid=e9d2f8b1-028a-4cff-8eb1-d0e66fbefcca&sktid=a48cca56-e6da-484e-a814-9c849652bcb3&skt=2025-08-13T08%3A58%3A13Z&ske=2025-08-14T08%3A58%3A13Z&sks=b&skv=2024-08-04&sig=KJkC1CVkTickYQPWsG1WU5LjcDPDmc9YPviZz0oSlIc%3D" alt="StudySnap Logo">
      </a>
    <a class="navbar-brand" href="planner.php">Study Planner</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse ms-auto" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="login.php">Login</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="signup.php">Sign Up</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="doubt.php">Doubt Forum</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="planner.php">Planner</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- About Section -->
<div class="container mt-5">
  <h2 class="section-title">About Us</h2>
  <div class="about-card">
    <p>
      Welcome to the <strong>Study Planner & Doubt Exchange Portal</strong> — your one-stop solution for organizing your academic journey and collaborating with fellow students. 
      We aim to make learning more interactive, structured, and efficient.
    </p>
  </div>

  <h4 class="section-title">Our Mission</h4>
  <div class="about-card">
    <p>
      Our mission is to help students plan their study schedules effectively while providing a safe space to ask questions and clear doubts. 
      We believe that shared knowledge leads to better learning outcomes.
    </p>
  </div>

  <h4 class="section-title">Key Features</h4>
  <div class="row">
    <div class="col-md-4">
      <div class="about-card text-center">
        <div class="about-icon">📅</div>
        <h5>Study Planner</h5>
        <p>Organize your study schedule and never miss important deadlines.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="about-card text-center">
        <div class="about-icon">💬</div>
        <h5>Doubt Exchange</h5>
        <p>Ask questions, share answers, and collaborate with peers easily.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="about-card text-center">
        <div class="about-icon">📊</div>
        <h5>Progress Tracking</h5>
        <p>Monitor your learning progress and achieve your academic goals.</p>
      </div>
    </div>
  </div>
</div>
<footer class="text-center">
    <p>© 2025 Study Planner & Doubt Exchange Portal | Designed with ❤</p>
  </footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>