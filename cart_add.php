<?php
// Author: Task 5 - Add to Cart using PHP Sessions + stock check
// cart_add.php - handles POST from product page or shop, validates stock

session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: shop.php');
    exit;
}

// Must be logged in to add items to cart
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php?msg=' . urlencode('Please login to add items to your cart'));
    exit;
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity  = isset($_POST['quantity'])   ? (int)$_POST['quantity']   : 1;
$redirect  = isset($_POST['redirect'])  ? $_POST['redirect']         : 'shop.php';

// Sanitize redirect to prevent open redirect
$allowedRedirects = ['shop.php', 'checkout.php'];
$redirectBase = strtok($redirect, '?');
if (!in_array($redirectBase, $allowedRedirects) && strpos($redirect, 'product.php') !== 0) {
    $redirect = 'shop.php';
}

if ($productId <= 0 || $quantity < 1) {
    header('Location: shop.php?msg=Invalid+request');
    exit;
}

// Task 5: Check quantity against available stock in database
$stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: shop.php?msg=Product+not+found');
    exit;
}

// Check how many already in cart
$alreadyInCart = 0;
if (isset($_SESSION['cart'][$productId])) {
    $alreadyInCart = $_SESSION['cart'][$productId]['quantity'];
}
$totalRequested = $alreadyInCart + $quantity;

if ($totalRequested > $product['stock']) {
    $available = $product['stock'] - $alreadyInCart;
    $errorMsg = $available > 0
        ? "Only $available more item(s) available (you already have $alreadyInCart in cart)."
        : "No more stock available for this item.";

    // Redirect back with error message
    $sep = strpos($redirect, '?') !== false ? '&' : '?';
    header("Location: $redirect{$sep}error=" . urlencode($errorMsg));
    exit;
}

// Task 5: Add to PHP session cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$productId])) {
    // Update existing cart item
    $_SESSION['cart'][$productId]['quantity'] += $quantity;
} else {
    // Add new cart item
    $_SESSION['cart'][$productId] = [
        'product_id' => $product['product_id'],
        'name'       => $product['name'],
        'price'      => $product['price'],
        'image'      => $product['image'],
        'quantity'   => $quantity,
    ];
}

$sep = strpos($redirect, '?') !== false ? '&' : '?';
header("Location: $redirect{$sep}msg=" . urlencode($product['name'] . ' added to cart'));
exit;
?>
