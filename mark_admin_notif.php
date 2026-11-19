<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);

    $stmt = $conn->prepare("UPDATE tbl_notification SET is_read = 1 WHERE notification_id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $notif_id);

    if ($stmt->execute()) {
        header("Location: admin-dashboard.php#notifications");
        exit;    } else {
        die("Error executing query: " . $stmt->error);
    }

    $stmt->close();
} else {
    header("Location: admin-dashboard.php#notifications");
    exit;
}
?>

