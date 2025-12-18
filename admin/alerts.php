<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/db_connect.php';

function has_col(mysqli $c, $table, $col) {
	$r = $c->query("SHOW COLUMNS FROM $table LIKE '" . $c->real_escape_string($col) . "'");
	return $r && $r->num_rows > 0;
}

$threshold = isset($_GET['min']) ? max(0, (int)$_GET['min']) : 5;
$cols = 'id,name,brand,stock';
$has_expiry = has_col($conn, 'products', 'expiry_date');
if ($has_expiry) { $cols .= ',expiry_date'; }
$low = $conn->query("SELECT $cols FROM products WHERE stock <= $threshold ORDER BY stock ASC");
$expiring = null;
if ($has_expiry) {
	$expiring = $conn->query("SELECT $cols FROM products WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY expiry_date ASC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Alerts</title>
	<link rel="stylesheet" href="../assets/css/main.css">
	<style>.admin-wrap{max-width:1100px;margin:140px auto 40px;padding:20px}.card{background:#fff;border:1px solid #eee;border-radius:10px;box-shadow:var(--shadow);padding:18px;margin-bottom:16px}</style>
</head>
<body>
	<header class="site-header">
		<div class="container header-content">
			<a href="index.php" class="logo"><i class="fas fa-spray-can"></i> Admin</a>
			<nav><ul class="nav-links"><li><a href="index.php">Dashboard</a></li><li><a href="../logout.php">Logout</a></li></ul></nav>
			<div class="menu-toggle"><i class="fas fa-bars"></i></div>
		</div>
	</header>
    <main class="admin-wrap">
        <h1>Alerts</h1>
        <form method="get" class="card" style="margin-bottom:16px; display:flex; gap:10px; align-items:center; max-width:520px;">
            <label>Low stock threshold
                <input type="number" name="min" value="<?php echo (int)$threshold; ?>" min="0" style="width:120px; margin-left:8px;">
            </label>
            <button class="btn btn-outline" type="submit">Apply</button>
        </form>
		<div class="card">
			<h2>Low Stock (≤ <?php echo (int)$threshold; ?>)</h2>
            <table style="width:100%;border-collapse:collapse">
				<tr><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">ID</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Name</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Brand</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Stock</th><?php if ($has_expiry): ?><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Expiry</th><?php endif; ?></tr>
                <?php if ($low && $low->num_rows > 0): while($row=$low->fetch_assoc()): ?>
				<tr>
					<td style="padding:8px;border-bottom:1px solid #eee;">#<?php echo (int)$row['id']; ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;">&nbsp;<?php echo htmlspecialchars($row['name']); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;">&nbsp;<?php echo htmlspecialchars($row['brand']); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;">&nbsp;<?php echo (int)$row['stock']; ?></td>
					<?php if ($has_expiry): ?><td style="padding:8px;border-bottom:1px solid #eee;">&nbsp;<?php echo htmlspecialchars($row['expiry_date']); ?></td><?php endif; ?>
				</tr>
                <?php endwhile; else: ?>
                <tr><td colspan="<?php echo $has_expiry?5:4; ?>" style="padding:12px; color:#666;">No items at or below the selected threshold.</td></tr>
                <?php endif; ?>
			</table>
		</div>
		<?php if ($has_expiry): ?>
		<div class="card">
			<h2>Expiring in 30 Days</h2>
			<table style="width:100%;border-collapse:collapse">
				<tr><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">ID</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Name</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Brand</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Stock</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Expiry</th></tr>
                <?php if ($expiring && $expiring->num_rows > 0): while($row=$expiring->fetch_assoc()): ?>
				<tr>
					<td style="padding:8px;border-bottom:1px solid #eee;">#<?php echo (int)$row['id']; ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;">&nbsp;<?php echo htmlspecialchars($row['name']); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;">&nbsp;<?php echo htmlspecialchars($row['brand']); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;">&nbsp;<?php echo (int)$row['stock']; ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;">&nbsp;<?php echo htmlspecialchars($row['expiry_date']); ?></td>
				</tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" style="padding:12px; color:#666;">No products expiring within 30 days.</td></tr>
                <?php endif; ?>
			</table>
		</div>
		<?php endif; ?>
	</main>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
