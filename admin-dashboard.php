<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
session_start();
$name = 'ADMIN';
$sqlListings = "SELECT COUNT(*) AS total_listings FROM tbl_books WHERE is_active = 1";
$resultListings = $conn->query($sqlListings);

if ($resultListings && $rowListings = $resultListings->fetch_assoc()) {
    $totalListings = $rowListings['total_listings'];
} else {
    $totalListings = 0;
}
$totalOrders = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM tbl_orders");
if ($res && $row = $res->fetch_assoc()) {
  $totalOrders = $row['total'];
}


$deliveryResult = $conn->query("
    SELECT COUNT(*) AS total_deliveries 
    FROM tbl_order_assignments 
    WHERE status = 'delivered'
");
$deliveryCount = 0;
if($deliveryResult && $rowDelivery = $deliveryResult->fetch_assoc()){
    $deliveryCount = $rowDelivery['total_deliveries'];
}


$top_sellers_sql = "
SELECT 
    u.id AS seller_id,
    u.name AS seller_name,
    COALESCE(book_sales.books_sold,0) AS books_sold,
    COALESCE(ratings.avg_rating,0) AS avg_rating,
    COALESCE(ratings.total_ratings,0) AS total_ratings
FROM users u
LEFT JOIN (
    SELECT b.sellerid, COUNT(*) AS books_sold
    FROM tbl_books b
    JOIN tbl_orders o ON o.bookid = b.bookid AND o.status='confirmed'
    GROUP BY b.sellerid
) AS book_sales ON book_sales.sellerid = u.id
LEFT JOIN (
    SELECT seller_id, COUNT(*) AS total_ratings, ROUND(AVG(rating),2) AS avg_rating
    FROM tbl_seller_ratings
    GROUP BY seller_id
) AS ratings ON ratings.seller_id = u.id
WHERE book_sales.books_sold IS NOT NULL
ORDER BY books_sold DESC
LIMIT 5
";


$top_sellers_result = $conn->query($top_sellers_sql);



$top_buyers_sql = "
SELECT u.id AS buyer_id, u.name AS buyer_name, 
       COUNT(o.id) AS orders_placed
FROM users u
JOIN tbl_orders o ON u.id = o.id
WHERE u.role = 'buyer' AND o.status = 'confirmed'
GROUP BY u.id, u.name
ORDER BY orders_placed DESC
LIMIT 5
";

$top_buyers_result = $conn->query($top_buyers_sql);

$categories = [];
$bookCounts = [];

$sql = "
    SELECT c.name, COUNT(b.bookid) AS total_books
    FROM tbl_categories c
    LEFT JOIN tbl_books b ON b.category_id = c.category_id AND b.is_active = 1
    GROUP BY c.category_id
";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $categories[] = $row['name'];
    $bookCounts[] = $row['total_books'];
}

$categories_js = json_encode($categories);
$bookCounts_js = json_encode($bookCounts);

$dates = [];
$counts = [];
for ($i = 89; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $dates[] = $day;
    $res = $conn->query("SELECT COUNT(*) AS total FROM users WHERE DATE(created_at)='$day'");
    $row = $res->fetch_assoc();
    $counts[] = (int)$row['total'];
}
$dates_js = json_encode($dates);
$counts_js = json_encode($counts);


$orderStatuses = ['pending', 'confirmed', 'delivered', 'cancelled'];
$statusCounts = [];

foreach ($orderStatuses as $status) {
    $res = $conn->query("SELECT COUNT(*) AS total FROM tbl_orders WHERE status='$status'");
    $row = $res->fetch_assoc();
    $statusCounts[] = (int)$row['total'];
}

$statusCounts_js = json_encode($statusCounts);
$orderStatuses_js = json_encode($orderStatuses);

$monthlyRevenueLabels = [];
$monthlyRevenueData = [];

for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months")); 
    $monthlyRevenueLabels[] = date('M Y', strtotime($month)); 
    
    $res = $conn->query("
        SELECT IFNULL(SUM(b.price), 0) AS total
        FROM tbl_orders o
        JOIN tbl_books b ON o.bookid = b.bookid
        WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$month'
        AND o.status='confirmed'
    ");
    
    $row = $res->fetch_assoc();
    $monthlyRevenueData[] = (float)$row['total'];
}

$monthlyRevenueLabels_js = json_encode($monthlyRevenueLabels);
$monthlyRevenueData_js = json_encode($monthlyRevenueData);



if (isset($_POST['delete_listing'])) {
    $bookid = intval($_POST['bookid']);
    $delete_sql = "DELETE FROM tbl_books WHERE bookid = $bookid";

    if ($conn->query($delete_sql) === TRUE) {
        echo "<script>alert('Listing deleted successfully'); window.location.reload();</script>";
    } else {
        echo "<script>alert('Error deleting listing');</script>";
    }
}



  $query = "SELECT id, name, email, is_active FROM users WHERE role='courier'";
  $couriers_result = mysqli_query($conn, $query);
  if (!$couriers_result) {
    die("Query failed: " . $conn->error);
}


$payments_sql = "
        SELECT p.payment_id, o.orderid, u.name AS buyer_name, p.amount_paid, p.payment_method, p.payment_status, p.payment_date
        FROM tbl_payment_details p
        JOIN tbl_orders o ON p.order_id = o.orderid
        JOIN users u ON o.id = u.id
        ORDER BY p.payment_date DESC
      ";
      $payments_result = $conn->query($payments_sql);



?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="theme-color" content="#f2e3d5" />
  <title>Admin Dashboard | Biblio</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>

  <style>
    :root {
      --creamy-beige: #f2e3d5;
      --soft-white: #fdfdfb;
      --dark-gray: #2d2d2d;
      --accent: #9b6c47;
      --hover: #e2c7aa;
    }

    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: url('kk.png') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      overflow-x: hidden;
    }

    .container {
      display: flex;
      width: 100%;
    }

    .sidebar {
      width: 240px;
      background-color: var(--creamy-beige);
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

    .sidebar h2 {
      font-family: 'Poppins', sans-serif;
      color: var(--accent);
      font-size: 1.8rem;
      margin-bottom: 50px;
      text-align: center;
    }

    .sidebar a {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 25px 0;
      text-decoration: none;
      color: var(--dark-gray);
      font-weight: 600;
      font-size: 1.1rem;
      font-family: 'Poppins', sans-serif;
      background-color: white;
      border: 2px solid var(--accent);
      border-radius: 15px;
      padding: 12px 18px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    .sidebar a:hover {
      background-color: var(--hover);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }

    .sidebar a i {
      width: 20px;
      text-align: center;
    }

    .navbar {
      height: 80px;
      background-color: rgba(255, 255, 255, 0.6);
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
      font-size: 1.9rem;
      font-family: 'Poppins', sans-serif;
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

    .stats-row {
      display: flex;
      gap: 30px;
      margin-top: 30px;
      flex-wrap: wrap;
    }

    .stat-box {
      flex: 1 1 200px;
      padding: 20px;
      background: #fff9f1;
      border-radius: 20px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
      text-align: center;
      font-family: 'Poppins', sans-serif;
    }

    .stat-box .emoji {
      font-size: 2rem;
      margin-bottom: 10px;
    }

    .stat-box .value {
      font-size: 2rem;
      font-weight: bold;
      color: #3a2f25;
    }

    .stat-box .label {
      font-size: 1rem;
      color: #7a6d61;
    }
    table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 10px;
  margin-top: 20px;
  font-family: 'Poppins', sans-serif;
}

table thead {
  background-color: var(--creamy-beige);
}

table th, table td {
  padding: 14px 20px;
  text-align: left;
}

table th {
  font-size: 1rem;
  color: var(--dark-gray);
  border-bottom: 2px solid #ddd;
}

table tr {
  background-color: #fffdf9;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  transition: 0.3s ease;
}

table tr:hover {
  background-color: #f9efe3;
}

table td {
  font-size: 0.95rem;
  color: #333;
  border-top: 1px solid #eee;
}

.action-btn {
  background-color: var(--accent);
  color: white;
  border: none;
  padding: 6px 14px;
  font-size: 0.9rem;
  border-radius: 8px;
  cursor: pointer;
  margin-right: 6px;
  text-decoration: none;
  transition: 0.2s ease;
}

.action-btn:hover {
  background-color: var(--hover);
  color: white;
}

.delete-btn {
  background-color: #d9534f;
}

.delete-btn:hover {
  background-color: #c9302c;
}
.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Poppins', sans-serif;
    color: #ffffffff; 
}

.admin-table th, .admin-table td {
    border: 1px solid #d6e2d3;
    padding: 10px;
    text-align: left;
    color: #000000ff;
}

.admin-table th {
   background-color: var(--creamy-beige);
  color: var(--accent);
}
.admin-table tr {
    background-color: var(--creamy-beige);
}

.admin-table tr:nth-child(even) {
    background-color: #f7f2e9; 
}

.btn-activate, .btn-deactivate, .btn-delete {
    border: none;
    padding: 6px 10px;
    margin-right: 5px;
    cursor: pointer;
    border-radius: 5px;
    font-size: 0.9em;
}

.btn-activate {
    background-color: #8bbf88;
    color: white;
}

.btn-deactivate {
    background-color: #d6b85a;
    color: white;
}

.btn-delete {
    background-color: #d67373;
    color: white;
}
#manage-listings {
  font-family: 'Poppins', sans-serif;
  color: #3a2f25;
}

#manage-listings .card {
  background-color: var(--creamy-beige);
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
}

.improved-table {
  border-collapse: separate; 
  border-spacing: 0 10px; 
  width: 100%;
  font-family: 'Inter', sans-serif;
}

.improved-table th {
  background-color: #5D4A3E; 
  color: #F8F6F4; 
  text-align: left;
  padding: 12px 20px;
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
}

.improved-table td {
  background-color: #F8F6F4; 
  padding: 12px 20px;
}

.improved-table tr td:first-child {
  border-top-left-radius: 8px;
  border-bottom-left-radius: 8px;
}

.improved-table tr td:last-child {
  border-top-right-radius: 8px;
  border-bottom-right-radius: 8px;
}

.improved-table tr:hover td {
  background-color: #ECE6DB; 
}

.improved-table th, .improved-table td {
  transition: background-color 0.3s ease;
}


  </style>
</head>
<body>
  <div class="container">
    <div class="sidebar">
      <a href="#" onclick="showSection('dashboard')"><i class="fas fa-chart-line"></i> Dashboard</a>
      <a href="#" onclick="showSection('notifications')"><i class="fas fa-bell"></i> Notifications</a>
      <a href="#" onclick="showSection('manage-users')"><i class="fas fa-users"></i> Manage Users</a>
      <a href="#" onclick="showSection('manage-listings')"><i class="fas fa-book-open"></i> Manage Listings</a>
      <a href="#" onclick="showSection('courier')"><i class="fas fa-truck"></i> Courier Activity</a>
      <a href="#" onclick="showSection('couriers')"><i class="fas fa-comment-dots"></i> Courier Management</a>
      <a href="#" onclick="showSection('payments')"><i class="fas fa-credit-card"></i> Payments</a>
      
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
      <div class="navbar">
        <h1>Welcome, Admin</h1>
        <div class="user">
          <i class="fas fa-user-circle"></i> ADMIN
        </div>
      </div>

      <div id="dashboard" class="section active">
        <h2 style="font-family: 'Poppins', sans-serif; margin-left: 20px;">Dashboard Overview</h2>
        <div class="stats-row">
          <div class="stat-box">
            <div class="emoji">👤</div>
            <div class="value">
  <?php
    $result = $conn->query("SELECT COUNT(*) AS total FROM users");
    $row = $result->fetch_assoc();
    echo $row['total'];
  ?>
</div>

            <div class="label">Users</div>
          </div>
          <div class="stat-box">
            <div class="emoji">📖</div>
            <div class="value"><?php echo $totalListings; ?></div>
            <div class="label">Active Listings</div>
          </div>
          <div class="stat-box">
            <div class="emoji">🛍️</div>
            <div class="value"><?php echo $totalOrders; ?></div>
            <div class="label">Orders</div>
          </div>
          <div class="stat-box">
            <div class="emoji">🚚</div>
            <div class="value"><?php echo $deliveryCount; ?></div>
            <div class="label">Deliveries</div>
          </div>
        </div>
      
        <div style="height:50px;"></div>
        <hr style="border: 1px solid #e2c7aa; margin: 50px 0; border-radius: 5px;">

      <div id="top-sellers" style="margin-top:40px; margin-left:40px;">
  <h3 style="font-family: 'Poppins', sans-serif; margin-top:30px; color:#2e4a3c; font-size:1.5rem;">Top 5 Sellers</h3>
  <div class="top-sellers-grid" style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;">
    <?php
    if ($top_sellers_result && $top_sellers_result->num_rows > 0) {
        while ($seller = $top_sellers_result->fetch_assoc()) {
            $avgRating = $seller['avg_rating'] ? $seller['avg_rating'] : '--';
            $totalRatings = $seller['total_ratings'];
            $booksSold = $seller['books_sold'];
            echo "
            <div class='seller-card' style='flex:1 1 200px; padding:20px; background:#fff9f1; border-radius:20px; box-shadow:0 8px 25px rgba(0,0,0,0.05); text-align:center;'>
              <div class='seller-name' style='font-weight:600; font-size:1.1rem; margin-bottom:8px; color:#3a2f25;'>".htmlspecialchars($seller['seller_name'])."</div>
              <div class='books-sold' style='font-size:1rem; color:#7a6d61; margin-bottom:5px;'>Books Sold: $booksSold</div>
              <div class='avg-rating' style='font-size:0.9rem; color:#7a6d61; margin-bottom:5px;'>⭐ $avgRating ($totalRatings ratings)</div>
            </div>
            ";
        }
    } else {
        echo "<div style='color:#7a6d61; font-style:italic;'>No ratings yet</div>";
    }
    ?>
  </div>
</div>
<div style="height:50px;"></div>
<hr style="border: 1px solid #e2c7aa; margin: 50px 0; border-radius: 5px;">


<div id="top-buyers" style="margin-top:40px; margin-left:40px;">
  <h3 style="font-family: 'Poppins', sans-serif; margin-top:30px; color:#2e4a3c; font-size:1.5rem;">Top 5 Buyers</h3>
  <div class="top-buyers-grid" style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;">
    <?php
    if ($top_buyers_result && $top_buyers_result->num_rows > 0) {
        while ($buyer = $top_buyers_result->fetch_assoc()) {
            $ordersPlaced = $buyer['orders_placed'];
            echo "
            <div class='buyer-card' style='flex:1 1 200px; padding:20px; background:#fff9f1; border-radius:20px; box-shadow:0 8px 25px rgba(0,0,0,0.05); text-align:center;'>
              <div class='buyer-name' style='font-weight:600; font-size:1.1rem; margin-bottom:8px; color:#3a2f25;'>".htmlspecialchars($buyer['buyer_name'])."</div>
              <div class='orders-placed' style='font-size:1rem; color:#7a6d61;'>Confirmed Orders: $ordersPlaced</div>
            </div>
            ";
        }
    } else {
        echo "<div style='color:#7a6d61; font-style:italic;'>No confirmed orders yet</div>";
    }
    ?>
  </div>
</div>
<div style="height:50px;"></div>
<hr style="border: 1px solid #e2c7aa; margin: 50px 0; border-radius: 5px;">

<div style="display:flex; gap:120px; margin-top:40px; margin-left:20px; flex-wrap:wrap; align-items:flex-start;">
  
  <div style="background:#fff9f1; border-radius:20px; padding:20px; box-shadow:0 8px 25px rgba(0,0,0,0.05); flex:1 1 300px; max-width:400px;">
    <h3 style="font-family:'Poppins',sans-serif; color:#2e4a3c; text-align:center;">Books per Category</h3>
    <div style="height:250px;"> 
      <canvas id="categoryPieChart" style="max-height:100%; max-width:100%;"></canvas>
    </div>
  </div>


  <div style="background:#fff9f1; border-radius:20px; padding:20px; box-shadow:0 8px 25px rgba(0,0,0,0.05); flex:1 1 300px; max-width:400px;">
    <h3 style="font-family:'Poppins',sans-serif; color:#2e4a3c; text-align:center;">User Registrations Over Time</h3>
    <div style="height:250px;"> 
      <canvas id="registrationsChart" style="max-height:100%; max-width:100%;"></canvas>
    </div>
  </div>
</div>

<div style="display:flex; gap:120px; margin-top:40px; margin-left:20px; flex-wrap:wrap; align-items:flex-start;">
  
  <div style="background:#fff9f1; border-radius:20px; padding:20px; box-shadow:0 8px 25px rgba(0,0,0,0.05); flex:1 1 300px; max-width:400px;">
    <h3 style="font-family:'Poppins',sans-serif; color:#2e4a3c; text-align:center;">Orders by Status</h3>
    <div style="height:250px;"> 
      <canvas id="ordersStatusChart" style="max-height:100%; max-width:100%;"></canvas>
    </div>
  </div>

  
  <div style="background:#fff9f1; border-radius:20px; padding:20px; box-shadow:0 8px 25px rgba(0,0,0,0.05); flex:1 1 300px; max-width:400px;">
    <h3 style="font-family:'Poppins',sans-serif; color:#2e4a3c; text-align:center;">Revenue per Month</h3>
    <div style="height:250px;"> 
      <canvas id="monthlyRevenueChart" style="max-height:100%; max-width:100%;"></canvas>
    </div>
  </div>
</div>


  </div>




    <div id="manage-users" class="section">

  <h2 style="
  font-family: 'Poppins', sans-serif;
  font-size: 1.3rem;
  font-weight: 600;
  margin-left:40px;
  margin-bottom: 15px;
  color: #2e4a3c;"> Manage Users</h2>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Date Registered</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $users = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
      while ($u = $users->fetch_assoc()) {
        echo "<tr>
                <td>{$u['id']}</td>
                <td>{$u['name']}</td>
                <td>{$u['email']}</td>
                <td style='text-transform: capitalize;'>{$u['role']}</td>
                <td>{$u['created_at']}</td>
                <td>
                  <a href='delete-user.php?id={$u['id']}' onclick=\"return confirm('Are you sure you want to delete this user?');\" class='action-btn delete-btn'>Delete</a>

                
                </td>
              </tr>";
      }
      ?>
    </tbody>
  </table>
</div>

<div id="notifications" class="section">
  <h2 style="
  font-family: 'Poppins', sans-serif;
  font-size: 1.3rem;
  font-weight: 600;
  margin-left:40px;
  margin-bottom: 15px;
  color: #2e4a3c;">Notifications</h2>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Message</th>
        <th>Date</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
      
      $sql = "SELECT * FROM tbl_notification WHERE user_id = 1 ORDER BY created_at DESC"; 
      $result = $conn->query($sql);

      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              $rowStyle = $row['is_read'] ? "background:#f9f9f9;" : "background:#ffeaea;";
              ?>
              <tr style="<?php echo $rowStyle; ?>">
                <td><?php echo $row['notification_id']; ?></td>
                <td><?php echo htmlspecialchars($row['message']); ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td><?php echo $row['is_read'] ? "Read" : "Unread"; ?></td>
                <td>
                  <?php if ($row['is_read'] == 0): ?>
                    <form method='POST' action='mark_admin_notif.php' style='display:inline;'>
                      <input type='hidden' name='notif_id' value='<?php echo $row['notification_id']; ?>'>
                      <button type='submit' class='action-btn'>Mark as Read</button>
                    </form>
                  <?php else: ?>
                    <span style="color: gray; font-size: 12px;">Already Read</span>
                  <?php endif; ?>

                  <form method='POST' action='delete_admin_notif.php' style='display:inline;'>
                    <input type='hidden' name='notif_id' value='<?php echo $row['notification_id']; ?>'>
                    <button type='submit' class='action-btn delete-btn'>Delete</button>
                  </form>
                </td>
              </tr>
              <?php
          }
      } else {
          echo "<tr><td colspan='5' style='text-align:center;'>No notifications yet</td></tr>";
      }
      ?>
    </tbody>
  </table>
</div>



<div id="manage-listings" class="section">
      <div class="card">
    <h2>Manage Listings</h2>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Author</th>
                <th>Price</th>
                <th>Condition</th>
                <th>Seller</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT b.bookid, b.title, b.author, b.price, b.book_condition, b.image_url, b.is_active, u.name AS seller_name
                    FROM tbl_books b
                    JOIN users u ON b.sellerid = u.id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $imageFile = basename($row['image_url']);
                    $webPath = "uploads/" . $imageFile;
                    $status = $row['is_active'] ? 'Active' : 'Inactive';

                    echo "<tr>
                            <td><img src='" . htmlspecialchars($webPath) . "' alt='Book Image' style='width:70px; height:auto;'></td>
                            <td>" . html_entity_decode($row['title']) . "</td>
                            <td>" . htmlspecialchars($row['author']) . "</td>
                            <td>$" . htmlspecialchars($row['price']) . "</td>
                            <td>" . htmlspecialchars($row['book_condition']) . "</td>
                            <td>" . htmlspecialchars($row['seller_name']) . "</td>
                            <td>$status</td>
                            <td>
                            <form method='POST' onsubmit='return confirm(\"Are you sure you want to delete this listing?\");'>
                            <input type='hidden' name='bookid' value='" . $row['bookid'] . "'>
                            <button type='submit' name='delete_listing' style='background:#9F3D3D; color:#fff; border:none; padding:5px 10px; border-radius:5px; cursor:pointer;'>Delete</button>
                            </form>
                            </td>

                          </tr>";
                }
            } else {
                echo "<tr><td colspan='8'>No listings found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
          </div>
        

      <div id="courier" class="section">
        <h2 style="
  font-family: 'Poppins', sans-serif;
  font-size: 1.3rem;
  font-weight: 600;
  margin-left:40px;
  margin-bottom: 15px;
  color: #2e4a3c;">
  Courier Activity – Assign Order
</h2>

         <table>
    <thead>
      <tr>
        <th>Order ID</th>
        <th>Book</th>
        <th>Buyer</th>
        <th>Status</th>
        <th>Assign Courier</th>
      </tr>
    </thead>
    <tbody>
      <?php
      
      $orders_sql = "
        SELECT o.orderid, b.title AS book_title, u.name AS buyer_name, o.status
        FROM tbl_orders o
        JOIN tbl_books b ON o.bookid = b.bookid
        JOIN users u ON o.id = u.id
        WHERE o.status = 'pending'
        ORDER BY o.order_date DESC
      ";
      $orders_result = $conn->query($orders_sql);

      
      $couriers_sql = "SELECT id, name FROM users WHERE role='courier'";
      $couriers_result = $conn->query($couriers_sql);

      if ($orders_result->num_rows > 0) {
        while ($order = $orders_result->fetch_assoc()) {
          echo "<tr>
            <td>{$order['orderid']}</td>
            <td>{$order['book_title']}</td>
            <td>{$order['buyer_name']}</td>
            <td>{$order['status']}</td>
            <td>
              <form method='POST' action='assign_courier.php'>
                <input type='hidden' name='order_id' value='{$order['orderid']}'>
                <select name='courier_id' required style='
      padding:6px 10px;
      border:1px solid #ccc;
      border-radius:6px;
      background:#fff;
      font-size:14px;
      margin-right:8px;
      cursor:pointer;'>
                  <option value=''>Select Courier</option>";
                  
                  mysqli_data_seek($couriers_result, 0);
                  while ($c = $couriers_result->fetch_assoc()) {
                    echo "<option value='{$c['id']}'>{$c['name']}</option>";
                  }
              echo "</select>
                <button type='submit' style='
            padding: 6px 12px; 
            background-color: #5D4A3E; 
            color: #fff; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer;
            font-weight: 500;
        '>Assign</button>
              </form>
            </td>
          </tr>";
        }
      } else {
        echo "<tr><td colspan='5'>No pending orders</td></tr>";
      }
      ?>
    </tbody>
  </table>
      

      <h3 style="
font-family: 'Poppins', sans-serif;
font-size: 1.1rem;
font-weight: 600;
margin-left:40px;
margin-top:40px;
margin-bottom: 15px;
color: #2e4a3c;">
Declined Orders – Needs Reassignment
</h3>

<table>
  <thead>
    <tr>
      <th>Order ID</th>
      <th>Book</th>
      <th>Buyer</th>
      <th>Previous Courier</th>
      <th>Reassign To</th>
    </tr>
  </thead>
  <tbody>
<?php

$declined_sql = "
SELECT 
  oa.assignment_id,
  o.orderid,
  b.title AS book_title,
  u_buyer.name AS buyer_name,
  u_courier.name AS courier_name,
  oa.courier_id
FROM tbl_order_assignments oa
JOIN tbl_orders o ON oa.order_id = o.orderid
JOIN tbl_books b ON o.bookid = b.bookid
JOIN users u_buyer ON o.id = u_buyer.id
JOIN users u_courier ON oa.courier_id = u_courier.id
WHERE oa.status='declined'
";
$declined_result = $conn->query($declined_sql);

if ($declined_result && $declined_result->num_rows > 0) {
  while ($row = $declined_result->fetch_assoc()) {
    echo "<tr>
      <td>{$row['orderid']}</td>
      <td>".html_entity_decode($row['book_title'])."</td>
      <td>".htmlspecialchars($row['buyer_name'])."</td>
      <td>".htmlspecialchars($row['courier_name'])."</td>
      <td>
        <form method='POST' action='reassign_courier.php'>
          <input type='hidden' name='assignment_id' value='{$row['assignment_id']}'>
          <select name='new_courier_id' required style=\"
            padding:6px 10px;border:1px solid #ccc;border-radius:6px;background:#fff;font-size:14px;margin-right:8px;cursor:pointer;\">
            <option value=''>Select Courier</option>";
            
            $couriers_sql = "SELECT id,name FROM users WHERE role='courier' AND id != ?";
            $stmt_c = $conn->prepare($couriers_sql);
            $stmt_c->bind_param("i", $row['courier_id']);
            $stmt_c->execute();
            $couriers = $stmt_c->get_result();
            while ($c = $couriers->fetch_assoc()) {
              echo "<option value='{$c['id']}'>{$c['name']}</option>";
            }
    echo "  </select>
          <button type='submit' style='
            padding: 6px 12px; 
            background-color: #5D4A3E; 
            color: #fff; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer;
            font-weight: 500;
        '>Assign</button>
        </form>
      </td>
    </tr>";
  }
} else {
  echo "<tr><td colspan='5'>No declined orders at the moment</td></tr>";
}
?>
  </tbody>
</table>
</div>



<div id="payments" class="section" style="padding:20px;">
  <h2 style="font-family: 'Poppins', sans-serif; font-size:1.3rem; font-weight:600; margin-left:40px; color:#2e4a3c;">
    Payments Overview
  </h2>

  <table class="improved-table" style="width:90%; margin-left:40px;">
    <thead>
      <tr>
        <th>Transaction ID</th>
        <th>Order ID</th>
        <th>Buyer</th>
        <th>Amount</th>
        <th>Payment Method</th>
        <th>Status</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php

      if ($payments_result && $payments_result->num_rows > 0) {
        while($p = $payments_result->fetch_assoc()) {
          echo "<tr>
                  <td>{$p['payment_id']}</td>
                  <td>{$p['orderid']}</td>
                  <td>".htmlspecialchars($p['buyer_name'])."</td>
                  <td>$".number_format($p['amount_paid'],2)."</td>
                  <td>".htmlspecialchars($p['payment_method'])."</td>
                  <td>".htmlspecialchars($p['payment_status'])."</td>
                  <td>{$p['payment_date']}</td>
                </tr>";
        }
      } else {
        echo "<tr><td colspan='7' style='text-align:center;'>No payment records found</td></tr>";
      }
      ?>
    </tbody>
  </table>
</div>
    

    <div id="couriers" class="section" style="padding:20px;">
  <h2 style="
    font-family: 'Poppins', sans-serif;
    font-size: 1.3rem;
    font-weight: 600;
    margin-left:40px;
    margin-bottom: 15px;
    color: #2e4a3c;">Courier Management</h2>

  <?php
  
  $couriers_result = $conn->query("SELECT id, name, email FROM users WHERE role='courier'");
  ?>

  <table class="improved-table" style="width:90%; margin-left:40px; border-collapse: collapse; font-family: 'Inter', sans-serif;">
      <thead>
          <tr style="background-color:#B69E59; color:white;">
              <th style="padding:10px; border:1px solid #ddd;">Name</th>
              <th style="padding:10px; border:1px solid #ddd;">Email</th>
              <th style="padding:10px; border:1px solid #ddd;">Actions</th>
          </tr>
      </thead>
      <tbody>
    <?php 
    if($couriers_result->num_rows > 0){
        while($row = $couriers_result->fetch_assoc()): ?>
            <tr style="border:1px solid #ddd; text-align:center;">
                <td style="padding:10px;"><?php echo htmlspecialchars($row['name']); ?></td>
                <td style="padding:10px;"><?php echo htmlspecialchars($row['email']); ?></td>
                <td style="padding:10px;">
                    <a href="delete_courier.php?id=<?php echo $row['id']; ?>" 
                       style="padding:5px 10px; background:#9F3D3D; color:white; text-decoration:none; border-radius:5px;"
                       onclick="return confirm('Are you sure you want to delete this courier?');">Delete</a>
                </td>
            </tr>
    <?php endwhile; 
    } else {
        echo "<tr><td colspan='3' style='text-align:center;'>No couriers found.</td></tr>";
    }
    ?>
  </tbody>
  </table>

  
  <h3 style="font-family: 'Poppins', sans-serif; margin-left:40px; margin-top:40px; color:#2e4a3c;">Assigned Deliveries</h3>

  <?php
  $deliveries_result = $conn->query("
    SELECT oa.assignment_id AS assignment_id, u.name AS courier_name, b.title, u2.name AS buyer_name, oa.status, oa.assigned_at
    FROM tbl_order_assignments oa
    JOIN users u ON u.id = oa.courier_id
    JOIN tbl_orders o ON o.orderid = oa.order_id
    JOIN users u2 ON u2.id = o.id
    JOIN tbl_books b ON b.bookid = o.bookid
    ORDER BY oa.assigned_at DESC
  ");
  ?>

  <table class="improved-table" style="width:95%; margin-left:40px; margin-top:10px; border-collapse: collapse; font-family: 'Inter', sans-serif;">
      <thead>
          <tr style="background-color:#5D4A3E; color:white;">
              <th style="padding:10px; border:1px solid #ddd;">Courier</th>
              <th style="padding:10px; border:1px solid #ddd;">Book</th>
              <th style="padding:10px; border:1px solid #ddd;">Buyer</th>
              <th style="padding:10px; border:1px solid #ddd;">Status</th>
              <th style="padding:10px; border:1px solid #ddd;">Assigned On</th>
          </tr>
      </thead>
      <tbody>
    <?php 
    if($deliveries_result->num_rows > 0){
        while($row = $deliveries_result->fetch_assoc()): ?>
            <tr style="border:1px solid #ddd; text-align:center;">
                <td style="padding:10px;"><?php echo htmlspecialchars($row['courier_name']); ?></td>
                <td style="padding:10px;"><?php echo html_entity_decode($row['title']); ?></td>
                <td style="padding:10px;"><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                <td style="padding:10px;"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></td>
                <td style="padding:10px;"><?php echo htmlspecialchars(date('d M Y', strtotime($row['assigned_at']))); ?></td>
            </tr>
    <?php endwhile; 
    } else {
        echo "<tr><td colspan='5' style='text-align:center;'>No assigned deliveries found.</td></tr>";
    }
    ?>
  </tbody>
  </table>
</div>

                  </div>
                  </div>
      

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    function showSection(id) {
      const sections = document.querySelectorAll('.section');
      sections.forEach(sec => sec.classList.remove('active'));
      document.getElementById(id).classList.add('active');
    }


    const ctxCat = document.getElementById('categoryPieChart').getContext('2d');
const categoryChart = new Chart(ctxCat, {
    type: 'pie',
    data: {
        labels: <?php echo $categories_js; ?>,
        datasets: [{
            label: 'Books',
            data: <?php echo $bookCounts_js; ?>,
            backgroundColor: [
                '#B69E59', '#5D4A3E', '#9F3D3D', '#2D2D2D', '#F8F6F4',
                '#D9B382', '#A66C6C', '#7A5E4D', '#C8A69F', '#6B7D7D'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    color: '#3a2f25',
                    font: { family: 'Poppins', size: 14 }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.raw + ' books';
                    }
                }
            }
        }
    }
});


const regCtx = document.getElementById('registrationsChart').getContext('2d');


  const labels = <?php echo $dates_js; ?>;
const data = <?php echo $counts_js; ?>;
 

  new Chart(regCtx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Registrations',
        data: data,
        fill: true,
        backgroundColor: 'rgba(155,108,71,0.2)',
        borderColor: 'rgba(155,108,71,1)',
        tension: 0.3,
        pointRadius: 5,
        pointBackgroundColor: 'rgba(155,108,71,1)',
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        }
      }
    }
  });

  const ctxOrders = document.getElementById('ordersStatusChart').getContext('2d');
new Chart(ctxOrders, {
    type: 'doughnut',
    data: {
        labels: <?php echo $orderStatuses_js; ?>,
        datasets: [{
            data: <?php echo $statusCounts_js; ?>,
            backgroundColor: ['#B69E59', '#9F3D3D', '#5D4A3E', '#2D2D2D'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right' }
        }
    }
});

const revenueLabels = <?php echo $monthlyRevenueLabels_js; ?>;
const revenueData = <?php echo $monthlyRevenueData_js; ?>;

new Chart(document.getElementById('monthlyRevenueChart'), {
    type: 'line', 
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Revenue',
            data: revenueData,
            fill: true,
            backgroundColor: 'rgba(155,108,71,0.3)',
            borderColor: 'rgba(155,108,71,1)',
            tension: 0.3
        }]
    },
    options: {
        scales: {
            x: {
                type: 'category' 
            },
            y: {
                beginAtZero: true
            }
        }
    }
});
function showToast(message) {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.position = 'fixed';
    toast.style.top = '20px';              
    toast.style.left = '50%';              
    toast.style.transform = 'translateX(-50%)'; 
    toast.style.background = '#9b6c47';
    toast.style.color = '#fff';
    toast.style.padding = '10px 20px';
    toast.style.borderRadius = '5px';
    toast.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
    toast.style.zIndex = 9999;
    toast.style.opacity = 0;
    toast.style.transition = 'opacity 0.5s';
    document.body.appendChild(toast);

    setTimeout(() => toast.style.opacity = 1, 100);
    setTimeout(() => {
        toast.style.opacity = 0;
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}




 document.addEventListener("DOMContentLoaded", () => {
    const params = new URLSearchParams(window.location.search);
    if (params.has('msg')) {
        const msg = params.get('msg');
        if (msg === 'reassigned') {
            showToast("✅ Delivery reassigned successfully!");
            
            params.delete('msg');
            window.history.replaceState({}, '', location.pathname);
        }
    }
});





  </script>
</body>
</html>
