<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db_connect.php';
$userLoggedIn = false;
$username = '';
if (!empty($_SESSION['user_id'])) {
$userLoggedIn = true;
$username = htmlspecialchars($_SESSION['username']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<header class="site-header">
<div class="container header-content">
<a href="index.html" class="logo"><i class="fas fa-spray-can"></i> Perfume</a>
<nav>
<ul class="nav-links">
<li><a href="index.html">Home</a></li>
<li><a href="products.php">Shop</a></li>
<li><a href="about.php">About</a></li>
<li><a href="contact.php">Contact</a></li>
<?php if ($userLoggedIn): ?>
<li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
<li class="nav-welcome">Welcome, <?= $username ?> (<a href="logout.php">Logout</a>)</li>
<?php else: ?>
<li><a href="login.php" class="btn btn-outline signin-btn">Sign In</a></li>
<?php endif; ?>
</ul>
</nav>
<div class="menu-toggle"><i class="fas fa-bars"></i></div>
</div>
</header>
<main>