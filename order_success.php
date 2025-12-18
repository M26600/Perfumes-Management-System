<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check order context
// Accept explicit order_id, or fall back to session
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    $order_id = (int)($_SESSION['order_id'] ?? 0);
}
if ($order_id <= 0) {
    header('Location: products.php');
    exit();
}
$user_id = (int)$_SESSION['user_id'];
$is_cashier = is_cashier();
$is_admin = !empty($_SESSION['is_admin']);

// Get order details
$order = [];
$order_items = [];

try {
    // Get order info
    if ($is_admin) {
        // Admin can view any order
        $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->bind_param('i', $order_id);
    } else {
        // Customers and cashiers: start with user-bound query
        $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->bind_param('i', $order_id);
    }
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        throw new Exception('Order not found');
    }

    // If user is not admin and is not the owner, deny access
    if (!$is_admin && (int)$order['user_id'] !== $user_id) {
        throw new Exception('You are not allowed to view this order.');
    }

    // Additional rule: a cashier cannot view receipts for their own orders
    if ($is_cashier && !$is_admin && (int)$order['user_id'] === $user_id) {
        throw new Exception('Cashiers cannot review receipts for their own orders.');
    }

    // Check order status
    $status = strtolower($order['status'] ?? '');
    $is_rejected = ($status === 'rejected');
    $awaiting_approval = !$is_rejected && !in_array($status, ['completed','paid','approved'], true);

    // Initialize variables
    $order_items = [];
    $subtotal = 0;
    $tax = 0;
    $grand_total = 0;
    $points_awarded = 0;
    $current_loyalty_points = 0;
    $free_perfume_available = false;

    // Only calculate details if approved
    if (!$awaiting_approval) {
        // Get order items with product names (join with products table)
        $stmt = $conn->prepare("
            SELECT oi.order_id, oi.product_id, oi.quantity, oi.price, 
                   p.name as product_name 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Calculate item total
            $row['item_total'] = (float)$row['price'] * (int)$row['quantity'];
            $order_items[] = $row;
        }
        $stmt->close();

        // Calculate subtotal from order items
        foreach ($order_items as $item) {
            $subtotal += $item['item_total'];
        }
        
        // Calculate tax (8%)
        $tax = $subtotal * 0.08;
        
        // Grand total is stored in order['total'] field
        $grand_total = (float)$order['total'];
        
        // If grand_total doesn't match, use calculated value
        if (abs($grand_total - ($subtotal + $tax)) > 0.01) {
            $grand_total = $subtotal + $tax;
        }
    }

} catch (Exception $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    $_SESSION['error'] = "Error retrieving order details. Please contact support.";
    header('Location: products.php');
    exit();
}

// Get current loyalty points and check for free perfume eligibility (only if approved and for customers)
if (!$awaiting_approval && !$is_cashier && !$is_admin) {
    $points_awarded = isset($_SESSION['points_awarded']) ? (int)$_SESSION['points_awarded'] : 0;

    $points_stmt = $conn->prepare("SELECT loyalty_points FROM users WHERE user_id = ?");
    if ($points_stmt) {
        $points_stmt->bind_param('i', $user_id);
        $points_stmt->execute();
        $points_result = $points_stmt->get_result();
        if ($points_row = $points_result->fetch_assoc()) {
            $current_loyalty_points = (int)$points_row['loyalty_points'];
            if ($current_loyalty_points > 5) {
                $free_perfume_available = true;
            }
        }
        $points_stmt->close();
    }
}

// Clear the success flag to prevent refresh issues
unset($_SESSION['order_success']);
unset($_SESSION['points_awarded']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Perfumes Management System</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }
        header {
            background: white;
            padding: 1.5rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .order-confirmation {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success-icon {
            color: #28a745;
            font-size: 4rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        .order-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border: 1px solid #e9ecef;
        }
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            background: white;
        }
        .order-items th, .order-items td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .order-items th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-align: center;
        }
        .order-items td {
            text-align: center;
        }
        .order-items td:first-child {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .payment-info {
            background: #e9f7ef;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            border-left: 4px solid #28a745;
        }
        .payment-info h3 {
            margin-top: 0;
            color: #155724;
        }
        .payment-info p {
            margin: 0.5rem 0;
        }
        .loyalty-info {
            background: #fff3cd;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            border-left: 4px solid #ffc107;
        }
        .loyalty-info h3 {
            margin-top: 0;
            color: #856404;
        }
        .free-perfume-notice {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .free-perfume-notice h3 {
            margin-top: 0;
            font-size: 1.5rem;
        }
        .free-perfume-notice .btn {
            margin-top: 1rem;
            background: white;
            color: #667eea;
            border: none;
        }
        .free-perfume-notice .btn:hover {
            background: #f8f9fa;
        }
        .order-actions {
            text-align: center;
            margin-top: 2rem;
        }
        .order-actions a {
            display: inline-block;
            margin: 0.5rem;
        }
        .text-center {
            text-align: center;
        }
        footer {
            background: white;
            padding: 2rem 0;
            margin-top: 3rem;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
<header>
    <div class="container">
        <div class="header-content">
            <h1>Order Confirmed</h1>
            <a href="products.php" class="btn btn-outline">Continue Shopping</a>
        </div>
    </div>
</header>

<main class="container page-content">
<?php if (!empty($is_rejected)): ?>
    <div class="order-confirmation" style="background:#fdecea;border:1px solid #f5c2c7;">
        <div class="text-center">
            <div class="success-icon" style="color:#b02a37; font-size:4rem;">✖</div>
            <h2>Payment Rejected</h2>
            <p style="font-size:1.1rem; margin:1rem 0;">Your payment proof for <strong>Order #<?php echo htmlspecialchars($order_id); ?></strong> was rejected by the cashier.</p>
            <p style="color:#666;">Please place a new order and upload a valid payment proof. If you believe this is an error, contact support.</p>
            <div style="margin-top:2rem;">
                <a href="products.php" class="btn btn-primary">Shop Again</a>
                <a href="contact.php" class="btn btn-outline" style="margin-left:10px;">Contact Support</a>
            </div>
        </div>
    </div>
<?php elseif (!empty($awaiting_approval)): ?>
    <div class="order-confirmation" style="background:#fff3cd;border:1px solid #ffe8a1;">
        <div class="text-center">
            <div class="success-icon" style="color:#856404; font-size:4rem;">⏳</div>
            <h2>Payment Under Review</h2>
            <p style="font-size:1.1rem; margin:1rem 0;">Your payment proof for <strong>Order #<?php echo htmlspecialchars($order_id); ?></strong> has been submitted and is awaiting cashier approval.</p>
            <p style="color:#666;">Once the cashier approves your payment, you'll be able to view your complete order receipt here.</p>
            <div style="margin-top:2rem;">
                <a href="order_success.php?order_id=<?php echo (int)$order_id; ?>" class="btn btn-primary">Check Status</a>
                <a href="products.php" class="btn btn-outline" style="margin-left:10px;">Continue Shopping</a>
            </div>
            <p style="margin-top:1.5rem; font-size:0.9rem; color:#666;">You can bookmark this page and check back later: <br><code style="background:#f0f0f0; padding:4px 8px; border-radius:4px;">order_success.php?order_id=<?php echo (int)$order_id; ?></code></p>
        </div>
    </div>
    <?php else: ?>
    <div class="order-confirmation">
        <div class="text-center">
            <div class="success-icon">✓</div>
            <h2>Thank You for Your Order!</h2>
            <p>Your order #<?php echo htmlspecialchars($order_id); ?> has been received and is being processed.</p>
        </div>
        
        <?php if ($points_awarded > 0): ?>
            <div class="loyalty-info">
                <h3>🎉 Loyalty Point Earned!</h3>
                <p>You've earned <strong>1 loyalty point</strong> from this order!</p>
                <p>Your current total: <strong><?php echo $current_loyalty_points; ?> loyalty points</strong> (<?php echo $current_loyalty_points; ?> orders completed)</p>
                <p>Earn 1 point with every order you complete. Get more than 5 points to qualify for a free perfume!</p>
            </div>
        <?php endif; ?>

        <?php if ($free_perfume_available): ?>
            <div class="free-perfume-notice">
                <h3>🎁 Congratulations! You've Earned a Free Perfume!</h3>
                <p>You have completed <strong><?php echo $current_loyalty_points; ?> orders</strong> (<?php echo $current_loyalty_points; ?> loyalty points), which qualifies you for a FREE perfume!</p>
                <p>Redeem your free perfume now and we'll deduct 6 points from your account.</p>
                <a href="redeem_free_perfume.php" class="btn">Claim Your Free Perfume</a>
            </div>
        <?php endif; ?>

        <div class="payment-info">
            <h3>Payment Instructions</h3>
            <p>Please make a Mobile Money payment to complete your order:</p>
            <p><strong>Mobile Money Number:</strong> +256700000000</p>
            <p><strong>Amount to Pay:</strong> $<?php echo number_format($grand_total, 2); ?></p>
            <p><strong>Reference:</strong> ORDER-<?php echo $order_id; ?></p>
            <p>Once payment is received, we'll process your order and notify you when it's on its way.</p>
        </div>
        
        <div class="order-details">
            <h3>Order Summary</h3>
            <p><strong>Order Number:</strong> #<?php echo $order_id; ?></p>
            <?php if (isset($order['created_at']) && !empty($order['created_at'])): ?>
                <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
            <?php else: ?>
                <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a'); ?></p>
            <?php endif; ?>
            
            <h4>Order Items</h4>
            <?php if (!empty($order_items)): ?>
                <table class="order-items">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo number_format((float)$item['price'], 2); ?></td>
                            <td>$<?php echo number_format($item['item_total'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                            <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Tax (8%):</strong></td>
                            <td><strong>$<?php echo number_format($tax, 2); ?></strong></td>
                        </tr>
                        <tr style="font-size: 1.1em; border-top: 2px solid #333;">
                            <td colspan="3" class="text-right"><strong>Total:</strong></td>
                            <td><strong>$<?php echo number_format($grand_total, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <p>No items found for this order.</p>
            <?php endif; ?>
        </div>
        
        <div class="order-actions">
            <p>Need help? <a href="contact.php">Contact our support team</a></p>
            <a href="products.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
