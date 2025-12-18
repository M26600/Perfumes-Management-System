<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$total = 0;
$items = [];

// Calculate total and prepare items array
foreach ($cart as $item) {
    $item_total = $item['price'] * $item['qty'];
    $total += $item_total;
    $items[] = [
        'id' => $item['id'],
        'name' => $item['name'],
        'qty' => $item['qty'],
        'price' => $item['price'],
        'total' => $item_total
    ];
}

// Add tax (8% example)
$tax = $total * 0.08;
$grand_total = $total + $tax;

// Merchant MoMo details (replace with actual details)
$merchant_momo_number = '+84123456789';
$merchant_contact = '+84123456789';
$merchant_name = 'Perfumes Management System';

// Start transaction
$conn->begin_transaction();

try {
    // Create order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, payment_method, status, momo_number) VALUES (?, ?, 'momo', 'pending_payment', ?)");
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('ids', $user_id, $grand_total, $merchant_momo_number);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $order_id = $conn->insert_id;
    $stmt->close();

    // Insert order items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        throw new Exception("Prepare failed for order items: " . $conn->error);
    }
    
    foreach ($items as $item) {
        if (!$stmt->bind_param('iiid', 
            $order_id, 
            $item['id'], 
            $item['qty'], 
            $item['price']
        )) {
            throw new Exception("Binding parameters failed: " . $stmt->error);
        }
        if (!$stmt->execute()) {
            throw new Exception("Execute failed for order item: " . $stmt->error);
        }
    }
    $stmt->close();

    // Update product stock
    $update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    if ($update_stock === false) {
        throw new Exception("Prepare failed for stock update: " . $conn->error);
    }
    
    foreach ($cart as $item) {
        if (!$update_stock->bind_param('ii', $item['qty'], $item['id'])) {
            throw new Exception("Binding parameters failed for stock update: " . $update_stock->error);
        }
        if (!$update_stock->execute()) {
            throw new Exception("Stock update failed: " . $update_stock->error);
        }
    }
    $update_stock->close();

    // Change order status to awaiting_proof
    $up = $conn->prepare("UPDATE orders SET status='awaiting_proof' WHERE order_id=?");
    $up->bind_param('i', $order_id);
    $up->execute();
    $up->close();

    // Commit transaction
    $conn->commit();

    // Clear cart only after successful order creation
    unset($_SESSION['cart']);

    // Redirect to proof upload page
    $_SESSION['order_id'] = $order_id;
    header('Location: payment_proof.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log the error
    error_log("Order processing failed: " . $e->getMessage());
    
    // Redirect back to cart with error
    $_SESSION['checkout_error'] = "We encountered an error processing your order. Please try again or contact support.";
    header('Location: cart.php');
    exit;
}
?>