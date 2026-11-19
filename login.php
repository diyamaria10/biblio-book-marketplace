<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$dbname = "BIBLIO";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $_POST["email"];
  $pass = $_POST["password"];


  
  if ($email == "admin@gmail.com" && $pass == "admin123") {
    $_SESSION['role'] = "admin";
    header("Location: admin-dashboard.php");
    exit();
  }

  
  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();

    if (password_verify($pass, $user['password'])) {
      $_SESSION['id'] = $user['id'];
      $_SESSION['name'] = $user['name'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['email'] = $user['email'];
      $_SESSION['show_welcome'] = true;




      if ($user['role'] == 'buyer' || $user['role'] == 'seller') {
        header("Location: user-dashboard.php");
      } elseif ($user['role'] == 'courier') {
        header("Location: educourier-dashboard.php");
      }
      exit();
    } else {
      echo "<script>alert('Incorrect password');</script>";
    }
  } else {
    echo "<script>alert('No user found with that email');</script>";
  }

  $stmt->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | Biblio</title>
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

    input[type="email"],
    input[type="password"],
    select {
      width: 100%;
      padding: 12px 14px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 1rem;
      transition: border-color 0.3s ease;
      background-color: #fff;
      color: var(--text-gray);
      font-family: 'Inter', sans-serif;
    }

    input:focus,
    select:focus {
      border-color: var(--sage-green);
      outline: none;
    }

    .forgot {
      text-align: right;
      margin-top: -14px;
      margin-bottom: 20px;
    }

    .forgot a {
      font-size: 0.85rem;
      color: var(--blush-pink);
      text-decoration: none;
    }

    .forgot a:hover {
      text-decoration: underline;
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

.role-buttons input[type="radio"] {
  display: none;
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

.role-buttons input[type="radio"]:checked + .role-btn {
  background-color: var(--sage-green);
  color: white;
  border-color: var(--sage-green);
}

  </style>
</head>
<body>
  <a href="index1.php?skipSplash=true" class="back-arrow">←</a>
  <div class="login-container">
    <h2>Login</h2>
    <form method="POST">
      <label for="email">Email Address</label>
      <input type="email" id="email" name="email" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <div class="forgot">
        <a href="fpswrd.php">Forgot Password?</a>
      </div>
      <button type="submit">Login</button>
    </form>

    <div class="footer">
      Don’t have an account? <a href="signup.php">Sign Up</a>
    </div>
  </div>
</body>
</html>