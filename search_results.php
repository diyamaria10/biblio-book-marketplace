<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

// Connect to DB
$conn = new mysqli('localhost', 'root', '', 'BIBLIO');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");
if (!$conn || $conn->connect_errno) {
    die("Database connection not available.");
}

$where = ["(b.is_active = 1 OR b.is_active = 0)"];
$params = [];
$types = "";
// Fetch categories for dropdown
$catResult = $conn->query("SELECT category_id, name FROM tbl_categories ORDER BY name ASC");

// Get filters from GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$condition = isset($_GET['condition']) ? $_GET['condition'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'recent';


// Keyword search
if (!empty($search)) {
    $where[] = "(b.title LIKE ? OR b.author LIKE ?)";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
    $types .= "ss";
}

// Category filter
if ($category > 0) {
    $where[] = "b.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

// Price filters
if ($minPrice > 0) {
    $where[] = "b.price >= ?";
    $params[] = $minPrice;
    $types .= "d";
}
if ($maxPrice > 0) {
    $where[] = "b.price <= ?";
    $params[] = $maxPrice;
    $types .= "d";
}

// Condition filter
if ($condition !== 'all') {
    $where[] = "b.book_condition = ?";
    $params[] = $condition;
    $types .= "s";
}

// Sorting
switch ($sort) {
    case 'price_low':
        $orderBy = "b.price ASC";
        break;
    case 'price_high':
        $orderBy = "b.price DESC";
        break;
    case 'oldest':
        $orderBy = "b.bookid ASC"; // oldest first
        break;
    case 'recent':
    default:
        $orderBy = "b.bookid DESC";
}

$sql = "SELECT 
    b.*, 
    c.name AS category_name,
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM tbl_orders o
            INNER JOIN tbl_order_assignments oa ON o.orderid = oa.order_id
            INNER JOIN tbl_delivery_status ds ON ds.assignment_id = oa.assignment_id
            WHERE o.bookid = b.bookid
              AND ds.status IN ('Accepted', 'Delivered', 'Completed')
        ) THEN 1 
        ELSE 0 
    END AS is_sold
FROM tbl_books b
LEFT JOIN tbl_categories c ON b.category_id = c.category_id
WHERE " . implode(' AND ', $where) . "
ORDER BY $orderBy";





$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch books
$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Results</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="navbar.css?v=<?php echo time(); ?>">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background:
                linear-gradient(rgba(224,236,228,0.85), rgba(224,236,228,0.85)),
                url('bggg.jpeg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            color: #2D2D2D;
        }
        h1 {
            text-align: center;
            background-color: rgba(255,255,255,0.9);
            padding: 20px;
            border-radius: 12px;
            width: fit-content;
            margin: 80px auto 30px auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            font-weight: 600;
            color: #5D4A3E;
        }
        .book-container {
            display: flex;
            flex-direction: column;
            gap: 25px;
            width: 90%;
            max-width: 1100px;
            margin: auto;
        }
        .book-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: rgba(255,255,255,0.95);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .book-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .book-image img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .book-info {
            flex: 1;
            padding: 0 20px;
        }
        .book-info p.category {
            color: gray;
            font-size: 14px;
            margin: 0;
        }
        .book-info h2 {
            margin: 6px 0;
            color: #5D4A3E;
            font-weight: 600;
        }
        .book-info p.description {
            margin: 4px 0;
            color: #555;
            font-size: 15px;
        }
        .book-action {
            text-align: right;
            min-width: 120px;
        }
        .book-action p.price {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            color: #9F3D3D;
        }
        .book-action a.details-btn {
            display: inline-block;
            margin-top: 10px;
            background-color: #B69E59;
            color: white;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s, transform 0.2s;
        }
        .book-action a.details-btn:hover {
            background-color: #9F3D3D;
            transform: translateY(-2px);
        }
        @media(max-width: 900px){
            .book-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .book-info {
                padding: 15px 0;
            }
            .book-action {
                min-width: unset;
            }
        }
        .sold-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #b14b4b;
    color: #fff;
    padding: 12px 18px;
    font-weight: 800;
    border-radius: 8px;
    text-transform: uppercase;
    font-size: 16px;
    letter-spacing: 1px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
    border: 2px solid #fff;
    transform: rotate(-8deg);
}


.book-image {
    position: relative; /* ensures the badge is positioned correctly */
}

    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <?php if (!empty($search)): ?>
    <h1>Search Results for "<?php echo htmlspecialchars($search); ?>"</h1>
<?php else: ?>
    <h1>Search Results</h1>
<?php endif; ?>

    <div class="book-container">
    <?php if (!empty($books)): ?>
        <?php foreach ($books as $book): ?>
            <div class="book-card">
                <div class="book-image" style="position: relative;">
                    <?php 
                        $filename = basename($book['image_url']); 
                        $is_own_listing = isset($_SESSION['id']) && $book['sellerid'] == $_SESSION['id'];
                        $is_sold = ($book['is_sold'] == 1);
                    ?>

                    <img src="uploads/<?php echo htmlspecialchars($filename); ?>" alt="<?php echo html_entity_decode($book['title']); ?>">

                    <!-- ✅ Your Listing badge -->
                    <?php if ($is_own_listing): ?>
                        <span style="
                            position: absolute;
                            top: 10px;
                            left: 10px;
                            background-color: #5D4A3E;
                            color: #F8F6F4;
                            padding: 5px 10px;
                            border-radius: 6px;
                            font-size: 13px;
                            font-weight: 600;
                            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                            z-index: 10;
                        ">
                            Your Listing
                        </span>
                    <?php endif; ?>

                    <!-- ✅ SOLD badge, moves down slightly if it's your listing -->
                    <?php if ($is_sold): ?>
                        <span class="sold-badge" style="<?php echo $is_own_listing ? 'top: 40px; left: 10px;' : ''; ?>">
                            SOLD
                        </span>
                    <?php endif; ?>

                </div>

                    <div class="book-info">
                        <p class="category"><?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?> | <?php echo htmlspecialchars($book['book_condition']); ?></p>
                        <h2><?php echo html_entity_decode($book['title']); ?></h2>
                        <p class="description"><?php echo htmlspecialchars($book['author']); ?> — <?php echo htmlspecialchars(substr($book['description'], 0, 80)); ?>...</p>
                    </div>
                    <div class="book-action">
    <p class="price">₹<?php echo htmlspecialchars($book['price']); ?></p>
    <?php if (isset($_SESSION['id'])): ?>
        <!-- Logged in user: show details -->
        <a href="book_details.php?bookid=<?php echo $book['bookid']; ?>" class="details-btn">Details</a>
    <?php else: ?>
        <!-- Not logged in: show login/signup links -->
        <a href="login.php" class="details-btn">Login to view</a>
        <br>
        <a href="signup.php" class="details-btn" style="margin-top:8px; background:#5D4A3E;">Sign Up</a>
    <?php endif; ?>
</div>


                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; font-size:18px; color:#5D4A3E; margin:40px 0;">No books found.</p>
        <?php endif; ?>
    </div>
<script src="navbar.js"></script>
</body>
</html>
