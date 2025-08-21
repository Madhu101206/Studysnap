<?php
$success = false;
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'connection.php';

    // Fetch form values
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'] ?? '';
    $currentYear = $_POST['currentYear'];
    $semester = $_POST['semester'];
    $department = $_POST['department'];
    $degree = $_POST['degree'];

    // Validation
if ($password !== $confirmPassword) {
    $error = "Passwords do not match!";
} else {
    // Check if email already exists
    $checkEmail = "SELECT * FROM signup WHERE email='$email'";
    $result = mysqli_query($con, $checkEmail);
    if (mysqli_num_rows($result) > 0) {
        $error = "Email already registered.";
    } else {
        // Insert into database without hashing
        $sql = "INSERT INTO signup (name, email, password, phone, dob, gender, currentYear, semester, department, degree)
                VALUES ('$name', '$email', '$password', '$phone', '$dob', '$gender', '$currentYear', '$semester', '$department', '$degree')";

        if (mysqli_query($con, $sql)) {
    // Redirect to login page after successful registration
    header("Location: login.php");
    exit;
} else {
    $error = "Database error: " . mysqli_error($con);
}
    }
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Signup - Study Planner</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    /* (keep your existing styles unchanged) */
    body {
      background-color: #e6f0ff;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #0047b3;
    }

    h2 {
      color: #0066ff;
      font-weight: 700; 
      text-align: center;
      margin-bottom: 30px;
    }

    .container {
      background-color: #ffffff;
      border-radius: 8px;
      box-shadow: 0 6px 15px rgba(0, 0, 128, 0.15);
      padding: 30px 40px;
    }

    .btn-primary {
      background-color: #0066ff;
      border-color: #0066ff;
      font-weight: 600;
      font-size: 1.1rem;
      transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #0047b3;
      border-color: #003d99;
    }

    .alert {
      margin-top: 10px;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4">Sign Up</h2>

    <?php if ($success): ?>
      <div class="alert alert-success">Registration successful! <a href="login.php">Click here to login</a></div>
    <?php elseif (!empty($error)): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" name="name" id="name" class="form-control" required />
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" name="email" id="email" class="form-control" required />
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" id="password" class="form-control" required />
      </div>

      <div class="mb-3">
        <label for="confirmPassword" class="form-label">Confirm Password</label>
        <input type="password" name="confirmPassword" id="confirmPassword" class="form-control" required />
      </div>

      <div class="mb-3">
        <label for="phone" class="form-label">Phone Number</label>
        <input type="tel" name="phone" id="phone" class="form-control" pattern="[0-9]{10}" placeholder="10-digit number" required />
      </div>

      <div class="mb-3">
        <label for="dob" class="form-label">Date of Birth</label>
        <input type="date" name="dob" id="dob" class="form-control" required />
      </div>

      <div class="mb-3">
        <label class="form-label">Gender</label><br />
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male" required />
          <label class="form-check-label" for="genderMale">Male</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female" />
          <label class="form-check-label" for="genderFemale">Female</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="gender" id="genderOther" value="Other" />
          <label class="form-check-label" for="genderOther">Other</label>
        </div>
      </div>

      <div class="mb-3">
        <label for="currentYear" class="form-label">Current Year</label>
        <select name="currentYear" id="currentYear" class="form-select" required>
          <option value="">Select Year</option>
          <option value="1">1st Year</option>
          <option value="2">2nd Year</option>
          <option value="3">3rd Year</option>
          <option value="4">4th Year</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="semester" class="form-label">Semester</label>
        <select name="semester" id="semester" class="form-select" required>
          <option value="">Select Semester</option>
          <option value="1">Semester 1</option>
          <option value="2">Semester 2</option>
          <option value="3">Semester 3</option>
          <option value="4">Semester 4</option>
          <option value="5">Semester 5</option>
          <option value="6">Semester 6</option>
          <option value="7">Semester 7</option>
          <option value="8">Semester 8</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="department" class="form-label">Department</label>
        <select name="department" id="department" class="form-select" required>
          <option value="">Select Department</option>
          <option value="Computer Science">Computer Science</option>
          <option value="Electronics">Electronics</option>
          <option value="Mechanical">Mechanical</option>
          <option value="Civil">Civil</option>
          <option value="Chemical">Chemical</option>
          <option value="Electrical">Electrical</option>
          <option value="Information Technology">Information Technology</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="degree" class="form-label">Degree</label>
        <select name="degree" id="degree" class="form-select" required>
          <option value="">Select Degree</option>
          <option value="B.Tech">B.Tech</option>
          <option value="B.E">B.E</option>
          <option value="M.Tech">M.Tech</option>
          <option value="M.Sc">M.Sc</option>
          <option value="Ph.D">Ph.D</option>
          <option value="MBA">MBA</option>
        </select>
      </div>

      <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="terms" required />
        <label class="form-check-label" for="terms">I agree to the Terms & Conditions</label>
      </div>

      <button type="submit" class="btn btn-primary w-100">Sign Up</button>
      <p class="mt-3 text-center">
        Already have an account? <a href="login.php">Login here</a>
      </p>
      <a href="index.php" class="d-block text-center mt-2">&larr; Back to Home</a>
    </form>
  </div>
</body>
</html>