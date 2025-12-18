<?php
require_once __DIR__ . '/../includes/auth.php';
require_cashier_or_admin();
require_once __DIR__ . '/../includes/db_connect.php';

// Helper: check if a column exists so we can support optional discounts and cost price without breaking older DBs
function products_has_column(mysqli $conn, string $column): bool {
    $res = $conn->query("SHOW COLUMNS FROM products LIKE '" . $conn->real_escape_string($column) . "'");
    return $res && $res->num_rows > 0;
}
$has_discount_col = products_has_column($conn, 'discount_percent');
$has_cost_col = products_has_column($conn, 'cost_price');

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$message = '';

// Handle deletions first to avoid the POST add/edit handler intercepting delete posts
if ($action === 'delete' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $conn->query("DELETE FROM products WHERE id=" . $id);
    header('Location: perfumes.php?msg=' . urlencode('Perfume deleted.'));
    exit();
}
if ($action === 'delete_discounted' && $_SERVER['REQUEST_METHOD'] === 'POST' && $has_discount_col) {
    verify_csrf();
    $conn->query("DELETE FROM products WHERE discount_percent > 0");
    header('Location: perfumes.php?msg=' . urlencode('All discounted perfumes deleted.'));
    exit();
}

// Add/Edit only when explicit save flag is present
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['act'] ?? '') === 'save')) {
	verify_csrf();
	$name = trim($_POST['name'] ?? '');
	$brand = trim($_POST['brand'] ?? '');
	$price = (float)($_POST['price'] ?? 0);
	$stock = (int)($_POST['stock'] ?? 0);
    $discount_percent = $has_discount_col ? (int)($_POST['discount_percent'] ?? 0) : 0;
    $cost_price = $has_cost_col ? (float)($_POST['cost_price'] ?? 0) : 0;
	$image_path = $_POST['existing_image'] ?? '';
    $edit_id = (int)($_POST['edit_id'] ?? 0);
    if (!empty($_FILES['image']['name'])) {
		$uploadDir = __DIR__ . '/../images/uploads/';
		if (!is_dir($uploadDir)) { mkdir($uploadDir, 0775, true); }
		$ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
		$fname = 'p_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
		$dest = $uploadDir . $fname;
        // basic MIME/size validation
        $allowed = ['image/jpeg','image/png','image/webp'];
        $mime = mime_content_type($_FILES['image']['tmp_name']);
        if (in_array($mime, $allowed, true) && $_FILES['image']['size'] <= 5 * 1024 * 1024 && move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
			$image_path = 'images/uploads/' . $fname;
		}
	}
    // Determine edit vs add by POST edit_id to avoid losing action on submit
    if ($edit_id > 0) {
        if ($has_discount_col && $has_cost_col) {
            $stmt = $conn->prepare("UPDATE products SET name=?, brand=?, price=?, stock=?, image=?, discount_percent=?, cost_price=? WHERE id=?");
            $stmt->bind_param('ssdisidi', $name, $brand, $price, $stock, $image_path, $discount_percent, $cost_price, $edit_id);
        } elseif ($has_discount_col) {
            $stmt = $conn->prepare("UPDATE products SET name=?, brand=?, price=?, stock=?, image=?, discount_percent=? WHERE id=?");
            $stmt->bind_param('ssdisii', $name, $brand, $price, $stock, $image_path, $discount_percent, $edit_id);
        } elseif ($has_cost_col) {
            $stmt = $conn->prepare("UPDATE products SET name=?, brand=?, price=?, stock=?, image=?, cost_price=? WHERE id=?");
            $stmt->bind_param('ssdisdi', $name, $brand, $price, $stock, $image_path, $cost_price, $edit_id);
        } else {
            $stmt = $conn->prepare("UPDATE products SET name=?, brand=?, price=?, stock=?, image=? WHERE id=?");
            $stmt->bind_param('ssdisi', $name, $brand, $price, $stock, $image_path, $edit_id);
        }
		$stmt->execute();
		$message = 'Perfume updated.';
	} else {
        if ($has_discount_col && $has_cost_col) {
            $stmt = $conn->prepare("INSERT INTO products (name, brand, price, stock, image, discount_percent, cost_price) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('ssdisid', $name, $brand, $price, $stock, $image_path, $discount_percent, $cost_price);
        } elseif ($has_discount_col) {
            $stmt = $conn->prepare("INSERT INTO products (name, brand, price, stock, image, discount_percent) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('ssdisi', $name, $brand, $price, $stock, $image_path, $discount_percent);
        } elseif ($has_cost_col) {
            $stmt = $conn->prepare("INSERT INTO products (name, brand, price, stock, image, cost_price) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('ssdisd', $name, $brand, $price, $stock, $image_path, $cost_price);
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, brand, price, stock, image) VALUES (?,?,?,?,?)");
            $stmt->bind_param('ssdis', $name, $brand, $price, $stock, $image_path);
        }
		$stmt->execute();
		$message = 'Perfume added.';
	}
    header('Location: perfumes.php?msg=' . urlencode($message));
	exit();
}

if ($action === 'delete' && $id > 0) {
	verify_csrf();
    $conn->query("DELETE FROM products WHERE id=" . $id);
    header('Location: perfumes.php?msg=' . urlencode('Perfume deleted.'));
	exit();
}

if ($action === 'delete_discounted' && $has_discount_col) {
    verify_csrf();
    $conn->query("DELETE FROM products WHERE discount_percent > 0");
    header('Location: perfumes.php?msg=' . urlencode('All discounted perfumes deleted.'));
    exit();
}

$current = null;
if ($action === 'edit' && $id > 0) {
	$res = $conn->query("SELECT * FROM products WHERE id=" . $id);
	$current = $res->fetch_assoc();
}
$selectCols = 'id,name,brand,price,stock,image'
    . ($has_discount_col ? ',discount_percent' : '')
    . ($has_cost_col ? ',cost_price' : '');
$products = $conn->query("SELECT $selectCols FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Manage Perfumes</title>
	<link rel="stylesheet" href="/assets/css/main.css">
    <style>
        .admin-wrap{max-width:1100px;margin:140px auto 40px;padding:20px}
        .form-card{border:1px solid #eee;border-radius:10px;padding:18px;background:#fff;margin:16px 0;box-shadow:var(--shadow)}
        table{width:100%;border-collapse:collapse;background:#fff;box-shadow:var(--shadow);border-radius:10px;overflow:hidden}
        th{background:#f8f9ff;color:#2d3436;font-weight:600}
        td,th{border-bottom:1px solid #eee;padding:12px;text-align:left}
        tr:last-child td{border-bottom:0}
        .actions a,.actions button{margin-right:8px}
        .thumb{width:70px;height:70px;object-fit:cover;border-radius:8px;border:1px solid #eee}
        .msg{padding:10px 14px;border-radius:8px;background:#e8f5e9;color:#2e7d32;margin:10px 0;display:inline-block}
        .btn-danger{background:#dc3545;color:#fff;border:none}
        .btn-danger:hover{background:#c82333}
        input,select{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;margin:6px 0}
    </style>
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
	<main class="admin-wrap">
		<h1>Manage Perfumes</h1>
    <?php if (!empty($_GET['msg'])): ?><div class="msg"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
    <div class="form-card">
			<h2><?php echo $current ? 'Edit Perfume' : 'Add Perfume'; ?></h2>
        <form method="post" enctype="multipart/form-data">
				<input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="act" value="save">
            <?php if ($current): ?><input type="hidden" name="edit_id" value="<?php echo (int)$current['id']; ?>"><?php endif; ?>
				<input type="text" name="name" placeholder="Name" required value="<?php echo htmlspecialchars($current['name'] ?? ''); ?>">
				<input type="text" name="brand" placeholder="Brand" required value="<?php echo htmlspecialchars($current['brand'] ?? ''); ?>">
				<input type="number" step="0.01" name="price" placeholder="Price" required value="<?php echo htmlspecialchars($current['price'] ?? ''); ?>">
				<input type="number" name="stock" placeholder="Stock" required value="<?php echo htmlspecialchars($current['stock'] ?? ''); ?>">
            <?php if ($has_discount_col): ?>
            <input type="number" name="discount_percent" min="0" max="90" placeholder="Discount % (optional)" value="<?php echo htmlspecialchars($current['discount_percent'] ?? ''); ?>">
            <?php endif; ?>
            <?php if ($has_cost_col): ?>
            <input type="number" step="0.01" name="cost_price" placeholder="Cost price (what we pay per unit)" value="<?php echo htmlspecialchars($current['cost_price'] ?? ''); ?>">
            <?php else: ?>
            <p style="color:#666;font-size:0.9rem;">Tip: to enable profit tracking, add a column: <code>ALTER TABLE products ADD COLUMN cost_price DECIMAL(10,2) DEFAULT 0;</code></p>
            <?php endif; ?>
            <?php if ($current && !empty($current['image'])): ?>
                <p>Current Image: <img class="thumb" src="../<?php echo htmlspecialchars($current['image']); ?>" alt=""></p>
					<input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($current['image']); ?>">
				<?php endif; ?>
				<input type="file" name="image" accept="image/*">
				<button type="submit" class="btn btn-primary">Save</button>
			</form>
		</div>
		<h2>All Perfumes</h2>
    <form method="post" action="perfumes.php?action=delete_discounted" onsubmit="return confirm('Delete ALL discounted perfumes?');" style="margin:12px 0;">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <?php if ($has_discount_col): ?>
        <button class="btn btn-danger" type="submit">Delete Discounted Perfumes</button>
        <?php endif; ?>
    </form>
    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Brand</th><th>Price</th><th>Stock</th>
            <?php if ($has_discount_col): ?><th>Discount %</th><?php endif; ?>
            <?php if ($has_cost_col): ?><th>Cost Price</th><?php endif; ?>
            <th>Image</th><th>Actions</th>
        </tr>
			<?php while ($row = $products->fetch_assoc()): ?>
			<tr>
				<td><?php echo (int)$row['id']; ?></td>
				<td><?php echo htmlspecialchars($row['name']); ?></td>
				<td><?php echo htmlspecialchars($row['brand']); ?></td>
				<td>$<?php echo number_format((float)$row['price'],2); ?></td>
				<td><?php echo (int)$row['stock']; ?></td>
            <?php if ($has_discount_col): ?><td><?php echo (int)($row['discount_percent'] ?? 0); ?></td><?php endif; ?>
            <?php if ($has_cost_col): ?><td>$<?php echo number_format((float)($row['cost_price'] ?? 0),2); ?></td><?php endif; ?>
            <td><?php if (!empty($row['image'])): ?><img class="thumb" src="../<?php echo htmlspecialchars($row['image']); ?>"><?php endif; ?></td>
				<td class="actions">
                <a class="btn btn-outline" href="perfumes.php?action=edit&id=<?php echo (int)$row['id']; ?>">Edit</a>
                <form method="post" action="perfumes.php?action=delete&id=<?php echo (int)$row['id']; ?>" style="display:inline" onsubmit="return confirm('Delete this perfume?');">
						<input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
						<button class="btn btn-danger" type="submit">Delete</button>
					</form>
				</td>
			</tr>
			<?php endwhile; ?>
		</table>
	</main>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
