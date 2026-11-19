<?php
session_start();
header('Content-Type: application/json');

// Check user login
if(!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to submit an offer.']);
    exit;
}

$userId = $_SESSION['id'];
$bookId = isset($_POST['bookid']) ? intval($_POST['bookid']) : 0;
$offerPrice = isset($_POST['offer_price']) ? floatval($_POST['offer_price']) : 0;

if($bookId <= 0 || $offerPrice <= 0){
    echo json_encode(['success' => false, 'message' => 'Invalid book or price.']);
    exit;
}

// Connect to DB
$conn = new mysqli('localhost', 'root', '', 'BIBLIO');
if($conn->connect_error){
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Fetch the book’s current price and seller id
$stmtBook = $conn->prepare("SELECT price,sellerid FROM tbl_books WHERE bookid = ?");
$stmtBook->bind_param("i", $bookId);
$stmtBook->execute();
$result = $stmtBook->get_result();
$book = $result->fetch_assoc();
$stmtBook->close();

$initialPrice = $book ? floatval($book['price']) : 0;
$sellerId = $book ? intval($book['sellerid']) : 0;

if($initialPrice <= 0 || $sellerId <= 0){
    echo json_encode(['success' => false, 'message' => 'Book not found or invalid seller.']);
    exit;
}

// Insert negotiation (make sure column list matches table structure)
$stmt = $conn->prepare("
    INSERT INTO tbl_negotiation 
    (bookid, buyerid,sellerid,initial_price, offer_price, status, updated_at) 
    VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
");
$stmt->bind_param("iiidd", $bookId, $userId,$sellerId, $initialPrice, $offerPrice);

if($stmt->execute()){
    echo json_encode(['success' => true, 'message' => 'Offer submitted successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit offer.']);
}

$stmt->close();
$conn->close();
?>


