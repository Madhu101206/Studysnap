<?php
session_start();

$logged = 0;
$invalid = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'connection.php';

    $email = isset($_POST['name']) ? mysqli_real_escape_string($con, $_POST['name']) : '';
    $password = isset($_POST['pass']) ? $_POST['pass'] : '';

    if (!empty($email) && !empty($password)) {
        $query = "SELECT * FROM signup WHERE email='$email'";
        $result = mysqli_query($con, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            if ($password === $row['password']) {
                $_SESSION['name'] = $row['name']; // ✅ Store the user's actual name
                header("Location: index.php");
                exit();
            } else {
                $invalid = 1;
            }
        } else {
            $invalid = 1;
        }
    } else {
        $invalid = 1;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login Page</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script>
    function formValidation(){
        let x = document.forms["form1"]["name"].value;
        let y = document.forms["form1"]["pass"].value;
        if(x == ""){
            alert("Email must be filled");
            return false;
        }
        if(y == ""){
            alert("Password must be filled");
            return false;
        }
    }
  </script>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #e6f0ff, #f2f6ff);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .login-container {
      background: white;
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0px 10px 40px rgba(0, 0, 0, 0.1);
      width: 350px;
      text-align: center;
    }
    h2 {
      color: #007bff;
      margin-bottom: 20px;
    }
    label {
      display: block;
      text-align: left;
      margin: 10px 0 5px;
      font-weight: bold;
    }
    input {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
    }
    .login-btn {
      width: 100%;
      padding: 10px;
      background: #0d6efd;
      border: none;
      border-radius: 8px;
      color: white;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
    }
    .login-btn:hover {
      background: #084298;
    }
    p {
      font-size: 14px;
    }
    a {
      color: #007bff;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    .home-link {
      display: block;
      margin-top: 8px;
      font-size: 14px;
    }
    .alert {
      padding: 10px;
      margin: 10px auto;
      width: 100%;
      border-radius: 8px;
      text-align: center;
      font-weight: bold;
    }
    .alert-danger {
      background: #f8d7da;
      color: #721c24;
    }
    .alert-success {
      background: #d4edda;
      color: #155724;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Welcome Back</h2>

    <?php if($invalid): ?>
      <div class="alert alert-danger">Error: Please register first or check credentials.</div>
    <?php endif; ?>

    <form action="login.php" method="POST" onsubmit="return formValidation();" name="form1">
      <label>Email</label>
      <input type="email" name="name" placeholder="Enter your email">

      <label>Password</label>
      <input type="password" name="pass" placeholder="Enter your password">

      <button type="submit" class="login-btn">Login</button>

      <p>Don't have an account? <a href="signup.php">Sign up</a></p>
      <a href="index.php" class="home-link">← Back to Home</a>
    </form>
  </div>
</body>
</html>