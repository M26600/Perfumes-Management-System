<?php
session_start();
require_once 'includes/db_connect.php';

$message = "";
$message_type = ""; // 'success' or 'error'

// Helper: check if column exists
function users_has_column(mysqli $conn, string $col): bool {
    $res = $conn->query("SHOW COLUMNS FROM users LIKE '" . $conn->real_escape_string($col) . "'");
    return $res && $res->num_rows > 0;
}
$has_loyalty_col = users_has_column($conn, 'loyalty_points');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $message = "⚠️ Please fill in all fields.";
        $message_type = "error";
    } elseif (strlen($password) < 6) {
        $message = "⚠️ Password must be at least 6 characters long.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "⚠️ Please enter a valid email address.";
        $message_type = "error";
    } else {
        // Check if email already exists using prepared statement
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = "⚠️ Email already registered! Please use a different email or <a href='login.php'>login</a>.";
                $message_type = "error";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user using prepared statement
                if ($has_loyalty_col) {
                    $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, loyalty_points) VALUES (?, ?, ?, 0)");
                } else {
                    $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                }
                if ($insert_stmt) {
                    if ($has_loyalty_col) {
                        $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
                    } else {
                        $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
                    }
                    
                    if ($insert_stmt->execute()) {
                        // Get the newly created user ID
                        $user_id = $conn->insert_id;
                        
                        // Automatically log the user in
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['registration_success'] = true;
                        
                        // Close the statement
                        $insert_stmt->close();
                        $check_stmt->close();
                        
                        // Redirect to products page
                        header("Location: products.php");
                        exit();
                    } else {
                        $message = "❌ Error: " . $insert_stmt->error;
                        $message_type = "error";
                        $insert_stmt->close();
                    }
                } else {
                    $message = "❌ Error preparing statement: " . $conn->error;
                    $message_type = "error";
                }
            }
            $check_stmt->close();
        } else {
            $message = "❌ Error preparing statement: " . $conn->error;
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
    <title>Register | Perfume Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 5rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .auth-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        }
        .auth-container form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .auth-container input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .auth-container input:focus {
            outline: none;
            border-color: #007bff;
        }
        .msg {
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .msg.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .msg.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .auth-container p {
            text-align: center;
            margin-top: 1rem;
            color: #666;
        }
        .auth-container a {
            color: #007bff;
            text-decoration: none;
        }
        .auth-container a:hover {
            text-decoration: underline;
        }
        .home-link {
            text-align: center;
            margin-bottom: 1rem;
        }
        .home-link a {
            color: #007bff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .home-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="home-link">
            <a href="index.html"><i class="fas fa-home"></i> Back to Home</a>
        </div>
        <h2>Create Account</h2>
        <?php if ($message): ?>
            <div class="msg <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required 
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            <input type="email" name="email" placeholder="Email address" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            <div style="position:relative;">
                <input id="regPwd" type="password" name="password" placeholder="Password (min. 6 characters)" required>
                <button type="button" aria-label="Toggle password visibility" onclick="togglePwd('regPwd', this)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:#666; cursor:pointer;">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <button type="submit" class="btn btn-auth">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login</a></p>
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
