<?php
session_start();
if (!isset($_SESSION['id'])) {
    die("You must be logged in to modify your cart.");
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_item_id'])) {
    $cart_item_id = intval($_POST['cart_item_id']);

    $conn = new mysqli("localhost", "root", "", "BIBLIO");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    // First, verify that the cart item belongs to this user
    $stmt = $conn->prepare("
        SELECT ci.item_id 
        FROM tbl_cart_item ci
        INNER JOIN tbl_carts c ON ci.cart_id = c.cart_id
        WHERE ci.item_id = ? AND c.user_id = ? AND c.is_active = 1
    ");
    $stmt->bind_param("ii", $cart_item_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        die("Cart item not found or does not belong to you.");
    }
    $stmt->close();

    // Delete the cart item
    $stmt_del = $conn->prepare("DELETE FROM tbl_cart_item WHERE item_id = ?");
    $stmt_del->bind_param("i", $cart_item_id);
    $stmt_del->execute();

    $stmt_del->close();
    $conn->close();

    // Redirect back to cart page
    header("Location: cart.php");
    exit();
} else {
    die("Invalid request.");
}
