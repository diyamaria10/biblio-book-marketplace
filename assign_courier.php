<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db.php'; // your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id']);
    $courier_id = intval($_POST['courier_id']);
    $admin_id = 1; // single admin

    // 1. Insert assignment record
    $stmt = $conn->prepare("INSERT INTO tbl_order_assignments (order_id, courier_id, assigned_by) VALUES (?,?,?)");
    $stmt->bind_param("iii", $order_id, $courier_id, $admin_id);

    if ($stmt->execute()) {
        $assignment_id = $conn->insert_id; // get the assignment_id of this assignment

        // 2. Update order status
        $conn->query("UPDATE tbl_orders SET status='confirmed' WHERE orderid=$order_id");

        // 3. Insert initial delivery status
        $initial_status = "pending";
        $notes = "Courier has been assigned but not yet accepted.";
        $stmt2 = $conn->prepare("INSERT INTO tbl_delivery_status (assignment_id, status, updated_at, updated_by, notes) VALUES (?, ?, NOW(), ?, ?)");
        if (!$stmt2) {
    die("Prepare failed: " . $conn->error);
}
$stmt2->bind_param("isis", $assignment_id, $initial_status, $admin_id, $notes);
if (!$stmt2->execute()) {
    die("Execute failed: " . $stmt2->error);
}

        // 4. Optional notification for courier
        $msg = "You have been assigned to Order #$order_id.";
        $conn->query("INSERT INTO tbl_notification (user_id, message, is_read, created_at) 
                      VALUES ($courier_id, '$msg', 0, NOW())");

        header("Location: admin-dashboard.php#courier-activity");
        exit;
    } else {
        echo "Error assigning courier: " . $conn->error;
    }
}
?>
