<?php
// Start session before any HTML output to avoid "headers already sent" warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userLoggedIn = !empty($_SESSION['user_id']);
$username = $userLoggedIn ? htmlspecialchars($_SESSION['username']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Perfume Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .about-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }
        .about-hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .about-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        .about-content {
            padding: 4rem 0;
        }
        .about-section {
            margin-bottom: 3rem;
        }
        .about-section h2 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }
        .about-section p {
            line-height: 1.8;
            color: #666;
            margin-bottom: 1rem;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .feature-card i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .feature-card h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .feature-card p {
            color: #666;
        }
        .values-list {
            list-style: none;
            padding: 0;
        }
        .values-list li {
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        .values-list li strong {
            color: #667eea;
            display: block;
            margin-bottom: 0.5rem;
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
                    <li><a href="about.php" class="active">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
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

    <section class="about-hero">
        <div class="container">
            <h1>About Perfume Management</h1>
            <p>Your trusted destination for premium fragrances</p>
        </div>
    </section>

    <main class="container about-content page-content">
        <div class="about-section">
            <h2>Our Story</h2>
            <p>Welcome to Perfume Management, where passion meets fragrance. Established in 2015, we are dedicated to bringing you the finest collection of perfumes from around the world. Our journey began with a simple belief: everyone deserves to find their perfect scent that tells their unique story.</p>
            <p>With over 1,000+ satisfied customers and partnerships with 50+ premium fragrance brands, we've grown from a small boutique to a leading online destination for perfume enthusiasts. Our expert team of perfumers and fragrance consultants are passionate about helping you discover scents that resonate with your personality and style.</p>
            <p>We pride ourselves on offering an exceptional selection of authentic, high-quality fragrances, from timeless classics to the latest designer releases. Each bottle in our collection is carefully selected to ensure it meets our strict quality standards.</p>
        </div>

        <div class="about-section">
            <h2>Our Mission</h2>
            <p>Our mission is to make luxury fragrances accessible to everyone while maintaining the highest standards of quality and customer service. We believe that a great perfume is more than just a scent—it's a form of self-expression, a memory maker, and a confidence booster.</p>
        </div>

        <div class="about-section">
            <h2>What We Offer</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-spray-can"></i>
                    <h3>Premium Quality</h3>
                    <p>We source only the finest fragrances from trusted manufacturers worldwide.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shipping-fast"></i>
                    <h3>Fast Delivery</h3>
                    <p>Quick and secure shipping to get your favorite scents to you as soon as possible.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-gift"></i>
                    <h3>Loyalty Rewards</h3>
                    <p>Earn points with every purchase and redeem them for free perfumes.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-headset"></i>
                    <h3>Customer Support</h3>
                    <p>Our dedicated team is here to help you find your perfect fragrance.</p>
                </div>
            </div>
        </div>

        <div class="about-section">
            <h2>Our Values</h2>
            <ul class="values-list">
                <li>
                    <strong>Quality First</strong>
                    We never compromise on the quality of our products. Every perfume in our collection is carefully selected and tested.
                </li>
                <li>
                    <strong>Customer Satisfaction</strong>
                    Your happiness is our priority. We go above and beyond to ensure you have the best shopping experience.
                </li>
                <li>
                    <strong>Transparency</strong>
                    We believe in honest pricing and clear communication with our customers.
                </li>
                <li>
                    <strong>Innovation</strong>
                    We continuously update our collection with the latest and most popular fragrances.
                </li>
            </ul>
        </div>

        <div class="about-section">
            <h2>Why Choose Us?</h2>
            <p>At Perfume Management, we understand that choosing a perfume is a personal journey. That's why we offer:</p>
            <ul class="values-list">
                <li>
                    <strong>Wide Selection</strong>
                    Browse through hundreds of carefully curated fragrances from top brands.
                </li>
                <li>
                    <strong>Competitive Prices</strong>
                    Get the best value for your money with our competitive pricing.
                </li>
                <li>
                    <strong>Easy Shopping</strong>
                    Our user-friendly platform makes it easy to find and purchase your favorite scents.
                </li>
                <li>
                    <strong>Secure Payment</strong>
                    Shop with confidence knowing your transactions are safe and secure.
                </li>
            </ul>
        </div>
        <div class="contact-section" style="margin-top: 4rem; padding: 2rem 0; border-top: 1px solid #eee;">
            <h2>Get In Touch</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-top: 2rem;">
                <div class="contact-info">
                    <h3>Contact Information</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Fragrance Avenue, Scent City, 10001</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@perfumemanagement.com</p>
                    <p><i class="fas fa-clock"></i> Monday - Friday: 9:00 AM - 6:00 PM EST</p>
                    
                    <div class="social-links" style="margin-top: 2rem;">
                        <h3>Follow Us</h3>
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <a href="#" style="color: #4267B2; font-size: 1.5rem;" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                            <a href="#" style="color: #1DA1F2; font-size: 1.5rem;" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" style="color: #E1306C; font-size: 1.5rem;" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" style="color: #0A66C2; font-size: 1.5rem;" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
                
               
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

