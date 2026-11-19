<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['id'])) {
    die("You must be logged in to add to cart.");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['bookid'])) {
    $user_id = $_SESSION['id'];
    $bookid = intval($_GET['bookid']);

    // ✅ Step 1: Check if the user already has an active cart
    $stmt = $conn->prepare("SELECT cart_id FROM tbl_carts WHERE user_id=? AND is_active=1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $cart_id = $row['cart_id'];
    } else {
        // Create new cart for the user
        $stmt2 = $conn->prepare("INSERT INTO tbl_carts (user_id) VALUES (?)");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $cart_id = $stmt2->insert_id;
        $stmt2->close();
    }
    $stmt->close();

    // ✅ Step 2: Check if the book already exists in this cart
    $stmt3 = $conn->prepare("SELECT quantity FROM tbl_cart_item WHERE cart_id=? AND book_id=?");
    $stmt3->bind_param("ii", $cart_id, $bookid);
    $stmt3->execute();
    $result2 = $stmt3->get_result();

    if ($row2 = $result2->fetch_assoc()) {
        // If book already in cart, increase quantity
        $stmt4 = $conn->prepare("UPDATE tbl_cart_item SET quantity = quantity + 1 WHERE cart_id=? AND book_id=?");
        $stmt4->bind_param("ii", $cart_id, $bookid);
        $stmt4->execute();
        $stmt4->close();
    } else {
        // Otherwise, insert new row
        $stmt4 = $conn->prepare("INSERT INTO tbl_cart_item (cart_id, book_id, quantity) VALUES (?, ?, 1)");
        $stmt4->bind_param("ii", $cart_id, $bookid);
        $stmt4->execute();
        $stmt4->close();
    }
    $stmt3->close();

    // ✅ Redirect back to cart
    header("Location: cart.php?msg=added");
    exit();
}
?>

