<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$showWelcome = false;
if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome'] === true) {
  $showWelcome = true;
  unset($_SESSION['show_welcome']); // unset it *before* output starts
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

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Roboto&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;600&display=swap" rel="stylesheet">

  <!-- Styles -->
  <style>
    :root {
      --sage-green: #b7c9a8;
      --soft-white: #fdfdfb;
      --dark-gray: #2d2d2d;
    }

    body {
  background-color: #fdfdfb;
  background-image: url('bb.png'); /* path to your image */
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
      display: none;
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
  min-height: calc(100vh - 80px); /* subtract navbar height */
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
  display: none; /* hidden by default */
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
  background: #d8e3d5; /* soft sage green */
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
    color: #3b5d3b; /* dark sage */
    border-color: #8aa382;
}
.table-action.accept:hover {
    background-color: #8aa382;
    color: white;
}

.table-action.reject {
    color: #8a2f2f; /* muted red */
    border-color: #dca1a1;
}
.table-action.reject:hover {
    background-color: #dca1a1;
    color: white;
}

.table-action.counter {
    color: #8a6f2f; /* muted mustard */
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
    <a href="#" onclick="showSection('order-history')"><i class="fas fa-history"></i> Order History</a>    
    <a href="#" onclick="showSection('order-history')"><i class="fas fa-history"></i> Delivery Address</a>


    <a href="#" onclick="showSection('account-settings')"><i class="fas fa-user-cog"></i> Account Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="container">
    <div class="main-content">
      <div class="navbar">
        <h1 class="dashboard-title">Dashboard</h1>
        <div class="user">
          
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

        // Get logged-in seller ID
        $sellerid = $_SESSION['id'] ?? 0;

        if ($sellerid) {
            $stmt = $conn->prepare("SELECT title, author, price, book_condition, image_url 
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
                      </tr>";

               while ($row = $result->fetch_assoc()) {
    $imageFile = basename($row['image_url']); // Extract just filename
    $webPath = "uploads/" . $imageFile; // Path relative to project root

    echo "<tr>
            <td><img src='" . htmlspecialchars($webPath) . "' alt='Book Image' style='width:80px; height:auto;'></td>
            <td>" . html_entity_decode($row['title']) . "</td>
            <td>" . htmlspecialchars($row['author']) . "</td>
            <td>$" . htmlspecialchars($row['price']) . "</td>
            <td>" . htmlspecialchars($row['book_condition']) . "</td>
          </tr>";
}


                echo "</table>";
            } else {
                echo "<p>You haven’t listed any books yet.</p>";
            }

            $stmt->close();
            
        }

       
        ?>
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
               n.initial_price, n.offer_price, n.status, n.updated_at
        FROM tbl_negotiation n
        JOIN tbl_books b ON n.bookid = b.bookid
        JOIN users u ON n.sellerid = u.id
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
                <td>" . htmlspecialchars($row['book_title']) . "</td>
                <td>" . htmlspecialchars($row['seller_name']) . "</td>
                <td>₹" . number_format($row['initial_price'], 2) . "</td>
                <td>₹" . number_format($row['offer_price'], 2) . "</td>
                <td>" . htmlspecialchars(ucfirst($row['status'])) . "</td>
                <td>" . htmlspecialchars($row['updated_at']) . "</td>
                <td>";

        // Action cell
if ($row['status'] === 'accepted') {
    echo "<form action='checkout.php' method='get' style='display:inline;'>
            <input type='hidden' name='negotiation_id' value='{$row['negotiation_id']}'>
            <button type='submit' class='table-action pay'>Proceed to Payment</button>
          </form>";
} else {
    echo "-";
}


        echo "</td></tr>";
    }
    echo "</table><br>";
}

// Render sections
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
        <input type="text" name="category" placeholder="Book Category" required>
        <input type="text" name="book_condition" placeholder="Condition (e.g., Excellent, Good, Fair)" required>
        <input type="number" name="price" placeholder="Your Price" min="0" step="0.01" required>
        <input type="text" name="payment_mode" placeholder="Preferred Payment Mode" required>
        <textarea name="description" placeholder="Book Description..." rows="3"></textarea>
        
        <label class="file-label">
            <span>Upload Book Image</span>
            <input type="file" name="image" accept="image/*" required>
        </label>
        <h2 class="form-section-title"> Your Details </h2>
        <input type="text" name="name" placeholder="Your Name" required>
        <input type="tel" name="phone" placeholder="Phone Number" required>
        
        <button type="submit" class="btn-submit">Post Ad</button>
    </form>
</div>
</div>



      <div id="order-history" class="section">
        <h2>Order History</h2>
        <div class="card">
          <p>Here you’ll find a log of all your past orders and transactions.</p>
        </div>
      </div>

    <h2>📦 My Delivery Address</h2>
<form action="save_user_address.php" method="POST">
  <input type="text" name="full_name" placeholder="Full Name" value="<?php echo htmlspecialchars($addr['full_name'] ?? ''); ?>" required>
  <input type="text" name="phone_number" placeholder="Phone" value="<?php echo htmlspecialchars($addr['phone_number'] ?? ''); ?>" required>
  <input type="text" name="address_line1" placeholder="House/Street" value="<?php echo htmlspecialchars($addr['address_line1'] ?? ''); ?>" required>
  <input type="text" name="address_line2" placeholder="Landmark (optional)" value="<?php echo htmlspecialchars($addr['address_line2'] ?? ''); ?>">
  <input type="text" name="city" placeholder="City" value="<?php echo htmlspecialchars($addr['city'] ?? ''); ?>" required>
  <input type="text" name="state" placeholder="State" value="<?php echo htmlspecialchars($addr['state'] ?? ''); ?>" required>
  <input type="text" name="pincode" placeholder="Pincode" value="<?php echo htmlspecialchars($addr['pincode'] ?? ''); ?>" required>
  <button type="submit">💾 Save Address</button>
</form>


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
    function showSection(section) {
      const sections = document.querySelectorAll('.section');
      sections.forEach(sec => sec.classList.remove('active'));
      document.getElementById(section).classList.add('active');
    }
  const welcomeBox = document.getElementById("welcomeBox");

  if (welcomeBox) {

    // OR hide on any click in the page
    document.addEventListener("click", () => {
      welcomeBox.style.opacity = "0";
      setTimeout(() => welcomeBox.remove(), 500);
    });
  }
  function showToast(message) {
  const toast = document.getElementById("toast");
  toast.textContent = message;
  toast.style.visibility = "visible";
  toast.style.opacity = "1";

  setTimeout(() => {
    toast.style.opacity = "0";
    setTimeout(() => {
      toast.style.visibility = "hidden";
    }, 500);
  }, 3000); // visible for 3 seconds
}
// Check if the URL has ?status=toast
if (window.location.search.includes('status=toast')) {
  showToast("✅ Settings saved successfully!");
  // Remove the query param after showing toast (optional, cleaner UX)
  window.history.replaceState({}, document.title, window.location.pathname);
}
<?php if (isset($_GET['negotiation']) && $_GET['negotiation'] === 'success'): ?>
<div id="successModal" class="success-modal">
  <div class="success-modal-content">
    <span class="close" onclick="closeSuccessModal()">&times;</span>
    <h2>Offer Submitted!</h2>
    <p>Your price offer has been sent to the seller successfully.</p>
  </div>
</div>
  document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("successModal");
    modal.style.display = "flex";
    // Auto-dismiss after 4 seconds
    setTimeout(() => {
      closeSuccessModal();
    }, 4000);
  });
  function closeSuccessModal() {
    document.getElementById("successModal").style.display = "none";
  }
<?php endif; ?>

function openCounterModal(negotiationId) {
    document.getElementById("negotiation_id").value = negotiationId;
    document.getElementById("counterModal").style.display = "flex";
  }

  function closeCounterModal() {
    document.getElementById("counterModal").style.display = "none";
  }

  // Close when clicking outside the modal
  window.onclick = function(event) {
    const modal = document.getElementById("counterModal");
    if (event.target === modal) {
      modal.style.display = "none";
    }
  }


  </script>
  <?php $conn->close(); ?>

</body>
</html>