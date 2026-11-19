<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
$catResult = $conn->query("SELECT category_id, name FROM tbl_categories");
$search = $search ?? ($_GET['search'] ?? '');
$session_name = $_SESSION['name'] ?? '';
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Parisienne&family=DM+Serif+Display&display=swap" rel="stylesheet">

<div class="navbar" style="
  background-color: #F8F6F4;
  border-bottom: 2px solid #B69E59;
  box-shadow: 0 3px 8px rgba(0,0,0,0.06);
  
  top: 0;
  z-index: 100;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 0 14px;
  transition: all 0.3s ease;
">

  <!-- 🔖 Top Row: Logo + Nav Links -->
  <div style="display:flex; align-items:center; justify-content:space-between; width:95%; max-width:1200px;">
    <div class="logo" style="
      font-family: 'Parisienne', cursive;
      font-size: 34px;
      color: #5D4A3E;
      letter-spacing: 1px;
      transition: transform 0.3s ease;
    " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
      Biblio
    </div>

    <div class="nav-links" style="display:flex; gap:20px; align-items:center; font-family:'Inter', sans-serif; font-size:15px;">
      <?php if (isset($_SESSION['id'])): ?>
        <a href="user-dashboard.php" style="color:#5D4A3E; text-decoration:none; font-weight:500;">👤 <?php echo htmlspecialchars($_SESSION['name']); ?></a>
        <a href="cart.php" style="color:#5D4A3E; text-decoration:none; font-weight:500;">🛒 Cart</a>
        <a href="logout.php" style="color:#9F3D3D; text-decoration:none; font-weight:600;">Logout</a>
      <?php else: ?>
        <a href="login.php" style="color:#5D4A3E; text-decoration:none; font-weight:500;">Login</a>
        <a href="signup.php" style="color:#5D4A3E; text-decoration:none; font-weight:500;">Sign Up</a>
        <a href="cart.php" style="color:#5D4A3E; text-decoration:none; font-weight:500;">🛒 Cart</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- 🎯 Filter Bar -->
  <div style="
    margin-top:10px;
    background-color: rgba(255,255,255,0.96);
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 10px 16px;
    width: 95%;
    max-width: 1100px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:8px;
    transition: box-shadow 0.3s ease;
  " onmouseover="this.style.boxShadow='0 3px 10px rgba(0,0,0,0.07)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.04)'">
    
    <form action="search_results.php" method="GET" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:8px;">

      <input type="text" name="search" placeholder="🔍 Search books..."
             value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
             style="padding:9px 12px; border-radius:8px; border:1px solid #ccc; width:220px; font-family:'Inter'; font-size:14px; transition:all 0.3s ease;"
             onfocus="this.style.borderColor='#B69E59'; this.style.boxShadow='0 0 3px #B69E59';"
             onblur="this.style.borderColor='#ccc'; this.style.boxShadow='none';">

      <select name="category" style="padding:9px 12px; border-radius:8px; border:1px solid #ccc; font-family:'Inter'; font-size:14px; transition:all 0.3s ease;"
        onfocus="this.style.borderColor='#B69E59';" onblur="this.style.borderColor='#ccc';">
          <option value="0">All Categories</option>
          <?php
          $conn = new mysqli("localhost", "root", "", "BIBLIO");
          $result = $conn->query("SELECT category_id, name FROM tbl_categories ORDER BY name ASC");
          while ($cat = $result->fetch_assoc()) {
              $selected = (isset($_GET['category']) && $_GET['category'] == $cat['category_id']) ? 'selected' : '';
              echo "<option value='{$cat['category_id']}' $selected>{$cat['name']}</option>";
          }
          $conn->close();
          ?>
      </select>

      <input type="number" step="0.01" name="min_price" placeholder="Min ₹"
             value="<?php echo $_GET['min_price'] ?? ''; ?>"
             style="padding:9px 10px; width:90px; border-radius:8px; border:1px solid #ccc; font-family:'Inter'; font-size:14px; transition:all 0.3s ease;"
             onfocus="this.style.borderColor='#B69E59';" onblur="this.style.borderColor='#ccc';">
      <input type="number" step="0.01" name="max_price" placeholder="Max ₹"
             value="<?php echo $_GET['max_price'] ?? ''; ?>"
             style="padding:9px 10px; width:90px; border-radius:8px; border:1px solid #ccc; font-family:'Inter'; font-size:14px; transition:all 0.3s ease;"
             onfocus="this.style.borderColor='#B69E59';" onblur="this.style.borderColor='#ccc';">

      <select name="condition" style="padding:9px 12px; border:1px solid #ccc; border-radius:8px; font-family:'Inter'; font-size:14px; transition:all 0.3s ease;"
        onfocus="this.style.borderColor='#B69E59';" onblur="this.style.borderColor='#ccc';">
        <option value="all">Any Condition</option>
        <option value="excellent" <?php if(isset($_GET['condition']) && $_GET['condition']=="excellent") echo "selected";?>>Excellent</option>
        <option value="good" <?php if(isset($_GET['condition']) && $_GET['condition']=="good") echo "selected";?>>Good</option>
        <option value="fair" <?php if(isset($_GET['condition']) && $_GET['condition']=="fair") echo "selected";?>>Fair</option>
      </select>

      <select name="sort" style="padding:9px 12px; border-radius:8px; border:1px solid #ccc; font-family:'Inter'; font-size:14px; transition:all 0.3s ease;"
        onfocus="this.style.borderColor='#B69E59';" onblur="this.style.borderColor='#ccc';">
          <option value="recent" <?php echo ($_GET['sort'] ?? '')=='recent'?'selected':''; ?>>Newest</option>
          <option value="oldest" <?php echo ($_GET['sort'] ?? '')=='oldest'?'selected':''; ?>>Oldest</option>
          <option value="price_low" <?php echo ($_GET['sort'] ?? '')=='price_low'?'selected':''; ?>>Price: Low → High</option>
          <option value="price_high" <?php echo ($_GET['sort'] ?? '')=='price_high'?'selected':''; ?>>Price: High → Low</option>
      </select>

      <button type="submit" style="
        background-color:#9F3D3D;
        color:#fff;
        padding:9px 18px;
        border:none;
        border-radius:8px;
        font-family:'Inter';
        font-weight:600;
        cursor:pointer;
        font-size:14px;
        transition:all 0.3s ease;
      " onmouseover="this.style.backgroundColor='#B69E59'; this.style.transform='scale(1.05)'"
        onmouseout="this.style.backgroundColor='#9F3D3D'; this.style.transform='scale(1)'">
        Apply
      </button>
    </form>
  </div>
</div>

<!-- ✨ spacing below navbar -->
<div style="margin-bottom: 25px;"></div>

