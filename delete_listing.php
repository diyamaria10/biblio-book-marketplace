<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_POST['bookid'])) {
    echo json_encode(['success' => false, 'message' => 'No book ID provided']);
    exit();
}

$user_id = $_SESSION['id'];
$bookid = intval($_POST['bookid']);

$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM tbl_books WHERE bookid=? AND sellerid=?");
$stmt->bind_param("ii", $bookid, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
$stmt->close();
$conn->close();

