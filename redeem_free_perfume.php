<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$message = "";
$message_type = "";
$free_perfume_available = false;
$current_points = 0;

// Get current loyalty points
$points_stmt = $conn->prepare("SELECT loyalty_points FROM users WHERE user_id = ?");
if ($points_stmt) {
    $points_stmt->bind_param('i', $user_id);
    $points_stmt->execute();
    $points_result = $points_stmt->get_result();
    if ($points_row = $points_result->fetch_assoc()) {
        $current_points = (int)$points_row['loyalty_points'];
        if ($current_points > 5) {
            $free_perfume_available = true;
        }
    }
    $points_stmt->close();
}

// Handle redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem'])) {
    if (!$free_perfume_available) {
        $message = "❌ You don't have enough loyalty points to redeem a free perfume. You need more than 5 points.";
        $message_type = "error";
    } else {
        $product_id = (int)($_POST['product_id'] ?? 0);
        
        if ($product_id <= 0) {
            $message = "❌ Please select a perfume to redeem.";
            $message_type = "error";
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Check if product exists and is in stock
                $product_stmt = $conn->prepare("SELECT id, name, stock FROM products WHERE id = ? AND stock > 0");
                $product_stmt->bind_param('i', $product_id);
                $product_stmt->execute();
                $product_result = $product_stmt->get_result();
                $product = $product_result->fetch_assoc();
                $product_stmt->close();
                
                if (!$product) {
                    throw new Exception("Selected perfume is not available or out of stock.");
                }
                
                // Verify user still has enough points
                $verify_points = $conn->prepare("SELECT loyalty_points FROM users WHERE user_id = ? FOR UPDATE");
                $verify_points->bind_param('i', $user_id);
                $verify_points->execute();
                $verify_result = $verify_points->get_result();
                $verify_row = $verify_result->fetch_assoc();
                $verify_points->close();
                
                if (!$verify_row || (int)$verify_row['loyalty_points'] <= 5) {
                    throw new Exception("You don't have enough loyalty points.");
                }
                
                // Deduct 6 points (since they need > 5, we deduct 6)
                $deduct_points = $conn->prepare("UPDATE users SET loyalty_points = loyalty_points - 6 WHERE user_id = ?");
                $deduct_points->bind_param('i', $user_id);
                if (!$deduct_points->execute()) {
                    throw new Exception("Failed to deduct loyalty points.");
                }
                $deduct_points->close();
                
                // Create a free order
                $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total, payment_method, status, momo_number) VALUES (?, 0.00, 'free_perfume', 'completed', 'N/A')");
                $order_stmt->bind_param('i', $user_id);
                if (!$order_stmt->execute()) {
                    throw new Exception("Failed to create order.");
                }
                $free_order_id = $conn->insert_id;
                $order_stmt->close();
                
                // Add order item
                $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, 1, 0.00)");
                $item_stmt->bind_param('ii', $free_order_id, $product_id);
                if (!$item_stmt->execute()) {
                    throw new Exception("Failed to add order item.");
                }
                $item_stmt->close();
                
                // Update stock
                $stock_stmt = $conn->prepare("UPDATE products SET stock = stock - 1 WHERE id = ?");
                $stock_stmt->bind_param('i', $product_id);
                if (!$stock_stmt->execute()) {
                    throw new Exception("Failed to update stock.");
                }
                $stock_stmt->close();
                
                // Commit transaction
                $conn->commit();
                
                // Success message
                $_SESSION['free_perfume_redeemed'] = true;
                $_SESSION['redeemed_product_name'] = $product['name'];
                $_SESSION['new_points_balance'] = (int)$verify_row['loyalty_points'] - 6;
                
                header('Location: products.php');
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $message = "❌ Error: " . $e->getMessage();
                $message_type = "error";
            }
        }
    }
}

// Get available products for redemption
$products = [];
if ($free_perfume_available) {
    $products_query = $conn->query("SELECT id, name, brand, price, stock, image FROM products WHERE stock > 0 ORDER BY name ASC");
    if ($products_query) {
        while ($row = $products_query->fetch_assoc()) {
            $products[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeem Free Perfume - Perfumes Management System</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }
        .redeem-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .points-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .points-display h2 {
            margin: 0 0 0.5rem 0;
        }
        .msg {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .msg.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .msg.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .product-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s;
        }
        .product-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .product-card.selected {
            border-color: #28a745;
            background: #f0fff4;
        }
        .product-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        .product-card h3 {
            margin: 0.5rem 0;
            font-size: 1rem;
        }
        .product-card .brand {
            color: #666;
            font-size: 0.9rem;
            margin: 0.25rem 0;
        }
        .product-card input[type="radio"] {
            margin-top: 0.5rem;
        }
        .redeem-btn {
            width: 100%;
            padding: 1rem;
            margin-top: 2rem;
            font-size: 1.1rem;
        }
        .not-eligible {
            text-align: center;
            padding: 3rem;
            background: #fff3cd;
            border-radius: 8px;
            color: #856404;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>Redeem Free Perfume</h1>
                <a href="products.php" class="btn btn-outline">Back to Products</a>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="redeem-container">
            <div class="points-display">
                <h2>Your Loyalty Points</h2>
                <p style="font-size: 1.5rem; margin: 0;"><strong><?php echo $current_points; ?> Points</strong></p>
                <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">(<?php echo $current_points; ?> orders completed)</p>
            </div>

            <?php if ($message): ?>
                <div class="msg <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!$free_perfume_available): ?>
                <div class="not-eligible">
                    <h2>Not Eligible Yet</h2>
                    <p>You need more than 5 loyalty points (more than 5 orders) to redeem a free perfume.</p>
                    <p>You currently have <strong><?php echo $current_points; ?> points</strong> (<?php echo $current_points; ?> orders completed).</p>
                    <p>Keep shopping to earn more points! Each order gives you 1 loyalty point.</p>
                    <a href="products.php" class="btn btn-primary" style="margin-top: 1rem;">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div>
                    <h2>Select Your Free Perfume</h2>
                    <p>You have completed <strong><?php echo $current_points; ?> orders</strong> (<?php echo $current_points; ?> loyalty points). Redeeming a free perfume will deduct 6 points from your account.</p>
                    
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to redeem this free perfume? This will deduct 6 loyalty points from your account.');">
                        <div class="products-grid">
                            <?php if (empty($products)): ?>
                                <p>No perfumes are currently available for redemption.</p>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <label class="product-card">
                                        <input type="radio" name="product_id" value="<?php echo $product['id']; ?>" required>
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 150px; background: #e9ecef; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
                                                <span>No Image</span>
                                            </div>
                                        <?php endif; ?>
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p class="brand"><?php echo htmlspecialchars($product['brand'] ?? ''); ?></p>
                                        <p style="color: #28a745; font-weight: bold;">FREE</p>
                                        <p style="font-size: 0.85rem; color: #666;">Stock: <?php echo $product['stock']; ?></p>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($products)): ?>
                            <button type="submit" name="redeem" class="btn btn-primary redeem-btn">
                                Redeem Free Perfume (Deduct 6 Points)
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>

