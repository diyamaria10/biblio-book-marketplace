<?php
session_start();
$conn = new mysqli("localhost", "root", "", "BIBLIO");

if ($conn->connect_error) {
    http_response_code(500);
    echo "Database connection failed";
    exit;
}

if (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];

    $stmt = $conn->prepare("UPDATE tbl_notification SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        http_response_code(500);
        echo "error";
    }

    $stmt->close();
} else {
    http_response_code(401); // not logged in
    echo "unauthorized";
}
?>
