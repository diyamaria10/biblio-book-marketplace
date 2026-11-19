<?php
$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    $stmt = $conn->prepare("DELETE FROM tbl_notification WHERE notification_id = ?");
    $stmt->bind_param("i", $notif_id);
    $stmt->execute();
    $stmt->close();
}
header("Location: admin-dashboard.php#notifications");
exit;
?>
