<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php'; // Include DB connection

$buyerId = $_SESSION['id'] ?? 0;

if (!$buyerId) {
    // Buyer not logged in
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);

    // 1️⃣ Update order status to "Received" by buyer
    $updateStmt = $conn->prepare("UPDATE tbl_order_assignments SET status='Received' WHERE order_id=?");
    $updateStmt->bind_param("i", $orderId);
    $updateStmt->execute();

    // 2️⃣ Get seller info for this order
    $sellerQuery = $conn->prepare("
        SELECT b.sellerid, b.title 
        FROM tbl_orders o
        JOIN tbl_books b ON o.bookid = b.bookid
        WHERE o.orderid = ?
    ");
    $sellerQuery->bind_param("i", $orderId);
    $sellerQuery->execute();
    $sellerResult = $sellerQuery->get_result();

    if ($sellerResult->num_rows > 0) {
        $row = $sellerResult->fetch_assoc();

        // 🩹 FIX: correct column name to match your DB field
        $sellerId = $row['sellerid']; // not seller_id
        $bookTitle =  html_entity_decode($row['title']);

        // 3️⃣ Insert notification for seller
        // 🩹 FIX: table name should be tbl_notifications (plural, based on your DB)
        $notifStmt = $conn->prepare("
            INSERT INTO tbl_notification (user_id, message, type, created_at)
            VALUES (?, ?, 'order_received', NOW())
        ");
        $message = "Your book '$bookTitle'  has been received by the buyer.";
        $notifStmt->bind_param("is", $sellerId, $message);
        $notifStmt->execute();
        $notifStmt->close();
    }

    // Cleanup
    $updateStmt->close();
    $sellerQuery->close();

    // Redirect back to user dashboard with success flag
    header("Location: user-dashboard.php?success=received");
    exit;

} else {
    // Invalid access or missing data
    header("Location: user-dashboard.php");
    exit;
}
?>
