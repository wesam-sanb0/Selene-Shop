<?php
// Author: Task 4 - Display Product Details from database
// product.php - shows individual product, allows adding to session cart

session_start();
require 'db.php';

// Validate product ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: shop.php');
    exit;
}

// Fetch product from DB
$stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: shop.php?msg=Product+not+found');
    exit;
}

// Cart count for nav
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) $cartCount += $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Selene Shop | <?= htmlspecialchars($product['name']) ?></title>
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

<div class="detail-layout" style="padding: 40px 32px; max-width:1100px; margin:0 auto; display:grid; grid-template-columns:1fr 1fr; gap:48px; align-items:start;">
  <div class="detail-img">
    <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width:100%;border-radius:12px;object-fit:cover;">
  </div>
  <div>
    <div class="detail-meta"><?= htmlspecialchars($product['category'] ?? '') ?></div>
    <div class="detail-name"><?= htmlspecialchars($product['name']) ?></div>
    <div class="detail-price"><?= number_format($product['price'], 0) ?> SAR</div>
    <p class="detail-desc"><?= htmlspecialchars($product['description']) ?></p>

    <?php if (isset($_GET['error'])): ?>
      <div style="color:#e53e3e;margin-bottom:12px;font-size:14px;">
        <?= htmlspecialchars($_GET['error']) ?>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['msg'])): ?>
      <div style="color:var(--gold);margin-bottom:12px;font-size:14px;">
        <?= htmlspecialchars($_GET['msg']) ?>
      </div>
    <?php endif; ?>

    <p style="font-size:13px;color:var(--muted);margin-bottom:16px;">
      In stock: <strong style="color:var(--gold)"><?= (int)$product['stock'] ?></strong> available
    </p>

    <!-- Task 4 & 5: Add to cart form with quantity -->
    <form id="addToCartForm" method="POST" action="cart_add.php" novalidate>
      <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
      <input type="hidden" name="redirect" value="product.php?id=<?= $product['product_id'] ?>">
      <div class="qty-control">
        <span class="qty-label">Quantity</span>
        <div class="qty-input-wrap">
          <button class="qty-btn" type="button" id="qtyMinus">−</button>
          <input class="qty-num" type="number" name="quantity" id="qtyInput" value="1" min="1" max="<?= (int)$product['stock'] ?>">
          <button class="qty-btn" type="button" id="qtyPlus">+</button>
        </div>
      </div>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:20px;">
        <button class="btn btn-gold" type="submit" id="addToCartBtn">Add to Cart</button>
        <a class="btn btn-outline" href="checkout.php">View Cart</a>
        <a class="btn btn-outline" href="shop.php">← Back to Shop</a>
      </div>
    </form>

    <div class="detail-help" style="margin-top:30px;">
      <h4>📦 Product Details</h4>
      <p>Color: <?= htmlspecialchars($product['color'] ?? 'N/A') ?><br>
      Free shipping on orders over 500 SAR. Returns accepted within 30 days.<br>
      For inquiries, visit our <a href="contact.php" style="color:var(--gold)">Contact</a> page.</p>
    </div>
  </div>
</div>

<!-- Task 14: Help popup window -->
<button class="help-button" id="helpButton" type="button">Need Help?</button>
<div class="modal-overlay" id="helpModal" style="display:none">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="helpTitle">
    <h3 id="helpTitle">How to Purchase</h3>
    <ul class="help-list">
      <li><strong>Choose quantity:</strong> use the − and + buttons to set how many you want.</li>
      <li><strong>Check stock:</strong> you cannot order more than what is available.</li>
      <li><strong>Add to Cart:</strong> click the gold button to add items to your cart.</li>
      <li><strong>Checkout:</strong> click View Cart to review your order and complete your purchase.</li>
      <li><strong>Returns:</strong> accepted within 30 days of delivery.</li>
    </ul>
    <div class="modal-footer">
      <button class="btn btn-outline" id="closeHelp">Close</button>
    </div>
  </div>
</div>

<script>
  // Task 13: JS form validation
  const qtyInput = document.getElementById('qtyInput');
  const maxStock = <?= (int)$product['stock'] ?>;

  document.getElementById('qtyMinus').addEventListener('click', () => {
    const v = parseInt(qtyInput.value) || 1;
    if (v > 1) qtyInput.value = v - 1;
  });
  document.getElementById('qtyPlus').addEventListener('click', () => {
    const v = parseInt(qtyInput.value) || 1;
    if (v < maxStock) qtyInput.value = v + 1;
  });

  document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    const qty = parseInt(qtyInput.value);
    if (!qty || qty < 1) {
      e.preventDefault();
      alert('Please enter a valid quantity (minimum 1).');
      return;
    }
    if (qty > maxStock) {
      e.preventDefault();
      alert('Sorry, only ' + maxStock + ' items are available in stock.');
      return;
    }
  });

  // Help modal
  document.getElementById('helpButton').addEventListener('click', () => {
    document.getElementById('helpModal').style.display = 'flex';
  });
  document.getElementById('closeHelp').addEventListener('click', () => {
    document.getElementById('helpModal').style.display = 'none';
  });
  document.getElementById('helpModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) document.getElementById('helpModal').style.display = 'none';
  });
</script>
</body>
</html>
