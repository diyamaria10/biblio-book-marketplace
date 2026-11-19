<?php
session_start();
$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
if (!isset($_SESSION['id'])) die("User not logged in.");

$user_id = $_SESSION['id'];
$preferred_method = 'UPI'; // default fallback
$cart_items = []; // initialize for later use

// --- 1. Negotiation checkout ---
if (isset($_GET['negotiation_id'])) {
    $negotiation_id = intval($_GET['negotiation_id']);
    $sql = "SELECT n.negotiation_id, n.final_price, 
                   b.bookid, b.title, b.author, b.sellerid, b.preferred_payment_method,
                   u.name AS seller_name, u.email AS seller_email
            FROM tbl_negotiation n
            JOIN tbl_books b ON n.bookid = b.bookid
            JOIN users u ON b.sellerid = u.id
            WHERE n.negotiation_id = ? AND n.status = 'Accepted'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $negotiation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) die("No accepted negotiation found.");
    $negotiation = $result->fetch_assoc();

    $bookid = $negotiation['bookid'];
    $title =  html_entity_decode($negotiation['title']);
    $author = $negotiation['author'];
    $price = $negotiation['final_price'];
    $seller_name = $negotiation['seller_name'];
    $seller_email = $negotiation['seller_email'];
    $preferred_method = $negotiation['preferred_payment_method'];
}

// --- 2. Existing order checkout ---
elseif (isset($_GET['orderid'])) {
    $orderid = intval($_GET['orderid']);
    $sql = "SELECT o.orderid, o.total_price, b.bookid, b.title, b.author, b.preferred_payment_method,
                   u.name AS seller_name, u.email AS seller_email
            FROM tbl_orders o
            JOIN tbl_books b ON o.bookid = b.bookid
            JOIN users u ON b.sellerid = u.id
            WHERE o.orderid = ? AND o.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $orderid, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) die("Order not found.");
    $order = $result->fetch_assoc();

    $bookid = $order['bookid'];
    $title =  html_entity_decode($order['title']);
    $author = $order['author'];
    $price = $order['total_price'];
    $seller_name = $order['seller_name'];
    $seller_email = $order['seller_email'];
    $preferred_method = $order['preferred_payment_method'];
}

// --- 3. Direct Buy Now checkout ---
elseif (isset($_GET['bookid'])) {
    $bookid = intval($_GET['bookid']);
    $sql = "SELECT b.bookid, b.title, b.author, b.price, b.sellerid, b.preferred_payment_method,
                   u.name AS seller_name, u.email AS seller_email
            FROM tbl_books b
            JOIN users u ON b.sellerid = u.id
            WHERE b.bookid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) die("Book not found.");
    $book = $result->fetch_assoc();

    $bookid = $book['bookid'];
    $title =  html_entity_decode($book['title']);
    $author = $book['author'];
    $price = $book['price'];
    $seller_name = $book['seller_name'];
    $seller_email = $book['seller_email'];
    $preferred_method = $book['preferred_payment_method'];
}

// --- 4. Cart Checkout ---
else {
    $sql = "SELECT b.bookid, b.title, b.author, b.price, b.preferred_payment_method,
                   u.name AS seller_name, u.email AS seller_email, ci.quantity
            FROM tbl_cart_item ci
            INNER JOIN tbl_carts c ON ci.cart_id = c.cart_id
            INNER JOIN tbl_books b ON ci.book_id = b.bookid
            INNER JOIN users u ON b.sellerid = u.id
            WHERE c.user_id = ? AND c.is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) die("Your cart is empty.");

    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }

    $all_methods = array_unique(array_column($cart_items, 'preferred_payment_method'));
if (count($all_methods) === 1) {
    $preferred_method = $all_methods[0]; // use seller's preferred method
} else {
    $preferred_method = 'UPI'; // fallback if mixed preferences
}
}


// Fetch address
$sql_addr = "SELECT full_name, phone_number, address_line1, address_line2, city, state, pincode
             FROM tbl_user_address 
             WHERE user_id = ? LIMIT 1";
$stmt_addr = $conn->prepare($sql_addr);
$stmt_addr->bind_param("i", $user_id);
$stmt_addr->execute();
$result_addr = $stmt_addr->get_result();
$user_address = $result_addr->num_rows > 0 ? $result_addr->fetch_assoc() : null;
$can_place_order = false;
if (
    $user_address &&
    !empty($user_address['full_name']) &&
    !empty($user_address['address_line1']) &&
    !empty($user_address['city']) &&
    !empty($user_address['state']) &&
    !empty($user_address['pincode']) &&
    !empty($user_address['phone_number'])
) {
    $full_address = htmlspecialchars(
        $user_address['full_name'] . ', ' . 
        $user_address['address_line1'] . ', ' .
        $user_address['address_line2'] . ', ' .
        $user_address['city'] . ', ' .
        $user_address['state'] . ' - ' .
        $user_address['pincode'] . ' 📞 ' .
        $user_address['phone_number']
    );
    $can_place_order = true;
} else {
    $full_address = "No delivery address entered yet. Please update in your dashboard.";
}
// --- Insert order on Proceed to Pay ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_place_order) {
    $payment_method = $_POST['payment_method'];
    $negotiation_id_post = isset($_POST['negotiation_id']) ? intval($_POST['negotiation_id']) : null;

    // Prevent duplicate pending orders
    $check = $conn->prepare("SELECT orderid FROM tbl_orders WHERE id=? AND bookid=? AND status='pending'");
    $check->bind_param("ii", $user_id, $bookid);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows > 0) {
        $orderid = $res->fetch_assoc()['orderid'];
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO tbl_orders (id, bookid, negotiation_id, total_price, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt_insert->bind_param("iiid", $user_id, $bookid, $negotiation_id_post, $price);
        $stmt_insert->execute();
        $orderid = $stmt_insert->insert_id;
        $stmt_insert->close();
    }

    // Insert payment details
    $payment_status = 'Paid';
    $stmt_pay = $conn->prepare("INSERT INTO tbl_payment_details (order_id, user_id, amount_paid, payment_method, payment_status, payment_date) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt_pay->bind_param("iids", $orderid, $user_id, $price, $payment_method, $payment_status);
    $stmt_pay->execute();
    $payment_id = $stmt_pay->insert_id;
    $stmt_pay->close();

    //update book status
    $stmt_update = $conn->prepare("UPDATE tbl_books SET is_active=0 WHERE bookid=?");
$stmt_update->bind_param("i", $bookid);
$stmt_update->execute();
$stmt_update->close();

    // Redirect to order success
    header("Location: order_success.php?order_id=$orderid&payment_id=$payment_id");
    exit();
}



?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <!-- Import Poppins font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eaf4ea, #d9ead9, #f3f8f3);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .checkout-box {
            max-width: 520px;
            width: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
            animation: fadeIn 0.8s ease-in-out;
            border: 2px solid #cddfd0;
        }

        h2 {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            color: #355e3b;
            margin-bottom: 25px;
        }

        p {
            font-size: 1rem;
            color: #444;
            margin: 10px 0;
        }

        p strong {
            color: #355e3b;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 6px;
            font-weight: 500;
            color: #2f4f4f;
        }

        input, select, textarea {
            width: 100%;
            padding: 14px;
            border: 1.5px solid #c0d8c0;
            border-radius: 12px;
            font-size: 1rem;
            background: #fdfdfd;
            transition: all 0.3s ease;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #7aa97a;
            box-shadow: 0 0 8px rgba(122,169,122,0.4);
            outline: none;
            background: #f6faf6;
        }

        button {
            margin-top: 25px;
            padding: 16px;
            width: 100%;
            background: linear-gradient(135deg, #7aa97a, #5f875f);
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 14px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 15px rgba(122,169,122,0.4);
        }

        button:hover {
            background: linear-gradient(135deg, #678f67, #4c704c);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 20px rgba(103,143,103,0.5);
        }

        /* Subtle floating animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .highlight {
            background: linear-gradient(90deg, #dfeee0, #edf6ed);
            padding: 6px 10px;
            border-radius: 8px;
            display: inline-block;
            font-weight: 600;
            color: #2f4f2f;
        }
        .button-group {
    display: flex;
    gap: 12px;
    margin-top: 25px;
}

.back-btn {
    flex: 1;
    padding: 14px;
    background: transparent;
    border: 2px solid #7aa97a;
    color: #355e3b;
    font-weight: 600;
    border-radius: 12px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.back-btn:hover {
    background: #e8f2e8;
    transform: translateY(-2px);
}

.pay-btn {
    flex: 2;
    padding: 14px;
    background: linear-gradient(135deg, #7aa97a, #5f875f);
    color: white;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 6px 15px rgba(122,169,122,0.4);
}

.pay-btn:hover {
    background: linear-gradient(135deg, #678f67, #4c704c);
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 10px 20px rgba(103,143,103,0.5);
}
.pay-btn:disabled {
    opacity: 0.5;           /* makes the button semi-transparent */
    cursor: not-allowed;    /* shows the “not clickable” cursor */
    box-shadow: none;       /* removes the hover shadow */
    transform: none;        /* disables hover animation */
}

.hidden {
    display: none;
}


    </style>
</head>
<body>

<div class="checkout-box">
    <h2>Checkout</h2>
    <p><strong>Book:</strong> 
  <span class="highlight"><?php echo htmlspecialchars($title); ?></span> 
  by <?php echo htmlspecialchars($author); ?>
</p>

<p><strong>Price:</strong> 
  <span class="highlight">₹<?php echo htmlspecialchars($price); ?></span>
</p>

<p><strong>Seller:</strong> 
  <span class="highlight">
    <?php echo htmlspecialchars($seller_name); ?> 
    (<?php echo htmlspecialchars($seller_email); ?>)
  </span>
</p>

    <form action="simulate_payment.php" method="POST">
       <p><strong>Delivery Address:</strong>  
   <span class="highlight">
      <?php
        if ($user_address) {
            echo htmlspecialchars($user_address['full_name']) . ', ' .
                 htmlspecialchars($user_address['address_line1']) . ', ' .
                 htmlspecialchars($user_address['address_line2']) . ', ' .
                 htmlspecialchars($user_address['city']) . ', ' .
                 htmlspecialchars($user_address['state']) . ' - ' .
                 htmlspecialchars($user_address['pincode']) . 
                 ' 📞 ' . htmlspecialchars($user_address['phone_number']);
            $can_place_order = true;
        } else {
            echo "No delivery address entered yet. Please update in your dashboard.";
            $can_place_order = false;
        }
      ?>
   </span>
</p>
    <input type="hidden" name="bookid" value="<?php echo $bookid; ?>">
<input type="hidden" name="amount" value="<?php echo $price; ?>">
<?php if (isset($_GET['negotiation_id'])): ?>
  <input type="hidden" name="negotiation_id" value="<?php echo intval($_GET['negotiation_id']); ?>">
<?php endif; ?>
<?php if (isset($_GET['orderid'])): ?>
  <input type="hidden" name="orderid" value="<?php echo intval($_GET['orderid']); ?>">
<?php endif; ?>

        <label for="payment_method">Payment Method:</label>
        <select name="payment_method" id="payment_method" required>
    <option value="UPI" <?php if($preferred_method == 'UPI') echo 'selected'; ?>>
        UPI <?php if($preferred_method == 'UPI') echo '(Seller’s Preference)'; ?>
    </option>
    <option value="Card" <?php if($preferred_method == 'Card') echo 'selected'; ?>>
        Card <?php if($preferred_method == 'Card') echo '(Seller’s Preference)'; ?>
    </option>
</select>

         <div class="button-group">
        <button type="button" class="back-btn" onclick="window.history.back()">⬅ Back</button>
        <button type="submit" class="pay-btn" <?php if (!$can_place_order) echo 'disabled title="Complete delivery address required"'; ?>>
    Proceed to Pay 💳
</button>

    </div>
    </form>
</div>

</body>
</html>

