<?php
session_start();
require_once 'includes/db_connect.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit();
}

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity   = (int)($_POST['quantity'] ?? 1);
$posted_price = isset($_POST['unit_price']) ? (float)$_POST['unit_price'] : null;

if ($product_id <= 0 || $quantity <= 0) {
    $_SESSION['cart_msg'] = 'Invalid request.';
    header('Location: products.php');
    exit();
}

/* ---------- 1. Verify product & stock ---------- */
$stmt = $conn->prepare(
    "SELECT id, name, price, stock, IFNULL(discount_percent,0) AS discount_percent FROM products WHERE id = ? AND stock > 0 FOR UPDATE"
);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['cart_msg'] = 'Product not available or out of stock.';
    $_SESSION['cart_msg_error'] = true;
    header('Location: products.php');
    exit();
}

if ($quantity > $product['stock']) {
    $_SESSION['cart_msg'] = "Only {$product['stock']} unit(s) in stock.";
    $_SESSION['cart_msg_error'] = true;
    header('Location: products.php');
    exit();
}

/* ---------- 2. Add / update cart (session) ---------- */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['id'] == $product_id) {
        $newQty = $item['qty'] + $quantity;
        if ($newQty > $product['stock']) {
            $_SESSION['cart_msg'] = "Cannot add more – only {$product['stock']} in stock.";
            $_SESSION['cart_msg_error'] = true;
            header('Location: products.php');
            exit();
        }
        $item['qty'] = $newQty;
        $found = true;
        break;
    }
}

if (!$found) {
    $price = (float)$product['price'];
    $discount_percent = (int)($product['discount_percent'] ?? 0);
    $final_price = $discount_percent > 0 ? $price * (1 - $discount_percent/100) : $price;
    // Trust server-side calculated price; fall back to posted if provided and lower
    if ($posted_price !== null && $posted_price < $final_price) {
        $final_price = $posted_price; // allow promotional lower price if posted
    }
    $_SESSION['cart'][] = [
        'id'    => $product['id'],
        'name'  => $product['name'],
        'price' => $final_price,
        'qty'   => $quantity,
        'stock' => (int)$product['stock']
    ];
}

$_SESSION['cart_msg'] = "Added {$quantity} × {$product['name']} to cart.";
header('Location: cart.php');
exit();