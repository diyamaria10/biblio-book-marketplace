<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
$categories=[];
$result = $conn->query("SELECT category_id,name FROM tbl_categories ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
  $categories[]=$row;
}
$message = "";
$errors = [];
$user_id = $_SESSION['id'] ?? 0;
$address = [
    'full_name' => $_SESSION['name'] ?? '',
    'phone_number' => '',
    'address_line1' => '',
    'address_line2' => '',
    'city' => '',
    'state' => '',
    'pincode' => ''
];

$notif_stmt = $conn->prepare("SELECT * FROM tbl_notification WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
$notifications = $notif_result->fetch_all(MYSQLI_ASSOC);
$notif_stmt->close();
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_notification WHERE user_id = ? AND is_read = 0");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_stmt->bind_result($notif_count);
$count_stmt->fetch();
$count_stmt->close();


$stmt = $conn->prepare("SELECT full_name, phone_number, address_line1, address_line2, city, state, pincode 
                        FROM tbl_user_address 
                        WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($db_full_name, $db_phone, $db_line1, $db_line2, $db_city, $db_state, $db_pincode);
if ($stmt->fetch()) {
   $address['full_name']    = $db_full_name;
    $address['phone_number'] = $db_phone;
    $address['address_line1']= $db_line1;
    $address['address_line2']= $db_line2;
    $address['city']         = $db_city;
    $address['state']        = $db_state;
    $address['pincode']      = $db_pincode;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_address'])) {
  

    $user_id = $_SESSION['id']; 
    $full_name = $_POST['full_name'];
    $phone_number = $_POST['phone_number'];
    $address_line1 = $_POST['address_line1'];
    $address_line2 = $_POST['address_line2'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    
    if (strlen($full_name) < 3 || strlen($full_name) > 50) {
        $errors[] = "Full name must be between 3 and 50 characters.";
    }

    if (!preg_match('/^[0-9]{10}$/', $phone_number)) {
        $errors[] = "Phone must be a 10-digit number.";
    }

    if (empty($address_line1)) {
        $errors[] = "Address Line 1 is required.";
    }

    if (empty($city)) {
        $errors[] = "City is required.";
    }

    if (empty($state)) {
        $errors[] = "State is required.";
    }

    if (!preg_match('/^\d{6}$/', $pincode)) {
        $errors[] = "Pincode must be 6 digits.";
    }

    if (empty($errors)) {
        
        $stmt = $conn->prepare("SELECT user_id FROM tbl_user_address WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            
            $stmt->close();
            $stmt = $conn->prepare("UPDATE tbl_user_address 
                                    SET full_name = ?, phone_number = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, pincode = ?, updated_at = NOW() 
                                    WHERE user_id = ?");
            $stmt->bind_param("sssssssi", $full_name, $phone_number, $address_line1, $address_line2, $city, $state, $pincode, $user_id);
            $action = "updated";
        } else {
            
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO tbl_user_address 
                (user_id, full_name, phone_number, address_line1, address_line2, city, state, pincode, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("isssssss", $user_id, $full_name, $phone_number, $address_line1, $address_line2, $city, $state, $pincode);
            $action = "saved";
        }

        if ($stmt->execute()) {
            $message = "✅ Address $action successfully!";
        } else {
            $message = "❌ Error saving address: " . $conn->error;
        }
        $stmt->close();
    }
}

$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$showWelcome = false;
if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome'] === true) {
  $showWelcome = true;
  unset($_SESSION['show_welcome']); 
}
if (isset($_GET['status']) && $_GET['status'] === 'book_added') {
  echo "
    <div id='successMessage' 
         style='
           background-color:#dfe8dc; 
           color:#2e4e34; 
           font-weight:bold; 
           font-size:18px; 
           font-family: \"Poppins\", sans-serif; 
           padding:15px; 
           max-width:600px; 
           margin:20px auto; 
           text-align:center; 
           border-radius:8px;
           box-shadow:0px 2px 6px rgba(0,0,0,0.1);
           transition:opacity 0.5s ease;'>
         ✅ Book successfully added!
    </div>
    <script>
      setTimeout(function(){
        const msg = document.getElementById('successMessage');
        if (msg) {
          msg.style.opacity = '0';
          setTimeout(() => msg.remove(), 500); // Wait for fade before removing
        }
      }, 3000); // 3 seconds
    </script>
  ";
}


$orders_result = $conn->query("
            SELECT o.id AS order_id, b.title AS book_title, s.name AS seller_name, o.status, o.order_date
            FROM tbl_orders o
            JOIN tbl_books b ON b.bookid = o.bookid
            JOIN users s ON s.id = b.sellerid
            WHERE o.id = '$user_id'
            ORDER BY o.order_date DESC
        ");









?>

<?php if (isset($_SESSION['negotiation_success'])): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      alert("<?php echo addslashes($_SESSION['negotiation_success']); ?>");
    });
  </script>
  <?php unset($_SESSION['negotiation_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['negotiation_error'])): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      alert("<?php echo addslashes($_SESSION['negotiation_error']); ?>");
    });
  </script>
  <?php unset($_SESSION['negotiation_error']); ?>
<?php endif; ?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Dashboard | Biblio</title>

  
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Roboto&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;600&display=swap" rel="stylesheet">

  
  <style>
    :root {
      --sage-green: #b7c9a8;
      --soft-white: #fdfdfb;
      --dark-gray: #2d2d2d;
    }


body {
  background-color: #fdfdfb;
  background-image: url('bb.png'); 
  background-repeat: no-repeat;
  background-position: right bottom;
  background-size: 1200px;
  background-attachment: fixed;
}


    .container {
      display: flex;
    }

    .sidebar {
      width: 240px;
      background-color: var(--sage-green);
      height: 100vh;
      padding: 100px 20px 20px;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
      position: fixed;
      top: 0;
      left: 0;
      z-index: 2;
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

    .sidebar a i {
      width: 22px;
      text-align: center;
    }

    .sidebar a:hover {
      background-color: rgba(255, 255, 255, 0.15);
      border-radius: 10px;
      padding-left: 10px;
    }

    .main-content {
      margin-left: 240px;
      padding: 20px 20px 20px 40px;
      flex: 1;
      max-width: 1000px;    
  margin-right: auto;   
  margin-left: 240px;   
    }

    .navbar {
      height: 80px;
      background-color: var(--soft-white);
      border-bottom: 2px solid #ccc;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 30px;
      position: sticky;
      top: 0;
      z-index: 1;
    }

    .navbar h1 {
      font-size: 2.5rem;
      font-family: 'League Spartan', sans-serif;
      color: var(--dark-gray);
      text-transform: uppercase;
      font-weight: 600;
      margin: 0;
    }

    .navbar .user {
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: 'Poppins', sans-serif;
      font-weight: 500;
      font-size: 1rem;
    }

    .navbar .user i {
      font-size: 1.8rem;
    }


    .section {
      display: flex;
  justify-content: center;   
  align-items: flex-start;   
  width: 100%;
    }
    .section > * {
  max-width: 1000px;         
  width: 100%;               
}

    .section.active {
      display: block;
    }
    .dashboard-wrapper {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 40px;
    width: 100%;
}
.dashboard title{
  margin-bottom:30px;

}
.sell-book-date_form{
max-width: 500px;
width:100%;
margin: 0 auto;
padding: 20px;
box-sizing: border-box;
}

.sell-book-container {
    font-family: 'Poppins', sans-serif;
    background: white;
    padding: 25px 30px;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    max-width: 600px;
    margin: 0 auto;
}

.sell-book-container h2 {
    margin-bottom: 15px;
    color: #2f4f2f;
    font-size: 1.4rem;
    text-align: center;
}

.sell-book-container input,
.sell-book-container textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 12px;
    border: 1px solid #d9d9d9;
    border-radius: 8px;
    font-size: 14px;
    box-sizing: border-box;
}

.sell-book-container textarea {
    resize: none;
}
.form-section-title{
  font-family: 'Poppins', sans-serif;
  font-size: 0.95rem;
  font-weight: 500;
  margin: 15px 0 8px;
  color: #4d4d4d;
  text-align:left;
  display: block;
  width:100%;
  padding-left: 4px;
}
.form-section {
  margin-top: 40px;
}

.file-label {
    display: block;
    font-size: 14px;
    margin-bottom: 12px;
    cursor: pointer;
    color: #2f4f2f;
}

.file-label input {
    margin-top: 5px;
}

.btn-submit {
    background-color: #8aa382;
    color: white;
    border: none;
    padding: 10px;
    font-size: 15px;
    border-radius: 8px;
    cursor: pointer;
    width: 100%;
    transition: background 0.3s ease;
}

.btn-submit:hover {
    background-color: #6f896d;
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
      border-color: #dca1a1;
      outline: none;
    }

    button.save-btn {
      background-color: #dca1a1;
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
      background-color: #e6b7b7;
    }

    .section h2 {
      font-family: 'Poppins', sans-serif;
      font-size: 1.6rem;
      margin-bottom: 10px;
    }

    .card {
      background-color: #ffffff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
      margin-top: 15px;
    }

    .card p {
      margin: 0;
      font-size: 1rem;
    }

    .listings-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Poppins', sans-serif;
    border: 1px solid #cdd8cf;
}
.listings-table th {
    background-color: #cfe3d3;
    color: #2e4d36;
    padding: 10px;
    text-align: left;
    font-size: 15px;
}
.listings-table td {
    padding: 10px;
    border-bottom: 1px solid #d8e6dc;
    vertical-align: middle;
    font-size: 14px;
    color: #333;
}
.listings-table tr:nth-child(even) {
    background-color: #f4f8f5;
}
.listings-table tr:hover {
    background-color: #e7f1eb;
}
.listings-table img {
    width: 80px;
    height: auto;
    border-radius: 6px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

 .success-modal {
  display: none; 
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.4);
  justify-content: center;
  align-items: center;
  font-family: 'Poppins', sans-serif;
}

.success-modal-content {
  background: #d8e3d5; 
  padding: 25px 30px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  text-align: center;
  width: 320px;
  animation: fadeInScale 0.3s ease-out;
}

.success-modal-content h2 {
  margin-top: 0;
  color: #3b5d3b;
  font-weight: 600;
}

.success-modal-content p {
  margin: 10px 0 0;
  color: #2f4f2f;
}

.success-modal .close {
  position: absolute;
  top: 15px;
  right: 20px;
  color: #3b5d3b;
  font-size: 24px;
  font-weight: bold;
  cursor: pointer;
}
.table-action {
    padding: 4px 8px;
    border: 1px solid transparent;
    border-radius: 12px;
    font-size: 0.8rem;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    background: none;
    transition: all 0.2s ease;
}

.table-action.accept {
    color: #3b5d3b; 
    border-color: #8aa382;
}
.table-action.accept:hover {
    background-color: #8aa382;
    color: white;
}

.table-action.reject {
    color: #8a2f2f; 
    border-color: #dca1a1;
}
.table-action.reject:hover {
    background-color: #dca1a1;
    color: white;
}

.table-action.counter {
    color: #8a6f2f; 
    border-color: #f0c877;
}
.table-action.counter:hover {
    background-color: #f0c877;
    color: white;
}
.status-msg.accepted {
    color: green;
    font-weight: bold;
    margin-bottom: 10px;
}
.status-msg.rejected {
    color: red;
    font-weight: bold;
    margin-bottom: 10px;
}
.listings-table td {
    vertical-align: middle;
    text-align: center;
}

.table-action {
    padding: 6px 12px;
    font-size: 0.9rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}


.table-action.pay {
    background: linear-gradient(135deg, #9fb993, #7d9b6d);
    color: #fff;
    font-weight: 600;
    padding: 10px 18px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-size: 0.95rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.12);
    transition: all 0.3s ease;
}

.table-action.pay:hover {
    background: linear-gradient(135deg, #7d9b6d, #9fb993);
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0,0,0,0.18);
}
.section {
  display: none;
}
.section.active {
  display: block;
}

@keyframes fadeInScale {
  0% { opacity: 0; transform: scale(0.9); }
  100% { opacity: 1; transform: scale(1); }
}

  

  </style>
</head>
<body>

  <div class="sidebar">
    <a href="#" onclick="showSection('my-listings')"><i class="fas fa-layer-group"></i> My Listings</a>
    <a href="#" onclick="showSection('my-orders')"><i class="fas fa-box"></i> My Orders</a>
    <a href="#" onclick="showSection('my-negotiations')"><i class="fas fa-handshake"></i>  My Negotiations</a>
    <a href="#" onclick="showSection('browse-books')"><i class="fas fa-book-open"></i> Browse Books</a>
    <a href="#" onclick="showSection('sell-book')"><i class="fas fa-upload"></i> Sell a Book</a>
    <a href="#" onclick="showSection('track-orders')"><i class="fas fa-shipping-fast"></i> Track My Orders</a>
    <a href="#" onclick="showSection('order-history')"><i class="fas fa-history"></i> Order History</a>

    <a href="#" onclick="showSection('delivery-address')"><i class="fas fa-map-marker-alt"></i> Delivery Address</a>



    <a href="#" onclick="showSection('account-settings')"><i class="fas fa-user-cog"></i> Account Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="container">
    <div class="main-content">
      <div class="navbar">
        <h1 class="dashboard-title">Dashboard</h1>
        <div class="user">
           <span class="user-info">
    👤 <?php echo htmlspecialchars($_SESSION['name']); ?>
  </span>
<div style="position: relative; display: inline-block; margin-left:20px;">
  <button id="notifBell" style="background:none; border:none; cursor:pointer; font-size:20px; position:relative;">
    🔔
    <?php if ($notif_count > 0): ?>
      <span id="notifCount" style="
        position:absolute; top:-8px; right:-8px;
        background:red; color:white; font-size:12px; font-weight:bold;
        border-radius:50%; padding:2px 6px;">
        <?php echo $notif_count; ?>
      </span>
    <?php endif; ?>
  </button>

  
  <div id="notifDropdown" style="
        display:none; position:absolute; top:35px; right:0;
        background:#fff; border:1px solid #ccc; border-radius:8px;
        width:280px; box-shadow:0 2px 8px rgba(0,0,0,0.15);
        z-index:1000; max-height:300px; overflow-y:auto;">
    <?php if (empty($notifications)): ?>
      <p style="padding:12px; font-size:14px; margin:0; color:#555;">No new notifications</p>
    <?php else: ?>
      <?php foreach ($notifications as $notif): ?>
  <?php
    
    $icon = "🔔"; 
    if ($notif['type'] === 'order') $icon = "📦";
    if ($notif['type'] === 'system') $icon = "⚙️";
    if ($notif['type'] === 'message') $icon = "💬";
  ?>
  <div style="
      display:flex; 
      align-items:flex-start; 
      gap:10px; 
      padding:12px; 
      border-bottom:1px solid #eee; 
      font-family:'poppins', sans-serif;
      cursor:pointer;
      transition: background 0.2s ease;
  " 
  onmouseover="this.style.background='#f9f9f9';" 
  onmouseout="this.style.background='transparent';">
    
    
    <div style="font-size:18px; flex-shrink:0;">
      <?php echo $icon; ?>
    </div>

    
    <div style="flex:1;">
      <div style="font-weight:600; color:#222; margin-bottom:4px;">
        <?php echo htmlspecialchars($notif['message']); ?>
      </div>
      <small style="color:#777; font-size:12px; display:block;">
        <?php echo date("M j, Y g:i A", strtotime($notif['created_at'])); ?>
      </small>
    </div>
  </div>
<?php endforeach; ?>


    <?php endif; ?>
  </div>
</div>
</div>
</div>


<?php if ($showWelcome): ?>
  <div id="welcomeBox" style="
    background-color: #ffffffff;
    color: #2d2d2d;
    padding: 16px 32px;
    border-left: 6px solid #9fb993;
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


<div id="my-listings" class="section">
        <h2>My Listings</h2>
        <div class="card">
          <?php

        
        $sellerid = $_SESSION['id'] ?? 0;

        if ($sellerid) {
            $stmt = $conn->prepare("SELECT bookid, title, author, price, book_condition, edition, image_url 
                                    FROM tbl_books 
                                    WHERE sellerid = ?");
            $stmt->bind_param("i", $sellerid);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<table class='listings-table'>";
                echo "<tr style='background-color: #f2f2f2;'>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Price</th>
                        <th>Condition</th>
                        <th>Edition</th>
                        <th>Actions</th>
                      </tr>";

               while ($row = $result->fetch_assoc()):
    $imageFile = basename($row['image_url']);
    $webPath = "uploads/" . $imageFile;
?>
<tr
 data-bookid="<?php echo $row['bookid']; ?>"
 data-title="<?php echo html_entity_decode($row['title'], ENT_QUOTES); ?>"
 data-author="<?php echo htmlspecialchars($row['author'], ENT_QUOTES); ?>"
 data-price="<?php echo htmlspecialchars($row['price'], ENT_QUOTES); ?>"
 data-condition="<?php echo htmlspecialchars($row['book_condition'], ENT_QUOTES); ?>"
 data-edition="<?php echo htmlspecialchars($row['edition'] ?? '', ENT_QUOTES); ?>"
 data-image="<?php echo htmlspecialchars($row['image_url'], ENT_QUOTES); ?>"
>
    <td><img src="<?php echo $webPath; ?>" alt="Book Image" style="width:80px; height:auto;"></td>
    <td><?php echo html_entity_decode($row['title']); ?></td>
    <td><?php echo htmlspecialchars($row['author']); ?></td>
    <td>$<?php echo htmlspecialchars($row['price']); ?></td>
    <td><?php echo htmlspecialchars($row['book_condition']); ?></td>
    <td><?php echo htmlspecialchars($row['edition'] ?: '-'); ?></td>
    <td>
        
        <button class="btn-edit-row" style="background:#8AA382; color:#F8F6F4; padding:8px 14px; border:none; border-radius:10px; cursor:pointer;">Edit</button>

        
         <td>
    <button class="btn-delete" data-bookid="<?php echo $row['bookid']; ?>"
        style="background:#9F3D3D; color:#F8F6F4; padding:8px 14px; border:none; border-radius:10px; cursor:pointer;">
        Delete
    </button>
</td>

      
    </td>
</tr>

<?php endwhile; ?>



            </table>
        <?php
            } else {
                echo "<p>You haven’t listed any books yet.</p>";
            }

            $stmt->close();
        }
        ?>
    </div>
</div>
     

<div id="editModal" class="modal" style="
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background: rgba(0,0,0,0.55);
    padding-top:60px; 
    justify-content:center;
    z-index:9999;
    font-family:'Poppins',serif;
    overflow-y:auto;
">
    <div class="modal-content" style="
    background:#F8F6F4;
    padding:35px 40px;
    border-radius:20px;
    max-width:520px;
    width:90%;
    position:relative;
    box-shadow: 0 12px 30px rgba(0,0,0,0.3);
    border-left: 6px solid #8AA382;
    transition: transform 0.3s ease;
    max-height:85vh;
    overflow-y:auto;
">

        <span class="close" style="
            position:absolute;
            top:15px;
            right:20px;
            font-size:26px;
            cursor:pointer;
            color:#9F3D3D;
            font-weight:600;
            transition: color 0.2s ease;
        " onmouseover="this.style.color='#5D4A3E'" onmouseout="this.style.color='#9F3D3D'">&times;</span>
        <h2 style="
            text-align:center;
            font-family:'Poppins', serif;
            color:#5D4A3E;
            margin-bottom:25px;
            font-size:2rem;
            font-weight:500;
        ">Edit Listing</h2>
        <form id="edit-book-form" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:18px;">
    <input type="hidden" name="bookid" id="modal-bookid">
    <input type="hidden" name="old_image" id="modal-old-image">
    
    <label>Title</label>
    <input type="text" name="title" id="modal-title" required
        style="padding:10px; border-radius:10px; border:1px solid #ccc;">
    
    <label>Author</label>
    <input type="text" name="author" id="modal-author" required
        style="padding:10px; border-radius:10px; border:1px solid #ccc;">
    
    <label>Price</label>
    <input type="number" name="price" id="modal-price" step="0.01" required
        style="padding:10px; border-radius:10px; border:1px solid #ccc;">
    
    <label>Condition</label>
    <input type="text" name="book_condition" id="modal-condition" required
        style="padding:10px; border-radius:10px; border:1px solid #ccc;">

    <label>Edition (optional)</label>
    <input type="text" name="edition" id="modal-edition"
       style="padding:10px; border-radius:10px; border:1px solid #ccc;">

    
    <label>Book Image</label>
    
    <input type="file" id="modal-image" name="book_image" accept="image/*" style="
        padding:8px;
        border:1px solid #ccc;
        border-radius:8px;
        width:100%;
        background:#fff;
        font-family:'Poppins',serif;">


    
    <button type="submit" id="update-book-btn" 
        style="background:#8AA382; color:#F8F6F4; padding:12px; border:none; border-radius:10px; cursor:pointer; font-weight:500; transition: transform 0.2s, background 0.2s;"
        onmouseover="this.style.background='#6F896D'; this.style.transform='translateY(-2px)';" 
        onmouseout="this.style.background='#8AA382'; this.style.transform='translateY(0)';">
        Update Listing
    </button>
</form>


<div id="update-message" style="margin-top:10px; color:green; display:none;"></div>

    </div>
</div>




    <div id="my-negotiations" class="section">
    <h2>My Negotiation Offers</h2>
    <div class="card">
        <h3 style="
            font-family: 'Poppins', sans-serif; 
            font-size: 1.3rem; 
            font-weight: 600; 
            margin-bottom: 15px; 
            color: #2e4d36;
        ">Offers I Have Made</h3>

<?php
$buyerId = $_SESSION['id'] ?? 0;

if ($buyerId) {
    $stmt = $conn->prepare("
        SELECT n.negotiation_id, b.title AS book_title, u.name AS seller_name, 
               n.initial_price, n.offer_price, n.status, n.updated_at, o.status as order_status
        FROM tbl_negotiation n
        JOIN tbl_books b ON n.bookid = b.bookid
        JOIN users u ON n.sellerid = u.id
        LEFT JOIN tbl_orders o ON o.negotiation_id = n.negotiation_id
        WHERE n.buyerid = ?
        ORDER BY n.updated_at DESC
    ");
    $stmt->bind_param("i", $buyerId);
    $stmt->execute();
    $result = $stmt->get_result();

    $pending = [];
    $accepted = [];
    $rejected = [];

    while ($row = $result->fetch_assoc()) {
        switch ($row['status']) {
            case 'pending': $pending[] = $row; break;
            case 'accepted': $accepted[] = $row; break;
            case 'rejected': $rejected[] = $row; break;
        }
    }

function renderOffersTable($offers, $highlight = '') {
    if (count($offers) === 0) return;
    echo "<table class='listings-table'>";
    echo "<tr>
            <th>Book</th>
            <th>Seller</th>
            <th>Initial Price</th>
            <th>My Offer</th>
            <th>Status</th>
            <th>Last Updated</th>
            <th>Action</th>
          </tr>";

    foreach ($offers as $row) {
        $rowStyle = $highlight ? "style='background-color:$highlight;'" : '';
        echo "<tr $rowStyle>
                <td>" . html_entity_decode($row['book_title']) . "</td>
                <td>" . htmlspecialchars($row['seller_name']) . "</td>
                <td>₹" . number_format($row['initial_price'], 2) . "</td>
                <td>₹" . number_format($row['offer_price'], 2) . "</td>
                <td>" . htmlspecialchars(ucfirst($row['status'])) . "</td>
                <td>" . htmlspecialchars($row['updated_at']) . "</td>
                <td>";

        
if ($row['status'] === 'accepted') {
    if (!empty($row['order_status'])) {
        
        echo "<span style='color:gray; font-weight:600;'>Order Placed ({$row['order_status']})</span>";
    } else {
        
        echo "<form action='checkout.php' method='get' style='display:inline;'>
                <input type='hidden' name='negotiation_id' value='{$row['negotiation_id']}'>
                <button type='submit' class='table-action pay'>Proceed to Payment</button>
              </form>";
    }
} else {
    echo "-";
}


        echo "</td></tr>";
    }
    echo "</table><br>";
}


if (count($pending)) {
    echo "<h4 style='color:#8a6f2f; font-weight:600;'>Pending Offers</h4>";
    renderOffersTable($pending, '#fdf4e4'); 
}

if (count($accepted)) {
    echo "<h4 style='color:green; font-weight:600;'>Accepted Offers</h4>";
    renderOffersTable($accepted, '#e0f4e0');
}

if (count($rejected)) {
    echo "<h4 style='color:red; font-weight:600;'>Rejected Offers</h4>";
    renderOffersTable($rejected, '#f9e0e0');
}

if (count($pending) + count($accepted) + count($rejected) === 0) {
    echo "<p>You haven’t made any negotiation offers yet.</p>";
}

$stmt->close();
}
?>

    </div>
</div>


<div id="my-orders" class="section">
        <h2>My Orders</h2>
        <div class="card">
          <h3 style="
        font-family: 'Poppins', sans-serif; 
        font-size: 1.3rem; 
        font-weight: 600; 
        margin-bottom: 15px; 
        color: #2e4d36;
    ">Incoming Requests</h3>
<?php
$sellerId = $_SESSION['id'] ?? 0;

if ($sellerId) {
    $stmt = $conn->prepare("
        SELECT n.negotiation_id, b.title AS book_title, u.name AS buyer_name, 
               n.offer_price, n.status, n.updated_at
        FROM tbl_negotiation n
        JOIN tbl_books b ON n.bookid = b.bookid
        JOIN users u ON n.buyerid = u.id
        WHERE b.sellerid = ? AND n.status='pending'
        ORDER BY n.updated_at DESC
    ");
    $stmt->bind_param("i", $sellerId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<table class='listings-table'>";
        echo "<tr>
                <th>Book</th>
                <th>Buyer</th>
                <th>Offer Price</th>
                <th>Status</th>
                <th>Last Updated</th>
              </tr>";

        while ($row = $result->fetch_assoc()) {
    ?>
    <tr>
        <td><?php echo html_entity_decode($row['book_title']); ?></td>
        <td><?php echo htmlspecialchars($row['buyer_name']); ?></td>
        <td>₹<?php echo htmlspecialchars($row['offer_price']); ?></td>
        <td><?php echo htmlspecialchars($row['status']); ?></td>
        <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
        <td>
            <form method="POST" action="handle_counter_response.php" style="display:flex; gap:5px; justify-content:center;">
                <input type="hidden" name="negotiation_id" value="<?php echo $row['negotiation_id']; ?>">
                <button type="submit" name="action" value="accept" class="table-action accept">Accept</button>
                <button type="submit" name="action" value="reject" class="table-action reject">Reject</button>
            </form>
        </td>
    </tr>
    <?php
}

        echo "</table>";
    } else {
        echo "<p>No negotiation offers on your books yet.</p>";
    }

    $stmt->close();

}
?>
</div>

      </div>

<a href="index1.php" style="text-decoration:none; color:inherit; display:block;">
  <div id="browse-books" class="section">
    <h2>Browse Books</h2>
    <div class="card">
      <p>Explore second-hand books listed by students across departments.</p>
    </div>
  </div>
</a>

    <div id="sell-book" class="section">
     <div class="sell-book-container">
    <h2>Add Book Details</h2>
    <form action="sell_book.php" method="POST" enctype="multipart/form-data">
      <h2 class="form-section-title"> Book Details </h2>
        <input type="text" name="title" placeholder="Book Title" required>
        <input type="text" name="author" placeholder="Author Name" required>

      <label style="display:block; margin-bottom:6px; color:#A8B5A0; font-family:'Poppins', sans-serif;">
        Book Category:
      </label>
      <select name="category_id" required
        style="width:100%; padding:10px 12px; margin:6px 0; 
               font-family:'Poppins', sans-serif; font-size:14px; 
               border:1px solid #ccc; border-radius:8px; 
               background-color:#fff; color:#333;">
        <option value="">-- Select Category --</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['category_id']; ?>">
              <?php echo htmlspecialchars($cat['name']); ?>
            </option>
        <?php endforeach; ?>
      </select>
              <input type="text" name="book_condition" placeholder="Condition (e.g., Excellent, Good, Fair)" required>
              <input type="text" name="edition" placeholder="Edition(eg. 2nd Edition)" style="margin-top:6px;">
        <input type="number" name="price" placeholder="Your Price" min="0" step="0.01" required>
        <label for="preferred_payment"
        style="display:block; margin-bottom:6px; color:#A8B5A0; font-family:'Poppins', sans-serif;">
        Preferred Payment Method:</label>
<select name="preferred_payment" id="preferred_payment" required
  style="width:100%; padding:10px 12px; margin:6px 0; 
               font-family:'Poppins', sans-serif; font-size:14px; 
               border:1px solid #ccc; border-radius:8px; 
               background-color:#fff; color:#333; 
               appearance:none; -webkit-appearance:none; -moz-appearance:none;
               background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="none"><path d="M5 7L10 12L15 7" stroke="%23666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>');
               background-repeat:no-repeat; 
               background-position:right 12px center;
               background-size:14px;">
    <option value="UPI">UPI</option>
    <option value="Card">Card</option>
</select>

        <textarea name="description" placeholder="Book Description..." rows="3"></textarea>
        
        <label class="file-label">
            <span>Upload Book Image</span>
            <input type="file" name="image" accept="image/*" required>
        </label>
        
        <button type="submit" class="btn-submit">Post Ad</button>
    </form>
</div>
</div>

<div id="track-orders" class="section">
  <h2>Track My Orders</h2>

  <div class="card" style="padding:20px;">
    <?php
    
    $buyerId = $_SESSION['id'] ?? 0;

    if ($buyerId) {
        
        $stmt = $conn->prepare("
            SELECT o.orderid, b.title AS book_title, b.sellerid,
                   da.address_line1, da.city, da.state,
                   oa.status, oa.assigned_at, oa.delivered_at, o.order_date
            FROM tbl_orders o
            JOIN tbl_books b ON o.bookid = b.bookid
            LEFT JOIN tbl_order_assignments oa ON o.orderid = oa.order_id
            LEFT JOIN tbl_user_address da ON da.user_id = o.id
            WHERE o.id = ?
            ORDER BY o.order_date DESC
        ");
        $stmt->bind_param('i', $buyerId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<div style='display:flex;flex-direction:column;gap:15px;'>";

            while ($row = $result->fetch_assoc()) {
                $statusText = isset($row['status']) ? ucfirst($row['status']) : 'Pending';
                $badgeColor = match($statusText) {
                    'Pending' => '#f0ad4e',
                    'Assigned' => '#5bc0de',
                    'Delivered' => '#5cb85c',
                    'Received' => '#9F3D3D',
                    default => '#777'
                };

                $lastUpdate = $row['delivered_at'] ?? $row['assigned_at'] ?? $row['order_date'] ?? null;
                $formattedDate = $lastUpdate ? date('M j, Y g:i A', strtotime($lastUpdate)) : 'N/A';

                echo "<div style='border:1px solid #ccc; padding:15px; border-radius:8px;'>
                        <h3 style='margin:0;'>".html_entity_decode($row['book_title'])."</h3>
                        <p style='margin:5px 0;'>Delivery Address: "
                        . htmlspecialchars($row['address_line1'] ?? '-') . ', '
                        . htmlspecialchars($row['city'] ?? '-') . ', '
                        . htmlspecialchars($row['state'] ?? '-') . "</p>
                        <span style='background-color:$badgeColor; color:white; padding:5px 10px; border-radius:4px;'>$statusText</span>
                        <p style='margin:5px 0; font-size:0.9em; color:#555;'>Last Updated: $formattedDate</p>";

                if ($statusText === 'Delivered' || $statusText === 'Received') {
                    echo "<div style='margin-top:10px; display:flex; flex-wrap:wrap; align-items:center; gap:10px;'>";

                    echo "<form action='confirm_received.php' method='POST' style='display:inline;'>
                            <input type='hidden' name='order_id' value='" . $row['orderid'] . "'>";

                    if ($statusText === 'Delivered') {
                        echo "<button type='submit' style='padding:5px 10px; border:none; background:#337ab7; color:white; border-radius:4px; cursor:pointer;'>Confirm Received</button>";
                    } else {
                        echo "<button type='button' disabled style='padding:5px 10px; border:none; background:#999; color:white; border-radius:4px; opacity:0.7;'>Received ✔</button>";
                    }

                    echo "</form>";
                    
                    echo "<button onclick=\"openRateModal('{$row['orderid']}', '{$row['sellerid']}')\" 
                          style='background-color:#B69E59; border:none; color:white; padding:6px 12px; border-radius:6px; cursor:pointer;'>Rate Seller</button>";



                    echo "</div>"; 
                }

                echo "</div>"; 
            }

            echo "</div>"; 
        } else {
            echo "<p>You have no orders yet.</p>";
        }

        $stmt->close();
    } else {
        echo "<p>Please log in to view your orders.</p>";
    }
    ?>
  </div> 
</div> 



<div id="rateSellerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:30px; border-radius:12px; width:400px; text-align:center; box-shadow:0 0 15px rgba(0,0,0,0.3); position:relative;">
        <h2 style="margin-bottom:15px;">Rate Seller</h2>
        
        
        <div id="starRating" style="margin-bottom:15px;">
            <span class="star" data-value="1" style="font-size:25px; cursor:pointer;">☆</span>
            <span class="star" data-value="2" style="font-size:25px; cursor:pointer;">☆</span>
            <span class="star" data-value="3" style="font-size:25px; cursor:pointer;">☆</span>
            <span class="star" data-value="4" style="font-size:25px; cursor:pointer;">☆</span>
            <span class="star" data-value="5" style="font-size:25px; cursor:pointer;">☆</span>
        </div>

        
        <textarea id="sellerComment" placeholder="Write a short review (optional)" style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc; resize:none; height:80px;"></textarea>

        <input type="hidden" id="modalOrderId">
        <input type="hidden" id="modalSellerId">

        
        <div style="margin-top:15px;">
            <button onclick="submitSellerRating()" style="background-color:#5D4A3E; color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer;">Submit</button>
            <button onclick="closeModal()" style="background-color:#ccc; border:none; padding:8px 16px; border-radius:6px; cursor:pointer;">Cancel</button>
        </div>
    </div>
</div>



      <div id="order-history" class="section" style="padding:20px;">
    <h2 style="font-family:'Poppins', sans-serif; color:#5D4A3E; margin-left:40px; margin-bottom:15px;">Order History</h2>
    
     <div class="card" style="
        background:#F8F6F4; 
        padding:20px; 
        margin-left:40px; 
        margin-right:40px; 
        border-radius:12px; 
        box-shadow:0 4px 8px rgba(0,0,0,0.05);">
      

        <table class="improved-table" style="width:100%; border-collapse:collapse; font-family:'Inter', sans-serif;">
            <thead>
                <tr style="background-color:#b7c9a8; color:white;">
                    <th style="padding:10px; border:1px solid #ddd;">Book</th>
                    <th style="padding:10px; border:1px solid #ddd;">Seller</th>
                    <th style="padding:10px; border:1px solid #ddd;">Status</th>
                    <th style="padding:10px; border:1px solid #ddd;">Order Date</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if($orders_result->num_rows > 0){
                    while($row = $orders_result->fetch_assoc()): ?>
                        <tr style="text-align:center; border:1px solid #ddd;">
                            <td style="padding:10px;"><?php echo html_entity_decode($row['book_title']); ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars($row['seller_name']); ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></td>
                            <td style="padding:10px;"><?php echo htmlspecialchars(date('d M Y', strtotime($row['order_date']))); ?></td>
                            
                            </td>
                        </tr>
                <?php endwhile; 
                } else {
                    echo "<tr><td colspan='5' style='text-align:center;'>You haven't placed any orders yet.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

    
    
<div id="delivery-address" class="section" style="display:none; font-family:'Poppins', sans-serif; padding:40px; background-color:#F8F6F4;">
  <div class="card" style="
      max-width:600px; 
      margin:0 auto; 
      background:#fff; 
      padding:30px 25px; 
      border-radius:16px; 
      box-shadow:0 6px 16px rgba(0,0,0,0.1); 
      border:1px solid #e0e0e0;
  ">
    
    <h2 style="text-align:center; font-weight:600; font-size:24px; margin-bottom:25px; color:#2d2d2d;">
      Update Your Delivery Address
    </h2>

    <?php if (!empty($message)): ?>
      <div id="addressMessage" style="
        background-color:#dfe8dc; 
        color:#2e4e34; 
        font-weight:500; 
        font-size:15px; 
        font-family:'Poppins', sans-serif; 
        padding:12px 18px; 
        margin-bottom:20px; 
        text-align:center; 
        border-radius:8px;
        box-shadow:0px 2px 6px rgba(0,0,0,0.1);
        transition:opacity 0.5s ease;">
        <?php echo $message; ?>
      </div>
      <script>
        setTimeout(function(){
          const msg = document.getElementById('addressMessage');
          if (msg) {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500);
          }
        }, 3000);
      </script>
    <?php endif; ?>

  <form id="update-address-form" method="POST" style="max-width:500px; margin:0 auto; text-align:left;">

    <label style="display:block; margin-bottom:6px; font-weight:500; color:#2d2d2d;">Full Name</label>
    <input type="text" name="full_name" required
           value="<?php echo htmlspecialchars($address['full_name']); ?>"
           style="width:100%; padding:12px; margin-bottom:14px; border:1px solid #ccc; border-radius:8px; font-size:14px;">

    <label style="display:block; margin-bottom:6px; font-weight:500; color:#2d2d2d;">Phone Number</label>
    <input type="text" name="phone_number" required
           value="<?php echo htmlspecialchars($address['phone_number']); ?>"
           style="width:100%; padding:12px; margin-bottom:14px; border:1px solid #ccc; border-radius:8px; font-size:14px;">

    <label style="display:block; margin-bottom:6px; font-weight:500; color:#2d2d2d;">Address Line 1</label>
    <input type="text" name="address_line1" required
           value="<?php echo htmlspecialchars($address['address_line1']); ?>"
           style="width:100%; padding:12px; margin-bottom:14px; border:1px solid #ccc; border-radius:8px; font-size:14px;">

    <label style="display:block; margin-bottom:6px; font-weight:500; color:#2d2d2d;">Address Line 2</label>
    <input type="text" name="address_line2"
           value="<?php echo htmlspecialchars($address['address_line2']); ?>"
           style="width:100%; padding:12px; margin-bottom:14px; border:1px solid #ccc; border-radius:8px; font-size:14px;">

    <label style="display:block; margin-bottom:6px; font-weight:500; color:#2d2d2d;">City</label>
    <input type="text" name="city" required
           value="<?php echo htmlspecialchars($address['city']); ?>"
           style="width:100%; padding:12px; margin-bottom:14px; border:1px solid #ccc; border-radius:8px; font-size:14px;">

    <label style="display:block; margin-bottom:6px; font-weight:500; color:#2d2d2d;">State</label>
    <input type="text" name="state" required
           value="<?php echo htmlspecialchars($address['state']); ?>"
           style="width:100%; padding:12px; margin-bottom:14px; border:1px solid #ccc; border-radius:8px; font-size:14px;">

    <label style="display:block; margin-bottom:6px; font-weight:500; color:#2d2d2d;">Pincode</label>
    <input type="text" name="pincode" required
           value="<?php echo htmlspecialchars($address['pincode']); ?>"
           style="width:100%; padding:12px; margin-bottom:20px; border:1px solid #ccc; border-radius:8px; font-size:14px;">

    <button type="submit" name="save_address"
            style="width:100%; padding:14px; background:#2d2d2d; color:white; font-size:16px; font-weight:500; 
                   border:none; border-radius:10px; cursor:pointer; transition:background 0.3s ease;">
        Save Address
    </button>
    </form>
  </div>
</div>




      <div id="account-settings" class="section">
  <div class="settings-wrapper">
    <i class="fas fa-user-circle" style="font-size: 3.2rem; color: #2d2d2d; margin-bottom: 10px;"></i>
    <h2 style="margin: 0 0 28px 0; font-size: 1.3rem; font-family: 'Poppins', sans-serif; font-weight: 600; color: #2d2d2d;">
      <?php echo ucwords($_SESSION['name']); ?>
    </h2>

    <form method="POST" action="update-courier.php" style="width: 100%; box-sizing: border-box; padding: 0 15px;">
      <input type="hidden" name="redirect" value="user-dashboard.php">
      <div style="margin-bottom: 18px;">
        <label for="name" style="font-weight:600; font-size: 0.95rem;">Full Name</label>
        <input type="text" id="name" name="name" value="<?php echo $_SESSION['name']; ?>" required>
      </div>

      <div style="margin-bottom: 18px;">
        <label for="email" style="font-weight:600; font-size: 0.95rem;">Email</label>
       <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
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
document.addEventListener("DOMContentLoaded", function() {

  
  const editModal = document.getElementById('editModal');
  const editCloseBtn = editModal?.querySelector('.close');

  editCloseBtn?.addEventListener('click', () => editModal.style.display = 'none');
  window.addEventListener('click', (e) => { 
      if (e.target === editModal) editModal.style.display = 'none';
  });

  document.querySelectorAll('.btn-edit-row').forEach(btn => {
      btn.addEventListener('click', () => {
          const tr = btn.closest('tr');
          document.getElementById('modal-bookid').value = tr.dataset.bookid;
          document.getElementById('modal-title').value = tr.dataset.title;
          document.getElementById('modal-author').value = tr.dataset.author;
          document.getElementById('modal-price').value = tr.dataset.price;
          document.getElementById('modal-condition').value = tr.dataset.condition;
          document.getElementById('modal-edition').value = tr.dataset.edition || '';
           document.getElementById('modal-old-image').value = tr.dataset.image || '';
          editModal.style.display = 'flex';
      });
  });
  
const editBookForm = document.getElementById('edit-book-form');
const updateMessage = document.getElementById('update-message');

if (editBookForm) {

    editBookForm.addEventListener('submit', function(e) {
        e.preventDefault(); 

        const formData = new FormData(editBookForm);

        
        formData.append('update_book', '1');

        fetch('update_book.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(data => {
            if (data.trim() === 'success') {
                updateMessage.textContent = "✅ Listing updated successfully!";
                updateMessage.style.display = "block";

                
                const bookId = document.getElementById('modal-bookid').value;
                const row = document.querySelector(`tr[data-bookid='${bookId}']`);
                if (row) {
                    row.dataset.title = document.getElementById('modal-title').value;
                    row.dataset.author = document.getElementById('modal-author').value;
                    row.dataset.price = document.getElementById('modal-price').value;
                    row.dataset.condition = document.getElementById('modal-condition').value;
                    row.dataset.edition = document.getElementById('modal-edition').value || '';
                    
                    const fileInput = document.getElementById('modal-image');
                    if (fileInput.files.length > 0) {
                        const newFilename = fileInput.files[0].name;
                        row.dataset.image = newFilename;
                    }

                    row.cells[1].textContent = row.dataset.title;
                    row.cells[2].textContent = row.dataset.author;
                    row.cells[3].textContent = "$" + row.dataset.price;
                    row.cells[4].textContent = row.dataset.condition;
                    if (row.cells[5]) row.cells[5].textContent = row.dataset.edition || '-';
                  }
                setTimeout(() => {
                    editModal.style.display = 'none';
                    updateMessage.style.display = 'none';
                }, 2000);
            } else {
                updateMessage.textContent = "❌ " + data;
                updateMessage.style.display = "block";
            }
        })
        .catch(err => {
            console.error(err);
            updateMessage.textContent = "❌ An error occurred.";
            updateMessage.style.display = "block";
        });
    });
}
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm('Delete this listing?')) return;

        const bookid = btn.dataset.bookid;
        const row = btn.closest('tr');

        fetch('delete_listing.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'bookid=' + encodeURIComponent(bookid)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                row.remove(); 
                showToast("✅ Listing deleted successfully!");
            } else {
                alert("Error: " + (data.message || 'Unable to delete'));
            }
        })
        .catch(err => alert("Request failed: " + err));
    });
});


  
  const sellBookForm = document.querySelector('#sell-book form');
if (sellBookForm) {
  sellBookForm.addEventListener('submit', function (e) {
    const title = this.title.value.trim();
    const author = this.author.value.trim();
    const category = this.category_id.value.trim();
    const condition = this.book_condition.value.trim();
    const edition = this.edition ? this.edition.value.trim() : '';
    const priceValue = this.price.value.trim();
    const payment = this.preferred_payment.value;
    const image = this.image.files[0];

    
    const textPattern = /^[A-Za-zÀ-ÖØ-öø-ÿ0-9'.,\s-]+$/;
    const pricePattern = /^\d+(\.\d{1,2})?$/;
    const allowedConditions = ["Excellent", "Good", "Fair"];

    
    if (!textPattern.test(title)) {
      alert("Title should only contain letters and spaces.");
      e.preventDefault();
      return false;
    }

    if (!textPattern.test(author)) {
      alert("Author name should only contain letters and spaces.");
      e.preventDefault();
      return false;
    }

    if (!textPattern.test(category)) {
      alert("Category should only contain letters and spaces.");
      e.preventDefault();
      return false;
    }

    if (!allowedConditions.includes(condition)) {
      alert("Invalid book condition. Please select Excellent, Good, or Fair.");
      e.preventDefault();
      return false;
    }

    if (edition && edition.length > 50) {
      alert("Edition name seems too long.");
      e.preventDefault();
      return false;
    }

    if (!pricePattern.test(priceValue) || parseFloat(priceValue) <= 0) {
      alert("Please enter a valid numeric price greater than 0.");
      e.preventDefault();
      return false;
    }

    if (!["UPI", "Card"].includes(payment)) {
      alert("Select a valid payment method.");
      e.preventDefault();
      return false;
    }

    if (!image) {
      alert("Please upload a book image.");
      e.preventDefault();
      return false;
    }
  });
}


  
  window.showSection = function(sectionId) {
      document.querySelectorAll('.section').forEach(sec => sec.style.display = "none");
      const target = document.getElementById(sectionId);
      if (target) target.style.display = "block";
  }

  
  const welcomeBox = document.getElementById("welcomeBox");
  if (welcomeBox) {
      document.addEventListener("click", () => {
          welcomeBox.style.opacity = "0";
          setTimeout(() => welcomeBox.remove(), 500);
      });
  }

  
  window.showToast = function(message) {
      const toast = document.getElementById("toast");
      if (!toast) return;
      toast.textContent = message;
      toast.style.visibility = "visible";
      toast.style.opacity = "1";
      setTimeout(() => {
          toast.style.opacity = "0";
          setTimeout(() => toast.style.visibility = "hidden", 500);
      }, 3000);
  }

  if (window.location.search.includes('status=toast')) {
      showToast("✅ Settings saved successfully!");
      window.history.replaceState({}, document.title, window.location.pathname);
  }

  
  const negotiationSuccess = <?php echo isset($_GET['negotiation']) && $_GET['negotiation'] === 'success' ? 'true' : 'false'; ?>;
  if (negotiationSuccess) {
      const modal = document.getElementById("successModal");
      if (modal) {
          modal.style.display = "flex";
          setTimeout(() => modal.style.display = "none", 4000);
          modal.querySelector(".close")?.addEventListener("click", () => modal.style.display = "none");
      }
  }

  
  window.openCounterModal = function(negotiationId) {
      const modal = document.getElementById("counterModal");
      if (modal) {
          document.getElementById("negotiation_id").value = negotiationId;
          modal.style.display = "flex";
      }
  }

  window.closeCounterModal = function() {
      const modal = document.getElementById("counterModal");
      if (modal) modal.style.display = "none";
  }

  window.addEventListener("click", function(event) {
      const modal = document.getElementById("counterModal");
      if (modal && event.target === modal) modal.style.display = "none";
  });

  
  const bell = document.getElementById("notifBell");
  const dropdown = document.getElementById("notifDropdown");
  const badge = document.getElementById("notifCount");
  if (bell && dropdown) {
      bell.addEventListener("click", function(e) {
          e.stopPropagation();
          dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";

          if (dropdown.style.display === "block" && badge) {
              fetch("mark_notifications_read.php", { method: "POST" })
              .then(res => res.text())
              .then(data => { if (data.trim() === "success") badge.remove(); })
              .catch(err => console.error("Error marking notifications read:", err));
          }
      });

      window.addEventListener("click", function(e) {
          if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
              dropdown.style.display = "none";
          }
      });
  }

});


const stars = document.querySelectorAll('.star');
let selectedRating = 0;

stars.forEach(star => {
    star.addEventListener('click', () => {
        selectedRating = star.getAttribute('data-value');
        stars.forEach(s => s.textContent = s.getAttribute('data-value') <= selectedRating ? '★' : '☆');
    });
});


function openRateModal(orderId, sellerId) {
    document.getElementById('rateSellerModal').style.display = 'flex';
    document.getElementById('modalOrderId').value = orderId;
    document.getElementById('modalSellerId').value = sellerId;
}


function closeModal() {
    document.getElementById('rateSellerModal').style.display = 'none';
}


function submitSellerRating() {
    const rating = selectedRating;
    const comment = document.getElementById('sellerComment').value;
    const orderId = document.getElementById('modalOrderId').value;
    const sellerId = document.getElementById('modalSellerId').value;

    if (rating === 0) {
        alert("Please select a star rating first!");
        return;
    }

    
   fetch('rate_seller.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
        order_id: orderId,
        seller_id: sellerId,
        rating: rating,
        comment: comment
    })
})
.then(res => res.json())
.then(data => {
    if (data.success) {
        alert(data.message);

        
        const modal = document.getElementById('rateSellerModal');
        modal.style.transition = 'opacity 0.3s ease';
        modal.style.opacity = '0';

        setTimeout(() => {
            modal.style.display = 'none';
            location.reload(); 
        }, 300);
    } else {
        alert('Error: ' + data.message);
    }
})
.catch(err => {
    alert('Something went wrong: ' + err);
});

}


  
const updateAddressForm = document.getElementById('update-address-form');
if (updateAddressForm) {
  updateAddressForm.addEventListener('submit', function(e) {
    const name = this.querySelector('input[name="full_name"]').value.trim();
    const phone = this.querySelector('input[name="phone_number"]').value.trim();
    const city = this.querySelector('input[name="city"]').value.trim();
    const state = this.querySelector('input[name="state"]').value.trim();
    const pincode = this.querySelector('input[name="pincode"]').value.trim();

    let errors = [];

    if (!/^[A-Za-z\s]+$/.test(name)) errors.push("Full name should contain only letters and spaces.");
    if (!/^[0-9]{10}$/.test(phone)) errors.push("Phone number must be 10 digits.");
    if (!/^[A-Za-z\s]+$/.test(city)) errors.push("City name should contain only letters.");
    if (!/^[A-Za-z\s]+$/.test(state)) errors.push("State name should contain only letters.");
    if (!/^[0-9]{6}$/.test(pincode)) errors.push("Pincode must be exactly 6 digits.");

    if (errors.length > 0) {
      alert(errors.join("\n"));
      e.preventDefault(); 
    }
  });
}


</script>


  <?php $conn->close(); ?>

</body>
</html>