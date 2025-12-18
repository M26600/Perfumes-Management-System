<?php
session_start();
require_once __DIR__ . '/includes/db_connect.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['order_id'])) {
	header('Location: products.php');
	exit();
}

$order_id = (int)$_SESSION['order_id'];
$user_id = (int)$_SESSION['user_id'];
$message = '';

// Ensure payments table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payments (
	id INT AUTO_INCREMENT PRIMARY KEY,
	order_id INT NOT NULL,
	user_id INT NOT NULL,
	image_path VARCHAR(255) NOT NULL,
	status ENUM('pending','approved','rejected') DEFAULT 'pending',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX(order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!empty($_FILES['proof']['name'])) {
		$dir = __DIR__ . '/images/uploads/payments/';
		if (!is_dir($dir)) { mkdir($dir, 0775, true); }
		$ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
		$fname = 'pay_' . $order_id . '_' . time() . '.' . $ext;
		$dest = $dir . $fname;
		$allowed = ['image/jpeg','image/png','image/webp'];
		$mime = mime_content_type($_FILES['proof']['tmp_name']);
		if (in_array($mime, $allowed, true) && $_FILES['proof']['size'] <= 8*1024*1024 && move_uploaded_file($_FILES['proof']['tmp_name'], $dest)) {
			$rel = 'images/uploads/payments/' . $fname;
			$stmt = $conn->prepare('INSERT INTO payments (order_id,user_id,image_path,status) VALUES (?,?,?,\'pending\')');
			$stmt->bind_param('iis', $order_id, $user_id, $rel);
			$stmt->execute();
			$stmt->close();
			// update order status
			$u = $conn->prepare("UPDATE orders SET status='pending_cashier_review' WHERE order_id=?");
			$u->bind_param('i', $order_id);
			$u->execute();
			$u->close();
			// Redirect to order success page after upload
			header('Location: order_success.php?order_id=' . $order_id);
			exit();
		} else {
			$message = 'Invalid file. Please upload JPG/PNG/WebP up to 8MB.';
		}
	} else {
		$message = 'Please select an image to upload.';
	}
}

// Check if already uploaded and get order status
$check = $conn->query("SELECT id FROM payments WHERE order_id=$order_id AND user_id=$user_id LIMIT 1");
$has_uploaded = $check && $check->num_rows > 0;

// Get order status
$order_status = $conn->query("SELECT status FROM orders WHERE order_id=$order_id AND user_id=$user_id LIMIT 1");
$status_row = $order_status ? $order_status->fetch_assoc() : null;
$current_status = strtolower($status_row['status'] ?? '');

// Handle status outcomes
if (in_array($current_status, ['completed','approved','paid'], true)) {
	header('Location: order_success.php?order_id=' . $order_id);
	exit();
}

if ($current_status === 'rejected') {
	// Let customer know and send them back to shop
	$_SESSION['error'] = 'Your payment proof was rejected. Please place a new order and upload a new proof.';
	header('Location: products.php');
	exit();
}

// If already uploaded but pending, show message with link
if ($has_uploaded && $current_status === 'pending_cashier_review') {
	$message = 'Payment proof already uploaded. Waiting for cashier approval. <a href="order_success.php?order_id=' . $order_id . '">View order status</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Upload Payment Proof</title>
	<link rel="stylesheet" href="assets/css/main.css">
	<style>.wrap{max-width:720px;margin:140px auto 40px;padding:20px;background:#fff;border:1px solid #eee;border-radius:10px;box-shadow:var(--shadow)}</style>
</head>
<body>
	<header class="site-header">
		<div class="container header-content">
			<a href="index.html" class="logo"><i class="fas fa-spray-can"></i> Perfume</a>
			<nav><ul class="nav-links"><li><a href="products.php">Shop</a></li><li><a href="logout.php">Logout</a></li></ul></nav>
			<div class="menu-toggle"><i class="fas fa-bars"></i></div>
		</div>
	</header>
	<main class="wrap">
		<h1>Upload Payment Proof</h1>
		<?php if ($message): ?><p class="msg"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
		<form method="post" enctype="multipart/form-data">
			<label>Screenshot / Image of Payment</label>
			<input type="file" name="proof" accept="image/*" required>
			<button class="btn btn-primary" type="submit">Submit</button>
		</form>
		<p style="margin-top:12px;color:#666;">Once the cashier approves your payment, your order will be marked as completed and you will see the order receipt.</p>
	</main>
	<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
