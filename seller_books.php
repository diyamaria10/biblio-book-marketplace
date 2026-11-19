<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB connection
$conn = new mysqli('localhost', 'root', '', 'BIBLIO');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sellerid = isset($_GET['sellerid']) ? intval($_GET['sellerid']) : 0;

// Fetch seller info
$stmt = $conn->prepare("SELECT u.name, u.email, u.created_at, a.phone_number FROM users u LEFT JOIN tbl_user_address a ON u.id=a.user_id WHERE u.id = ?");
$stmt->bind_param("i", $sellerid);
$stmt->execute();
$seller_res = $stmt->get_result();
$seller = $seller_res->fetch_assoc();



// Count confirmed orders for this seller
$stmt = $conn->prepare("
    SELECT COUNT(*) AS sold_count 
    FROM tbl_orders o
    JOIN tbl_books b ON o.bookid = b.bookid
    WHERE b.sellerid = ? AND o.status = 'delivered'
");
$stmt->bind_param("i", $sellerid);
$stmt->execute();
$result = $stmt->get_result();
$sold_count = $result->fetch_assoc()['sold_count'];
$stmt->close();

// Fetch seller's books
$stmt = $conn->prepare("
    SELECT 
        b.bookid, 
        b.title, 
        b.price, 
        b.image_url, 
        b.book_condition, 
        c.name AS category_name,
        IFNULL(AVG(r.rating), 0) AS avg_rating,
        COUNT(r.rating) AS total_ratings
    FROM tbl_books b
    LEFT JOIN tbl_categories c ON b.category_id = c.category_id
    LEFT JOIN tbl_orders o ON o.bookid = b.bookid
    LEFT JOIN tbl_seller_ratings r ON r.order_id = o.orderid
    WHERE b.sellerid = ?
    GROUP BY b.bookid, b.title, b.price, b.image_url, b.book_condition, c.name
    ORDER BY b.bookid DESC
");

$stmt->bind_param("i", $sellerid);
$stmt->execute();
$books = $stmt->get_result();
$stmt->close();


// Calculate overall rating for seller
$stmt = $conn->prepare("
    SELECT IFNULL(AVG(r.rating),0) AS overall_rating, COUNT(r.rating) AS total_ratings
    FROM tbl_seller_ratings r
    JOIN tbl_orders o ON r.order_id = o.orderid
    JOIN tbl_books b ON o.bookid = b.bookid
    WHERE b.sellerid = ?
");
$stmt->bind_param("i", $sellerid);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$overallRating = number_format($res['overall_rating'], 1);
$totalRatings = $res['total_ratings'];
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($seller['name']); ?>'s Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="navbar.css?v=<?php echo time(); ?>">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(rgba(224, 236, 228, 0.85), rgba(224, 236, 228, 0.85)), url('bdet.png') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding-top: 80px;
            color: #2D2D2D;
        }

        .container {
            max-width: 1100px;
            margin: 60px auto;
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .account-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            background: rgba(255, 235, 205, 0.5);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #9F3D3D;
        }

        .account-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            background: #ccc;
        }

        .account-header .info h2 {
            margin: 0;
            color: #5D4A3E;
        }

        .account-header .info p {
            margin: 4px 0;
            color: #333;
        }

        .account-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .account-stats .stat {
            background: #fff;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
            text-align: center;
            flex: 1;
        }

        .account-stats .stat h3 {
            margin: 0;
            color: #9F3D3D;
        }

        .account-stats .stat p {
            margin: 4px 0;
            color: #333;
        }

        .book-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .book-card {
            flex: 1 1 220px;
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }

        .book-card:hover {
            transform: translateY(-5px);
        }

        .book-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .book-card p {
            font-weight: 600;
            color: #333;
            margin: 6px 0;
        }

        .book-card span {
            color: #9F3D3D;
            font-weight: 700;
        }

        .book-card a {
            text-decoration: none;
            color: inherit;
        }
        .profile-pic {
    width: 80px;
    height: 80px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    background: #ccc;
    text-align: center;
}

        @media(max-width: 900px){
            .book-card { flex: 1 1 100%; }
            .account-stats { flex-direction: column; }
            .account-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
   <div class="account-header">
    <div class="profile-pic" style="font-size: 60px;">👤</div>
    <div class="info">
        <h2><?php echo htmlspecialchars($seller['name']); ?></h2>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($seller['email']); ?></p>
        <p><strong>Joined:</strong> <?php echo date('d M Y', strtotime($seller['created_at'] ?? 'now')); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($seller['phone_number'] ?? 'Not provided'); ?></p>

    </div>
</div>


    <div class="account-stats">
        <div class="stat">
            <h3><?php echo $books->num_rows; ?></h3>
            <p>Total Books</p>
        </div>
        <div class="stat">
    <h3><?php echo $sold_count; ?></h3>
    <p>Books Sold</p>
</div>
        <div class="stat">
            <h3><?php echo $overallRating; ?> ★ </h3>
            <p><?php echo $totalRatings; ?> Rating(s) </p>
        </div>
    </div>

    <div class="book-grid">
        <?php if ($books && $books->num_rows > 0): ?>
            <?php while($book = $books->fetch_assoc()): ?>
                <div class="book-card">
                    <a href="book_details.php?bookid=<?php echo $book['bookid']; ?>">
                        <img src="uploads/<?php echo htmlspecialchars(basename($book['image_url'])); ?>" alt="Book Image">
                        <p><?php echo html_entity_decode($book['title']); ?></p>
                        <p><em><?php echo htmlspecialchars($book['category_name'] ?? 'No Category'); ?></em></p>
                        <span>₹<?php echo htmlspecialchars($book['price']); ?></span>
                    </a>
                    <?php
                    $avg= number_format($book['avg_rating'],1);
                    $count= $book['total_ratings'];
                    ?>
                    <p style="margin-top:8px; font-size:0.9em; color:$555;">
                        Rating: <?php echo $avg; ?> ★ (<?php echo $count; ?> reviews)
            </p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No books found from this seller.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

