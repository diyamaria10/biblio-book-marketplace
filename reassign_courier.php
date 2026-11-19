<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
$conn = new mysqli("localhost","root","","BIBLIO");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $assignment_id = intval($_POST['assignment_id']);
  $new_courier_id = intval($_POST['new_courier_id']);
  $admin_id = $_SESSION['admin_id'] ?? 1; // or however you store admin ID

  // Update assignment table
  $stmt = $conn->prepare("UPDATE tbl_order_assignments 
    SET courier_id=?, status='pending', assigned_at=NOW(), assigned_by=? 
    WHERE assignment_id=?");

  $stmt->bind_param("iii",$new_courier_id,$admin_id,$assignment_id);
  $stmt->execute();

  // Insert new delivery status row
  $status='Pending';
  $notes='Courier reassigned by admin(awaiting acceptance)';
  $stmt2 = $conn->prepare("INSERT INTO tbl_delivery_status 
    (assignment_id,status,updated_at,updated_by,notes)
    VALUES (?,?,NOW(),?,?)");
  $stmt2->bind_param("isis",$assignment_id,$status,$admin_id,$notes);
  $stmt2->execute();

  header("Location: admin-dashboard.php?msg=reassigned");
  exit();
}
?>
