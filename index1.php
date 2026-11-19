<?php
session_start();

$conn = new mysqli("localhost", "root", "", "BIBLIO");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
$isLoggedIn = isset($_SESSION['id']);
$name = $isLoggedIn ? $_SESSION['name'] : '';

$catResult = $conn->query("SELECT category_id, name FROM tbl_categories ORDER BY name ASC");


$books = [];
$searchTerm = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$condition = $_GET['condition'] ?? '';
$sort = $_GET['sort'] ?? '';

$where = ["is_active = 1"];
$params = [];
$types = "";


if (!empty(trim($searchTerm))) {
    $where[] = "(title LIKE ? OR author LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $types .= "ss";
}


$category = intval($_GET['category'] ?? 0);
if ($category > 0) {
    $where[] = "category_id = ?";
    $params[] = $category;
    $types .= "i";
}


if (is_numeric($minPrice)) {
    $where[] = "price >= ?";
    $params[] = $minPrice;
    $types .= "d";
}
if (is_numeric($maxPrice)) {
    $where[] = "price <= ?";
    $params[] = $maxPrice;
    $types .= "d";
}


if (!empty($condition)) {
    $where[] = "book_condition = ?";
    $params[] = $condition;
    $types .= "s";
}


$sql = "SELECT * FROM tbl_books";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}


$allowedSort = [
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'newest' => 'bookid DESC'
];
if (isset($allowedSort[$sort])) {
    $sql .= " ORDER BY " . $allowedSort[$sort];
} else {
    $sql .= " ORDER BY bookid DESC";
}


$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Biblio</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Parisienne&family=DM+Serif+Display&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond&family=Playfair+Display&family=Lora&display=swap" rel="stylesheet">

   <style>
    :root {
      --sage-green: #b7c9a8;
      --blush-pink: #e8a7b0;
      --soft-cream: #fdfdfb;
      --dark-gray: #2d2d2d;
      --olive: #4a6b57;
    }

    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      background-color: var(--soft-cream);
      color: var(--dark-gray);
    }

    .navbar {
      background-color: var(--sage-green);
      color: white;
      padding: 24px 32px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 999;
      backdrop-filter: blur(6px);
    }
 .logo {
      font-family: 'Parisienne', cursive;
      font-size: 3.6rem;
      font-weight: 800;
  color: #2c4631; 
  letter-spacing: 3px;
text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
      
}
   .nav-links a {
  color: white;
  text-decoration: none;
  margin-left: 24px;
  font-weight: 500;
  font-size: 1rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;  
}
.nav-links {
  display: flex;
  align-items: center; 
  gap: 20px;  
  flex-wrap: nowrap;          
}


.nav-icon {
  vertical-align: middle;
  fill: white; 
}


    .hero-banner {
      background:linear-gradient(rgba(253, 243, 245, 0.3), rgba(253, 243, 245, 0.3)), url('bg.png') center center / cover no-repeat;
      height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: flex-start;
      text-align: left;
      padding: 80px 60px 0 60px;
      margin: 0;
      


    }

    .overlay-text h1 {
      font-family: 'Playfair Display', serif;
      font-weight: 600;
      font-size: 5.3rem;
      color: var(--dark-gray);
      color: #fdfdfb;
      margin-bottom: 8px;
    }

    .overlay-text p {
      font-size: 1.2rem;
      color: var(--olive);
      flex-direction: column;
      align-items: flex-start;
    }
    
    .overlay-text.tagline {
      font-size: 1.2rem;
      color: var(--olive);
      margin:0;
    }
    
    .tagline{
      font-size: 1.2rem;
      color: var(--olive);
      margin-top:0px;
      margin-bottom: 0px;
      padding-left:18px;
      
    }

    .section {
      padding: 60px 20px 100px;
      max-width: 1000px;
      margin: auto;
      text-align: center;
    }

    .section h2 {
      font-family: 'DM Serif Display', serif;
      font-size: 2.2rem;
      color: var(--olive);
      margin-bottom: 30px;
      border-bottom: 3px solid currentColor;
      display: inline-block;
      padding-bottom: 6px;
    }
    
    .hero-trust-features {
  display: flex;
  gap: 32px;
  margin-top: 24px;
  align-items: center;
  flex-wrap: wrap;
}


.search-form {
  display: flex;
  align-items: center;
  gap: 12px;         
  flex-wrap: wrap;    
  background-color: rgba(255,255,255,0.3);
  padding: 8px 12px;
  border-radius: 12px;
}


.search-form input,
.search-form select {
  padding: 8px 10px;
  font-size: 0.9rem;
  font-family: 'Inter', sans-serif;
  border: 1px solid #ccc;
  border-radius: 8px;
  background-color: #fff;
  color: #333;
  min-width: 110px;
  transition: all 0.3s ease;
}


.search-form input:focus,
.search-form select:focus {
  outline: none;
  border-color: var(--olive);
  box-shadow: 0 0 0 3px rgba(74,107,87,0.15);
}


.search-button {
  background-color: var(--olive);
  color: #fff;
  padding: 9px 16px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s ease;
}

.search-button:hover {
  background-color: #375541;
}


.book-results {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
  }

  .book-card {
    background: white;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .book-card:hover {
    transform: translateY(-6px);
  }

  .book-image {
    width: 140px;
    height: 200px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 12px;
  }

  .book-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    color: var(--olive);
    margin-bottom: 6px;
    font-weight: 600;
  }

  .book-author {
    font-size: 0.9rem;
    color: #555;
    margin-bottom: 10px;
    font-style: italic;
  }

  .book-price {
    font-weight: 700;
    color: var(--blush-pink);
    font-size: 1rem;
  }

  
  .no-results {
    max-width: 800px;
    margin: 40px auto;
    font-size: 1.3rem;
    color: var(--blush-pink);
    text-align: center;
  }

.trust-feature {
  display: flex;
  align-items: center;
  gap: 10px;
  background-color: rgba(255, 255, 255, 0.15);
  padding: 8px 12px;
  border-radius: 10px;
  backdrop-filter: blur(3px);
}

.trust-feature img {
  width: 22px;
  height: 22px;
}

.trust-feature span {
  font-size: 1rem;
  font-weight: 500;
  color: var(--soft-cream);
}

    .steps-container {
      display: flex;
      flex-wrap: nowrap;
      justify-content: center;
      gap: 30px;
      margin-top: 40px;
    }

    .step-card {
      background-color: white;
      padding: 30px 20px;
      border-radius: 16px;
      width: 260px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease;
    }

    .step-card:hover {
      transform: translateY(-5px);
    }

    .step-number {
      background-color: var(--blush-pink);
      color: white;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      font-weight: bold;
      margin: 0 auto 16px;
    }

    .step-icon {
      width: 50px;
      height: 50px;
      margin: 10px auto;
      display: block;
    }

    .step-arrow {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .testimonial-section {
      background: linear-gradient(rgba(253,243,245,0.6), rgba(253,243,245,0.6)), url('demo.jpeg') center center / cover no-repeat;
      background-attachment: fixed;
      padding: 100px 20px 120px;
      text-align: center;
    }

    .testimonial-section h2 {
      font-family: 'DM Serif Display', serif;
      color: #b96982;
      font-size: 2.2rem;
      margin-bottom: 50px;
      border-bottom: 3px solid currentColor;
      display: inline-block;
      padding-bottom: 6px;
    }

    .testimonial-grid {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 40px;
    }

    .testimonial-box {
      background-color: #fff;
      padding: 25px 20px;
      border-radius: 16px;
      width: 300px;
      box-shadow: 0 6px 24px rgba(0,0,0,0.06);
      text-align: left;
      transition: all 0.3s ease;
    }

    .testimonial-box:hover {
      transform: translateY(-5px);
    }

    .section-divider {
      border: none;
      border-top: 3px dashed var(--blush-pink);
      width: 80%;
      margin: 80px auto;
    }

    footer {
      background-color: #b7c9a8;
      color: var(--soft-cream);
      padding: 40px 20px;
    }

    .about-section-bg {
      background: linear-gradient(rgba(255,255,255,0.7), rgba(255,255,255,0.7)), url('demo.jpeg') center center / cover no-repeat;
      background-attachment: fixed;
      padding: 100px 0;
    }

    .sell-section-bg {
      background: linear-gradient(rgba(255,255,255,0.6), rgba(255,255,255,0.6)), url('demo.jpeg') center center / cover no-repeat;
      background-attachment: fixed;
      padding: 100px 0;
    }

    .buy-section-bg {
      background: linear-gradient(rgba(255,255,255,0.6), rgba(255,255,255,0.6)), url('demo.jpeg') center center / cover no-repeat;
      background-attachment: fixed;
      padding: 100px 0;
    }
    .profile {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
}

.profile img {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover; 
}




  </style>
</head>
<body>
  <div class="navbar">
    <div class="logo">Biblio</div>
    <form method="GET" action="search_results.php" class="search-form" style="margin-left: 24px;">
        <input
          type="text"
          name="search"
          placeholder="Search books..."
          class="search-input"
          value="<?php echo htmlspecialchars($search); ?>"
          aria-label="Search books"
        />
        
  <select name="category" style="padding:8px;border:1px solid #ccc;border-radius:6px;">
    <option value="">All Categories</option>
    <?php while($cat = $catResult->fetch_assoc()): ?>
      <option value="<?php echo $cat['category_id']; ?>" 
        <?php echo (isset($_GET['category']) && $_GET['category']==$cat['category_id'])?'selected':''; ?>>
        <?php echo htmlspecialchars($cat['name']); ?>
      </option>
    <?php endwhile; ?>
  </select>

  
  <input type="number" name="min_price" placeholder="Min Price" style="width:90px;padding:8px;border:1px solid #ccc;border-radius:6px;" 
    value="<?php echo isset($_GET['min_price'])?htmlspecialchars($_GET['min_price']):'';?>">
  <input type="number" name="max_price" placeholder="Max Price" style="width:90px;padding:8px;border:1px solid #ccc;border-radius:6px;" 
    value="<?php echo isset($_GET['max_price'])?htmlspecialchars($_GET['max_price']):'';?>">

  
  <select name="condition" style="padding:8px;border:1px solid #ccc;border-radius:6px;">
    <option value="all">Any Condition</option>
    <option value="excellent" <?php if(isset($_GET['condition']) && $_GET['condition']=="excellent") echo "selected";?>>excellent</option>
    <option value="good" <?php if(isset($_GET['condition']) && $_GET['condition']=="good") echo "selected";?>>good</option>
    <option value="fair" <?php if(isset($_GET['condition']) && $_GET['condition']=="fair") echo "selected";?>>fair</option>
  </select>

  
  <select name="sort" style="padding:8px;border:1px solid #ccc;border-radius:6px;">
    <option value="">Sort By</option>
    <option value="recent" <?php if(isset($_GET['sort']) && $_GET['sort']=="recent") echo "selected";?>>Recently Added</option>
    <option value="oldest" <?php if(isset($_GET['sort']) && $_GET['sort']=="oldest") echo "selected";?>>Oldest</option>
    <option value="price_low" <?php if(isset($_GET['sort']) && $_GET['sort']=="price_low") echo "selected";?>>Price: Low to High</option>
    <option value="price_high" <?php if(isset($_GET['sort']) && $_GET['sort']=="price_high") echo "selected";?>>Price: High to Low</option>
  </select>

  <button type="submit" class="search-button" style="padding:8px 12px;background:#5D4A3E;color:#fff;border:none;border-radius:6px;">Search</button>
</form>
      
   
<div class="nav-links">
  <?php if (isset($_SESSION['id'])): ?>
    <a href="user-dashboard.php">
      <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 12c2.7 0 4.88-2.17 4.88-4.88S14.7 2.25 12 2.25 7.13 4.42 7.13 7.13 9.3 12 12 12zm0 2.25c-2.67 0-8 1.34-8 4v1.5h16v-1.5c0-2.66-5.33-4-8-4z"/>
      </svg>
      <?php echo htmlspecialchars($_SESSION['name']); ?>
    </a>
    <a href="logout.php">
      <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
        <path d="M16 13v-2a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0zM12 2a6 6 0 0 1 6 6v2h-2V8a4 4 0 0 0-8 0v2H6V8a6 6 0 0 1 6-6zM4 20h16v2H4v-2z"/>
      </svg>
      Logout
    </a>
    <a href="cart.php">
      <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
        <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2zM7.82 14l.94 2h8.49l1.8-8H6.16l-.94-2H1v2h2l3.6 7.59L5.25 18c-.14.34-.25.74-.25 1.17 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25L7.82 14z"/>
      </svg>
      Cart
    </a>
  <?php else: ?>
    <a href="login.php">
      <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
        <path d="M16 13v-2a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0zM12 2a6 6 0 0 1 6 6v2h-2V8a4 4 0 0 0-8 0v2H6V8a6 6 0 0 1 6-6zM4 20h16v2H4v-2z"/>
      </svg>
      Login
    </a>
    <a href="signup.php">
      <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 12c2.7 0 4.88-2.17 4.88-4.88S14.7 2.25 12 2.25 7.13 4.42 7.13 7.13 9.3 12 12 12zm0 2.25c-2.67 0-8 1.34-8 4v1.5h16v-1.5c0-2.66-5.33-4-8-4z"/>
      </svg>
      Sign Up
    </a>
  <?php endif; ?>
</div>
  </div>
 
<section class="hero-banner">
  <div class="overlay-text">
    <h1>Buy & Sell<br> College Books<br> With Ease </h1>
    <span class="tagline">Second-hand. First Choice.</span>
  <div class="hero-trust-features">
      <div class="trust-feature">
        🛡️
        <span>Verified Sellers</span>
      </div>
      <div class="trust-feature">
         🚚
        <span>Affordable Delivery</span>
      </div>
      <div class="trust-feature">
        ✅
        <span>Quality Assured</span>
      </div>
    </div>
  </div>

</section>


  <div class="section-divider"> </div>

  <section class="about-section-bg" id="about">
    <div class="section">
    <h2>Why Biblio?</h2>
    <p>Are your old textbooks gathering dust? Or do you blaze through books only to forget them in the back of your closet?</p>
    <p><strong style="color: var(--blush-pink);">Meet Biblio</strong> — a smart, student-powered platform where college students buy and sell used books directly.</p>
    <p>As a <strong style="color: var(--blush-pink);">seller</strong>, you earn money for books you no longer need. As a <strong style="color: var(--blush-pink);">buyer</strong>, you save cash and help reduce waste.</p>
    <p><strong>Educourier</strong> bridges the gap — we pick up books from sellers and deliver them safely to buyers.</p>
  </div>
  </section>

  <div class="section-divider"></div>

  <section class="sell-section-bg" id="sell">
    <div class="section">
    <h2>How to SELL Used Books</h2>
    <div class="steps-container">
      <div class="step-card">
        <div class="step-number">1</div>
        <img src="p1.jpeg" class="step-icon" alt="Post Icon" />
        <h3>Post an Ad</h3>
        <p>Describe your book and publish it on Biblio.</p>
      </div>
      <div class="step-arrow">
  <svg width="60" height="40" viewBox="0 0 60 40" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M0 20 C20 0, 40 40, 60 20" stroke="#d16b86" stroke-width="3" fill="none" />
  </svg>
</div>

      <div class="step-card">
        <div class="step-number">2</div>
        <img src="p2.jpeg" class="step-icon" alt="Post Icon" />
        <h3>Set a Price</h3>
        <p>Choose a reasonable price based on condition.</p>
      </div>
     <div class="step-arrow">
  <svg width="60" height="40" viewBox="0 0 60 40" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M0 20 C20 0, 40 40, 60 20" stroke="#d16b86" stroke-width="3" fill="none" />
  </svg>
</div>

      <div class="step-card">
        <div class="step-number">3</div>
        <img src="p3.jpeg" class="step-icon" alt="Post Icon" />
        <h3>Get Paid</h3>
        <p>Receive payment after delivery through UPI or bank transfer.</p>
      </div>
    </div>
    </div>
  </section>

  <div class="section-divider"></div>

  <section class="buy-section-bg" id="buy">
    <div class="section">
    <h2>How to BUY Used Books</h2>
    <div class="steps-container">
      <div class="step-card">
        <div class="step-number">1</div>
        <img src="p4.jpeg" class="step-icon" alt="Post Icon" />
        <h3>Select Books</h3>
         <p>Search from thousands of listings on Biblio.</p>
      </div>
<div class="step-arrow">
  <svg width="60" height="40" viewBox="0 0 60 40" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M0 20 C20 0, 40 40, 60 20" stroke="#d16b86" stroke-width="3" fill="none" />
  </svg>
</div>

      <div class="step-card">
        <div class="step-number">2</div>
        <img src="p5.jpeg" class="step-icon" alt="Post Icon" />
        <h3>Place Order</h3>
        <p>Securely pay online and confirm your purchase.</p>
      </div>
      <div class="step-arrow">
  <svg width="60" height="40" viewBox="0 0 60 40" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M0 20 C20 0, 40 40, 60 20" stroke="#d16b86" stroke-width="3" fill="none" />
  </svg>
</div>

      <div class="step-card">
        <div class="step-number">3</div>
        <img src="p6.jpeg" class="step-icon" alt="Post Icon" />
        <h3>Receive Delivery</h3>
        <p>Get your books delivered by Educourier at your doorstep.</p>
      </div>
    </div>
  </div>
  </section>


  <section class="testimonial-section">
    <h2>What Our Students Say</h2>

  <div class="testimonial-grid">

    <div class="testimonial-box">
      <div class="profile">
        <img src="l.png" alt="Lizzy S">
        <div>
          <strong>Lizzy S</strong><br>
          <span>Assumption College</span>
        </div>
      </div>
      <div class="stars">★★★★★</div>
      <p>"Saved over ₹3000 on textbooks this semester! EduCourier delivered right to my hostel."</p>
    </div>

    <div class="testimonial-box">
      <div class="profile">
        <img src="d.png" alt="Daniel Varghese">
        <div>
          <strong>Daniel Varghese</strong><br>
          <span>Baselious College </span>
        </div>
      </div>
      <div class="stars">★★★★★</div>
      <p>"Sold my old books easily and bought new ones at great prices. Amazing platform!"</p>
    </div>

    <div class="testimonial-box">
      <div class="profile">
        <img src="r.png" alt="Riya Dipu">
        <div>
          <strong>Riya Dipu</strong><br>
          <span>St.Josephs College</span>
        </div>
      </div>
      <div class="stars">★★★★★</div>
      <p>"EduCourier made book exchange so convenient. Fast delivery and great condition books."</p>
    </div>

  </div>
</section>




  <footer>
    <div style="max-width: 1200px; margin: auto; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 30px;">
      <div>
        <h3>Biblio</h3>
        <p style="max-width: 280px; line-height: 1.6;">A smart, student-powered portal for buying & selling second-hand college books — responsibly and affordably.</p>
      </div>

      <div>
        <h4 style="margin-bottom: 10px; font-size: 1rem;">Quick Links</h4>
        <ul style="list-style: none; padding: 0; line-height: 1.8;">
          <li><a href="#">Home</a></li>
          <li><a href="#sell">Sell</a></li>
          <li><a href="#buy">Buy</a></li>
          <li><a href="#about">About</a></li>
        </ul>
      </div>

      <div>
        <h4 style="margin-bottom: 10px; font-size: 1rem;">Contact</h4>
        <p>Email: support@biblio.com</p>
        <p>Phone: +91-9876543210</p>
      </div>
    </div>

    <hr style="margin: 30px 0; border: none; border-top: 1px solid #aaa;" />

    <div style="text-align: center; font-size: 0.9rem;">
      ©️ 2025 Biblio. All rights reserved.
    </div>
  </footer>
</body>
</html>