<?php
// Author: Customer logout
// logout.php - destroys customer session

session_start();
unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);
session_destroy();
header('Location: shop.php');
exit;
?>
