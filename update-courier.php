<?php
session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Sanitize inputs
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$password = $_POST['password'];

// Get current user email from session
$currentEmail = $_SESSION['email'];

// Build update query
if (!empty($password)) {
  $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE email = ?");
  $stmt->bind_param("ssss", $name, $email, $hashedPassword, $currentEmail);
} else {
  $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE email = ?");
  $stmt->bind_param("sss", $name, $email, $currentEmail);
}

if ($stmt->execute()) {
  // Update session values
  $_SESSION['name'] = $name;
  $_SESSION['email'] = $email;

 $redirectPage = isset($_POST['redirect']) ? $_POST['redirect'] : 'user-dashboard.php';

  echo "<script>
  alert('Changes updated successfully!');
    window.location.href='$redirectPage?status=toast';
  </script>";
  exit;
}


$stmt->close();
$conn->close();
?>

