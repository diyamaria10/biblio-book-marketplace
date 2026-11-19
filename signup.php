<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $host = "localhost";
    $username = "root";
    $password = "";
    $dbname = "BIBLIO";

    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password_raw = $_POST['password'] ?? '';
$role = trim($_POST['role'] ?? '');
if (!in_array($role, ['seller','buyer','courier'])) {
        echo "<script>alert('Please select a role before signing up.'); window.history.back();</script>";
        exit();
    }

$errors = [];

if (strlen($name) < 2 || !preg_match("/^[a-zA-Z\s]+$/", $name)) {
    $errors[] = "Invalid name";
}

$email = strtolower(trim($_POST['email'] ?? ''));


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
} 

if (strlen($password_raw) < 8) {
    $errors[] = "Password too short";
}

if (!in_array($role, ['seller','buyer','courier'])) {
    $errors[] = "Invalid role selected";
}

if (!empty($errors)) {
    foreach ($errors as $err) {
        echo "<p style='color:red; text-align:center;'>$err</p>";
    }
    echo "<p style='text-align:center;'><a href='signup.php'>Go back</a></p>";
    exit();
}

$pass = password_hash($password_raw, PASSWORD_DEFAULT);


    
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email already exists! Try logging in.'); window.location.href='login.php';</script>";
        exit();
    }

    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $pass, $role);

    if ($stmt->execute()) {
       
    $newUserId = $conn->insert_id;
        
        $_SESSION['id'] = $newUserId;
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['courier_origin'] = 'signup';
        $_SESSION['show_welcome'] = true;

        
        if ($role === "courier") {
            header("Location: educourier-dashboard.php");
        } else {
            header("Location: user-dashboard.php");
        }
        exit();
    } else {
    die("MySQL insert error: " . $stmt->error);

    }

    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up | Biblio</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Alex+Brush&display=swap" rel="stylesheet">
  <style>
    :root {
      --sage-green: #b7c9a8;
      --blush-pink: #e8a7b0;
      --soft-white: #fdfdfb;
      --olive: #4a6b57;
      --text-gray: #555;
    }

    * {
      box-sizing: border-box;
    }

    body {
  margin: 0;
  padding: 0;
  font-family: 'Poppins', sans-serif;
  background: url('bg1.jpeg') no-repeat center center fixed;
  background-size: cover;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}
    .back-arrow {
      position: absolute;
      top: 20px;
      left: 30px;
      font-size: 1.8rem;
      color: var(--sage-green);
      text-decoration: none;
      font-weight: bold;
      transition: color 0.3s ease, transform 0.2s ease;
    }

    .back-arrow:hover {
      color: var(--olive);
      transform: translateX(-5px);
    }

    .login-container {
      background-color: #ffffff;
      padding: 40px 35px;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
      width: 100%;
      max-width: 400px;
    }

    .login-container h2 {
      font-family: 'Alex Brush', serif;
      color: var(--olive);
      font-size: 2.4rem;
      text-align: center;
      margin-bottom: 24px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--olive);
      font-size: 0.95rem;
    }

    input, select {
      width: 100%;
      padding: 12px 14px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 1rem;
      background-color: #fff;
      color: var(--text-gray);
    }

    input:focus, select:focus {
      border-color: var(--sage-green);
      outline: none;
    }

    button {
      width: 100%;
      background-color: var(--blush-pink);
      color: white;
      border: none;
      padding: 12px;
      font-size: 1rem;
      border-radius: 25px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      font-weight: 500;
    }

    button:hover {
      background-color: #d98a99;
    }

    .footer {
      text-align: center;
      margin-top: 20px;
      font-size: 0.9rem;
      color: #666;
    }

    .footer a {
      color: var(--sage-green);
      text-decoration: none;
      font-weight: 500;
    }

    .footer a:hover {
      text-decoration: underline;
      color: var(--olive);
    }

    @media (max-width: 480px) {
      .login-container {
        padding: 30px 25px;
      }

      .login-container h2 {
        font-size: 2rem;
      }

      .back-arrow {
        font-size: 1.5rem;
        left: 20px;
      }
    }
.role-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 20px;
}

.role-btn {
  flex: 1 1 40%;
  text-align: center;
  padding: 10px 12px;
  border: 1.5px solid var(--sage-green);
  border-radius: 20px;
  cursor: pointer;
  color: var(--olive);
  background-color: #fff;
  transition: background-color 0.3s ease, color 0.3s ease;
  font-weight: 500;
}

.role-btn.selected {
  background-color: var(--sage-green);
  color: white;
  border-color: var(--sage-green);
}

  </style>
</head>
<body>
  <a href="login.php?skipSplash=true" class="back-arrow">←</a>
  <div class="login-container">
    <h2>Sign Up</h2>
    <form id="signupForm" method="POST" action="signup.php">
      <label for="name">Full Name</label>
      <input type="text" id="name" name="name" required>

      <label for="email">Email Address</label>
      <input type="email" id="email" name="email" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <label>Register as</label>
<div class="role-buttons">
  <input type="hidden" name="role" id="roleInput">

    <button type="button" class="role-btn" onclick="selectRole('seller', this)">Seller</button>
<button type="button" class="role-btn" onclick="selectRole('buyer', this)">Buyer</button>
<button type="button" class="role-btn" onclick="selectRole('courier', this)">Courier</button>
</div>

      <button type="submit">Sign Up</button>
    </form>
    <div class="footer">
      Already have an account? <a href="login.php">Login</a>
    </div>
  </div>
<script>
  function selectRole(role, btn) {
  
  document.getElementById('roleInput').value = role;

  
  document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('selected'));

  
  btn.classList.add('selected');
}
document.getElementById('signupForm').addEventListener('submit', function (e) {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const role = document.getElementById('roleInput').value;

    
    if (name.length < 2 || !/^[a-zA-Z\s]+$/.test(name)) {
        alert('Please enter a valid full name (letters and spaces only).');
        e.preventDefault();
        return;
    }

    
    const emailPattern = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;
    if (!emailPattern.test(email)) {
        alert('Please enter a valid email address(lowercase only)');
        e.preventDefault();
        return;
    }

    
    if (password.length < 8) {
        alert('Password must be at least 8 characters.');
        e.preventDefault();
        return;
    }

    
    if (!role) {
        alert('Please select a role.');
        e.preventDefault();
        return;
    }

});
</script>


</body>
</html>