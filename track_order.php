<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// --- Database connection ---
$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Get order_id from URL ---
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    die("Invalid Order ID");
}

// --- Get order details ---
$sql_order = "SELECT o.orderid, o.bookid, p.payment_method, p.payment_status, o.total_price, o.status,
                     b.title
              FROM tbl_orders o
              JOIN tbl_books b ON o.bookid = b.bookid
              LEFT JOIN tbl_payment_details p ON o.orderid = p.order_id
              WHERE o.orderid = ?";
$stmt = $conn->prepare($sql_order);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    die("Order not found");
}

// --- Get assignment_id for this order ---
$stmt_assign = $conn->prepare("SELECT assignment_id FROM tbl_order_assignments WHERE order_id = ?");
$stmt_assign->bind_param("i", $order_id);
$stmt_assign->execute();
$result_assign = $stmt_assign->get_result();

$statuses = [];
if ($result_assign->num_rows > 0) {
    $assignment = $result_assign->fetch_assoc();
    $assignment_id = $assignment['assignment_id'];

    // --- Get delivery status timeline ---
    $stmt_status = $conn->prepare("SELECT status, notes, updated_at
                                   FROM tbl_delivery_status
                                   WHERE assignment_id = ?
                                   ORDER BY updated_at ASC");
    $stmt_status->bind_param("i", $assignment_id);
    $stmt_status->execute();
    $status_result = $stmt_status->get_result();
    $statuses = $status_result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Order #<?php echo $order['orderid']; ?></title>
<style>
body { font-family: 'Poppins', sans-serif; background-color: #F8F6F4; margin:0; padding:0; color:#2D2D2D; }
.container { max-width: 700px; margin: 40px auto; background: #fff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
h2 { color: #5D4A3E; margin-bottom: 10px; }
h3 { color: #9F3D3D; margin-bottom: 20px; }
.timeline { border-left: 3px solid #B69E59; margin: 20px 0; padding-left: 20px; }
.event { margin-bottom: 20px; position: relative; }
.event::before { content: ''; position: absolute; left: -10px; top: 5px; width: 16px; height: 16px; background-color: #B69E59; border-radius: 50%; }
.status { font-weight: bold; color: #5D4A3E; }
.time { font-size: 12px; color: #888; }
.remarks { font-size: 12px; color: #9F3D3D; margin-top: 3px; }
.back-btn { display: inline-block; padding: 10px 20px; background: #B69E59; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
.back-btn:hover { background: #9F3D3D; }
</style>
</head>
<body>
<div class="container">
  <h2>Tracking Order #<?php echo $order['orderid']; ?></h2>
  <h3><?php echo html_entity_decode($order['title']); ?></h3>
  <p><strong>Total Price:</strong> ₹<?php echo number_format($order['total_price'], 2); ?></p>
  <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
  <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
  <p><strong>Current Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>

  <h3>Delivery Timeline</h3>
  <div class="timeline">
    <?php if (!empty($statuses)): ?>
      <?php foreach($statuses as $row): ?>
        <div class="event">
          <div class="status"><?php echo htmlspecialchars($row['status']); ?></div>
          <div class="time"><?php echo htmlspecialchars($row['updated_at']); ?></div>
          <?php if(!empty($row['notes'])): ?>
            <div class="remarks"><?php echo htmlspecialchars($row['notes']); ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No tracking updates yet.</p>
    <?php endif; ?>
  </div>

  <a href="user-dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
</div>
</body>
</html>
