<?php
require_once __DIR__ . '/../includes/auth.php';
require_cashier_or_admin();
require_once __DIR__ . '/../includes/db_connect.php';

$current_user_id = (int)($_SESSION['user_id'] ?? 0);
$is_cashier_only = is_cashier() && empty($_SESSION['is_admin']);

// Load pending payments
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payments (
	id INT AUTO_INCREMENT PRIMARY KEY,
	order_id INT NOT NULL,
	user_id INT NOT NULL,
	image_path VARCHAR(255) NOT NULL,
	status ENUM('pending','approved','rejected') DEFAULT 'pending',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX(order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (($_GET['action'] ?? '') === 'set' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$pid = (int)($_GET['id'] ?? 0);
	$new = ($_POST['status'] ?? '') === 'approved' ? 'approved' : 'rejected';
	// update payment status
	$ps = $conn->prepare("UPDATE payments SET status=? WHERE id=?");
	$ps->bind_param('si', $new, $pid);
	$ps->execute();
	$ps->close();
	// get order
	$r = $conn->query("SELECT p.order_id, o.user_id, o.status AS current_status FROM payments p LEFT JOIN orders o ON o.order_id=p.order_id WHERE p.id=".$pid);
	$prow = $r ? $r->fetch_assoc() : null;
	if ($prow && $new==='approved') {
		$oid = (int)$prow['order_id'];
		$uid = (int)$prow['user_id'];
		// Only award once when moving into a completed/approved state
		if (!in_array(strtolower($prow['current_status'] ?? ''), ['completed','approved','paid'], true)) {
			// award 1 loyalty point
			$lp = $conn->prepare("UPDATE users SET loyalty_points = loyalty_points + 1 WHERE user_id = ?");
			if ($lp) {
				$lp->bind_param('i', $uid);
				$lp->execute();
				$lp->close();
			}
		}
		$conn->query("UPDATE orders SET status='completed' WHERE order_id=".$oid);
	} elseif ($prow && $new==='rejected') {
		$oid = (int)$prow['order_id'];
		$conn->query("UPDATE orders SET status='rejected' WHERE order_id=".$oid);
	}
	header('Location: cashier_review.php');
	exit();
}

// Cashier should not see their own payments; admins can see all
$extraFilter = ($is_cashier_only && $current_user_id > 0)
    ? " AND p.user_id <> " . $current_user_id
    : "";

$pending = $conn->query(
    "SELECT p.id, p.order_id, p.user_id, p.image_path, p.created_at, u.username
     FROM payments p
     LEFT JOIN users u ON u.user_id = p.user_id
     WHERE p.status = 'pending'{$extraFilter}
     ORDER BY p.id DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Cashier Review</title>
	<link rel="stylesheet" href="../assets/css/main.css">
	<style>.wrap{max-width:1100px;margin:140px auto 40px;padding:20px}.card{background:#fff;border:1px solid #eee;border-radius:10px;box-shadow:var(--shadow);padding:18px}</style>
</head>
<body>
	<header class="site-header">
		<div class="container header-content">
			<a href="cashier_dashboard.php" class="logo"><i class="fas fa-spray-can"></i> Cashier</a>
			<nav><ul class="nav-links"><li><a href="cashier_dashboard.php">Dashboard</a></li><li><a href="../logout.php">Logout</a></li></ul></nav>
			<div class="menu-toggle"><i class="fas fa-bars"></i></div>
		</div>
	</header>
	<main class="wrap">
		<h1>Pending Payments</h1>
		<div class="card">
			<table style="width:100%;border-collapse:collapse">
				<tr><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">ID</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Order</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">User</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Proof</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Date</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Action</th></tr>
				<?php if ($pending && $pending->num_rows): while($row=$pending->fetch_assoc()): ?>
				<tr>
					<td style="padding:8px;border-bottom:1px solid #eee;">#<?php echo (int)$row['id']; ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;">Order #<?php echo (int)$row['order_id']; ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($row['username'] ?? ('User '.$row['user_id'])); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;"><a href="../<?php echo htmlspecialchars($row['image_path']); ?>" target="_blank">View</a></td>
					<td style="padding:8px;border-bottom:1px solid #eee;">&nbsp;<?php echo htmlspecialchars($row['created_at']); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;">
						<form method="post" action="cashier_review.php?action=set&id=<?php echo (int)$row['id']; ?>" style="display:inline;">
							<input type="hidden" name="status" value="approved">
							<button class="btn btn-primary" type="submit">Approve</button>
						</form>
						<form method="post" action="cashier_review.php?action=set&id=<?php echo (int)$row['id']; ?>" style="display:inline; margin-left:6px;">
							<input type="hidden" name="status" value="rejected">
							<button class="btn btn-danger" type="submit">Reject</button>
						</form>
						<a href="../order_success.php?order_id=<?php echo (int)$row['order_id']; ?>" target="_blank" class="btn btn-outline" style="margin-left:6px;">View Receipt</a>
					</td>
				</tr>
				<?php endwhile; else: ?>
				<tr><td colspan="6" style="padding:12px;color:#666;">No pending payments.</td></tr>
				<?php endif; ?>
			</table>
		</div>
	</main>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
