<?php
session_start();
$bookid = isset($_GET['bookid']) ? intval($_GET['bookid']) : 0;
$user_id = $_SESSION['id']; 
// Fetch book details
$conn = new mysqli('localhost', 'root', '', 'BIBLIO');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$stmt = $conn->prepare("SELECT title, price FROM tbl_books WHERE bookid = ?");
$stmt->bind_param("i", $bookid);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();
$stmt = $conn->prepare("SELECT * FROM tbl_negotiation WHERE bookid = ? AND buyerid = ? ORDER BY updated_at DESC");
$stmt->bind_param("ii", $bookid, $user_id);
$stmt->execute();
$negotiations = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Negotiate Price</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(rgba(192,208,192,0.7), rgba(192,208,192,0.7)), url('bdet.png') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .negotiation-box {
            background-color: #f8f6f4;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .negotiation-box h2 {
            margin-bottom: 20px;
            color: #5D4A3E;
        }

        .negotiation-box .book-info {
            margin-bottom: 15px;
            font-size: 14px;
            color: #5D4A3E;
        }

        .negotiation-box input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #B69E59;
            border-radius: 6px;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .negotiation-box button {
            padding: 12px 25px;
            background-color: #8BC34A;
            border: none;
            border-radius: 6px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .negotiation-box button:hover {
            background-color: #7aae3f;
        }
    </style>
</head>
<body>
    <div class="negotiation-box">
        <h2>Negotiate Price</h2>
        <div class="book-info">
            <strong><?php echo html_entity_decode($book['title']); ?></strong><br>
            Current Price: ₹<?php echo htmlspecialchars($book['price']); ?>
        </div>

        <form method="post" action="submit_negotiation.php">
            <input type="hidden" name="bookid" value="<?php echo $bookid; ?>">
            <input type="number" name="offer_price" placeholder="Enter your offer price" min="1" required>
            <button type="submit">Submit Offer</button>
        </form>
        <?php if ($negotiations->num_rows > 0): ?>
            <h3>Your Negotiation History</h3>
            <?php while ($row = $negotiations->fetch_assoc()): ?>
                <div style="border:1px solid #ccc; padding:10px; margin:10px 0; text-align:left;">
                    <strong>Your Offer:</strong> ₹<?php echo htmlspecialchars($row['offer_price']); ?><br>
                    <strong>Status:</strong> <?php echo htmlspecialchars($row['status']); ?>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

    </div>
</body>
</html>
<?php $conn->close(); ?>