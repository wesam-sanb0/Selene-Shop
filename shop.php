<?php
// Author: Task 3 - Display Products from database
// shop.php - replaces shop.php, fetches products from DB via PHP session cart

session_start();
require 'db.php';

// Task 12: Past purchases cookie
$pastPurchases = [];
// Task 12: Read past purchases from per-customer cookie (tied to logged-in user)
if (isset($_SESSION['customer_id'])) {
    $cookieName = 'selenePastPurchases_' . $_SESSION['customer_id'];
    if (isset($_COOKIE[$cookieName])) {
        $pastPurchases = json_decode($_COOKIE[$cookieName], true) ?? [];
    }
}

// Fetch all products from database
$stmt = $pdo->query("SELECT * FROM product ORDER BY product_id ASC");
$products = $stmt->fetchAll();

// Cart count from session
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Cart total
$cartTotal = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Selene Shop</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav id="mainNav">
  <div class="nav-logo">SEL<span>EN</span>E</div>
  <div class="nav-links">
    <a class="nav-link" href="shop.php">Shop</a>
    <a class="nav-link" href="contact.php">Contact</a>
    <a class="nav-link" href="admin.php">Admin</a>
    <?php if (isset($_SESSION['customer_id'])): ?>
      <span class="nav-link" style="color:var(--gold)">Hi, <?= htmlspecialchars($_SESSION['customer_name']) ?></span>
      <a class="nav-link auth-nav-btn" href="logout.php">Logout</a>
    <?php else: ?>
      <a class="nav-link auth-nav-btn" href="login.php">Login</a>
    <?php endif; ?>
    <a class="cart-btn" href="checkout.php">
      🛒 Cart
      <?php if ($cartCount > 0): ?>
        <span class="cart-count nonempty"><?= $cartCount ?></span>
      <?php endif; ?>
    </a>
  </div>
</nav>

<button class="help-button" id="helpButton" type="button">Need Help?</button>

<!-- Task 12: Past Purchases banner (cookie-based) -->
<?php if (!empty($pastPurchases)): ?>
<div style="background:var(--surface);border-bottom:1px solid var(--border);padding:14px 32px;font-size:13px;color:var(--muted);">
  <strong style="color:var(--gold)">Your past purchases:</strong>
  <?php foreach (array_slice($pastPurchases, -5) as $p): ?>
    <span style="margin-left:12px">• <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['date']) ?>)</span>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="hero">
  <h1>Find your perfect sparkle<br><em>with our accessories</em></h1>
  <p>Beautiful pieces curated with care and purpose.</p>
  <div class="hero-cta">
    <a class="btn btn-gold" href="#shopSection">Explore Collection</a>
  </div>
</div>

<div class="divider"></div>

<div class="section" id="shopSection">
  <div class="section-title">The Collection</div>
  <div class="section-sub">Handpicked accessories for your everyday elegance</div>

  <?php if (isset($_GET['msg'])): ?>
    <div style="text-align:center;margin-bottom:20px;color:var(--gold);font-size:14px;">
      <?= htmlspecialchars($_GET['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- Task 3: Products displayed from database -->
  <div class="products-grid">
    <?php foreach ($products as $product): ?>
    <div class="product-card">
      <div class="badge-new">New</div>
      <div class="product-img">
        <a href="product.php?id=<?= $product['product_id'] ?>">
          <span><img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>"></span>
        </a>
      </div>
      <div class="product-body">
        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
        <div class="product-desc"><?= htmlspecialchars($product['description']) ?></div>
        <div class="product-footer">
          <div class="product-price"><?= number_format($product['price'], 0) ?> SAR</div>
          <!-- Quick add to cart with qty 1 -->
          <form method="POST" action="cart_add.php" style="display:inline">
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
            <input type="hidden" name="quantity" value="1">
            <input type="hidden" name="redirect" value="shop.php">
            <button class="add-cart-btn" type="submit">🛒 Add</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Help modal -->
<div class="modal-overlay" id="helpModal" style="display:none">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="helpModalTitle">
    <h3 id="helpModalTitle">How can we help?</h3>
    <p>Follow these steps to shop and checkout smoothly.</p>
    <ul class="help-list">
      <li><strong>Browse products:</strong> view product cards in the collection.</li>
      <li><strong>Add to cart:</strong> click the 🛒 Add button on any product.</li>
      <li><strong>View cart:</strong> click the Cart button in the top navigation.</li>
      <li><strong>Checkout:</strong> complete your order on the checkout page.</li>
      <li><strong>Need support?</strong> visit the <a href="contact.php" style="color:var(--gold)">Contact</a> page.</li>
    </ul>
    <div class="modal-footer">
      <button class="btn btn-outline" id="closeHelpBtn" type="button">Close</button>
    </div>
  </div>
</div>

<script>
  // Task 13: JS form validation handled on individual pages
  document.getElementById('helpButton').addEventListener('click', () => {
    document.getElementById('helpModal').style.display = 'flex';
  });
  document.getElementById('closeHelpBtn').addEventListener('click', () => {
    document.getElementById('helpModal').style.display = 'none';
  });
  document.getElementById('helpModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) document.getElementById('helpModal').style.display = 'none';
  });
</script>
</body>
</html>