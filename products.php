<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// DEV: Show errors (REMOVE IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Guest';
$is_cashier_only = is_cashier() && empty($_SESSION['is_admin']);
$can_shop = !$is_cashier_only;

// Check for registration success message
$show_registration_success = false;
if (isset($_SESSION['registration_success']) && $_SESSION['registration_success']) {
    $show_registration_success = true;
    unset($_SESSION['registration_success']); // Clear the flag
}

// === Fetch Loyalty Points (Prepared Statement) ===
$loyalty_points = 0;
$free_perfume_available = false;
if ($lp_stmt = $conn->prepare("SELECT loyalty_points FROM users WHERE user_id = ?")) {
    $lp_stmt->bind_param("i", $user_id);
    $lp_stmt->execute();
    $result = $lp_stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $loyalty_points = (int)$row['loyalty_points'];
        if ($loyalty_points > 5) {
            $free_perfume_available = true;
        }
    }
    $lp_stmt->close();
} else {
    error_log("Failed to prepare loyalty points query: " . $conn->error);
}

// Check for free perfume redemption success message
$free_perfume_redeemed = false;
$redeemed_product_name = "";
$new_points_balance = 0;
if (isset($_SESSION['free_perfume_redeemed']) && $_SESSION['free_perfume_redeemed']) {
    $free_perfume_redeemed = true;
    $redeemed_product_name = $_SESSION['redeemed_product_name'] ?? '';
    $new_points_balance = (int)($_SESSION['new_points_balance'] ?? 0);
    unset($_SESSION['free_perfume_redeemed']);
    unset($_SESSION['redeemed_product_name']);
    unset($_SESSION['new_points_balance']);
    // Refresh loyalty points
    $loyalty_points = $new_points_balance;
    $free_perfume_available = ($loyalty_points > 5);
}

// === Fetch Products ===
// Detect optional discount column
$has_discount = false;
if ($colRes = $conn->query("SHOW COLUMNS FROM products LIKE 'discount_percent'")) {
    $has_discount = $colRes->num_rows > 0;
}

$selectCols = "id, name, brand, price, stock, image" . ($has_discount ? ", discount_percent" : "");
$products = $conn->query("SELECT $selectCols FROM products WHERE stock > 0 ORDER BY name ASC");

if ($products === false) {
    error_log("Product query failed: " . $conn->error);
    $products = []; // Fallback: empty result
} else {
    // We'll use fetch_all for cleaner loop if needed, but we'll stick to while() for memory
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Perfume Store</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .products-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            margin-bottom: 1rem;
        }
        .products-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.5rem 0.5rem;
        }
        .products-header-text h1 {
            margin-bottom: 0.25rem;
        }
        .products-header-text p {
            margin: 0;
            color: var(--gray);
            font-size: 0.95rem;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        @media (max-width: 768px) {
            .products-header-inner {
                flex-direction: column;
                align-items: flex-start;
            }
            .header-actions {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }
        }
        .out-of-stock { opacity: 0.6; pointer-events: none; }
        .stock-info { font-size: 0.85em; color: #666; margin: 4px 0; }
        .product-card form { margin-top: 8px; }
        .registration-success {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            margin: 1rem 0 2rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: slideDown 0.5s ease-out;
        }
        .registration-success h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }
        .registration-success p {
            margin: 0.5rem 0 0 0;
            opacity: 0.95;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .close-btn {
            float: right;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0 0.5rem;
            border-radius: 4px;
            line-height: 1;
        }
        .close-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .free-perfume-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            margin: 1rem 0 2rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .free-perfume-banner h3 {
            margin: 0;
            font-size: 1.3rem;
        }
        .free-perfume-banner p {
            margin: 0.5rem 0 0 0;
            opacity: 0.95;
        }
        .free-perfume-banner .btn {
            background: white;
            color: #667eea;
            border: none;
            padding: 0.75rem 1.5rem;
        }
        .free-perfume-banner .btn:hover {
            background: #f8f9fa;
        }
        .redemption-success {
            background: #d4edda;
            color: #155724;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            margin: 1rem 0 2rem 0;
            border: 1px solid #c3e6cb;
        }
        .redemption-success h3 {
            margin: 0 0 0.5rem 0;
            color: #155724;
        }
    </style>
</head>
<body>

<header class="products-header">
    <div class="container products-header-inner">
        <div class="products-header-text">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <?php if (!$is_cashier_only): ?>
                <p>Loyalty Points: <strong><?php echo number_format($loyalty_points); ?></strong> (<?php echo number_format($loyalty_points); ?> orders completed)</p>
            <?php endif; ?>
        </div>
        <div class="header-actions">
            <?php if ($is_cashier_only): ?>
                <a href="admin/cashier_dashboard.php" class="btn btn-cart">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-cart">Logout</a>
            <?php else: ?>
                <a href="cart.php" class="btn btn-cart">View Cart</a>
                <a href="logout.php" class="btn btn-cart">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container page-content">
    <?php if ($show_registration_success && !$is_cashier_only): ?>
        <div class="registration-success" id="registrationSuccess">
            <button class="close-btn" onclick="document.getElementById('registrationSuccess').style.display='none'">&times;</button>
            <h3>🎉 Welcome to Perfume Management System!</h3>
            <p>Your account has been successfully created. You're now logged in and ready to explore our amazing collection of perfumes. Start shopping and earn loyalty points with every purchase!</p>
        </div>
    <?php endif; ?>

    <?php if ($free_perfume_redeemed && !$is_cashier_only): ?>
        <div class="redemption-success">
            <h3>🎁 Free Perfume Redeemed Successfully!</h3>
            <p>Congratulations! You've successfully redeemed <strong><?php echo htmlspecialchars($redeemed_product_name); ?></strong> as your free perfume.</p>
            <p>Your new loyalty points balance: <strong><?php echo $new_points_balance; ?> points</strong> (<?php echo $new_points_balance; ?> orders completed)</p>
        </div>
    <?php endif; ?>

    <?php if ($free_perfume_available && !$is_cashier_only): ?>
        <div class="free-perfume-banner">
            <div>
                <h3>🎁 You've Earned a Free Perfume!</h3>
                <p>You have completed <strong><?php echo $loyalty_points; ?> orders</strong> (<?php echo $loyalty_points; ?> loyalty points). Redeem your free perfume now!</p>
            </div>
            <a href="redeem_free_perfume.php" class="btn">Claim Free Perfume</a>
        </div>
    <?php endif; ?>
    
    <section>
        <h2>Available Perfumes</h2>

        <?php if ($products && $products->num_rows > 0): ?>
            <div class="products-grid">
                <?php while ($prod = $products->fetch_assoc()): 
                    $in_stock = (int)$prod['stock'] > 0;
                    $img = !empty($prod['image']) ? htmlspecialchars($prod['image']) : 'assets/images/placeholder.jpg';
                    $max_qty = $in_stock ? (int)$prod['stock'] : 0;
                    $price = (float)$prod['price'];
                    $discount_percent = $has_discount ? (int)($prod['discount_percent'] ?? 0) : 0;
                    $final_price = $discount_percent > 0 ? $price * (1 - $discount_percent/100) : $price;
                ?>
                    <div class="product-card <?php echo !$in_stock ? 'out-of-stock' : ''; ?>">
                        <img src="<?php echo $img; ?>" 
                             alt="<?php echo htmlspecialchars($prod['name'] . ' by ' . ($prod['brand'] ?? 'Unknown Brand')); ?>" 
                             loading="lazy">

                        <h3><?php echo htmlspecialchars($prod['name']); ?></h3>
                        <p class="brand"><?php echo htmlspecialchars($prod['brand'] ?? ''); ?></p>
                        <p class="price">
                            <?php if ($discount_percent > 0): ?>
                                <span style="text-decoration:line-through;color:#888;margin-right:8px;">$<?php echo number_format($price,2); ?></span>
                                <strong>$<?php echo number_format($final_price,2); ?></strong>
                                <span style="color:#28a745; font-size:0.9em; margin-left:6px;">-<?php echo (int)$discount_percent; ?>%</span>
                            <?php else: ?>
                                $<?php echo number_format($price,2); ?>
                            <?php endif; ?>
                        </p>

                        <p class="stock-info">
                            <?php echo $in_stock ? "Stock: {$prod['stock']}" : '<em>Out of Stock</em>'; ?>
                        </p>

                        <?php if ($in_stock && $can_shop): ?>
                            <form method="POST" action="add_to_cart.php" class="add-to-cart-form">
                                <input type="hidden" name="product_id" value="<?php echo (int)$prod['id']; ?>">
                                <input type="hidden" name="unit_price" value="<?php echo htmlspecialchars(number_format($final_price,2,'.','')); ?>">
                                <div class="quantity-wrapper">
                                    <input type="number" 
                                           name="quantity" 
                                           value="1" 
                                           min="1" 
                                           max="<?php echo $max_qty; ?>" 
                                           required 
                                           aria-label="Quantity">
                                    <button type="submit" class="btn btn-outline">Add to Cart</button>
                                </div>
                            </form>
                        <?php elseif ($in_stock && !$can_shop): ?>
                            <p class="stock-info"><em>Cashiers can view stock and prices only. Customers add items from their own accounts.</em></p>
                        <?php else: ?>
                            <button class="btn btn-outline" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-products">No perfumes are currently available.</p>
        <?php endif; ?>
    </section>
</main>

<?php include 'includes/footer.php'; ?>