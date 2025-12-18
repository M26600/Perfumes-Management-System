<?php
require_once __DIR__ . '/../includes/auth.php';
require_cashier_or_admin();
require_once __DIR__ . '/../includes/db_connect.php';

// ensure column exists
$conn->query("ALTER TABLE users ADD COLUMN loyalty_points INT NOT NULL DEFAULT 0");

$message = '';
if (($_GET['action'] ?? '') === 'set' && $_SERVER['REQUEST_METHOD']==='POST') {
	verify_csrf();
	$uid = (int)($_GET['id'] ?? 0);
	$val = (int)($_POST['points'] ?? 0);
	$st = $conn->prepare('UPDATE users SET loyalty_points=? WHERE user_id=?');
	$st->bind_param('ii',$val,$uid);
	$st->execute();
	$message = 'Loyalty points updated.';
}
if (($_GET['action'] ?? '') === 'add' && $_SERVER['REQUEST_METHOD']==='POST') {
	verify_csrf();
	$uid = (int)($_GET['id'] ?? 0);
	$delta = (int)($_POST['delta'] ?? 1);
	$st = $conn->prepare('UPDATE users SET loyalty_points = loyalty_points + ? WHERE user_id=?');
	$st->bind_param('ii',$delta,$uid);
	$st->execute();
	$message = 'Loyalty points adjusted.';
}

$list = $conn->query("SELECT user_id, username, email, loyalty_points FROM users ORDER BY username ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Loyalty Management</title>
	<link rel="stylesheet" href="../assets/css/main.css">
	<style>.wrap{max-width:1100px;margin:140px auto 40px;padding:20px}.card{background:#fff;border:1px solid #eee;border-radius:10px;box-shadow:var(--shadow);padding:18px}</style>
</head>
<body>
	<header class="site-header">
		<div class="container header-content">
			<?php 
			$is_cashier_only = is_cashier() && empty($_SESSION['is_admin']);
			$dashboard_link = $is_cashier_only ? 'cashier_dashboard.php' : 'index.php';
			$logo_text = $is_cashier_only ? 'Cashier' : 'Admin';
			?>
			<a href="<?php echo $dashboard_link; ?>" class="logo"><i class="fas fa-spray-can"></i> <?php echo $logo_text; ?></a>
			<nav><ul class="nav-links"><li><a href="<?php echo $dashboard_link; ?>">Dashboard</a></li><li><a href="../logout.php">Logout</a></li></ul></nav>
			<div class="menu-toggle"><i class="fas fa-bars"></i></div>
		</div>
	</header>
	<main class="wrap">
		<h1>Loyalty Management</h1>
		<?php if ($message): ?><p class="msg"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
		<div class="card">
			<table style="width:100%;border-collapse:collapse;">
				<tr><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">User</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Email</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Points</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Adjust</th></tr>
				<?php while($u=$list->fetch_assoc()): ?>
				<tr>
					<td style="padding:8px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($u['username']); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($u['email']); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;"><?php echo (int)$u['loyalty_points']; ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;">
						<form method="post" action="loyalty.php?action=add&id=<?php echo (int)$u['user_id']; ?>" style="display:inline;">
							<input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
							<input type="number" name="delta" value="1" style="width:80px;">
							<button class="btn btn-primary" type="submit">Add</button>
						</form>
						<form method="post" action="loyalty.php?action=set&id=<?php echo (int)$u['user_id']; ?>" style="display:inline; margin-left:6px;">
							<input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
							<input type="number" name="points" value="<?php echo (int)$u['loyalty_points']; ?>" style="width:100px;">
							<button class="btn btn-outline" type="submit">Set</button>
						</form>
					</td>
				</tr>
				<?php endwhile; ?>
			</table>
		</div>
	</main>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
