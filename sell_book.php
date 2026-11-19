<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();


if (!isset($_SESSION['id'])) {
    // User not logged in, redirect to login page
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper function to sanitize input
function cleanInput($data) {
    return htmlspecialchars(trim($data));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get seller id from session
    $sellerid = $_SESSION['id'];

    // Sanitize form inputs
    $title = cleanInput($_POST['title'] ?? '');
    $edition=cleanInput($_POST['edition']??'');
    $author = cleanInput($_POST['author'] ?? '');
    $category = cleanInput($_POST['category_id'] ?? '');
    $book_condition = cleanInput($_POST['book_condition'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $description = cleanInput($_POST['description'] ?? '');
    $preferred_payment = cleanInput($_POST['preferred_payment'] ?? 'UPI');
    if (!in_array($preferred_payment, ['UPI', 'Card'])) {
        die("Invalid preferred payment method.");
    }

    // Validate required fields
    if (!$title || !$author || !$category || !$book_condition || $price<=0) {
        die("Please fill all required fields with valid values.");
    }
    if (!$preferred_payment) {
    die("Please select a preferred payment method.");
}

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';

        // Check if upload directory exists
        if (!is_dir($uploadDir)) {
            die("Uploads directory does not exist: $uploadDir");
        }

        // Check if upload directory is writable
        if (!is_writable($uploadDir)) {
            die("Uploads directory is not writable: $uploadDir");
        }

        $tmpName = $_FILES['image']['tmp_name'];
        $originalName = basename($_FILES['image']['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowedExt)) {
            die("Invalid image format. Allowed: jpg, jpeg, png, gif.");
        }

        // Create unique file name to avoid conflicts
        $newFileName = uniqid('book_', true) . '.' . $ext;
        $targetFilePath = $uploadDir . $newFileName;

        if (!move_uploaded_file($tmpName, $targetFilePath)) {
            die("Failed to upload image to destination: $targetFilePath");
        }
    } else {
        die("Please upload an image.");
    }

    // Prepare and bind SQL insert statement
    $stmt = $conn->prepare("INSERT INTO tbl_books (sellerid, title, edition, author, price, category_id,book_condition, image_url, description, preferred_payment_method, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,?, 1)");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("isssdsssss",$sellerid, $title, $edition, $author, $price, $category, $book_condition, $targetFilePath, $description,$preferred_payment);

    if ($stmt->execute()) {
        // Redirect back with success message
        header("Location: user-dashboard.php?status=book_added");
        exit;
    } else {
        die("Error inserting book: " . $stmt->error);
    }
} else {
    // If accessed directly, redirect
    header("Location: user-dashboard.php");
    exit;
}
?>
