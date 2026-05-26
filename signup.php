<?php
// Author: Customer Registration - saves to customer table in DB
// signup.php - registers a new customer with hashed password

session_start();
require 'db.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: shop.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $phone    = trim($_POST['phone'] ?? '');

    // Task 13: Validation
    if (!$name || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already registered
        $stmt = $pdo->prepare("SELECT customer_id FROM customer WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'This email is already registered. Please login.';
        } else {
            // phone is NOT NULL in DB, default to empty string if not provided
            $phone = $phone ?: '';
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO customer (name, email, password, phone) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed, $phone]);
                $customerId = $pdo->lastInsertId();
                // Auto-login after signup
                $_SESSION['customer_id']    = $customerId;
                $_SESSION['customer_name']  = $name;
                $_SESSION['customer_email'] = $email;
                header('Location: shop.php?msg=' . urlencode('Welcome to Selene, ' . $name . '!'));
                exit;
            } catch (PDOException $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up | Selene Shop</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav id="mainNav">
  <div class="nav-logo">SEL<span>EN</span>E</div>
  <div class="nav-links">
    <a class="nav-link" href="shop.php">Shop</a>
    <a class="nav-link" href="contact.php">Contact</a>
    <a class="nav-link auth-nav-btn" href="login.php">Login</a>
  </div>
</nav>
<main class="auth-wrap">
  <div class="auth-card">
    <h2>Create Account</h2>
    <p>Register to save your shopping progress and track orders.</p>

    <?php if ($error): ?>
      <div class="field-error" style="display:block;margin-bottom:12px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="signupForm" method="POST" novalidate>
      <div class="form-group">
        <label class="form-label" for="signupName">Full Name *</label>
        <input class="form-input" type="text" id="signupName" name="name"
               placeholder="Your name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        <div class="field-error" id="nameError"></div>
      </div>
      <div class="form-group">
        <label class="form-label" for="signupEmail">Email *</label>
        <input class="form-input" type="email" id="signupEmail" name="email"
               placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <div class="field-error" id="emailError"></div>
      </div>
      <div class="form-group">
        <label class="form-label" for="signupPhone">Phone (optional)</label>
        <input class="form-input" type="text" id="signupPhone" name="phone"
               placeholder="05xxxxxxxx" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        <div class="field-error" id="phoneError"></div>
      </div>
      <div class="form-group">
        <label class="form-label" for="signupPassword">Password * (min 8 characters)</label>
        <input class="form-input" type="password" id="signupPassword" name="password"
               placeholder="At least 8 characters" required>
        <div class="field-error" id="passwordError"></div>
      </div>
      <div class="form-group">
        <label class="form-label" for="signupConfirm">Confirm Password *</label>
        <input class="form-input" type="password" id="signupConfirm" name="confirm"
               placeholder="Repeat password" required>
        <div class="field-error" id="confirmError"></div>
      </div>
      <button class="btn btn-gold" type="submit" style="width:100%;justify-content:center;">Create Account</button>
    </form>
    <p style="margin-top:24px;color:var(--muted);font-size:13px;">
      Already have an account? <a href="login.php" style="color:var(--gold)">Login</a>.
    </p>
  </div>
</main>

<script>
  // Task 13: JS validation
  const form = document.getElementById('signupForm');
  function validate() {
    let valid = true;
    const name = document.getElementById('signupName').value.trim();
    const email = document.getElementById('signupEmail').value.trim();
    const phone = document.getElementById('signupPhone').value.trim();
    const password = document.getElementById('signupPassword').value;
    const confirm = document.getElementById('signupConfirm').value;

    document.getElementById('nameError').textContent = '';
    document.getElementById('emailError').textContent = '';
    document.getElementById('phoneError').textContent = '';
    document.getElementById('passwordError').textContent = '';
    document.getElementById('confirmError').textContent = '';

    if (!name) { document.getElementById('nameError').textContent = 'Name is required.'; valid = false; }
    if (!email) { document.getElementById('emailError').textContent = 'Email is required.'; valid = false; }
    else if (!/^\S+@\S+\.\S+$/.test(email)) { document.getElementById('emailError').textContent = 'Enter a valid email.'; valid = false; }
    if (phone && !/^05\d{8}$/.test(phone)) { document.getElementById('phoneError').textContent = 'Enter a valid Saudi phone number (05xxxxxxxx).'; valid = false; }
    if (!password || password.length < 8) { document.getElementById('passwordError').textContent = 'Password must be at least 8 characters.'; valid = false; }
    if (password !== confirm) { document.getElementById('confirmError').textContent = 'Passwords do not match.'; valid = false; }
    return valid;
  }

  form.addEventListener('submit', e => { if (!validate()) e.preventDefault(); });
  ['signupName','signupEmail','signupPhone','signupPassword','signupConfirm'].forEach(id => {
    document.getElementById(id).addEventListener('input', validate);
  });
</script>
</body>
</html>
