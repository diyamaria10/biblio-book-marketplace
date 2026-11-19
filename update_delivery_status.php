<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Get and sanitize input
$assignment_id = (int)($_POST['assignment_id'] ?? 0);
$new_status = trim($_POST['new_status'] ?? '');
$new_status = substr($new_status, 0, 20); // ensure max 20 chars for VARCHAR(20)

if (!$assignment_id || !$new_status) {
    die("Error: Missing assignment ID or status. assignment_id='$assignment_id', new_status='$new_status'");
}

$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Map assignment statuses to order statuses
$order_status_map = [
    'accepted'   => 'confirmed',
    'picked_up'  => 'shipped',
    'in_transit' => 'shipped',
    'delivered'  => 'delivered'
];

$order_status = $order_status_map[$new_status] ?? 'pending';


// 1️⃣ Get order_id from assignment(newest added)
$orderIdStmt = $conn->prepare("SELECT order_id FROM tbl_order_assignments WHERE assignment_id = ?");
$orderIdStmt->bind_param("i", $assignment_id);
$orderIdStmt->execute();
$orderIdStmt->bind_result($order_id);
$orderIdStmt->fetch();
$orderIdStmt->close();

if (!$order_id) die("Error: No order found for this assignment.");

// Update tbl_order_assignments
if ($new_status === 'delivered') {
    $sql = "UPDATE tbl_order_assignments SET status = ?, delivered_at = NOW() WHERE assignment_id = ?";
} else {
    $sql = "UPDATE tbl_order_assignments SET status = ? WHERE assignment_id = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $new_status, $assignment_id);
if (!$stmt->execute()) {
    die("Error updating tbl_order_assignments: " . $stmt->error);
}
$stmt->close();

// Update tbl_orders to reflect delivery status
$updateOrder = $conn->prepare("
    UPDATE tbl_orders 
    SET status = ? 
    WHERE orderid = (SELECT order_id FROM tbl_order_assignments WHERE assignment_id = ?)
");
$updateOrder->bind_param("si", $order_status, $assignment_id);
if (!$updateOrder->execute()) {
    die("Error updating tbl_orders: " . $updateOrder->error);
}
$updateOrder->close();

$conn->close();

// Redirect after successful update
header("Location: educourier-dashboard.php?status=updated");
exit;
?>
