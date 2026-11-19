<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['id'])) {
    die("You must be logged in to place an order.");
}

$user_id = $_SESSION['id'];

// ✅ Step 1: Get active cart for this user
$stmt = $conn->prepare("SELECT cart_id FROM tbl_carts WHERE user_id=? AND is_active=1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    die("Your cart is empty.");
}

$cart_id = $row['cart_id'];
$stmt->close();

// ✅ Step 2: Get all cart items
$stmt = $conn->prepare("SELECT ci.book_id, ci.quantity, b.price 
                        FROM tbl_cart_item ci
                        INNER JOIN tbl_books b ON ci.book_id = b.bookid
                        WHERE ci.cart_id=?");
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Your cart is empty.");
}

// ✅ Step 3: Insert orders
$payment_methods = [];
$order_ids = [];
while ($item = $result->fetch_assoc()) {
    $bookid = $item['book_id'];
    $quantity = $item['quantity'];
    $total_price = $item['price'] * $quantity;

    $stmt2 = $conn->prepare("INSERT INTO tbl_orders (id, bookid, negotiation_id, order_date, total_price, status)
                             VALUES (?, ?, NULL, NOW(), ?, 'Pending')");
    $stmt2->bind_param("iid", $user_id, $bookid, $total_price);
    $stmt2->execute();
    $order_ids[] = $stmt2->insert_id;
    $payment_methods[] = $payment_method;
    $stmt2->close();

    // ✅ Step 3b: Mark that same book as inactive (sold)
    $stmt3 = $conn->prepare("UPDATE tbl_books SET is_active=0 WHERE bookid=?");
    $stmt3->bind_param("i", $bookid);
    $stmt3->execute();
    $stmt3->close();
}
    
// ✅ Step 4: Mark cart as inactive
$stmt = $conn->prepare("UPDATE tbl_carts SET is_active=0 WHERE cart_id=?");
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$stmt->close();

// ✅ Step 5: Create a fresh empty cart for the user
$newCart = $conn->prepare("INSERT INTO tbl_carts (user_id, is_active) VALUES (?, 1)");
$newCart->bind_param("i", $user_id);
$newCart->execute();
$newCart->close();

// ✅ Step 5: Redirect to checkout with first orderid
$first_orderid = $order_ids[0];
$first_payment = urlencode($payment_methods[0]);
header("Location: checkout.php?orderid=$first_orderid");
exit();
?>
