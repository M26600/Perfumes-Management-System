<?php
session_start();
require_once 'includes/db_connect.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = (int)$_SESSION['user_id'];

/* ---------- Handle quantity updates & removals ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $action     = $_POST['action'] ?? '';

    if ($product_id > 0 && isset($_SESSION['cart'])) {
        // Get current stock from database for accurate validation
        $stock_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $current_stock = 0;
        if ($stock_stmt) {
            $stock_stmt->bind_param('i', $product_id);
            $stock_stmt->execute();
            $stock_result = $stock_stmt->get_result();
            if ($stock_row = $stock_result->fetch_assoc()) {
                $current_stock = (int)$stock_row['stock'];
            }
            $stock_stmt->close();
        }

        foreach ($_SESSION['cart'] as $k => $item) {
            if ($item['id'] == $product_id) {
                if ($action === 'remove') {
                    unset($_SESSION['cart'][$k]);
                    $_SESSION['cart_msg'] = "Removed " . htmlspecialchars($item['name']) . " from cart.";
                } elseif ($action === 'update') {
                    $newQty = (int)($_POST['qty'] ?? 0);
                    if ($newQty <= 0) {
                        unset($_SESSION['cart'][$k]);
                        $_SESSION['cart_msg'] = "Removed " . htmlspecialchars($item['name']) . " from cart.";
                    } elseif ($current_stock > 0 && $newQty > $current_stock) {
                        $_SESSION['cart_msg'] = "Only {$current_stock} in stock for " . htmlspecialchars($item['name']) . ".";
                        $_SESSION['cart_msg_error'] = true;
                    } elseif ($current_stock == 0) {
                        unset($_SESSION['cart'][$k]);
                        $_SESSION['cart_msg'] = htmlspecialchars($item['name']) . " is no longer available and has been removed from your cart.";
                        $_SESSION['cart_msg_error'] = true;
                    } else {
                        $_SESSION['cart'][$k]['qty'] = $newQty;
                        $_SESSION['cart'][$k]['stock'] = $current_stock; // Update stock info
                        $_SESSION['cart_msg'] = "Updated quantity for " . htmlspecialchars($item['name']) . ".";
                    }
                }
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']); // re-index        
    }
    header('Location: cart.php');
    exit();
}

/* ---------- Refresh stock information for cart items (for display only) ---------- */
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $k => $item) {
        $stock_check = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        if ($stock_check) {
            $stock_check->bind_param('i', $item['id']);
            $stock_check->execute();
            $stock_result = $stock_check->get_result();
            if ($stock_row = $stock_result->fetch_assoc()) {
                // Update stock value for accurate display and validation
                $_SESSION['cart'][$k]['stock'] = (int)$stock_row['stock'];
            } else {
                // Product no longer exists in database, mark stock as 0
                $_SESSION['cart'][$k]['stock'] = 0;
            }
            $stock_check->close();
        }
    }
}

/* ---------- Calculate totals ---------- */
$subtotal = 0;
foreach ($_SESSION['cart'] ?? [] as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
$tax      = $subtotal * 0.08;   // 8% example tax
$total    = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .cart-table { width:100%; border-collapse:collapse; margin-bottom:1rem; }
        .cart-table th, .cart-table td { padding:0.75rem; border:1px solid #ddd; text-align:center; }
        .cart-table th { background-color: #f8f9fa; font-weight: 600; }
        .qty-input { width:60px; padding: 0.5rem; text-align: center; }
        .msg { padding:0.5rem 1rem; margin-bottom:1rem; background:#e2f0d9; border-radius:4px; color: #155724; }
        .msg.error { background: #f8d7da; color: #721c24; }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.9rem; }
        .btn-danger { background-color: #dc3545; color: white; border: none; }
        .btn-danger:hover { background-color: #c82333; }
        .quantity-wrapper { display: flex; gap: 0.5rem; align-items: center; justify-content: center; }
        .action-buttons { display: flex; gap: 0.5rem; justify-content: center; }
    </style>
</head>
<body>
<header>
    <div class="container">
        <h1>Your Cart</h1>
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <a href="products.php" class="btn btn-cart">Continue Shopping</a>
            <a href="logout.php" class="btn btn-cart">Logout</a>
        </div>
    </div>
</header>

<main class="container page-content">
    <?php if (!empty($_SESSION['cart_msg'])): ?>
        <p class="msg <?php echo isset($_SESSION['cart_msg_error']) ? 'error' : ''; ?>">
            <?php 
            echo htmlspecialchars($_SESSION['cart_msg']); 
            unset($_SESSION['cart_msg']); 
            unset($_SESSION['cart_msg_error']);
            ?>
        </p>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['checkout_error'])): ?>
        <p class="msg error">
            <?php 
            echo htmlspecialchars($_SESSION['checkout_error']); 
            unset($_SESSION['checkout_error']);
            ?>
        </p>
    <?php endif; ?>

    <?php if (empty($_SESSION['cart'])): ?>
        <div style="text-align: center; padding: 3rem;">
            <p style="font-size: 1.2rem; margin-bottom: 1rem;">Your cart is empty.</p>
            <a href="products.php" class="btn btn-cart">Browse perfumes</a>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <!-- Update Form for this specific item -->
                            <form method="POST" action="cart.php" style="display: inline-block; margin: 0;">
                                <div class="quantity-wrapper">
                                    <input type="number" 
                                           name="qty" 
                                           class="qty-input"
                                           value="<?php echo $item['qty']; ?>" 
                                           min="1"
                                           max="<?php echo $item['stock']; ?>"
                                           required>     
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="action" value="update">
                                    <button type="submit" class="btn btn-sm">Update</button>
                                </div>
                            </form>
                        </td>
                        <td>$<?php echo number_format($item['price'] * $item['qty'], 2); ?></td>
                        <td>
                            <!-- Remove Form for this specific item -->
                            <form method="POST" action="cart.php" style="display: inline-block; margin: 0;">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" 
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Are you sure you want to remove <?php echo htmlspecialchars(addslashes($item['name'])); ?> from your cart?');">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="text-align:right; margin-top:2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
            <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">
                Subtotal: <strong>$<?php echo number_format($subtotal, 2); ?></strong>
            </p>
            <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">
                Tax (8%): <strong>$<?php echo number_format($tax, 2); ?></strong>
            </p>
            <p style="font-size: 1.3rem; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #ddd;">
                Total: <strong>$<?php echo number_format($total, 2); ?></strong>
            </p>
            <div class="checkout-actions" style="margin-top: 2rem;">
                <form action="checkout.php" method="POST" onsubmit="return confirm('Are you sure you want to proceed with your order?');">
                    <button type="submit" class="btn btn-cart" style="padding: 0.75rem 2rem; font-size: 1.1rem;">
                        Buy Now & Pay with Mobile Money
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>