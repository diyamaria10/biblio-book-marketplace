<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Ensure buyer is logged in
if (!isset($_SESSION['id'])) {
    die("You must be logged in to complete payment.");
}
$buyerid = $_SESSION['id'];
$negotiation_id = isset($_POST['negotiation_id']) ? intval($_POST['negotiation_id']) : null;

// ------------------ Notification Functions ------------------
function notifySeller($conn, $seller_id, $order_id) {
    $message = "You have a new order #$order_id for your book!";
    $stmt = $conn->prepare("INSERT INTO tbl_notification (user_id, message, type) VALUES (?, ?, 'order')");
    $stmt->bind_param("is", $seller_id, $message);
    $stmt->execute();
    $stmt->close();
}

function notifyAdmin($conn, $order_id) {
    $admin_id = 58; // Change if multiple admins or fetch dynamically
    $message = "New order #$order_id has been placed. Assign a courier.";
    $stmt = $conn->prepare("INSERT INTO tbl_notification (user_id, message, type) VALUES (?, ?, 'order')");
    $stmt->bind_param("is", $admin_id, $message);
    $stmt->execute();
    $stmt->close();
}

// ------------------ Main Logic ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookid = intval($_POST['bookid']);
    $amount = floatval($_POST['amount']);
    $payment_method = trim($_POST['payment_method']);

    // --- Step 0: Fetch seller_id for this book ---
    $stmt = $conn->prepare("SELECT sellerid FROM tbl_books WHERE bookid = ?");
    $stmt->bind_param("i", $bookid);
    $stmt->execute();
    $stmt->bind_result($seller_id);
    $stmt->fetch();
    $stmt->close();

    if (!$seller_id) {
        die("Seller not found for this book.");
    }

    // --- Step 1: Insert into tbl_orders ---
    $status = 'pending';
    $stmt = $conn->prepare("INSERT INTO tbl_orders 
        (id, bookid, negotiation_id, order_date, total_price, status) 
        VALUES (?, ?, ?, NOW(), ?, ?)");
    if (!$stmt) {
        die("Prepare failed (orders): " . $conn->error);
    }
    $stmt->bind_param("iiids", $buyerid, $bookid,$negotiation_id, $amount, $status);

    if (!$stmt->execute()) {
        die("Error inserting order: " . $stmt->error);
    }
    $order_id = $stmt->insert_id;
    $stmt->close();

    // --- Step 1b: Mark book as sold ---
$stmt_update = $conn->prepare("UPDATE tbl_books SET is_active=0 WHERE bookid=?");
$stmt_update->bind_param("i", $bookid);
$stmt_update->execute();
$stmt_update->close();

    // --- Step 2: Insert into tbl_payment_details ---
    $payment_status = 'Paid';
    $stmt2 = $conn->prepare("INSERT INTO tbl_payment_details 
        (order_id, user_id, amount_paid, payment_method, payment_status, payment_date) 
        VALUES (?, ?, ?, ?, ? , NOW())");
    if (!$stmt2) {
        die("Prepare failed (payments): " . $conn->error);
    }
    $stmt2->bind_param("iidss", $order_id, $buyerid, $amount, $payment_method, $payment_status);

    if (!$stmt2->execute()) {
        die("Error inserting payment: " . $stmt2->error);
    }
    $payment_id = $stmt2->insert_id;
    $stmt2->close();

    // --- Step 3: Send Notifications ---
    notifyAdmin($conn, $order_id);
    notifySeller($conn, $seller_id, $order_id);

    // --- Step 4: Redirect to success page ---
    header("Location: order_success.php?order_id=" . $order_id . "&payment_id=" . $payment_id);
    exit;
} else {
    header("Location: user-dashboard.php");
    exit;
}
?>


