<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;

if (!$order_id || !$payment_id) {
    die("Invalid order.");
}

// Fetch order details
$order_stmt = $conn->prepare("SELECT o.orderid, o.bookid, o.total_price, o.status, b.title
                              FROM tbl_orders o 
                              JOIN tbl_books b ON o.bookid = b.bookid
                              WHERE o.orderid = ?");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result()->fetch_assoc();
$order_stmt->close();

// Fetch payment details
$payment_stmt = $conn->prepare("SELECT payment_method, amount_paid, payment_status FROM tbl_payment_details WHERE payment_id = ?");
$payment_stmt->bind_param("i", $payment_id);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result()->fetch_assoc();
$payment_stmt->close();
$payment_status_display = 'Paid';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Success - Biblio</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8F6F4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .success-box {
            background-color: #ffff;
            border: 2px solid #B69E59; /* sage-gold accent */
            border-radius: 12px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-box h2 {
            color: #5D4A3E; /* deep espresso brown */
            font-family: 'DM Serif Display', serif;
            margin-bottom: 20px;
        }
        .success-box p {
            margin: 10px 0;
            color: #2D2D2D;
            font-size: 1rem;
        }
        .highlight {
            font-weight: 600;
            color: #9F3D3D; /* warm maroon */
        }
        .btn {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 25px;
            background-color: #B69E59; /* sage-gold accent */
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #9F8B46;
        }
    </style>
</head>
<body>

<div class="success-box">
    <h2>✅ Order Placed Successfully!</h2>

    <p><strong>Order ID:</strong> <span class="highlight"><?= $order_result['orderid'] ?></span></p>
    <p><strong>Book:</strong> <span class="highlight"><?= html_entity_decode($order_result['title']) ?></span></p>
    <p><strong>Total Price:</strong> <span class="highlight">₹<?= number_format($order_result['total_price'], 2) ?></span></p>
    <p><strong>Status:</strong> <span class="highlight"><?= ucfirst($order_result['status']) ?></span></p>
    <p><strong>Payment Method:</strong> <span class="highlight"><?= htmlspecialchars($payment_result['payment_method']) ?></span></p>
    <p><strong>Payment Status:</strong> <span class="highlight"><?= $payment_status_display ?></span></p>

<a href="track_order.php?order_id=<?php echo $order_id; ?>" class="btn">Track Order 📦</a>
    <br><br>
    <a class="btn" href="user-dashboard.php">Go to Dashboard 🏠</a>
</div>

</body>
</html>
