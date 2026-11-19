<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
$conn = new mysqli("localhost","root","","BIBLIO");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['assignment_id'],$_POST['action'])) {
  $assignment_id = intval($_POST['assignment_id']);
  $action = $_POST['action'];
  $courier_id = $_SESSION['id'];

    // decide status
if ($action === 'accept') {
    $status_assignment = 'accepted'; // for tbl_order_assignments
    $status_delivery   = 'accepted'; // must exist in ENUM of tbl_delivery_status
    $notes             = 'Courier accepted assignment';
    $redirect_status   = 'accepted';

} elseif ($action === 'decline') {
    $status_assignment = 'declined';
    $status_delivery   = 'failed'; // use 'declined' instead of 'failed'
    $notes             = 'Courier declined assignment';
    $redirect_status   = 'declined';

} else { // default pending
    $status_assignment = 'pending';
    $status_delivery   = 'pending'; // must exist in ENUM
    $notes             = 'Status pending';
    $redirect_status   = '';
}



   // ✅ update assignment table
  $stmt = $conn->prepare("UPDATE tbl_order_assignments SET status=?, assigned_at=NOW() WHERE assignment_id=?");
  $stmt->bind_param("si",$status_assignment,$assignment_id);
  $stmt->execute();
  $stmt->close();


  // ✅ insert new row into tbl_delivery_status
  $stmt2 = $conn->prepare("
    INSERT INTO tbl_delivery_status (assignment_id, status, updated_at, updated_by, notes)
    VALUES ( ?, ?, NOW(), ?, ? )
  ");
  $stmt2->bind_param("isis",$assignment_id,$status_delivery,$courier_id,$notes);
  $stmt2->execute();
  $stmt2->close();

   


  // Redirect back with success message
    header("Location: educourier-dashboard.php?msg=reassigned");
    exit();
} else {
    echo "Invalid request";
}
?>

