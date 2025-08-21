<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Study Planner & Doubt Exchange</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f4faff;
      font-family: 'Segoe UI', sans-serif;
    }
    .navbar {
      background: linear-gradient(90deg, #0d6efd, #004aad);
    }
    .navbar-brand img {
      height: 40px;
      margin-right: 10px;
    }
    .hero {
      background: url('https://images.unsplash.com/photo-1557683316-973673baf926') center/cover no-repeat;
      color: white;
      text-shadow: 1px 1px 4px rgba(0,0,0,0.5);
      padding: 100px 0;
    }
    .feature-card {
      transition: transform 0.3s;
    }
    .feature-card:hover {
      transform: translateY(-8px);
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
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand fw-bold" href="#">
        <img src="Studyplanner-Logo-01.png" alt="StudySnap Logo">
        Study Planner
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="planner.php">Planner</a></li>
          <li class="nav-item"><a class="nav-link" href="doubt.php">Doubt Forum</a></li>
          <li class="nav-item"><a class="nav-link" href="aboutus.php">About Us</a></li>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="signup.php">Signup</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero text-center">
    <div class="container">
      <h1 class="display-4 fw-bold">Plan. Learn. Succeed.</h1>
      <p class="lead">Organize your studies and clear doubts instantly with our all-in-one student platform.</p>
      <a href="signup.php" class="btn btn-light btn-lg">Get Started</a>
    </div>
  </section>

  <!-- Carousel -->
  <div id="featureCarousel" class="carousel slide mt-5" data-bs-ride="carousel">
    <div class="carousel-inner">
      <div class="carousel-item active text-center">
        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f" class="d-block mx-auto" style="max-height: 400px;" alt="Study Planner">
        <div class="carousel-caption d-none d-md-block">
          <h5>Smart Study Planner</h5>
          <p>Organize your daily, weekly, and monthly study schedules.</p>
        </div>
      </div>
      <div class="carousel-item text-center">
        <img src="https://images.unsplash.com/photo-1596495577886-d920f1fb7238" class="d-block mx-auto" style="max-height: 400px;" alt="Doubt Exchange">
        <div class="carousel-caption d-none d-md-block">
          <h5>Doubt Exchange Portal</h5>
          <p>Ask, answer, and discuss doubts with peers and mentors.</p>
        </div>
      </div>
      <div class="carousel-item text-center">
        <img src="https://images.unsplash.com/photo-1531482615713-2afd69097998" class="d-block mx-auto" style="max-height: 400px;" alt="Student Dashboard">
        <div class="carousel-caption d-none d-md-block">
          <h5>Student Dashboard</h5>
          <p>Track your progress and performance in one place.</p>
        </div>
      </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#featureCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#featureCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>

  <!-- Features Section -->
  <section class="container my-5">
    <div class="row text-center">
      <div class="col-md-4">
        <div class="card feature-card shadow-sm p-3">
          <h5>📅 Smart Planning</h5>
          <p>Create effective study plans tailored to your goals.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card feature-card shadow-sm p-3">
          <h5>💬 Doubt Forum</h5>
          <p>Post your questions and get answers from students and teachers.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card feature-card shadow-sm p-3">
          <h5>📊 Progress Tracking</h5>
          <p>Stay motivated by tracking your study achievements.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="text-center">
    <p>© 2025 Study Planner & Doubt Exchange Portal | Designed with ❤</p>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>