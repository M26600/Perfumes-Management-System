<?php
require_once __DIR__ . '/../includes/auth.php';
require_cashier_or_admin();
require_once __DIR__ . '/../includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Cashier Dashboard</title>
	<link rel="stylesheet" href="../assets/css/main.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<style>
		.admin-container{max-width:1100px;margin:140px auto 40px;padding:20px}
		.admin-card{
			border:1px solid #eee;
			border-radius:12px;
			padding:22px;
			background:#fff;
			box-shadow:var(--shadow);
			transition:transform .2s;
			text-decoration:none;
			color:#333;
			display:block;
		}
		.admin-card:hover{
			transform:translateY(-3px);
			box-shadow:0 4px 12px rgba(0,0,0,0.15);
		}
		.admin-grid{
			display:grid;
			grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
			gap:18px;
		}
		.admin-card strong{
			display:block;
			font-size:1.2rem;
			margin-bottom:8px;
			color:#007bff;
		}
		.admin-card p{
			color:#666;
			margin:0;
			font-size:0.95rem;
		}
		.admin-card i{
			font-size:2rem;
			color:#007bff;
			margin-bottom:12px;
			display:block;
		}
	</style>
</head>
<body>
	<header class="site-header">
		<div class="container header-content">
			<a href="cashier_dashboard.php" class="logo"><i class="fas fa-spray-can"></i> Cashier</a>
			<nav>
				<ul class="nav-links">
					<li><a href="cashier_dashboard.php">Dashboard</a></li>
					<li><a href="../products.php">Store</a></li>
					<li><a href="../logout.php">Logout</a></li>
				</ul>
			</nav>
			<div class="menu-toggle"><i class="fas fa-bars"></i></div>
		</div>
	</header>
	<main class="admin-container">
		<h1>Cashier Dashboard</h1>
		<p style="color:#666;margin-bottom:2rem;">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Cashier'); ?>! Manage payments, perfumes, and customer loyalty.</p>
		<div class="admin-grid">
			<a class="admin-card" href="cashier_review.php">
				<i class="fas fa-check-circle"></i>
				<strong>Review Payments</strong>
				<p>Approve or reject customer payment proofs</p>
			</a>
			<a class="admin-card" href="perfumes.php">
				<i class="fas fa-bottle-water"></i>
				<strong>Manage Perfumes</strong>
				<p>Add, edit, or delete perfumes and discounts</p>
			</a>
			<a class="admin-card" href="purchase.php">
				<i class="fas fa-calculator"></i>
				<strong>Record Purchase</strong>
				<p>Calculate total bill and record manual orders</p>
			</a>
			<a class="admin-card" href="loyalty.php">
				<i class="fas fa-star"></i>
				<strong>Loyalty Points</strong>
				<p>Manage and adjust customer loyalty points</p>
			</a>
		</div>
	</main>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>




