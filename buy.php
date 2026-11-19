<?php
session_start();
if (!isset($_SESSION['id'])) {
    die("User not logged in.");
}

if (!isset($_GET['bookid'])) {
    die("Book ID missing.");
}

$bookid = intval($_GET['bookid']);

// Redirect to checkout page
header("Location: checkout.php?bookid=" . $bookid);
exit();
?>
