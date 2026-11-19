<?php
include 'db.php'; // your database connection file

if(isset($_GET['id'])) {
    $courier_id = intval($_GET['id']);

    // Delete the courier
    $conn->query("DELETE FROM users WHERE id = $courier_id AND role='courier'");

    // Optionally redirect back to admin dashboard
    header("Location: admin_dashboard.php#couriers");
    exit;
}
?>
