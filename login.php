<?php
// Author: Task 8 - Customer Login, checked against database
// login.php - authenticates customer via DB

session_start();
require 'db.php';

// Already logged in
if (isset($_SESSION['customer_id'])) {
    header('Location: shop.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Task 13: Server-side validation
    if (!$email || !$password) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Task 8: Check credentials against customer table in DB
        $stmt = $pdo->prepare("SELECT * FROM customer WHERE email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if ($customer && password_verify($password, $customer['password'])) {
            // Login successful - start session
            $_SESSION['customer_id']   = $customer['customer_id'];
            $_SESSION['customer_name'] = $customer['name'];
            $_SESSION['customer_email']= $customer['email'];
            $redirect = isset($_GET['msg']) ? 'checkout.php' : 'shop.php';
            header("Location: $redirect");
            exit;
        } else {
            $error = 'Incorrect email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Selene Shop</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav id="mainNav">
  <div class="nav-logo">SEL<span>EN</span>E</div>
  <div class="nav-links">
    <a class="nav-link" href="shop.php">Shop</a>
    <a class="nav-link" href="contact.php">Contact</a>
    <a class="nav-link auth-nav-btn" href="signup.php">Sign Up</a>
  </div>
</nav>
<main class="auth-wrap">
  <div class="auth-card">
    <h2>Login</h2>
    <p>Enter your account details to continue shopping.</p>

    <?php if (isset($_GET['msg'])): ?>
      <div style="color:var(--gold);font-size:13px;margin-bottom:12px;"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="field-error" style="display:block;margin-bottom:12px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="loginForm" method="POST" novalidate>
      <div class="form-group">
        <label class="form-label" for="loginEmail">Email</label>
        <input class="form-input" type="email" id="loginEmail" name="email"
               placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <div class="field-error" id="loginEmailError"></div>
      </div>
      <div class="form-group">
        <label class="form-label" for="loginPassword">Password</label>
        <input class="form-input" type="password" id="loginPassword" name="password"
               placeholder="At least 8 characters" required>
        <div class="field-error" id="loginPasswordError"></div>
      </div>
      <button class="btn btn-gold" type="submit" style="width:100%;justify-content:center;">Login</button>
    </form>
    <p style="margin-top:24px;color:var(--muted);font-size:13px;">
      Don't have an account? <a href="signup.php" style="color:var(--gold)">Create one</a>.
    </p>
  </div>
</main>

<script>
  // Task 13: JS validation
  const loginForm = document.getElementById('loginForm');
  loginForm.addEventListener('submit', function(e) {
    let valid = true;
    const email = document.getElementById('loginEmail');
    const password = document.getElementById('loginPassword');
    const emailErr = document.getElementById('loginEmailError');
    const passErr = document.getElementById('loginPasswordError');
    emailErr.textContent = '';
    passErr.textContent = '';

    if (!email.value.trim()) {
      emailErr.textContent = 'Email is required.';
      valid = false;
    } else if (!/^\S+@\S+\.\S+$/.test(email.value)) {
      emailErr.textContent = 'Enter a valid email address.';
      valid = false;
    }
    if (!password.value) {
      passErr.textContent = 'Password is required.';
      valid = false;
    } else if (password.value.length < 8) {
      passErr.textContent = 'Password must be at least 8 characters.';
      valid = false;
    }
    if (!valid) e.preventDefault();
  });
</script>
</body>
</html>
