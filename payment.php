<?php
// Author: Payment, Address, Rating - saves order, address, payment & feedback to DB
// payment.php - processes checkout: address + payment method + star rating

session_start();
require 'db.php';

// Must be logged in to reach this page
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php?msg=' . urlencode('Please login to complete your purchase'));
    exit;
}

// Must have something in the cart (skip this check on success/rating pages and rating POST)
$isRatingPost = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rate');
if (!isset($_GET['success']) && !$isRatingPost && empty($_SESSION['cart'])) {
    header('Location: checkout.php?error=' . urlencode('Your cart is empty'));
    exit;
}

$error   = '';
$success = false;
$orderId = null;

// ── Handle rating POST (after order is placed) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate') {
    $rating  = (int)($_POST['rating'] ?? 0);
    $oid     = (int)($_POST['order_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $ratingError = '';

    if ($rating >= 1 && $rating <= 5 && $oid > 0) {
        try {
            // Fetch products from this order (verify it belongs to this customer)
            $items = $pdo->prepare(
                "SELECT oi.product_id
                 FROM order_items oi
                 JOIN orders o ON o.order_id = oi.order_id
                 WHERE oi.order_id = ? AND o.customer_id = ?"
            );
            $items->execute([$oid, $_SESSION['customer_id']]);
            $products = $items->fetchAll();

            foreach ($products as $row) {
                // Avoid duplicate: skip if already rated this product
                $check = $pdo->prepare(
                    "SELECT COUNT(*) FROM feedback WHERE customer_id = ? AND product_id = ?"
                );
                $check->execute([$_SESSION['customer_id'], $row['product_id']]);
                if ($check->fetchColumn() == 0) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO feedback (customer_id, product_id, rating, comment) VALUES (?, ?, ?, ?)"
                    );
                    $stmt->execute([$_SESSION['customer_id'], $row['product_id'], $rating, $comment]);
                }
            }
            header('Location: payment.php?success=1&order=' . $oid . '&rated=1');
            exit;
        } catch (PDOException $e) {
            $ratingError = 'Could not save rating: ' . $e->getMessage();
        }
    } else {
        $ratingError = 'Invalid rating. Please select 1-5 stars and try again.';
    }
    // Fall through to render the page with $ratingError shown
}

// ── Handle main payment POST ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'pay')) {

    $city          = trim($_POST['city']    ?? '');
    $street        = trim($_POST['street']  ?? '');
    $postalCode    = trim($_POST['postal_code'] ?? '');
    $payMethod     = trim($_POST['payment_method'] ?? 'credit-card');
    $billingEmail  = trim($_POST['billing_email']  ?? '');
    $phone         = trim($_POST['phone']          ?? '');

    // Server-side validation
    if (!$city || !$street || !$postalCode) {
        $error = 'Please fill in your full delivery address.';
    } elseif (!filter_var($billingEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid billing email.';
    } elseif (!$phone) {
        $error = 'Phone number is required.';
    } else {
        // Card-specific validation
        if ($payMethod === 'credit-card') {
            $cardName   = trim($_POST['card_name']   ?? '');
            $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
            $expiry     = trim($_POST['expiry']      ?? '');
            $cvc        = trim($_POST['cvc']         ?? '');

            if (!$cardName)                             $error = 'Cardholder name is required.';
            elseif (!preg_match('/^\d{16}$/', $cardNumber)) $error = 'Enter a valid 16-digit card number.';
            elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) $error = 'Use MM/YY for expiry.';
            elseif (!preg_match('/^[0-9]{3,4}$/', $cvc)) $error = 'Enter a valid CVC.';
        }
    }

    if (!$error) {
        try {
            $pdo->beginTransaction();

            // Check stock for every cart item
            foreach ($_SESSION['cart'] as $pid => $item) {
                $stmt = $pdo->prepare("SELECT stock FROM product WHERE product_id = ?");
                $stmt->execute([$pid]);
                $row = $stmt->fetch();
                if (!$row || $row['stock'] < $item['quantity']) {
                    $pdo->rollBack();
                    $error = 'Insufficient stock for: ' . htmlspecialchars($item['name']);
                    goto render;
                }
            }

            // Save delivery address
            $stmt = $pdo->prepare(
                "INSERT INTO address (customer_id, city, street, postal_code) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$_SESSION['customer_id'], $city, $street, $postalCode]);
            $addressId = $pdo->lastInsertId();

            // Calculate total
            $total = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            // Create order
            $stmt = $pdo->prepare(
                "INSERT INTO orders (customer_id, address_id, total_price, city, street, postal_code)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$_SESSION['customer_id'], $addressId, $total, $city, $street, $postalCode]);
            $orderId = $pdo->lastInsertId();

            // Insert order items & deduct stock
            foreach ($_SESSION['cart'] as $pid => $item) {
                $stmt = $pdo->prepare(
                    "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$orderId, $pid, $item['quantity'], $item['price']]);

                $stmt = $pdo->prepare("UPDATE product SET stock = stock - ? WHERE product_id = ?");
                $stmt->execute([$item['quantity'], $pid]);
            }

            // Save payment record
            $stmt = $pdo->prepare(
                "INSERT INTO payment (order_id, payment_method, payment_status, amount) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$orderId, $payMethod, 'Completed', $total]);

            $pdo->commit();

            // Task 12: Save past purchases to cookie (per customer)
            $cookieName = 'selenePastPurchases_' . $_SESSION['customer_id'];
            $pastPurchases = [];
            if (isset($_COOKIE[$cookieName])) {
                $pastPurchases = json_decode($_COOKIE[$cookieName], true) ?? [];
            }
            foreach ($_SESSION['cart'] as $item) {
                $pastPurchases[] = ['name' => $item['name'], 'date' => date('d M Y')];
            }
            $pastPurchases = array_slice($pastPurchases, -10);
            setcookie($cookieName, json_encode($pastPurchases), time() + (30 * 24 * 3600), '/');

            // Clear cart
            $_SESSION['cart'] = [];

            header('Location: payment.php?success=1&order=' . $orderId);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Order failed: ' . $e->getMessage();
        }
    }
}

render:
// ── Build cart totals for display ────────────────────────────────────────────
$cartItems = [];
$cartTotal = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pid => $item) {
        $subtotal    = $item['price'] * $item['quantity'];
        $cartTotal  += $subtotal;
        $cartItems[] = array_merge($item, ['subtotal' => $subtotal]);
    }
}
$cartCount = array_sum(array_column(array_map(fn($i) => ['q' => $i['quantity']], $cartItems), 'q'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment | Selene Shop</title>
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
    <a class="cart-btn" href="checkout.php">🛒 Cart</a>
  </div>
</nav>

<main class="auth-wrap">
  <div class="auth-card" style="max-width:600px;">

    <?php if (isset($_GET['success'])): ?>
    <!-- ── Order success + Rating ──────────────────────────────────────── -->
    <div style="text-align:center;padding:20px 0 10px;">
      <div style="font-size:56px;margin-bottom:12px;">✓</div>
      <h2 style="color:var(--gold);margin-bottom:8px;">Order Placed!</h2>
      <p style="color:var(--muted);margin-bottom:4px;">
        Order #<?= (int)$_GET['order'] ?> confirmed. Thank you for shopping with Selene.
      </p>
    </div>

    <?php if (!isset($_GET['rated'])): ?>
    <!-- Star rating form -->
    <div style="margin-top:28px;border-top:1px solid rgba(255,255,255,.08);padding-top:24px;">
      <h3 style="font-family:'Cormorant Garamond',serif;color:var(--cream);margin-bottom:8px;">Rate your purchase</h3>
      <p style="color:var(--muted);font-size:14px;margin-bottom:16px;">Tell us how you liked your shopping experience.</p>

      <form method="POST" id="ratingForm">
        <input type="hidden" name="action"   value="rate">
        <input type="hidden" name="order_id" value="<?= (int)$_GET['order'] ?>">
        <input type="hidden" name="rating"   id="ratingInput" value="0">

        <!-- Stars -->
        <div id="ratingStars" style="display:flex;gap:10px;font-size:36px;cursor:pointer;margin-bottom:16px;">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="rating-star" data-value="<?= $i ?>"
                  style="color:rgba(255,255,255,.25);transition:color .15s;">★</span>
          <?php endfor; ?>
        </div>
        <div id="ratingLabel" style="color:var(--muted);font-size:13px;margin-bottom:16px;min-height:18px;"></div>

        <div class="form-group">
          <label class="form-label" for="ratingComment">Comment (optional)</label>
          <textarea class="form-input" id="ratingComment" name="comment"
                    placeholder="Tell us what you think…" rows="3"></textarea>
        </div>
        <div id="ratingError" class="field-error" style="margin-bottom:8px;">
          <?php if (!empty($ratingError)) echo htmlspecialchars($ratingError); ?>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <button class="btn btn-gold" type="submit" id="submitRatingBtn">Submit Rating</button>
          <a class="btn btn-outline" href="shop.php">Skip → Return to Shop</a>
        </div>
      </form>
    </div>

    <?php else: ?>
    <!-- Already rated -->
    <div style="margin-top:24px;text-align:center;">
      <p style="color:var(--gold);margin-bottom:18px;">⭐ Thanks for your rating!</p>
      <a class="btn btn-gold" href="shop.php" style="display:inline-flex;justify-content:center;">Return to Shop</a>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- ── Payment form ──────────────────────────────────────────────────── -->
    <h2>Payment</h2>
    <p style="color:var(--muted);margin-bottom:20px;">Complete your order for
      <strong style="color:var(--gold)"><?= number_format($cartTotal, 0) ?> SAR</strong>
    </p>

    <!-- Order summary -->
    <?php if ($cartItems): ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:24px;font-size:14px;">
      <strong style="display:block;margin-bottom:10px;">Order Summary</strong>
      <?php foreach ($cartItems as $item): ?>
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;color:var(--muted);">
          <span><?= $item['quantity'] ?>× <?= htmlspecialchars($item['name']) ?></span>
          <span><?= number_format($item['subtotal'], 0) ?> SAR</span>
        </div>
      <?php endforeach; ?>
      <div style="border-top:1px solid var(--border);margin-top:10px;padding-top:10px;display:flex;justify-content:space-between;font-weight:600;">
        <span>Total</span>
        <span style="color:var(--gold)"><?= number_format($cartTotal, 0) ?> SAR</span>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="field-error" style="display:block;margin-bottom:16px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="paymentForm" method="POST" novalidate>
      <input type="hidden" name="action" value="pay">

      <!-- Payment method -->
      <div class="form-group">
        <label class="form-label">Payment Method</label>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:6px;">
          <?php
          $methods = ['credit-card' => 'Credit Card', 'apple-pay' => 'Apple Pay', 'tabby' => 'Tabby', 'tamara' => 'Tamara'];
          foreach ($methods as $val => $label):
          ?>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 12px;border:1px solid var(--border);border-radius:8px;">
              <input type="radio" name="payment_method" value="<?= $val ?>"
                     <?= ($val === 'credit-card') ? 'checked' : '' ?>>
              <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div id="methodNote" style="color:var(--muted);font-size:13px;margin:-4px 0 16px;">Pay with your card securely.</div>

      <!-- Card fields -->
      <div id="cardFields">
        <div class="form-group">
          <label class="form-label" for="cardName">Cardholder Name</label>
          <input class="form-input" type="text" id="cardName" name="card_name" placeholder="Your name on card">
          <div class="field-error" id="cardNameError"></div>
        </div>
        <div class="form-group">
          <label class="form-label" for="cardNumber">Card Number</label>
          <input class="form-input" type="text" id="cardNumber" name="card_number"
                 placeholder="1234 5678 9012 3456" maxlength="19">
          <div class="field-error" id="cardNumberError"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="form-group">
            <label class="form-label" for="expiry">Expiry</label>
            <input class="form-input" type="text" id="expiry" name="expiry" placeholder="MM/YY" maxlength="5">
            <div class="field-error" id="expiryError"></div>
          </div>
          <div class="form-group">
            <label class="form-label" for="cvc">CVC</label>
            <input class="form-input" type="text" id="cvc" name="cvc" placeholder="123" maxlength="4">
            <div class="field-error" id="cvcError"></div>
          </div>
        </div>
      </div>

      <!-- Billing info -->
      <div class="form-group">
        <label class="form-label" for="billingEmail">Billing Email *</label>
        <input class="form-input" type="email" id="billingEmail" name="billing_email"
               placeholder="you@example.com"
               value="<?= htmlspecialchars($_SESSION['customer_email'] ?? '') ?>">
        <div class="field-error" id="billingEmailError"></div>
      </div>
      <div class="form-group">
        <label class="form-label" for="phone">Phone Number *</label>
        <input class="form-input" type="tel" id="phone" name="phone" placeholder="05xxxxxxxx">
        <div class="field-error" id="phoneError"></div>
      </div>

      <!-- Delivery address -->
      <h3 style="margin:20px 0 12px;font-family:'Cormorant Garamond',serif;font-size:20px;">Delivery Address</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label class="form-label" for="city">City *</label>
          <input class="form-input" type="text" id="city" name="city" placeholder="e.g. Dammam">
          <div class="field-error" id="cityError"></div>
        </div>
        <div class="form-group">
          <label class="form-label" for="postalCode">Postal Code *</label>
          <input class="form-input" type="text" id="postalCode" name="postal_code" placeholder="e.g. 32241">
          <div class="field-error" id="postalError"></div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="street">Street Address *</label>
        <input class="form-input" type="text" id="street" name="street" placeholder="e.g. King Fahd Road, Building 5">
        <div class="field-error" id="streetError"></div>
      </div>

      <button class="btn btn-gold" type="submit" id="payBtn"
              style="width:100%;justify-content:center;margin-top:8px;">
        Pay <?= number_format($cartTotal, 0) ?> SAR →
      </button>
    </form>

    <p style="margin-top:20px;color:var(--muted);font-size:13px;">
      Need to change your order? <a href="checkout.php" style="color:var(--gold);">Return to cart</a>.
    </p>
    <?php endif; ?>

  </div>
</main>

<script>
// Task 13: JS validation for payment form
const paymentForm = document.getElementById('paymentForm');
const methodInputs = document.querySelectorAll('input[name="payment_method"]');
const cardFields   = document.getElementById('cardFields');
const methodNote   = document.getElementById('methodNote');
const payBtn       = document.getElementById('payBtn');

const notes = {
  'credit-card': 'Pay with your card securely.',
  'apple-pay':   'You will complete payment with Apple Pay.',
  'tabby':       'Pay in 4 installments with Tabby.',
  'tamara':      'Split your payment with Tamara.'
};

function getMethod() {
  return [...methodInputs].find(i => i.checked)?.value || 'credit-card';
}

function updateMethodUI() {
  if (!cardFields) return; // not on payment form page
  const m = getMethod();
  const isCard = m === 'credit-card';
  cardFields.style.display = isCard ? 'block' : 'none';
  if (methodNote) methodNote.textContent = notes[m] || '';
  if (payBtn) payBtn.textContent = isCard
    ? 'Pay <?= number_format($cartTotal, 0) ?> SAR →'
    : `Continue with ${m === 'apple-pay' ? 'Apple Pay' : m.charAt(0).toUpperCase() + m.slice(1)}`;
  ['cardName','cardNumber','expiry','cvc'].forEach(id => {
    const el = document.getElementById(id);
    if (el) isCard ? el.setAttribute('required','') : el.removeAttribute('required');
  });
}

methodInputs.forEach(i => i.addEventListener('change', updateMethodUI));
updateMethodUI();

// Auto-format card number with spaces
const cardNumberEl = document.getElementById('cardNumber');
if (cardNumberEl) {
  cardNumberEl.addEventListener('input', function() {
    let v = this.value.replace(/\D/g,'').slice(0,16);
    this.value = v.match(/.{1,4}/g)?.join(' ') || v;
  });
}

// Auto-format expiry MM/YY
const expiryEl = document.getElementById('expiry');
if (expiryEl) {
  expiryEl.addEventListener('input', function() {
    let v = this.value.replace(/\D/g,'');
    if (v.length >= 3) v = v.slice(0,2) + '/' + v.slice(2,4);
    this.value = v;
  });
}

function clearErrors() {
  ['cardNameError','cardNumberError','expiryError','cvcError',
   'billingEmailError','phoneError','cityError','postalError','streetError']
    .forEach(id => { const el = document.getElementById(id); if (el) el.textContent = ''; });
}

if (paymentForm) {
  paymentForm.addEventListener('submit', function(e) {
    clearErrors();
    let valid = true;
    const method = getMethod();

    if (method === 'credit-card') {
      const name   = document.getElementById('cardName').value.trim();
      const num    = document.getElementById('cardNumber').value.replace(/\s+/g,'');
      const expiry = document.getElementById('expiry').value.trim();
      const cvc    = document.getElementById('cvc').value.trim();

      if (!name)                               { document.getElementById('cardNameError').textContent   = 'Cardholder name is required.'; valid = false; }
      if (!/^\d{16}$/.test(num))               { document.getElementById('cardNumberError').textContent = 'Enter a valid 16-digit card number.'; valid = false; }
      if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry)) { document.getElementById('expiryError').textContent = 'Use MM/YY format.'; valid = false; }
      if (!/^\d{3,4}$/.test(cvc))              { document.getElementById('cvcError').textContent        = 'Enter a valid CVC.'; valid = false; }
    }

    const email  = document.getElementById('billingEmail').value.trim();
    const phone  = document.getElementById('phone').value.trim();
    const city   = document.getElementById('city').value.trim();
    const postal = document.getElementById('postalCode').value.trim();
    const street = document.getElementById('street').value.trim();

    if (!/^\S+@\S+\.\S+$/.test(email)) { document.getElementById('billingEmailError').textContent = 'Enter a valid email address.'; valid = false; }
    if (!phone)                         { document.getElementById('phoneError').textContent        = 'Phone number is required.'; valid = false; }
    if (!city)                          { document.getElementById('cityError').textContent         = 'City is required.'; valid = false; }
    if (!postal)                        { document.getElementById('postalError').textContent       = 'Postal code is required.'; valid = false; }
    if (!street)                        { document.getElementById('streetError').textContent       = 'Street address is required.'; valid = false; }

    if (!valid) e.preventDefault();
  });
}

// Star rating JS
const stars = document.querySelectorAll('.rating-star');
const ratingInput = document.getElementById('ratingInput');

function applyStars(value) {
  stars.forEach(s => {
    s.style.color = Number(s.dataset.value) <= value ? '#c9a96e' : 'rgba(255,255,255,.25)';
  });
  const labels = ['','Poor','Fair','Good','Very Good','Excellent'];
  const lbl = document.getElementById('ratingLabel');
  if (lbl) lbl.textContent = value > 0 ? labels[value] : '';
}

stars.forEach(star => {
  star.addEventListener('click', () => {
    const v = Number(star.dataset.value);
    if (ratingInput) ratingInput.value = v;
    applyStars(v);
  });
  star.addEventListener('mouseover', () => applyStars(Number(star.dataset.value)));
  star.addEventListener('mouseleave', () => applyStars(Number(ratingInput?.value || 0)));
});

const ratingForm = document.getElementById('ratingForm');
if (ratingForm) {
  ratingForm.addEventListener('submit', function(e) {
    const v = Number(document.getElementById('ratingInput')?.value || 0);
    if (v < 1) {
      e.preventDefault();
      const err = document.getElementById('ratingError');
      if (err) err.textContent = 'Please select a star rating before submitting.';
    }
  });
}
</script>
</body>
</html>