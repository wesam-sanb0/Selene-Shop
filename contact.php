<?php
// contact.php - handles contact form submission and saves to DB

session_start();
require 'db.php';

$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $message = trim($_POST['message'] ?? '');

    // Server-side validation
    if (!$name) {
        $errorMsg = 'Please enter your name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Please enter a valid email address.';
    } elseif (!$message) {
        $errorMsg = 'Please enter a message.';
    } else {
        try {
            // Use logged-in customer_id if available, otherwise NULL
            $customerId = $_SESSION['customer_id'] ?? null;

            $stmt = $pdo->prepare(
                "INSERT INTO contact_messages (name, email, customer_id, message)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $customerId, $message]);
            $successMsg = "Message sent! We'll reply soon.";
        } catch (PDOException $e) {
            $errorMsg = 'Could not send message: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Selene Shop Contact</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<input type="radio" name="page" id="tab-contact" checked hidden>

<nav id="mainNav">
  <div class="nav-logo">SEL<span>EN</span>E</div>
  <div class="nav-links">
    <a class="nav-link" href="shop.php">Shop</a>
    <?php if (isset($_SESSION['customer_id'])): ?>
      <span class="nav-link" style="color:var(--gold)">Hi, <?= htmlspecialchars($_SESSION['customer_name']) ?></span>
      <a class="nav-link auth-nav-btn" href="logout.php">Logout</a>
    <?php else: ?>
      <a class="nav-link auth-nav-btn" href="login.php">Login</a>
    <?php endif; ?>
    <a class="nav-link" href="admin.php">Admin</a>
  </div>
</nav>

<div class="page" id="page-contact">
  <div class="contact-layout">
    <div class="contact-info">
      <h2>Get in<br><em style="font-family:'Cormorant Garamond',serif;font-style:italic;color:var(--gold)">Touch</em></h2>
      <p>We'd love to hear from you. Whether it's a question about a product, a custom order, or simply to say hello — our team is here.</p>
      <div class="contact-details">
        <div class="contact-item">
          <div class="contact-item-icon">📍</div>
          <div class="contact-item-text">
            <h4>Our Atelier</h4>
            <p>Saudi Arabia<br>Dammam</p>
          </div>
        </div>
        <div class="contact-item">
          <div class="contact-item-icon">📞</div>
          <div class="contact-item-text">
            <h4>Phone</h4>
            <p>013 333 2003</p>
          </div>
        </div>
        <div class="contact-item">
          <div class="contact-item-icon">✉️</div>
          <div class="contact-item-text">
            <h4>Email</h4>
            <p>selene_store@gmail.com</p>
          </div>
        </div>
      </div>
      <div class="contact-form-card">
        <h3>Send a message</h3>

        <?php if ($successMsg): ?>
          <div style="color:var(--gold);margin-bottom:16px;">✓ <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
          <div style="color:#e53e3e;margin-bottom:16px;"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <?php if (!$successMsg): ?>
        <form method="POST" id="contactForm" novalidate>
          <div class="form-group">
            <label class="form-label" for="contactName">Name</label>
            <input class="form-input" type="text" id="contactName" name="name"
                   placeholder="Your name" required
                   value="<?= htmlspecialchars($_POST['name'] ?? ($_SESSION['customer_name'] ?? '')) ?>">
            <div class="form-error" id="contactNameError"></div>
          </div>
          <div class="form-group">
            <label class="form-label" for="contactEmail">Email</label>
            <input class="form-input" type="email" id="contactEmail" name="email"
                   placeholder="you@example.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? ($_SESSION['customer_email'] ?? '')) ?>">
            <div class="form-error" id="contactEmailError"></div>
          </div>
          <div class="form-group">
            <label class="form-label" for="contactMessage">Message</label>
            <textarea class="form-input" id="contactMessage" name="message"
                      placeholder="Tell us what you need..." rows="5" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            <div class="form-error" id="contactMessageError"></div>
          </div>
          <button class="btn btn-gold" type="submit">Send Message</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <div class="map-container">
      <iframe class="map-frame"
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14296.576211518623!2d50.19200129632715!3d26.386533306812428!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e49ef826c3c4529%3A0x126e95aa294da63c!2z2YPZhNmK2Kkg2LnZhNmI2YUg2KfZhNit2KfYs9ioINmI2KrZgtmG2YrYqSDYp9mE2YXYudmE2YjZhdin2Kog2YTZhNio2YbYp9iq!5e0!3m2!1sar!2ssa!4v1776272288690!5m2!1sar!2ssa"
        width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
      </iframe>
      <div class="map-pin">📍</div>
    </div>
  </div>
</div>

<script>
// Client-side validation (runs before PHP submission)
const contactForm = document.getElementById('contactForm');
if (contactForm) {
  contactForm.addEventListener('submit', function(e) {
    let valid = true;

    const name = document.getElementById('contactName').value.trim();
    const email = document.getElementById('contactEmail').value.trim();
    const message = document.getElementById('contactMessage').value.trim();

    document.getElementById('contactNameError').textContent    = '';
    document.getElementById('contactEmailError').textContent   = '';
    document.getElementById('contactMessageError').textContent = '';

    if (!name) {
      document.getElementById('contactNameError').textContent = 'Please enter your name.';
      valid = false;
    }
    if (!/^\S+@\S+\.\S+$/.test(email)) {
      document.getElementById('contactEmailError').textContent = 'Please enter a valid email.';
      valid = false;
    }
    if (!message) {
      document.getElementById('contactMessageError').textContent = 'Please enter a message.';
      valid = false;
    }

    if (!valid) e.preventDefault();
  });
}
</script>
</body>
</html>
