<?php
session_start();
if (!isset($_SESSION['id'])) exit("Session expired");

$user_id = $_SESSION['id'];
$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$bookid = intval($_POST['bookid'] ?? 0);
$title = trim($_POST['title'] ?? '');
$author = trim($_POST['author'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$book_condition = trim($_POST['book_condition'] ?? '');
$edition = trim($_POST['edition'] ?? '');
$image_url = "";

if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = "uploads/";
    $filename = basename($_FILES['book_image']['name']);
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['book_image']['tmp_name'], $targetPath)) {
        $image_url = $filename; // only store filename in DB
    } else {
        exit("Image upload failed.");
    }
} else {
    // if no new file, keep existing image
    $stmt = $conn->prepare("SELECT image_url FROM tbl_books WHERE bookid=? AND sellerid=?");
    $stmt->bind_param("ii", $bookid, $user_id);
    $stmt->execute();
    $stmt->bind_result($existingImage);
    $stmt->fetch();
    $image_url = $existingImage;
    $stmt->close();
}

if ($bookid && $title && $author) {
    $stmt = $conn->prepare("UPDATE tbl_books SET title=?, author=?, price=?, book_condition=?, edition=?, image_url=? WHERE bookid=? AND sellerid=?");
    $stmt->bind_param("ssdsssii", $title, $author, $price, $book_condition, $edition, $image_url, $bookid, $user_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error updating record: " . $conn->error;
    }
} else {
    echo "Invalid request.";
}
?>
