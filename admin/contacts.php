<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/db_connect.php';

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$list = $conn->query("SELECT id, name, email, subject, message, created_at FROM contacts ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Contact Messages</title>
	<link rel="stylesheet" href="../assets/css/main.css">
	<style>.admin-wrap{max-width:1100px;margin:140px auto 40px;padding:20px}.card{background:#fff;border:1px solid #eee;border-radius:10px;box-shadow:var(--shadow);padding:18px}</style>
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
		<h1>Contact Messages</h1>
		<div class="card">
			<table style="width:100%;border-collapse:collapse">
				<tr><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">ID</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Name</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Email</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Subject</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Message</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Date</th></tr>
				<?php while($row=$list->fetch_assoc()): ?>
				<tr>
					<td style="padding:8px;border-bottom:1px solid #eee;"><?php echo (int)$row['id']; ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($row['name']); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($row['email']); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($row['subject']); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee; max-width:420px;"><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
					<td style="padding:8px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($row['created_at']); ?></td>
				</tr>
				<?php endwhile; ?>
			</table>
		</div>
	</main>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>



