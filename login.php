<?php
session_start();
require_once 'includes/db_connect.php';
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = "⚠️ Please fill in all fields.";
        $message_type = "error";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT user_id, username, password, is_admin, IFNULL(role,'') AS role FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = (int)($user['is_admin'] ?? 0);
                $_SESSION['role'] = $user['role'] ?? '';
                if (!empty($_SESSION['is_admin'])) {
                    header("Location: admin/index.php");
                } elseif (!empty($_SESSION['role']) && strtolower($_SESSION['role'])==='cashier') {
                    header("Location: admin/cashier_dashboard.php");
                } else {
                    header("Location: products.php");
                }
                exit();
            } else {
                $message = "❌ Invalid email or password!";
                $message_type = "error";
            }
        } else {
            $message = "❌ Error: " . $conn->error;
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Perfume Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .auth-container {
            max-width: 400px;
            width: 90%;
            margin: 2rem auto;
            padding: 2.5rem;
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .auth-container h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary);
            font-size: 1.8rem;
        }
        
        .auth-container form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        
        .auth-container input {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .auth-container input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }
        
        .msg {
            padding: 0.9rem;
            border-radius: 6px;
            margin-bottom: 1.2rem;
            text-align: center;
            font-weight: 500;
        }
        
        .msg.success {
            background-color: #e6f7ee;
            color: #0d6832;
            border: 1px solid #a7f3d0;
        }
        
        .msg.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .auth-container p {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        .auth-container a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .auth-container a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .home-link {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .home-link a {
            color: var(--primary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .home-link a:hover {
            color: var(--primary-dark);
        }
        
        .password-input {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: var(--transition);
        }
        
        .password-toggle:hover {
            color: var(--primary);
            background: rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="home-link">
            <a href="index.html"><i class="fas fa-home"></i> Back to Home</a>
        </div>
        <h2>Login</h2>
        <?php if ($message): ?>
            <div class="msg <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email address" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            <div style="position:relative;">
                <input id="loginPassword" type="password" name="password" placeholder="Password" required>
                <button type="button" aria-label="Toggle password visibility" onclick="togglePwd('loginPassword', this)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:#666; cursor:pointer;">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <button type="submit" class="btn btn-auth">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register</a></p>
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
    <?php include 'includes/footer.php'; ?>
