<?php
require_once __DIR__ . '/../includes/auth.php';
require_cashier_or_admin();
require_once __DIR__ . '/../includes/db_connect.php';

$message = '';
$users = $conn->query("SELECT user_id, username FROM users ORDER BY username ASC");
$products = $conn->query("SELECT id, name, price, stock, cost_price FROM products WHERE stock > 0 ORDER BY name ASC");

function orders_has_column(mysqli $conn, string $column): bool {
    $res = $conn->query("SHOW COLUMNS FROM orders LIKE '" . $conn->real_escape_string($column) . "'");
    return $res && $res->num_rows > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$user_id = (int)($_POST['user_id'] ?? 0);
	$product_id = (int)($_POST['product_id'] ?? 0);
    $cost_price = isset($_POST['cost_price']) ? (float)$_POST['cost_price'] : null;
	$qty = (int)($_POST['qty'] ?? 1);
	if ($user_id && $product_id && $qty > 0) {
		// fetch product
		$stmt = $conn->prepare("SELECT price, stock, cost_price FROM products WHERE id=?");
		$stmt->bind_param('i', $product_id);
		$stmt->execute();
		$res = $stmt->get_result();
		$prod = $res->fetch_assoc();
		if ($prod && $prod['stock'] >= $qty) {
			$price = (float)$prod['price'];
            // If cashier provided a new cost price, or there is no cost set yet, update it for this product
            $effectiveCost = $prod['cost_price'] ?? 0;
            if ($cost_price !== null && $cost_price >= 0) {
                $effectiveCost = $cost_price;
                $upCost = $conn->prepare("UPDATE products SET cost_price = ? WHERE id = ?");
                if ($upCost) {
                    $upCost->bind_param('di', $effectiveCost, $product_id);
                    $upCost->execute();
                    $upCost->close();
                }
            }
			$total = $price * $qty;
			$tax = $total * 0.08;
			$grand = $total + $tax;

			$conn->begin_transaction();
        try {
				// order
            $hasTax = orders_has_column($conn, 'tax');
            $hasSubtotal = orders_has_column($conn, 'subtotal');
            $hasGrand = orders_has_column($conn, 'grand_total');

            if ($hasGrand || $hasTax || $hasSubtotal) {
                // Build dynamic insert
                $cols = ['user_id','payment_method','status','momo_number'];
                $vals = ['admin_recorded','completed','N/A'];
                $types = 'isss';
                $params = [$user_id, ...$vals];
                if ($hasSubtotal) { $cols[]='subtotal'; $types.='d'; $params[]=$total; }
                if ($hasTax) { $cols[]='tax'; $types.='d'; $params[]=$tax; }
                if ($hasGrand) { $cols[]='grand_total'; $types.='d'; $params[]=$grand; } else { $cols[]='total'; $types.='d'; $params[]=$grand; }

                $sql = 'INSERT INTO orders (' . implode(',', $cols) . ') VALUES (' . rtrim(str_repeat('?,', count($cols)), ',') . ')';
                $o = $conn->prepare($sql);
                $o->bind_param($types, ...$params);
            } else {
                $o = $conn->prepare("INSERT INTO orders (user_id, total, payment_method, status, momo_number) VALUES (?, ?, 'admin_recorded', 'completed', 'N/A')");
                $o->bind_param('id', $user_id, $grand);
            }
				$o->execute();
				$order_id = $conn->insert_id;
				$o->close();

				// item
				$i = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
				$i->bind_param('iiid', $order_id, $product_id, $qty, $price);
				$i->execute();
				$i->close();

				// stock
				$u = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
				$u->bind_param('ii', $qty, $product_id);
				$u->execute();
				$u->close();

				// loyalty: 1 point per order
				$lp = $conn->prepare("UPDATE users SET loyalty_points = loyalty_points + 1 WHERE user_id = ?");
				$lp->bind_param('i', $user_id);
				$lp->execute();
				$lp->close();

				$conn->commit();
				$message = 'Purchase recorded.';
			} catch (Exception $e) {
				$conn->rollback();
				$message = 'Error: ' . $e->getMessage();
			}
		} else {
			$message = 'Insufficient stock.';
		}
	} else {
		$message = 'Please select valid user, product, and quantity.';
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Record Purchase</title>
	<link rel="stylesheet" href="../assets/css/main.css">
	<style>
		.admin-wrap{max-width:900px;margin:140px auto 40px;padding:20px}
		.form-card{border:1px solid #eee;border-radius:10px;padding:18px;background:#fff;box-shadow:var(--shadow)}
		select,input{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;margin:6px 0}
		.btn-primary{margin-top:8px}
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
		<h1>Record Purchase</h1>
		<?php if ($message): ?><p class="msg"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
		<div class="form-card">
			<form method="post">
				<input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
				<label>User</label>
				<select name="user_id" required>
					<option value="">Select user</option>
					<?php $users->data_seek(0); while($u=$users->fetch_assoc()): ?>
					<option value="<?php echo (int)$u['user_id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
					<?php endwhile; ?>
				</select>
            <label>Perfume</label>
            <select name="product_id" id="selectProduct" required>
					<option value="">Select perfume</option>
                <?php $products->data_seek(0); while($p=$products->fetch_assoc()): ?>
                <option value="<?php echo (int)$p['id']; ?>" data-price="<?php echo htmlspecialchars(number_format((float)$p['price'],2,'.','')); ?>" data-stock="<?php echo (int)$p['stock']; ?>"><?php echo htmlspecialchars($p['name']) . ' ($' . number_format((float)$p['price'],2) . ', stock ' . (int)$p['stock'] . ')'; ?></option>
					<?php endwhile; ?>
				</select>
				<label>Quantity</label>
            <input type="number" name="qty" id="qty" min="1" value="1" required>
            <label>Cost price per unit (what we pay)</label>
            <input type="number" step="0.01" name="cost_price" id="cost_price" placeholder="e.g. 30.00">
				<button type="submit" class="btn btn-primary">Record</button>
			</form>
        <div style="margin-top:12px;">
            <h3>Bill Summary</h3>
            <p id="billSubtotal">Subtotal: $0.00</p>
            <p id="billTax">Tax (8%): $0.00</p>
            <p id="billGrand"><strong>Total: $0.00</strong></p>
        </div>
		</div>
	</main>
    <script>
    (function(){
        function recalc(){
            var sel = document.getElementById('selectProduct');
            var qtyEl = document.getElementById('qty');
            if(!sel || !qtyEl) return;
            var opt = sel.options[sel.selectedIndex];
            var price = opt ? parseFloat(opt.getAttribute('data-price')||'0') : 0;
            var qty = parseInt(qtyEl.value || '1', 10);
            if (qty < 1) qty = 1;
            var subtotal = price * qty;
            var tax = subtotal * 0.08;
            var grand = subtotal + tax;
            document.getElementById('billSubtotal').textContent = 'Subtotal: $' + subtotal.toFixed(2);
            document.getElementById('billTax').textContent = 'Tax (8%): $' + tax.toFixed(2);
            document.getElementById('billGrand').innerHTML = '<strong>Total: $' + grand.toFixed(2) + '</strong>';
        }
        document.getElementById('selectProduct')?.addEventListener('change', recalc);
        document.getElementById('qty')?.addEventListener('input', recalc);
        recalc();
    })();
    </script>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
