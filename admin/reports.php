<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/db_connect.php';

function has_col(mysqli $c, $table, $col) {
    $r = $c->query("SHOW COLUMNS FROM $table LIKE '".$c->real_escape_string($col)."'");
    return $r && $r->num_rows>0;
}

// Use COALESCE(grand_total, total) so that we always have an amount,
// even if only one of the columns is populated.
$has_grand = has_col($conn,'orders','grand_total');
$has_total = has_col($conn,'orders','total');

if ($has_grand && $has_total) {
    $amount_expr = "COALESCE(grand_total, total)";
} elseif ($has_grand) {
    $amount_expr = "grand_total";
} elseif ($has_total) {
    $amount_expr = "total";
} else {
    // Fallback: no known amount columns; treat as 0
    $amount_expr = "0";
}

$today = date('Y-m-d');
$monthStart = date('Y-m-01');

// Only count orders the cashier has approved (orders marked completed/approved/paid)
$has_status = has_col($conn, 'orders', 'status');
$statusWhere = $has_status ? " AND status IN ('completed','approved','paid')" : '';

$dailySql   = "SELECT IFNULL(SUM($amount_expr),0) AS revenue, COUNT(*) AS orders FROM orders WHERE DATE(created_at)='$today'$statusWhere";
$daily      = $conn->query($dailySql);
if (!$daily) { $daily = $conn->query("SELECT IFNULL(SUM($amount_col),0) AS revenue, COUNT(*) AS orders FROM orders WHERE DATE(NOW())='$today'$statusWhere"); }
$dailyRow   = $daily ? $daily->fetch_assoc() : ['revenue'=>0,'orders'=>0];

$monthlySql = "SELECT IFNULL(SUM($amount_expr),0) AS revenue, COUNT(*) AS orders FROM orders WHERE DATE(created_at) >= '$monthStart'$statusWhere";
$monthly    = $conn->query($monthlySql);
if (!$monthly) { $monthly = $conn->query("SELECT IFNULL(SUM($amount_col),0) AS revenue, COUNT(*) AS orders FROM orders WHERE DATE(NOW()) >= '$monthStart'$statusWhere"); }
$monthlyRow = $monthly ? $monthly->fetch_assoc() : ['revenue'=>0,'orders'=>0];

// Profit analysis (requires products.cost_price).
// Profit is calculated as SUM((selling_price - cost_price) * quantity) for completed/approved/paid orders.
$has_cost = has_col($conn,'products','cost_price');
$profitToday = 0; $profitMonth = 0;
if ($has_cost) {
	// join order_items with products; treat missing cost_price as 0 so profit still computes
	$q1 = $conn->query(
        "SELECT IFNULL(SUM((oi.price - COALESCE(p.cost_price,0)) * oi.quantity),0) AS profit
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         LEFT JOIN orders o ON o.order_id = oi.order_id
         WHERE DATE(o.created_at) = '$today'"
         . ($statusWhere ? " AND o.status IN ('completed','approved','paid')" : "")
    );
	$r1 = $q1 ? $q1->fetch_assoc()['profit'] : 0;
	$profitToday = (float)$r1;
	$q2 = $conn->query(
        "SELECT IFNULL(SUM((oi.price - COALESCE(p.cost_price,0)) * oi.quantity),0) AS profit
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         LEFT JOIN orders o ON o.order_id = oi.order_id
         WHERE DATE(o.created_at) >= '$monthStart'"
         . ($statusWhere ? " AND o.status IN ('completed','approved','paid')" : "")
    );
	$r2 = $q2 ? $q2->fetch_assoc()['profit'] : 0;
	$profitMonth = (float)$r2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reports</title>
	<link rel="stylesheet" href="../assets/css/main.css">
	<style>.admin-wrap{max-width:1100px;margin:140px auto 40px;padding:20px}.card{background:#fff;border:1px solid #eee;border-radius:10px;box-shadow:var(--shadow);padding:18px;margin-bottom:16px}.kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}.kpi .card h3{margin:0 0 6px 0}</style>
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
		<h1>Sales Reports</h1>
		<div class="kpi">
			<div class="card"><h3>Today Revenue</h3><div><strong>$<?php echo number_format((float)$dailyRow['revenue'],2); ?></strong></div><div><?php echo (int)$dailyRow['orders']; ?> orders</div></div>
			<div class="card"><h3>This Month Revenue</h3><div><strong>$<?php echo number_format((float)$monthlyRow['revenue'],2); ?></strong></div><div><?php echo (int)$monthlyRow['orders']; ?> orders</div></div>
			<?php if ($has_cost): ?>
			<div class="card"><h3>Today Profit</h3><div><strong>$<?php echo number_format($profitToday,2); ?></strong></div></div>
			<div class="card"><h3>This Month Profit</h3><div><strong>$<?php echo number_format($profitMonth,2); ?></strong></div></div>
			<?php endif; ?>
		</div>
	</main>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>



