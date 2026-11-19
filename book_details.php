<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to DB
$conn = new mysqli('localhost', 'root', '', 'BIBLIO');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$bookid = isset($_GET['bookid']) ? intval($_GET['bookid']) : 0;


$stmt = $conn->prepare("
  SELECT 
    b.bookid,
    b.title,
    b.price,
    b.book_condition,
    b.image_url,
    b.author,
    b.edition,
    b.category_id,
    c.name AS category_name,
    u.name AS seller_name,
    u.email AS seller_email,
    u.id AS sellerid,
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM tbl_orders o
            JOIN tbl_order_assignments oa ON o.orderid = oa.order_id
            JOIN tbl_delivery_status ds ON ds.assignment_id = oa.assignment_id
            WHERE o.bookid = b.bookid
              AND ds.status = 'Accepted'
        ) THEN 1 
        ELSE 0 
    END AS is_sold
  FROM tbl_books b
  LEFT JOIN tbl_categories c ON b.category_id = c.category_id
  LEFT JOIN users u ON b.sellerid = u.id
  WHERE b.bookid = ?
");
$stmt->bind_param("i", $bookid);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

// fetch related books
$stmt_related = $conn->prepare("
    SELECT b.bookid, b.title, b.price, b.image_url
    FROM tbl_books b
    WHERE b.category_id = ?
      AND b.bookid != ?
    LIMIT 4
");
$stmt_related->bind_param("ii", $book['category_id'], $bookid);
$stmt_related->execute();
$related_books = $stmt_related->get_result();
$stmt_related->close();
$is_own_book = isset($_SESSION['id']) && $book['sellerid'] == $_SESSION['id'];

$conn->close();

$is_sold = !empty($book['is_sold']) && $book['is_sold'] == 1;
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo html_entity_decode($book['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="navbar.css?v=<?php echo time(); ?>">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: 
                linear-gradient(rgba(224, 236, 228, 0.85), rgba(224, 236, 228, 0.85)),
                url('bdet.png') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding-top: 80px;
            color: #2D2D2D;
        }
        .container {
            max-width: 1100px;
            margin: 60px auto;
            background: rgba(255,255,255,0.95);
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .image-section { flex: 1 1 400px; text-align: center; }
        .image-section img {
            width: 100%;
            max-width: 400px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
        }
        .thumbnails { display: flex; gap: 12px; justify-content: center; margin-top: 15px; }
        .thumbnails img {
            width: 70px; height: 70px; object-fit: cover;
            border-radius: 6px; cursor: pointer;
            border: 2px solid #B69E59;
            transition: transform 0.2s, border 0.2s;
        }
        .thumbnails img:hover { transform: scale(1.1); border-color: #9F3D3D; }
        .info-section { flex: 1 1 500px; display: flex; flex-direction: column; gap: 18px; }
        .book-title { font-weight: 700; font-size: 2.2rem; color: #5D4A3E; margin: 0; }
        .book-price { font-size: 1.6rem; font-weight: 700; color: #9F3D3D; margin: 8px 0 15px 0; }
        .book-meta p { margin: 4px 0; font-size: 15px; color: #444; }

        .button {
            background-color: #9F3D3D;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 16px;
            padding: 12px 22px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            border: none;
            outline: none;
            transition: background 0.3s, transform 0.2s;
        }
        .button:hover { background-color: #9F3D3D; transform: translateY(-2px); }
        .button.chat { background-color: #4CAF50; }
        .button.chat:hover { background-color: #388E3C; }
        .button.cart { background-color: #FF9800; }
        .button.cart:hover { background-color: #FB8C00; transform: translateY(-2px); }

        .seller-info {
            background: rgba(255, 235, 205, 0.5);
            border-left: 4px solid #9F3D3D;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        .seller-info h3 { margin-top: 0; color: #9F3D3D; }
        .seller-info p { margin: 6px 0; font-size: 16px; color: #333; }

.related-books {
    margin-top: 50px;
    background: #fff;
    border-radius: 15px;
    padding: 30px 40px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}

.related-books h2 {
    font-size: 1.8rem;
    margin-bottom: 25px;
    color: #5D4A3E;
}

.related-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    justify-content: flex-start;
}

.related-item {
    flex: 1 1 200px;
    background: transparent; /* remove the white bg from individual cards */
    padding: 10px;
    text-align: center;
    transition: transform 0.2s;
}

.related-item img {
    max-width: 100%;
    max-height: 220px;
    border-radius: 8px;
    margin-bottom: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

/* Full-screen overlay */
#neg-modal {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(45,45,45,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 999;
    backdrop-filter: blur(3px);
}

/* Modal box */
.negotiation-box {
    background-color: #fdfdfb; /* soft off-white */
    padding: 35px 40px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    max-width: 400px;
    width: 90%;
    text-align: center;
    font-family: 'Inter', sans-serif;
    position: relative;
    transition: transform 0.3s ease, opacity 0.3s ease;
}

/* Heading */
.negotiation-box h3 {
    font-family: 'DM Serif Display', serif;
    font-size: 1.8rem;
    color: #5D4A3E; /* espresso brown */
    margin-bottom: 15px;
}

/* Book info */
.book-info {
    margin-bottom: 20px;
    font-size: 1rem;
    color: #2D2D2D;
    line-height: 1.4;
}

/* Highlight book title */
.negotiation-box strong {
    color: #B69E59; /* mustard gold accent */
    font-weight: 600;
}

/* Input field */
#neg-form input[type="number"] {
    width: 80%;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #b7c9a8; /* sage green border accent */
    font-size: 1rem;
    margin-bottom: 15px;
    color: #333;
    transition: border 0.3s ease, box-shadow 0.3s ease;
}

#neg-form input[type="number"]:focus {
    border-color: #9F3D3D; /* warm maroon accent on focus */
    outline: none;
    box-shadow: 0 0 5px rgba(159,61,61,0.3);
}

/* Submit button */
#neg-form button[type="submit"] {
    background-color: #9F3D3D; /* warm maroon */
    color: #fdfdfb;
    border: none;
    border-radius: 25px;
    padding: 12px 20px;
    font-size: 1rem;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

#neg-form button[type="submit"]:hover {
    background-color: #B69E59; /* mustard hover */
    transform: translateY(-2px);
}

/* Cancel button */
#close-modal {
    margin-top: 12px;
    background-color: #fdfdfb; /* soft off-white */
    color: #5D4A3E; /* espresso text */
    padding: 10px 18px;
    border: 1px solid #b7c9a8; /* sage border */
    border-radius: 20px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

#close-modal:hover {
    background-color: #b7c9a8; /* sage hover */
    color: #fdfdfb;
    transform: translateY(-2px);
}



    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

<div class="container">
    <div class="image-section" style="position: relative; display: inline-block;">
    <?php $filename = basename($book['image_url']); ?>
    
    <img 
        id="main-img" 
        src="uploads/<?php echo htmlspecialchars($filename); ?>" 
        alt="Book Image"
        style="<?php echo ($book['is_sold'] ?? false) 
            ? 'filter: grayscale(100%) brightness(0.7); opacity: 0.9;' 
            : ''; ?> 
            width: 100%; 
            max-width: 400px; 
            border-radius: 12px; 
            object-fit: cover;
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);"
    >

    <?php if ($is_sold): ?>
        <div style="
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            background-color: rgba(159, 61, 61, 0.85);
            color: white;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: 2px;
            padding: 10px 40px;
            border-radius: 10px;
            text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        ">
            SOLD
        </div>
    <?php endif; ?>

    <div class="thumbnails">
        <img src="uploads/<?php echo htmlspecialchars($filename); ?>" onclick="changeMainImage(this.src)">
    </div>
</div>



    <div class="info-section">
        <h1 class="book-title"><?php echo html_entity_decode($book['title']); ?></h1>

        <p class="book-price">₹<?php echo htmlspecialchars($book['price']); ?></p>
        <div class="book-meta">
            <p><strong>Book Condition:</strong> <?php echo ucfirst(htmlspecialchars($book['book_condition'])); ?></p>
            <p><strong>Category:</strong> <?php echo isset($book['category_name']) ? htmlspecialchars($book['category_name']) : 'Not specified'; ?></p>
            <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
            <?php if (!empty($book['edition'])): ?>
    <p><strong>Edition:</strong> <?php echo htmlspecialchars($book['edition']); ?></p>
<?php endif; ?>

        </div>

        <?php 
$disable_actions = $is_sold || $is_own_book;
?>

<div class="action-buttons">
    <button class="button buy"
      <?php echo $disable_actions 
        ? 'disabled style="opacity:0.5;cursor:not-allowed;"' 
        : 'onclick="window.location.href=\'buy.php?bookid='.$book['bookid'].'\'"'; ?>>
      Buy Now
    </button>

    <button id="neg-btn" class="button chat"
      <?php echo $disable_actions 
        ? 'disabled style="opacity:0.5;cursor:not-allowed;"' 
        : ''; ?>>
      Negotiate Price
    </button>

    <button class="button cart"
      <?php echo $disable_actions 
        ? 'disabled style="opacity:0.5;cursor:not-allowed;"' 
        : 'onclick="window.location.href=\'add_to_cart.php?bookid='.$book['bookid'].'\'"'; ?>>
      Add to Cart
    </button>
</div>

<?php if ($is_sold): ?>
  <p style="color:red;font-weight:bold;font-size:18px;">
    This book has been sold and is no longer available for purchase.
  </p>
<?php elseif ($is_own_book): ?>
  <p style="color:#9F3D3D;font-weight:bold;font-size:18px;">
    You can’t purchase or negotiate your own listing.
  </p>
<?php endif; ?>


        <div class="seller-info">
            <h3>Seller Information</h3>
            <p><strong>Name:</strong> <?php echo isset($book['seller_name']) ? htmlspecialchars($book['seller_name']) : 'N/A'; ?></p>
            <p><strong>Email:</strong> <?php echo isset($book['seller_email']) ? htmlspecialchars($book['seller_email']) : 'N/A'; ?></p>
            <a href="seller_books.php?sellerid=<?php echo $book['sellerid']; ?>">View all books from this seller</a>
        </div>
    </div>
</div>

<!-- Related Books -->
<div class="related-books" 
     style="width:100%; padding:40px; box-sizing:border-box;">

    <h2 style="font-family:'DM Serif Display', serif; font-size:26px; color:#2D2D2D; margin-bottom:20px;">
        Related Books
    </h2>

    <div class="related-grid" 
         style="display:flex; justify-content:flex-start; align-items:flex-start; flex-wrap:wrap; gap:25px;">

        <?php if ($related_books && $related_books->num_rows > 0): ?>
            <?php while($row = $related_books->fetch_assoc()): ?>
                <div class="related-item" 
                     style="width:200px; background:#fff; padding:15px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); text-align:left;">

                    <a href="book_details.php?bookid=<?php echo $row['bookid']; ?>" 
                       style="text-decoration:none; color:#2D2D2D; display:block;">
                        <img src="uploads/<?php echo htmlspecialchars(basename($row['image_url'])); ?>" 
                             alt="Book Image" 
                             style="width:140px; height:200px; object-fit:contain; border-radius:8px; display:block;">

                        <p style="margin-top:10px; font-weight:500; font-size:15px;">
                            <?php echo html_entity_decode($row['title']); ?>
                        </p>
                        <span style="color:#9F3D3D; font-weight:600;">₹<?php echo htmlspecialchars($row['price']); ?></span>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No related books found.</p>
        <?php endif; ?>

    </div>
</div>




<!-- Hidden negotiation form -->
<div id="neg-modal" style="display:none;">
    <div class="negotiation-box">
        <h3>Negotiate Price?</h3>
        <div class="book-info">
            <strong><?php echo html_entity_decode($book['title']); ?></strong><br>
            Current Price: ₹<?php echo htmlspecialchars($book['price']); ?>
        </div>
        <form id="neg-form">
            <input type="hidden" name="bookid" value="<?php echo $book['bookid']; ?>">
            <input type="number" name="offer_price" placeholder="Enter your offer price" min="1" required>
            <button type="submit">Submit Offer</button>
        </form>
        <button id="close-modal" style="margin-top:10px;background:#ccc;color:#333;padding:8px 14px;border:none;border-radius:6px;cursor:pointer;">Cancel</button>
    </div>
</div>

<script src="navbar.js"></script>
<script>
function changeMainImage(src){document.getElementById('main-img').src=src;}
const negBtn=document.getElementById('neg-btn');
const negModal=document.getElementById('neg-modal');
const closeModal=document.getElementById('close-modal');
if(negBtn){
    negBtn.addEventListener('click',()=>{negModal.style.display='flex';});
    closeModal.addEventListener('click',()=>{negModal.style.display='none';});
    negModal.addEventListener('click',e=>{if(e.target===negModal){negModal.style.display='none';}});
}
const negFormEl = document.getElementById('neg-form');
if (negFormEl) {
    negFormEl.addEventListener('submit', function (e) {
        e.preventDefault();

        const offerInput = document.querySelector('input[name="offer_price"]');
        const offerPrice = parseFloat(offerInput.value);

        // ✅ Client-side validation: no negatives, no zero, no NaN
        if (isNaN(offerPrice) || offerPrice <= 0) {
            alert('Please enter a valid positive offer price.');
            offerInput.focus();
            return;
        }

        const formData = new FormData(negFormEl);

        fetch('submit_negotiation.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                negModal.style.display = 'none';
                offerInput.value = ''; // clear input after success
            }
        })
        .catch(err => {
            alert('Error submitting offer.');
            console.error(err);
        });
    });
}


</script>
</body>
</html>
