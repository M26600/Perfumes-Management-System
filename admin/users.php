<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/db_connect.php';

// optional role support if a `role` column exists
function users_has_column(mysqli $conn, string $column): bool {
    $res = $conn->query("SHOW COLUMNS FROM users LIKE '" . $conn->real_escape_string($column) . "'");
    return $res && $res->num_rows > 0;
}
$has_role_col = users_has_column($conn, 'role');
$has_loyalty_col = users_has_column($conn, 'loyalty_points');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();
	$username = trim($_POST['username'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $role = $has_role_col ? (trim($_POST['role'] ?? 'customer')) : 'customer';
	if ($username && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 6) {
		$exists = $conn->prepare("SELECT user_id FROM users WHERE email=?");
		$exists->bind_param('s', $email);
		$exists->execute();
		$r = $exists->get_result();
		if ($r->num_rows > 0) {
			$message = 'Email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($has_role_col && $has_loyalty_col) {
                $stmt = $conn->prepare("INSERT INTO users (username,email,password,is_admin,role,loyalty_points) VALUES (?,?,?,?,?,0)");
                $stmt->bind_param('sssis', $username, $email, $hash, $is_admin, $role);
            } elseif ($has_role_col) {
                $stmt = $conn->prepare("INSERT INTO users (username,email,password,is_admin,role) VALUES (?,?,?,?,?)");
                $stmt->bind_param('sssis', $username, $email, $hash, $is_admin, $role);
            } elseif ($has_loyalty_col) {
                $stmt = $conn->prepare("INSERT INTO users (username,email,password,is_admin,loyalty_points) VALUES (?,?,?,?,0)");
                $stmt->bind_param('sssi', $username, $email, $hash, $is_admin);
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username,email,password,is_admin) VALUES (?,?,?,?)");
                $stmt->bind_param('sssi', $username, $email, $hash, $is_admin);
            }
			$stmt->execute();
			$message = 'User created.';
		}
	} else {
		$message = 'Please fill valid data (password min 6 chars).';
	}
}
// Handle deletion
if (($_GET['action'] ?? '') === 'delete' && !empty($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $uid = (int)$_GET['id'];
    // prevent self-delete to avoid lockout
    if (!empty($_SESSION['user_id']) && $uid === (int)$_SESSION['user_id']) {
        $message = 'You cannot delete your own admin account while logged in.';
    } else {
        $conn->query("DELETE FROM users WHERE user_id=" . $uid);
        $message = 'User deleted.';
    }
}

// Handle update permissions/role
if (($_GET['action'] ?? '') === 'update' && !empty($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $uid = (int)$_GET['id'];
    $new_admin = isset($_POST['is_admin']) ? 1 : 0;
    if ($has_role_col) {
        $new_role = trim($_POST['role'] ?? 'customer');
        $stmt = $conn->prepare("UPDATE users SET is_admin=?, role=? WHERE user_id=?");
        $stmt->bind_param('isi', $new_admin, $new_role, $uid);
    } else {
        $stmt = $conn->prepare("UPDATE users SET is_admin=? WHERE user_id=?");
        $stmt->bind_param('ii', $new_admin, $uid);
    }
    $stmt->execute();
    $message = 'User permissions updated.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Add User</title>
	<link rel="stylesheet" href="../assets/css/main.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<style>.admin-wrap{max-width:800px;margin:140px auto 40px;padding:20px}.form-card{border:1px solid #eee;border-radius:8px;padding:16px;background:#fff}</style>
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
		<h1>Add User</h1>
		<?php if ($message): ?><p class="msg"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    <div class="form-card" style="margin-bottom:24px;">
        <h2 style="margin-bottom:12px;">Add User</h2>
        <form method="post">
				<input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
				<input type="text" name="username" placeholder="Username" required>
				<input type="email" name="email" placeholder="Email" required>
            <div style="position:relative;">
                <input id="newUserPwd" type="password" name="password" placeholder="Password (min 6)" required>
                <button type="button" aria-label="Toggle password visibility" onclick="togglePwd('newUserPwd', this)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:#666; cursor:pointer;">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

    <div class="form-card">
        <h2 style="margin-bottom:12px;">All Users</h2>
        <table style="width:100%; border-collapse:collapse; background:#fff; box-shadow: var(--shadow); border-radius:10px; overflow:hidden;">
            <tr><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">ID</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Username</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Email</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Role</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Admin</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Actions</th></tr>
            <?php $all = $conn->query("SELECT user_id, username, email, IFNULL(role,'customer') AS role, IFNULL(is_admin,0) AS is_admin FROM users ORDER BY user_id DESC");
            while ($u = $all->fetch_assoc()): ?>
                <tr>
                    <td style="padding:8px;border-bottom:1px solid #eee;"><?php echo (int)$u['user_id']; ?></td>
                    <td style="padding:8px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($u['username']); ?></td>
                    <td style="padding:8px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($u['email']); ?></td>
                    <td style="padding:8px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars($u['role']); ?></td>
                    <td style="padding:8px;border-bottom:1px solid #eee;"><?php echo ((int)$u['is_admin'] ? 'Yes' : 'No'); ?></td>
                    <td style="padding:8px;border-bottom:1px solid #eee;">
                        <form method="post" action="users.php?action=delete&id=<?php echo (int)$u['user_id']; ?>" onsubmit="return confirm('Delete this user?');" style="display:inline; margin-right:6px;">
                            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                            <button class="btn btn-danger" type="submit">Delete</button>
                        </form>
                        <form method="post" action="users.php?action=update&id=<?php echo (int)$u['user_id']; ?>" style="display:inline;">
                            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                            <?php if ($has_role_col): ?>
                            <select name="role" style="padding:6px;border:1px solid #ddd;border-radius:6px;">
                                <?php $roles = ['customer','cashier','admin']; foreach($roles as $r): ?>
                                <option value="<?php echo $r; ?>" <?php if ($u['role']===$r) echo 'selected'; ?>><?php echo ucfirst($r); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                            <label style="margin-left:6px;">
                                <input type="checkbox" name="is_admin" value="1" <?php echo ((int)$u['is_admin'] ? 'checked' : ''); ?>> Admin
                            </label>
                            <button class="btn btn-outline" type="submit">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
            <label style="display:block;margin:8px 0"><input type="checkbox" name="is_admin"> Make Admin</label>
            <label style="display:block;margin:8px 0">Role
                <select name="role">
                    <option value="customer">Customer</option>
                    <option value="cashier">Cashier</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
				<button class="btn btn-primary" type="submit">Create User</button>
			</form>
		</div>

    <script>
    function togglePwd(id, btn){
        var el = document.getElementById(id);
        if(!el) return;
        var showing = el.type === 'text';
        el.type = showing ? 'password' : 'text';
        btn.innerHTML = showing ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    }
    </script>
	</main>
	<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
