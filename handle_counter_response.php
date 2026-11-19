<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'BIBLIO');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Make sure request is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['negotiation_id'])) {
    $action = $_POST['action'];
    $negotiation_id = intval($_POST['negotiation_id']);
    $user_id = $_SESSION['id'];  // current logged-in user

    // Only accept/decline if user is the buyer who owns this negotiation
    $stmt = $conn->prepare("SELECT sellerid FROM tbl_negotiation WHERE negotiation_id = ?");
$stmt->bind_param("i", $negotiation_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row || $row['sellerid'] != $user_id) {
    $_SESSION['negotiation_error'] = "Unauthorized action.";
    header("Location: {$_SERVER['HTTP_REFERER']}");
    exit();
}


    // Decide new status
    if ($action === 'accept') {
        $new_status = 'accepted';
    } elseif ($action === 'reject') {
        $new_status = 'rejected';
    } else {
        $_SESSION['negotiation_error'] = "Invalid action.";
        header("Location: {$_SERVER['HTTP_REFERER']}");
        exit();
    }

    // Update negotiation status
    if ($new_status === 'accepted') {
    // Also set final_price = offer_price when accepted
    $stmt = $conn->prepare("
        UPDATE tbl_negotiation 
        SET status=?, final_price=offer_price, updated_at=NOW() 
        WHERE negotiation_id=?
    ");
     $stmt->bind_param("si", $new_status, $negotiation_id);
} else {
    // For rejected, just update the status
    $stmt = $conn->prepare("
        UPDATE tbl_negotiation 
        SET status=?, updated_at=NOW() 
        WHERE negotiation_id=?
    ");
}
$stmt->bind_param("si", $new_status, $negotiation_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['negotiation_success'] = "offer $new_status successfully.";
    } else {
        $_SESSION['negotiation_error'] = "Failed to update negotiation.";
    }
    $stmt->close();

    header("Location: {$_SERVER['HTTP_REFERER']}");
    exit();
}

// fallback if direct access
header("Location: user-dashboard.php");
exit();
?>
