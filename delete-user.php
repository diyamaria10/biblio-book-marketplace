<?php
session_start();

// Connect to DB
$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get user ID from URL
$id = intval($_GET['id']);

// Delete user
$sql = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  echo "<script>alert('User deleted successfully.');
        window.location.href='admin-dashboard.php?section=manage-users';
        </script>";
} else {
  echo "<script>alert('Error deleting user.');
        window.location.href='admin-dashboard.php?section=manage-users';
        </script>";
}

$stmt->close();
$conn->close();
?>
