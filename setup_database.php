<?php
/**
 * Database Setup Script
 * Run this file once to create the database and tables
 * Access via browser: http://localhost/Perfumes Management System/setup_database.php
 */

// Database connection (supports both Docker and local environments)
// In Docker, use credentials from docker-compose.yml
// In local XAMPP/WAMP, fall back to root with no password
$isDocker = getenv('DOCKER_ENV') === 'true' || file_exists('/.dockerenv');

if ($isDocker) {
    // Docker MySQL (see docker-compose.yml -> service "db")
    $host = "db";
    $user = "perfume_user";
    $pass = "perfume_pass";
    $dbname = "perfume_db";
} else {
    // Local XAMPP/WAMP defaults
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "perfume_db";
}

echo "<h2>Database Setup for Perfumes Management System</h2>";
echo "<pre>";

// Connect to MySQL server (without selecting database)
$conn = mysqli_connect($host, $user, $pass);

if (!$conn) {
    die("❌ Error: Could not connect to MySQL server.\n\n"
        . "Tried: host=$host, user=$user\n"
        . "If running Docker, ensure containers are up: docker-compose up -d\n"
        . "If using XAMPP/WAMP, ensure MySQL service is running.\n");
}

echo "✅ Connected to MySQL server\n";

// Create database if it doesn't exist
$create_db = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (mysqli_query($conn, $create_db)) {
    echo "✅ Database '$dbname' created or already exists\n";
} else {
    die("❌ Error creating database: " . mysqli_error($conn) . "\n");
}

// Select the database
mysqli_select_db($conn, $dbname);

// Create users table
$users_table = "CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `loyalty_points` INT DEFAULT 0,
    `role` VARCHAR(20) DEFAULT 'customer',
    `is_admin` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $users_table)) {
    echo "✅ Table 'users' created or already exists\n";
} else {
    echo "⚠️  Warning creating users table: " . mysqli_error($conn) . "\n";
}

// Create products table
$products_table = "CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `brand` VARCHAR(100),
    `price` DECIMAL(10,2) NOT NULL,
    `stock` INT DEFAULT 0,
    `image` VARCHAR(255),
    `discount_percent` DECIMAL(5,2) DEFAULT 0,
    `cost_price` DECIMAL(10,2),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $products_table)) {
    echo "✅ Table 'products' created or already exists\n";
} else {
    echo "⚠️  Warning creating products table: " . mysqli_error($conn) . "\n";
}

// Create orders table
$orders_table = "CREATE TABLE IF NOT EXISTS `orders` (
    `order_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `grand_total` DECIMAL(10,2),
    `payment_method` VARCHAR(50) DEFAULT 'momo',
    `status` ENUM('pending_payment', 'awaiting_proof', 'pending_cashier_review', 'completed', 'approved', 'paid', 'rejected') DEFAULT 'pending_payment',
    `momo_number` VARCHAR(20),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $orders_table)) {
    echo "✅ Table 'orders' created or already exists\n";
} else {
    echo "⚠️  Warning creating orders table: " . mysqli_error($conn) . "\n";
}

// Create order_items table
$order_items_table = "CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $order_items_table)) {
    echo "✅ Table 'order_items' created or already exists\n";
} else {
    echo "⚠️  Warning creating order_items table: " . mysqli_error($conn) . "\n";
}

// Create payments table
$payments_table = "CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $payments_table)) {
    echo "✅ Table 'payments' created or already exists\n";
} else {
    echo "⚠️  Warning creating payments table: " . mysqli_error($conn) . "\n";
}

// Create contacts table (if needed) – includes subject column for contact.php form
$contacts_table = "CREATE TABLE IF NOT EXISTS `contacts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(190) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $contacts_table)) {
    echo \"✅ Table 'contacts' created or already exists\\n\";
} else {
    echo \"⚠️  Warning creating contacts table: \" . mysqli_error($conn) . \"\\n\";
}

// Ensure older databases (without subject column) are migrated
$col_check = mysqli_query($conn, \"SHOW COLUMNS FROM `contacts` LIKE 'subject'\");
if ($col_check && mysqli_num_rows($col_check) === 0) {
    $alter_contacts = \"ALTER TABLE `contacts` ADD COLUMN `subject` VARCHAR(190) NOT NULL AFTER `email`\";
    if (mysqli_query($conn, $alter_contacts)) {
        echo \"✅ Added 'subject' column to contacts table\\n\";
    } else {
        echo \"⚠️  Warning adding subject column to contacts table: \" . mysqli_error($conn) . \"\\n\";
    }
}

// Create subscribers table (if needed)
$subscribers_table = "CREATE TABLE IF NOT EXISTS `subscribers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $subscribers_table)) {
    echo "✅ Table 'subscribers' created or already exists\n";
} else {
    echo "⚠️  Warning creating subscribers table: " . mysqli_error($conn) . "\n";
}

echo "\n";
echo "🎉 Database setup completed successfully!\n";
echo "\n";
echo "Next steps:\n";
echo "1. Make sure XAMPP MySQL is running\n";
echo "2. Access your application: http://localhost/Perfumes Management System/\n";
echo "3. Register a new user to get started\n";
echo "\n";
echo "⚠️  IMPORTANT: Delete this file (setup_database.php) after setup for security!\n";

mysqli_close($conn);
echo "</pre>";
?>


