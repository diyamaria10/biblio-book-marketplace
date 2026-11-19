<?php
session_start();
include 'db.php'; // Include database connection

header('Content-Type: application/json'); // ✅ Important for AJAX responses

// ✅ Ensure the buyer is logged in
$buyerId = $_SESSION['id'] ?? 0;
if (!$buyerId) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// ✅ Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($orderId > 0 && $rating >= 1 && $rating <= 5) {

        // 1️⃣ Get the seller for this order
        $sellerQuery = $conn->prepare("
            SELECT b.sellerid 
            FROM tbl_orders o
            JOIN tbl_books b ON o.bookid = b.bookid
            WHERE o.orderid = ?
        ");
        $sellerQuery->bind_param("i", $orderId);
        $sellerQuery->execute();
        $result = $sellerQuery->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $sellerId = $row['sellerid'];

            // 2️⃣ Check if buyer has already rated this order
            $checkQuery = $conn->prepare("
                SELECT * FROM tbl_seller_ratings 
                WHERE order_id = ? AND buyer_id = ?
            ");
            $checkQuery->bind_param("ii", $orderId, $buyerId);
            $checkQuery->execute();
            $checkResult = $checkQuery->get_result();

            if ($checkResult->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'You have already rated this seller for this order']);
                exit;
            }

            // 2️⃣ Insert the rating into tbl_seller_ratings
            $insert = $conn->prepare("
                INSERT INTO tbl_seller_ratings (order_id, buyer_id, seller_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insert->bind_param("iiiis", $orderId, $buyerId, $sellerId, $rating, $comment);

            if ($insert->execute()) {
                // 3️⃣ Create a notification for the seller
                $message = "You received a new rating (" . $rating . "★) from a buyer for a recent order.";
                $notif = $conn->prepare("
                    INSERT INTO tbl_notification (user_id, message, type, created_at)
                    VALUES (?, ?, 'seller_rating', NOW())
                ");
                $notif->bind_param("is", $sellerId, $message);
                $notif->execute();

                echo json_encode(['success' => true, 'message' => 'Rating submitted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database insert failed']);
            }

        } else {
            echo json_encode(['success' => false, 'message' => 'Seller not found for this order']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid rating data']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
