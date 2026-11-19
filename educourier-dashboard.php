<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$name = isset($_SESSION['name']) ? strtoupper($_SESSION['name']) : 'EDUCOURIER';
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$showWelcome = false;
if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome'] === true) {
  $showWelcome = true;
  unset($_SESSION['show_welcome']); 
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="theme-color" content="#dca1a1" />
  <title>EduCourier Dashboard | Biblio</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
   <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --rose-blush: #dca1a1;
      --soft-white: #fdfdfb;
      --dark-gray: #2d2d2d;
      --hover: #e4bbbb;
      --orange: #fccda4;
      --beige: #C7A97B;
    }

    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: url('nv.png') no-repeat center center fixed;
      background-size: cover;
    
    }

    .container {
      display: flex;
      width: 100%;
    }

    .sidebar {
      width: 240px;
      background-color: transparent;
      backdrop-filter: blur(10px);           
      -webkit-backdrop-filter: blur(10px);  
      border-right: 1px solid rgba(255, 255, 255, 0.2); 
      height: 100vh;
      padding: 60px 20px;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
      border-top-right-radius: 30px;
      border-bottom-right-radius: 30px;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 10;
    }

   .sidebar a {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 25px 0;
      text-decoration: none;
      color: white;
      font-weight: bold;
      font-size: 1.2rem;
      font-family: 'Poppins', sans-serif;
      letter-spacing: 0.5px;
      border-bottom: 2px solid rgba(255, 255, 255, 0.4);
      padding-bottom: 12px;
      transition: all 0.3s ease;
    }


    .sidebar a:hover {
      background-color: rgba(255, 255, 255, 0.15);
      border-radius: 10px;
      padding-left: 10px;
    }

    .navbar {
      height: 80px;
      background-color: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 30px;
      position: sticky;
      top: 0;
      z-index: 5;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .navbar h1 {
      font-size: 2.5rem;
      font-family: 'League Spartan', sans-serif;
      color: var(--dark-gray);
      text-transform: uppercase;
      font-weight: 600;
      margin: 0;
      margin-right: auto;
    }

    .navbar .user {
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: 'Poppins', sans-serif;
      font-weight: 500;
      font-size: 1rem;
    }

    .main-content {
      margin-left: 240px;
      padding: 40px;
      flex: 1;
    }

    .section {
      display: none;
    }

    .section.active {
  display: block;
}

#settings.section.active {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 80px); 
  position: relative;
  padding: 40px 20px;
  box-sizing: border-box;
}
   .settings-wrapper {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  max-width: 360px;
  width: 100%;
  background: rgba(255, 255, 255, 0.92);
  padding: 28px 20px;
  border-radius: 18px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.05);
  backdrop-filter: blur(8px);
  display: flex;
  flex-direction: column;
  align-items: center;
}

    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 95%;
      padding: 8px 12px;
      font-size: 0.9rem;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      margin-bottom: 18px;
      font-family: 'Inter', sans-serif;
      transition: border 0.2s ease;
      box-sizing: border-box;
    }

    input:focus {
      border-color:#fccda4;
      outline: none;
    }

    button.save-btn {
      background-color: #fccda4;
      color: white;
      border: none;
      padding: 12px 22px;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      transition: background 0.3s ease;
      width: 100%;
    }

    button.save-btn:hover {
      background-color: #fccda4;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="sidebar">
      <a href="#" onclick="showSection('assigned')"><i class="fas fa-tasks"></i> Assigned Deliveries</a>
      <a href="#" onclick="showSection('history')"><i class="fas fa-clock"></i> Delivery History</a>
      <a href="#" onclick="showSection('settings')"><i class="fas fa-cog"></i> Settings</a>
      <a href="logout.php">
  <i class="fas fa-sign-out-alt"></i> Logout
</a>
    </div>

    <div class="main-content">
      <div class="navbar">
        <h1>Courier Dashboard</h1>
        <div class="user">
          <i class="fas fa-user-circle" style="font-size: 1.6rem; color: #2d2d2d;"></i>
          <?php echo ucwords($_SESSION['name']); ?>
        </div>
      </div>

<div id="assigned" class="section">
<?php

$courier_id = $_SESSION['id']; 


$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$sql = "
SELECT 
  a.assignment_id,
  o.orderid,
  b.title AS book_title,
  u.name AS buyer_name,
  a.status,
  a.assigned_at
FROM tbl_order_assignments a
JOIN tbl_orders o ON a.order_id = o.orderid
JOIN tbl_books b ON o.bookid = b.bookid
JOIN users u ON o.id = u.id
WHERE a.courier_id = ?
ORDER BY a.assigned_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $courier_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0): ?>
<div style="
  overflow-x:auto;
  background:#f9f6f1;
  border-radius:16px;
  box-shadow:0 6px 16px rgba(0,0,0,0.08);
  padding:24px;
  margin-top:25px;
  border:1px solid #e2d8c9;
">
  <h2 style="font-family:'poppins',sans-serif; font-size:1.9rem; color:#5D4A3E; margin-bottom:18px;">
    Assigned Deliveries
  </h2>

  <div style="width:100%; overflow-x:auto;">
  <table style="
    width:100%;
    min-width: 1100px;
    border-collapse:separate;
    border-spacing:0 8px;
    font-family:'Inter',sans-serif;
    font-size:0.95rem;
    color:#3c3a37;
  ">
    <thead>
      <tr style="background:#d6c4a3; color:#fff;">
        <th style="padding:12px 14px; text-align:left; border-top-left-radius:8px;">Order ID</th>
        <th style="padding:12px 14px; text-align:left;">Book Title</th>
        <th style="padding:12px 14px; text-align:left;">Buyer</th>
        <th style="padding:12px 14px; text-align:left;">Status</th>
        <th style="padding:12px 14px; text-align:left;">Assigned Date</th>
        <th style="padding:12px 14px; text-align:left; border-top-right-radius:8px;">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
      <tr style="
        background:#fffaf3;
        border:1px solid #e6dbc5;
        border-radius:8px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
      " 
      onmouseover="this.style.backgroundColor='#f2e9d9';" 
      onmouseout="this.style.backgroundColor='#fffaf3';">
        <td style="padding:12px 14px;"><?php echo htmlspecialchars($row['orderid']); ?></td>
        <td style="padding:12px 14px;"><?php echo html_entity_decode($row['book_title']); ?></td>
        <td style="padding:12px 14px;"><?php echo htmlspecialchars($row['buyer_name']); ?></td>
        <td style="padding:12px 14px;">
          <?php

switch (strtolower($row['status'])) {
  case 'assigned':
    $statusColor = '#e0d4b7'; 
    $textColor = '#3e3b36';   
    break;
  case 'accepted':
    $statusColor = '#d6e3cf'; 
    $textColor = '#2f4733';   
    break;
  case 'in_transit':
    $statusColor = '#e3d8b9'; 
    $textColor = '#3f382b';
    break;
  case 'delivered':
    $statusColor = '#cfe2cf'; 
    $textColor = '#2f4a2f';
    break;
  default:
    $statusColor = '#e7e0d3';
    $textColor = '#3e3a37';
}
?>
<span style="
  background-color:<?php echo $statusColor; ?>;
  color:<?php echo $textColor; ?>;
  padding:5px 12px;
  border-radius:8px;
  font-size:0.85rem;
  font-weight:600;
  font-family:'Poppins',sans-serif;
  text-transform:capitalize;
  ">
  <?php echo htmlspecialchars($row['status']); ?>
</span>

        </td>
        <td style="padding:12px 14px;">
          <?php 
          echo $row['assigned_at'] 
              ? date('d M Y, h:i A', strtotime($row['assigned_at'])) 
              : '—';
          ?>
        </td>
        <td style="padding:12px 14px;">
          <?php if ($row['status'] === 'pending' || $row['status'] === 'Assigned'): ?>
            <form method="POST" action="update_request_status.php" style="display:inline;">
              <input type="hidden" name="assignment_id" value="<?php echo $row['assignment_id']; ?>">
              <input type="hidden" name="action" value="accept">
              <button type="submit" style="
                padding:6px 12px;
                background:#7f6b52;
                color:#fff;
                border:none;
                border-radius:6px;
                cursor:pointer;
                font-weight:600;">Accept</button>
            </form>

            <form method="POST" action="update_request_status.php" style="display:inline;">
              <input type="hidden" name="assignment_id" value="<?php echo $row['assignment_id']; ?>">
              <input type="hidden" name="action" value="decline">
              <button type="submit" style="
                padding:6px 12px;
                background:#b36b5e;
                color:#fff;
                border:none;
                border-radius:6px;
                cursor:pointer;
                font-weight:600;">Decline</button>
            </form>
          <?php else: ?>
            <em style="color:#7b6f61;">No Action</em>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
          </div>
</div>

<?php else: ?>
  <p style="font-family:'Poppins',sans-serif; color:#555;">No deliveries assigned yet.</p>
<?php endif;
?>


<?php

$sql2 = "
SELECT 
  a.assignment_id,
  o.orderid,
  b.title AS book_title,
  u.name AS buyer_name,
  addr.phone_number AS buyer_phone,
  addr.address_line1 AS buyer_address1,
  addr.address_line2 AS buyer_address2,
  addr.city,
  addr.state,
  addr.pincode,
  a.status,
  a.assigned_at
FROM tbl_order_assignments a
JOIN tbl_orders o ON a.order_id = o.orderid
JOIN tbl_books b ON o.bookid = b.bookid
JOIN users u ON o.id = u.id
LEFT JOIN tbl_user_address addr ON u.id=addr.user_id
WHERE a.courier_id = ?
AND a.status IN ('accepted','picked_up','in_transit')
ORDER BY a.assigned_at DESC
";

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $courier_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

if ($result2->num_rows > 0): ?>
<div style="
  overflow-x:auto;
  background:#f9f6f1;
  border-radius:16px;
  box-shadow:0 6px 16px rgba(0,0,0,0.08);
  padding:24px;
  margin-top:40px;
  border:1px solid #e2d8c9;
">
  <h2 style="font-family:'Poppins',sans-serif; font-size:1.9rem; color:#5D4A3E; margin-bottom:18px;">
    📦 Accepted Deliveries
  </h2>

  <table style="
    width:100%;
    border-collapse:separate;
    border-spacing:0 8px;
    font-family:'Inter',sans-serif;
    font-size:0.95rem;
    color:#3c3a37;
  ">
    <thead>
      <tr style="background:#d6c4a3; color:#fff;">
        <th style="padding:12px 14px; text-align:left; border-top-left-radius:8px;">Order ID</th>
        <th style="padding:12px 14px; text-align:left;">Book Title</th>
        <th style="padding:12px 14px; text-align:left;">Buyer</th>
        <th style="padding:12px 14px; text-align:left;">Buyer Phone</th>
        <th style="padding:12px 14px; text-align:left;">Buyer Address 1</th>
        <th style="padding:12px 14px; text-align:left;">Buyer Address 2</th>
        <th style="padding:12px 14px; text-align:left;">City</th>
        <th style="padding:12px 14px; text-align:left;">State</th>
        <th style="padding:12px 14px; text-align:left;">Pincode</th>
        <th style="padding:12px 14px; text-align:left;">Current Status</th>
        <th style="padding:12px 14px; text-align:left;">Change Status</th>
        <th style="padding:12px 14px; text-align:left; border-top-right-radius:8px;">Action</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($row2 = $result2->fetch_assoc()): ?>
      <tr style="
        background:#fffaf3;
        border:1px solid #e6dbc5;
        border-radius:8px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
      "
      onmouseover="this.style.backgroundColor='#f2e9d9';" 
      onmouseout="this.style.backgroundColor='#fffaf3';">
        <td style="padding:12px 14px;"><?php echo htmlspecialchars($row2['orderid']); ?></td>
        <td style="padding:12px 14px; color:#9F3D3D; font-weight:500;"><?php echo html_entity_decode($row2['book_title']); ?></td>
        <td style="padding:12px 14px;"><?php echo htmlspecialchars($row2['buyer_name']); ?></td>
        <td style="padding:12px 14px;"><?php echo htmlspecialchars($row2['buyer_phone']); ?></td>
        <td style="padding:12px 14px;"><?php echo htmlspecialchars($row2['buyer_address1'] ?? ''); ?></td>
  <td style="padding:12px 14px;"><?php echo htmlspecialchars($row2['buyer_address2'] ?? ''); ?></td>
  <td style="padding:12px 14px;"><?php echo htmlspecialchars($row2['city'] ?? ''); ?></td>
  <td style="padding:12px 14px;"><?php echo htmlspecialchars($row2['state'] ?? ''); ?></td>
  <td style="padding:12px 14px;"><?php echo htmlspecialchars($row2['pincode'] ?? ''); ?></td>
        <td style="padding:12px 14px;">
          <?php
          
          switch (strtolower($row2['status'])) {
            case 'accepted':
              $bg = '#e0d4b7'; $text = '#3e3b36'; break;
            case 'picked_up':
              $bg = '#e3d8b9'; $text = '#3f382b'; break;
            case 'in_transit':
              $bg = '#d6e3cf'; $text = '#2f4733'; break;
            case 'delivered':
              $bg = '#cfe2cf'; $text = '#2f4a2f'; break;
            default:
              $bg = '#e7e0d3'; $text = '#3e3a37';
          }
          ?>
          <span style="
            background-color:<?php echo $bg; ?>;
            color:<?php echo $text; ?>;
            padding:5px 12px;
            border-radius:8px;
            font-size:0.85rem;
            font-weight:600;
            text-transform:capitalize;
            ">
            <?php echo htmlspecialchars($row2['status']); ?>
          </span>
        </td>
        <td style="padding:12px 14px;">
          <form method="POST" action="update_delivery_status.php" style="display:flex; align-items:center; gap:10px;">
            <input type="hidden" name="assignment_id" value="<?php echo $row2['assignment_id']; ?>">
            <select name="new_status" style="
              padding:8px 10px;
              border-radius:8px;
              border:1px solid #D2C7BA;
              background:#FCFBF7;
              font-size:0.9rem;
              color:#2D2D2D;">
              <option value="accepted" <?php if(strtolower($row2['status'])=='accepted') echo 'selected'; ?>>Accepted</option>
              <option value="picked_up" <?php if(strtolower($row2['status'])=='picked_up') echo 'selected'; ?>>Picked Up</option>
              <option value="in_transit" <?php if(strtolower($row2['status'])=='in_transit') echo 'selected'; ?>>In Transit</option>
              <option value="delivered" <?php if(strtolower($row2['status'])=='delivered') echo 'selected'; ?>>Delivered</option>
            </select>
            <button type="submit" style="
              padding:8px 14px;
              background:#7f6b52;
              color:#fff;
              border:none;
              border-radius:8px;
              cursor:pointer;
              font-weight:600;
              transition:all 0.2s ease;">
              Update
            </button>
          </form>
        </td>
        <td style="padding:12px 14px;">
          <?php if ($row2['status'] == 'delivered'): ?>
            <span style="color:green; font-weight:600;">Delivered</span>
          <?php else: ?>
            <em style="color:#7C6A63;">Ongoing</em>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
  <p style="font-family:'Poppins',sans-serif; color:#555;">No accepted deliveries yet.</p>
<?php endif; ?>
  

<?php if ($result2->num_rows > 0): ?>
  <div style="
    overflow-x:auto;
    background:#f9f6f1;
    border-radius:16px;
    box-shadow:0 6px 16px rgba(0,0,0,0.08);
    padding:24px;
    margin-top:40px;
    border:1px solid #e2d8c9;
  ">
    <h2 style="font-family:'Poppins',sans-serif; font-size:1.9rem; color:#5D4A3E; margin-bottom:18px;">
      🧾 Seller Information (for Accepted Deliveries)
    </h2>

    <table style="
      width:100%;
      border-collapse:separate;
      border-spacing:0 8px;
      font-family:'Inter',sans-serif;
      font-size:0.95rem;
      color:#3c3a37;
    ">
      <thead>
        <tr style="background:#d6c4a3; color:#fff;">
          <th style="padding:12px 14px; text-align:left; border-top-left-radius:8px;">Order ID</th>
          <th style="padding:12px 14px; text-align:left;">Book Title</th>
          <th style="padding:12px 14px; text-align:left;">Seller Name</th>
          <th style="padding:12px 14px; text-align:left;">Seller Email</th>
          <th style="padding:12px 14px; text-align:left;">Seller Phone</th>
          <th style="padding:12px 14px; text-align:left;">Seller Address 1</th>
          <th style="padding:12px 14px; text-align:left;">Seller Address 2</th>
          <th style="padding:12px 14px; text-align:left;">City</th>
    <th style="padding:12px 14px; text-align:left;">State</th>
    <th style="padding:12px 14px; text-align:left; border-top-right-radius:8px;">Pincode</th>
        </tr>
      </thead>
      <tbody>
        <?php
        
        $stmt3 = $conn->prepare("
          SELECT 
            o.orderid,
            b.title AS book_title,
            s.name AS seller_name,
            s.email AS seller_email,
            addr.phone_number AS seller_phone,
            addr.address_line1 AS seller_address,
            addr.address_line2 AS seller_address2,
            addr.city,
            addr.state,
            addr.pincode,
            a.status
          FROM tbl_order_assignments a
          JOIN tbl_orders o ON a.order_id = o.orderid
          JOIN tbl_books b ON o.bookid = b.bookid
          JOIN users s ON b.sellerid = s.id
          LEFT JOIN tbl_user_address addr ON s.id = addr.user_id
          WHERE a.courier_id = ?
          AND a.status IN ('accepted','picked_up','in_transit')
          ORDER BY a.assigned_at DESC
        ");
        $stmt3->bind_param("i", $courier_id);
        $stmt3->execute();
        $result3 = $stmt3->get_result();

        while ($row3 = $result3->fetch_assoc()):
        ?>
          <tr style="
            background:#fffaf3;
            border:1px solid #e6dbc5;
            border-radius:8px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
          "
          onmouseover="this.style.backgroundColor='#f2e9d9';" 
          onmouseout="this.style.backgroundColor='#fffaf3';">
            <td style="padding:12px 14px;"><?php echo htmlspecialchars($row3['orderid']); ?></td>
            <td style="padding:12px 14px;"><?php echo html_entity_decode($row3['book_title']); ?></td>
            <td style="padding:12px 14px;"><?php echo htmlspecialchars($row3['seller_name']); ?></td>
            <td style="padding:12px 14px;"><?php echo htmlspecialchars($row3['seller_email']); ?></td>
            <td style="padding:12px 14px;"><?php echo htmlspecialchars($row3['seller_phone']); ?></td>
            <td style="padding:12px 14px;"><?php echo htmlspecialchars($row3['seller_address'] ?? ''); ?></td>
  <td style="padding:12px 14px;"><?php echo htmlspecialchars($row3['seller_address2'] ?? ''); ?></td>
  <td style="padding:12px 14px;"><?php echo htmlspecialchars($row3['city'] ?? ''); ?></td>
  <td style="padding:12px 14px;"><?php echo htmlspecialchars($row3['state'] ?? ''); ?></td>
  <td style="padding:12px 14px;"><?php echo htmlspecialchars($row3['pincode'] ?? ''); ?></td>

          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
        </div>









      <div id="history" class="section">
        
        <?php
$sql3 = "
SELECT 
  a.assignment_id,
  o.orderid,
  b.title AS book_title,
  u.name AS buyer_name,
  a.status,
  a.delivered_at
FROM tbl_order_assignments a
JOIN tbl_orders o ON a.order_id = o.orderid
JOIN tbl_books b ON o.bookid = b.bookid
JOIN users u ON o.id = u.id
WHERE a.courier_id = ?
AND a.status = 'delivered'
ORDER BY a.delivered_at DESC
";

$stmt3 = $conn->prepare($sql3);
$stmt3->bind_param("i", $courier_id);
$stmt3->execute();
$result3 = $stmt3->get_result();
?>



  
  <?php if ($result3->num_rows > 0): ?>
  <div style="
    overflow-x:auto;
    background:#f9f6f1;
    border-radius:16px;
    box-shadow:0 6px 16px rgba(0,0,0,0.08);
    padding:24px;
    margin-top:25px;
    border:1px solid #e2d8c9;
  ">
    <h2 style="
      font-family:'poppins',sans-serif; 
      font-size:1.9rem; 
      color:#5D4A3E; 
      margin-bottom:18px;">
      Delivery History
    </h2>

    <table style="
      width:100%;
      border-collapse:separate;
      border-spacing:0 8px;
      font-family:'Inter',sans-serif;
      font-size:0.95rem;
      color:#3c3a37;
    ">
      <thead>
        <tr style="background:#d6c4a3; color:#fff;">
          <th style="padding:12px 14px; text-align:left; border-top-left-radius:8px;">Order ID</th>
          <th style="padding:12px 14px; text-align:left;">Book Title</th>
          <th style="padding:12px 14px; text-align:left;">Buyer</th>
          <th style="padding:12px 14px; text-align:left;">Delivered On</th>
          <th style="padding:12px 14px; text-align:left; border-top-right-radius:8px;">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row3 = $result3->fetch_assoc()): ?>
        <tr style="
          background:#fffaf3;
          border:1px solid #e6dbc5;
          border-radius:8px;
          transition: all 0.2s ease;
          box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        " 
        onmouseover="this.style.backgroundColor='#f2e9d9';" 
        onmouseout="this.style.backgroundColor='#fffaf3';">
          <td style="padding:12px 14px;"><?php echo htmlspecialchars($row3['orderid']); ?></td>
          <td style="padding:12px 14px;"><?php echo html_entity_decode($row3['book_title']); ?></td>
          <td style="padding:12px 14px;"><?php echo htmlspecialchars($row3['buyer_name']); ?></td>
          <td style="padding:12px 14px;">
            <?php 
            echo $row3['delivered_at'] 
                ? date('d M Y, h:i A', strtotime($row3['delivered_at'])) 
                : '—';
            ?>
          </td>
          <td style="padding:12px 14px;">
            <span style="
              background-color:#cfe2cf; /* same soft mint tone for delivered */
              color:#2f4a2f;
              padding:5px 12px;
              border-radius:8px;
              font-size:0.85rem;
              font-weight:600;
              font-family:'Poppins',sans-serif;
              text-transform:capitalize;">
              Delivered
            </span>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <p style="font-family:'Poppins',sans-serif; color:#555;">No completed deliveries yet.</p>
  <?php endif; 


$stmt2->close();
$conn->close();
?>

      </div>
      <?php if ($showWelcome): ?>
  <div id="welcomeBox" style="
  background-color: #ffffffff;
    color: #2d2d2d;
    padding: 16px 32px;
    border-left: 6px solid #dca1a1;
    border-radius: 10px;
    font-family: 'League Spartan', sans-serif;
    font-size: 18px;
    max-width: 900px;
    margin: 20px auto 0 auto;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    text-align: left;
  ">
    Welcome, <?php echo htmlspecialchars($name); ?>! Hope you're ready to dive in 
  </div>
<?php endif; ?>
     <div id="settings" class="section">
  <div class="settings-wrapper">
    <i class="fas fa-user-circle" style="font-size: 3.2rem; color: #2d2d2d; margin-bottom: 10px;"></i>
    <h2 style="margin: 0 0 28px 0; font-size: 1.3rem; font-family: 'Poppins', sans-serif; font-weight: 600; color: #2d2d2d;">
      <?php echo ucwords($_SESSION['name']); ?>
    </h2>
    


    <form method="POST" action="update-courier.php" style="width: 100%; box-sizing: border-box; padding: 0 15px;">
       <input type="hidden" name="redirect" value="educourier-dashboard.php">

      <div style="margin-bottom: 18px;">
        <label for="name" style="font-weight:600; font-size: 0.95rem;">Full Name</label>
        <input type="text" id="name" name="name" value="<?php echo $_SESSION['name']; ?>" required>
      </div>

      <div style="margin-bottom: 18px;">
        <label for="email" style="font-weight:600; font-size: 0.95rem;">Email</label>
        <input type="email" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" required>
      </div>

      <div style="margin-bottom: 25px;">
        <label for="password" style="font-weight:600; font-size: 0.95rem;">New Password <span style="font-weight: 400; font-size: 0.9rem; color: #888;">(leave blank to keep current)</span></label>
        <input type="password" id="password" name="password">
      </div>

      <button type="submit" class="save-btn">Save Changes</button>
      <?php if (isset($_GET['status']) && $_GET['status'] === 'saved'): ?>
        <p style="margin-top: 15px; color: green; font-weight: 500; text-align:center;">Changes saved!</p>
      <?php endif; ?>
    </form>
  </div>
</div>
<div id="toast" style="
  visibility: hidden;
  min-width: 260px;
  background-color: #ffffffff; /* sage green */
  color: #2d2d2d;
  text-align: center;
  border-radius: 12px;
  padding: 12px 16px;
  position: fixed;
  z-index: 9999;
  left: 50%;
  top: 30px;
  transform: translateX(-50%);
  font-family: 'League Spartan', sans-serif;
  font-weight: 500;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  transition: all 0.3s ease;
">
  ✅ Settings saved successfully!
</div>
<script>
  
  window.showSection = function(id) {
    const sections = document.querySelectorAll('.section');
    sections.forEach(sec => sec.classList.remove('active'));

    const target = document.getElementById(id);
    if (target) target.classList.add('active');
    else console.warn('No section with id', id);
  };

  window.showToast = function(message) {
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.style.visibility = "visible";
    toast.style.opacity = "1";

    setTimeout(() => {
      toast.style.opacity = "0";
      setTimeout(() => {
        toast.style.visibility = "hidden";
      }, 500);
    }, 3000);
  };

   const params = new URLSearchParams(window.location.search);
  if (params.has('status')) {
      const status = params.get('status');
      if (status === 'accepted') showToast("✅ Delivery accepted!");
      else if (status === 'declined') showToast("⚠ Delivery declined!");
      else if (status === 'saved') showToast("✅ Settings saved successfully!");
      
  }



  if (params.has('status') || params.has('msg')) {
      window.history.replaceState({}, document.title, window.location.pathname);
  }

 const welcomeBox = document.getElementById("welcomeBox");
  if (welcomeBox) {
    document.addEventListener("click", () => {
      welcomeBox.style.opacity = "0";
      setTimeout(() => welcomeBox.remove(), 500);
    });
  }
</script>

</body>
</html>