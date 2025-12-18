<?php
// Start session before any HTML output to avoid "headers already sent" warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userLoggedIn = !empty($_SESSION['user_id']);
$username = $userLoggedIn ? htmlspecialchars($_SESSION['username']) : '';

$message = "";
$message_type = "";
require_once __DIR__ . '/includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $message = "⚠️ Please fill in all fields.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "⚠️ Please enter a valid email address.";
        $message_type = "error";
    } else {
        // Ensure contacts table exists and has the necessary columns
        $createSql = "CREATE TABLE IF NOT EXISTS contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            subject VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($conn, $createSql);

        // Backward compatibility: if older DB is missing subject column, add it
        $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM contacts LIKE 'subject'");
        if ($colCheck && mysqli_num_rows($colCheck) === 0) {
            mysqli_query($conn, "ALTER TABLE contacts ADD COLUMN subject VARCHAR(190) NOT NULL AFTER email");
        }

        // Save message
        $stmt = $conn->prepare("INSERT INTO contacts (name,email,subject,message) VALUES (?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param('ssss', $name, $email, $subject, $message_text);
            if ($stmt->execute()) {
                $message = "✅ Thank you for contacting us! Your message has been received.";
                $message_type = "success";
                $_POST = array();
            } else {
                $message = "❌ Error saving your message. Please try again.";
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "❌ Error preparing to save your message.";
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
    <title>Contact Us | Perfume Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .contact-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }
        .contact-hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .contact-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        .contact-content {
            padding: 4rem 0;
        }
        .contact-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 2rem;
        }
        .contact-info {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
        }
        .contact-info h2 {
            color: #333;
            margin-bottom: 1.5rem;
        }
        .info-item {
            display: flex;
            align-items: start;
            margin-bottom: 2rem;
        }
        .info-item i {
            font-size: 1.5rem;
            color: #667eea;
            margin-right: 1rem;
            margin-top: 0.25rem;
        }
        .info-item div h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .info-item div p {
            color: #666;
            margin: 0;
        }
        .contact-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .contact-form h2 {
            color: #333;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }
        .msg {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
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
        @media (max-width: 768px) {
            .contact-wrapper {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container header-content">
            <a href="index.html" class="logo"><i class="fas fa-spray-can"></i> Perfume</a>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="products.php">Shop</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php" class="active">Contact</a></li>
                    <?php if ($userLoggedIn): ?>
                        <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                        <li class="nav-welcome">Welcome, <?= $username ?> (<a href="logout.php">Logout</a>)</li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn btn-outline signin-btn">Sign In</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="menu-toggle"><i class="fas fa-bars"></i></div>
        </div>
    </header>

    <section class="contact-hero">
        <div class="container">
            <h1>Contact Us</h1>
            <p>We're here to help! Get in touch with us</p>
        </div>
    </section>

    <main class="container contact-content page-content">
        <div class="contact-wrapper">
            <div class="contact-info">
                <h2>Get in Touch</h2>
                <p style="color: #666; margin-bottom: 2rem;">Have a question or need assistance? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
                
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h3>Address</h3>
                        <p>123 Perfume Street<br>Fragrance City, FC 12345<br>United States</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h3>Phone</h3>
                        <p>+1 (555) 123-4567<br>Mon-Fri: 9am - 6pm EST</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h3>Email</h3>
                        <p>info@perfumemanagement.com<br>support@perfumemanagement.com</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h3>Business Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM<br>Sunday: Closed</p>
                    </div>
                </div>
            </div>
            
            <div class="contact-form">
                <h2>Send us a Message</h2>
                
                <?php if ($message): ?>
                    <div class="msg <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Your Name *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Your Email *</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" required
                               value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
        
        <div style="margin-top: 4rem; padding: 2rem; background: #f8f9fa; border-radius: 8px;">
            <h2 style="color: #333; margin-bottom: 1rem;">Frequently Asked Questions</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div>
                    <h3 style="color: #667eea; margin-bottom: 0.5rem;">Shipping Information</h3>
                    <p style="color: #666;">We offer fast and secure shipping worldwide. Standard shipping takes 5-7 business days. Express shipping options are available at checkout.</p>
                </div>
                <div>
                    <h3 style="color: #667eea; margin-bottom: 0.5rem;">Returns & Exchanges</h3>
                    <p style="color: #666;">We accept returns within 30 days of purchase. Items must be unopened and in original packaging. Contact us for return authorization.</p>
                </div>
                <div>
                    <h3 style="color: #667eea; margin-bottom: 0.5rem;">Loyalty Program</h3>
                    <p style="color: #666;">Earn 1 loyalty point for every order you complete. When you reach more than 5 points, you qualify for a free perfume!</p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

