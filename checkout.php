<?php
// Author: Tasks 6 & 7 - Checkout, modify cart, buy products, update DB stock
// checkout.php - displays cart, allows modify/delete, handles purchase

session_start();
require 'db.php';

// --- Handle cart actions (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Task 6: Delete single item
    if ($action === 'delete') {
        $pid = (int)$_POST['product_id'];
        unset($_SESSION['cart'][$pid]);
        header('Location: checkout.php?msg=Item+removed');
        exit;
    }

    // Task 6: Update quantity
    if ($action === 'update') {
        $pid = (int)$_POST['product_id'];
        $qty = (int)$_POST['quantity'];
        if ($qty < 1) {
            unset($_SESSION['cart'][$pid]);
        } else {
            // Check stock
            $stmt = $pdo->prepare("SELECT stock FROM product WHERE product_id = ?");
            $stmt->execute([$pid]);
            $row = $stmt->fetch();
            if ($row && $qty <= $row['stock']) {
                $_SESSION['cart'][$pid]['quantity'] = $qty;
            } else {
                header('Location: checkout.php?error=Not+enough+stock');
                exit;
            }
        }
        header('Location: checkout.php?msg=Cart+updated');
        exit;
    }

    // Task 6: Empty cart
    if ($action === 'empty') {
        $_SESSION['cart'] = [];
        header('Location: checkout.php?msg=Cart+emptied');
        exit;
    }

    // Task 7: Buy is now handled by payment.php
}

// --- Build cart display ---
$cartItems = [];
$cartTotal = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pid => $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $cartTotal += $subtotal;
        $cartItems[] = array_merge($item, ['subtotal' => $subtotal]);
    }
}

$cartCount = array_sum(array_column($cartItems, 'quantity'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Selene Shop | Checkout</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav id="mainNav">
  <div class="nav-logo">SEL<span>EN</span>E</div>
  <div class="nav-links">
    <a class="nav-link" href="shop.php">Shop</a>
    <a class="nav-link" href="contact.php">Contact</a>
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

<div style="max-width:900px;margin:40px auto;padding:0 24px;">

  <?php if (isset($_GET['success'])): ?>
    <!-- Order success -->
    <div style="text-align:center;padding:60px 20px;">
      <div style="font-size:64px;margin-bottom:20px;">✓</div>
      <h2 style="color:var(--gold);margin-bottom:12px;">Order Placed!</h2>
      <p style="color:var(--muted);">Order #<?= (int)$_GET['order'] ?> confirmed. Thank you for shopping with Selene.</p>
      <br>
      <a class="btn btn-gold" href="shop.php" style="display:inline-flex;justify-content:center;">Continue Shopping</a>
    </div>

  <?php elseif (empty($cartItems)): ?>
    <!-- Empty cart -->
    <div style="text-align:center;padding:60px 20px;">
      <div style="font-size:64px;margin-bottom:20px;">🛒</div>
      <h3 style="margin-bottom:12px;">Your cart is empty</h3>
      <p style="color:var(--muted);margin-bottom:24px;">Browse our collection and add something beautiful.</p>
      <a class="btn btn-gold" href="shop.php" style="display:inline-flex;justify-content:center;">Browse Collection</a>
    </div>

  <?php else: ?>
    <h2 style="margin-bottom:24px;">Shopping Cart</h2>

    <?php if (isset($_GET['error'])): ?>
      <div style="background:#fff5f5;border:1px solid #fed7d7;color:#c53030;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
        <?= htmlspecialchars($_GET['error']) ?>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['msg'])): ?>
      <div style="background:#f0fff4;border:1px solid #c6f6d5;color:#276749;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
        <?= htmlspecialchars($_GET['msg']) ?>
      </div>
    <?php endif; ?>

    <!-- Task 6: Display all cart items with modify/delete -->
    <table style="width:100%;border-collapse:collapse;margin-bottom:24px;">
      <thead>
        <tr style="border-bottom:2px solid var(--border);text-align:left;">
          <th style="padding:12px 8px;">Product</th>
          <th style="padding:12px 8px;">Price</th>
          <th style="padding:12px 8px;">Quantity</th>
          <th style="padding:12px 8px;">Subtotal</th>
          <th style="padding:12px 8px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cartItems as $item): ?>
        <tr style="border-bottom:1px solid var(--border);">
          <td style="padding:16px 8px;display:flex;align-items:center;gap:12px;">
            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                 style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
            <span><?= htmlspecialchars($item['name']) ?></span>
          </td>
          <td style="padding:16px 8px;"><?= number_format($item['price'], 0) ?> SAR</td>
          <td style="padding:16px 8px;">
            <!-- Task 6: Modify quantity inline -->
            <form method="POST" style="display:flex;align-items:center;gap:8px;" id="updateForm<?= $item['product_id'] ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
              <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="99"
                     style="width:60px;padding:6px;border:1px solid var(--border);border-radius:6px;text-align:center;"
                     onchange="this.form.submit()">
            </form>
          </td>
          <td style="padding:16px 8px;"><?= number_format($item['subtotal'], 0) ?> SAR</td>
          <td style="padding:16px 8px;">
            <!-- Task 6: Delete single item -->
            <form method="POST" onsubmit="return confirm('Remove this item?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
              <button class="btn btn-outline btn-sm" type="submit" style="color:#e53e3e;border-color:#e53e3e;">Remove</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;flex-wrap:wrap;gap:12px;">
      <div style="font-size:20px;font-weight:600;">
        Total: <span style="color:var(--gold)"><?= number_format($cartTotal, 0) ?> SAR</span>
      </div>
      <!-- Task 6: Empty cart button -->
      <form method="POST" onsubmit="return confirm('Clear your entire cart?')">
        <input type="hidden" name="action" value="empty">
        <button class="btn btn-outline" type="submit" style="color:#e53e3e;border-color:#e53e3e;">🗑 Empty Cart</button>
      </form>
    </div>

    <!-- Task 7: Proceed to payment -->
    <?php if (!isset($_SESSION['customer_id'])): ?>
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;text-align:center;">
        <p style="color:var(--muted);margin-bottom:16px;">You need to be logged in to complete your purchase.</p>
        <a class="btn btn-gold" href="login.php?msg=Please+login+to+checkout" style="display:inline-flex;justify-content:center;">Login to Checkout</a>
      </div>
    <?php else: ?>
      <div style="text-align:right;">
        <a class="btn btn-gold" href="payment.php" style="display:inline-flex;justify-content:center;padding:14px 32px;font-size:16px;">
          💳 Proceed to Payment (<?= number_format($cartTotal, 0) ?> SAR) →
        </a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script>
  // JS validation handled in payment.php
</script>
</body>
</html>
