<?php
session_start();
$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['id'])) {
    die("You must be logged in to view your cart.");
}

$user_id = $_SESSION['id'];

$sql = "SELECT ci.item_id, b.bookid,b.title, b.author, b.price,b.preferred_payment_method, ci.quantity
        FROM tbl_cart_item ci
        INNER JOIN tbl_carts c ON ci.cart_id = c.cart_id
        INNER JOIN tbl_books b ON ci.book_id = b.bookid
        WHERE c.user_id = ? AND c.is_active = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_price = 0;
$total_items = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Cart - Biblio</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Inter', sans-serif;
        margin: 0;
        padding: 60px 0;
        background: 
            linear-gradient(rgba(248, 246, 244, 0.95), rgba(248, 246, 244, 0.95)),
            url('cartbg.png') no-repeat center center fixed; /* you can use any warm textured image here */
        background-size: cover;
        color: #2D2D2D;
    }

    h2, h3 { font-family: 'DM Serif Display', serif; }

    .cart-container {
        max-width: 1200px;
        margin: 60px auto;
        display: flex;
        gap: 40px;
        align-items: flex-start;
    }

    .cart-items {
        flex: 2;
    }

    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: #ffffffcc;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        backdrop-filter: blur(6px);
    }

    .cart-item h3 {
        margin: 0 0 5px 0;
        color: #5D4A3E;
    }

    .cart-item p {
        margin: 4px 0;
    }

    .remove-btn {
        padding: 6px 14px;
        background: #9F3D3D;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s ease;
    }

    .remove-btn:hover {
        background: #7f2f2f;
    }

    .order-summary {
        flex: 1;
        position: sticky;
        top: 100px;
        background: #ffffffcc;
        border-radius: 12px;
        box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        padding: 25px 30px;
        backdrop-filter: blur(6px);
    }

    .checkout-btn {
        width: 100%;
        padding: 12px 0;
        background: #3A6E6E;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: 1rem;
        transition: transform 0.2s ease, background 0.3s ease;
    }

    .checkout-btn:hover {
        transform: translateY(-2px);
        background: #2f5959;
    }

</style>
</head>
<body>

<div class="cart-container">
  <div class="cart-items">
    <h2 style="font-family:'DM Serif Display',serif; 
           font-size:2.8rem; 
           color:#5D4A3E; 
           margin-bottom:40px; 
           text-align:center; 
           letter-spacing:1px; 
           position:relative; 
           display:inline-block;">
  Your Cart
  <span style="display:block; width:60%; height:3px; background:#B69E59; margin:10px auto 0; border-radius:2px;"></span>
</h2>


    <?php if ($result->num_rows > 0): ?>
      <div style="display:grid; gap:20px;">
 <?php while ($row = $result->fetch_assoc()):
          $item_total = $row['price'] * $row['quantity'];
          $total_price += $item_total;
          $total_items += $row['quantity'];
        ?>
          <div class="cart-item">
            <div>
              <h3><?php echo html_entity_decode($row['title']); ?></h3>
              <p style="color:#2D2D2D; font-size:0.9rem;">by <?php echo htmlspecialchars($row['author']); ?></p>
              <p style="font-weight:600; color:#3A6E6E;">₹<?php echo number_format($row['price'],2); ?> × <?php echo $row['quantity']; ?></p>
            </div>
            <div style="text-align:right;">
              <p style="font-weight:600; font-size:1rem; color:#9F3D3D;">Total: ₹<?php echo number_format($item_total,2); ?></p>
              <form method="POST" action="remove_from_cart.php" style="margin-top:10px;">
                <input type="hidden" name="cart_item_id" value="<?php echo $row['item_id']; ?>">
                <button type="submit" class="remove-btn">Remove</button>
              </form>
            </div>
          </div>
        <?php endwhile; ?>

      </div>

    <?php else: ?>
      <p style="color:#555; font-size:1rem;">Your cart is empty.</p>
    <?php endif; ?>
  </div>

  <?php if ($total_items > 0): ?>
  <div class="order-summary" 
       style="width:320px; 
              background:#F8F6F4; 
              border-radius:16px; 
              padding:28px; 
              box-shadow:0 6px 16px rgba(0,0,0,0.12); 
              position:sticky; 
              top:120px; 
              margin-top:50px; 
              font-family:'Inter',sans-serif;">

    <h3 style="font-family:'DM Serif Display',serif; 
               font-size:1.6rem; 
               color:#5D4A3E; 
               margin-bottom:20px; 
               text-align:center; 
               border-bottom:2px solid #E0DDD8; 
               padding-bottom:10px;">
      Order Summary
    </h3>

    <p style="font-size:1rem; color:#2D2D2D; margin:8px 0;">
      Items in cart: <strong><?php echo $total_items; ?></strong>
    </p>

    <p style="font-size:1rem; color:#2D2D2D; margin:8px 0;">
      Subtotal: <strong>₹<?php echo number_format($total_price, 2); ?></strong>
    </p>

    <p style="margin:18px 0 22px 0; 
              font-size:1.2rem; 
              color:#3A6E6E; 
              border-top:1px solid #ddd; 
              padding-top:12px; 
              font-weight:700;">
      Total: ₹<?php echo number_format($total_price, 2); ?>
    </p>

    <form method="POST" action="create_order.php" style="text-align:center;">
      <input type="hidden" name="preferred_method" value="<?php echo $row['preferred_payment_method']; ?>">
      <button class="checkout-btn" 
              style="padding:12px 30px; 
                     background:#3A6E6E; 
                     color:#fff; 
                     border:none; 
                     border-radius:10px; 
                     cursor:pointer; 
                     font-weight:600; 
                     font-size:1rem; 
                     transition:all 0.3s ease;">
        Proceed to Checkout
      </button>
    </form>
  </div>
<?php endif; ?>


</body>
</html>
