<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin Dashboard</title>
	<link rel="stylesheet" href="../assets/css/main.css">
	<style>
		.admin-container{max-width:1100px;margin:140px auto 40px;padding:20px}
		.admin-card{border:1px solid #eee;border-radius:12px;padding:22px;background:#fff;box-shadow:var(--shadow);transition:transform .2s}
		.admin-card:hover{transform:translateY(-3px)}
		.admin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px}
	</style>
</head>
<body>
	<header class="site-header">
		<div class="container header-content">
			<a href="../index.html" class="logo"><i class="fas fa-spray-can"></i> Perfume</a>
			<nav>
				<ul class="nav-links">
					<li><a href="../products.php">Store</a></li>
					<li><a href="../logout.php">Logout</a></li>
				</ul>
			</nav>
			<div class="menu-toggle"><i class="fas fa-bars"></i></div>
		</div>
	</header>
	<main class="admin-container">
		<h1>Admin Dashboard</h1>
		<div class="admin-grid">
			<a class="admin-card" href="perfumes.php"><strong>Manage Perfumes</strong><p>Create, edit, delete perfumes</p></a>
			<a class="admin-card" href="users.php"><strong>Add User</strong><p>Create customer or admin</p></a>
			<a class="admin-card" href="purchase.php"><strong>Record Purchase</strong><p>Add manual order</p></a>
			<a class="admin-card" href="loyalty.php"><strong>Loyalty</strong><p>Adjust customer points</p></a>
			<a class="admin-card" href="alerts.php"><strong>Alerts</strong><p>Low stock and expiry warnings</p></a>
			<a class="admin-card" href="contacts.php"><strong>Contact Messages</strong><p>View messages from customers</p></a>
			<a class="admin-card" href="reports.php"><strong>Reports</strong><p>Daily/Monthly revenue and profit</p></a>
		</div>
	</main>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
