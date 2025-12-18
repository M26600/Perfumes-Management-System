<?php
/**
 * Database Connection
 * Supports both local development and Docker environments
 */

// Check if running in Docker - more reliable detection
// Only use Docker config if explicitly set or if running inside container
$isDocker = false;

// Method 1: Check for explicit environment variable
if (getenv('DOCKER_ENV') === 'true') {
    $isDocker = true;
}

// Method 2: Check for .dockerenv file (Linux containers only)
if (!$isDocker && file_exists('/.dockerenv')) {
    $isDocker = true;
}

// Method 3: Check hostname (Docker containers often have specific hostnames)
if (!$isDocker && isset($_SERVER['HOSTNAME']) && strpos($_SERVER['HOSTNAME'], 'docker') !== false) {
    $isDocker = true;
}

// Default to local development (XAMPP/WAMP)
if ($isDocker) {
    // Docker database configuration
    $host = "db";  // Service name from docker-compose.yml
    $user = "perfume_user";
    $pass = "perfume_pass";
    $dbname = "perfume_db";
    $port = 3306;
} else {
    // Local development configuration (XAMPP/WAMP)
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "perfume_db";
    $port = 3306; // Default MySQL port
}

// First, try to connect to MySQL server (without selecting database)
$conn = @mysqli_connect($host, $user, $pass, null, $port);

if (!$conn) {
    // MySQL server is not running or not accessible
    $error_msg = "<!DOCTYPE html><html><head><title>Database Connection Error</title>";
    $error_msg .= "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
    $error_msg .= ".error-box{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
    $error_msg .= "h1{color:#d32f2f;margin-top:0;} .step{background:#e3f2fd;padding:15px;margin:10px 0;border-left:4px solid #2196f3;border-radius:4px;}";
    $error_msg .= "code{background:#f5f5f5;padding:2px 6px;border-radius:3px;font-family:monospace;} .btn{display:inline-block;background:#2196f3;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;margin-top:10px;}</style></head><body>";
    $error_msg .= "<div class='error-box'>";
    $error_msg .= "<h1>❌ Database Connection Failed</h1>";
    $error_msg .= "<p><strong>Error:</strong> " . mysqli_connect_error() . "</p>";
    $error_msg .= "<p><strong>Trying to connect to:</strong></p>";
    $error_msg .= "<ul><li>Host: <code>$host</code></li>";
    $error_msg .= "<li>Port: <code>$port</code></li>";
    $error_msg .= "<li>User: <code>$user</code></li>";
    $error_msg .= "<li>Database: <code>$dbname</code></li></ul>";
    
    if (!$isDocker) {
        $error_msg .= "<h2>🔧 How to Fix (XAMPP/WAMP):</h2>";
        $error_msg .= "<div class='step'><strong>Step 1:</strong> Open XAMPP Control Panel</div>";
        $error_msg .= "<div class='step'><strong>Step 2:</strong> Click <strong>Start</strong> button next to MySQL</div>";
        $error_msg .= "<div class='step'><strong>Step 3:</strong> Wait until MySQL shows <strong style='color:green;'>Running</strong> (green)</div>";
        $error_msg .= "<div class='step'><strong>Step 4:</strong> <a href='setup_database.php' class='btn'>Run Database Setup</a> to create the database</div>";
        $error_msg .= "<div class='step'><strong>Step 5:</strong> Refresh this page</div>";
        $error_msg .= "<hr>";
        $error_msg .= "<p><strong>Alternative:</strong> If MySQL won't start, check if port 3306 is already in use by another application.</p>";
    } else {
        $error_msg .= "<h2>🔧 How to Fix (Docker):</h2>";
        $error_msg .= "<div class='step'><strong>Step 1:</strong> Run <code>docker-compose up -d</code></div>";
        $error_msg .= "<div class='step'><strong>Step 2:</strong> Check containers: <code>docker ps</code></div>";
        $error_msg .= "<div class='step'><strong>Step 3:</strong> Check logs: <code>docker-compose logs db</code></div>";
    }
    
    $error_msg .= "</div></body></html>";
    die($error_msg);
}

// Now select the database
if (!mysqli_select_db($conn, $dbname)) {
    // Database doesn't exist - provide helpful message
    $error_msg = "<!DOCTYPE html><html><head><title>Database Not Found</title>";
    $error_msg .= "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
    $error_msg .= ".error-box{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
    $error_msg .= "h1{color:#ff9800;margin-top:0;} .btn{display:inline-block;background:#4caf50;color:white;padding:12px 24px;text-decoration:none;border-radius:4px;margin-top:15px;font-size:16px;}</style></head><body>";
    $error_msg .= "<div class='error-box'>";
    $error_msg .= "<h1>⚠️ Database Not Found</h1>";
    $error_msg .= "<p>The database <strong><code>$dbname</code></strong> does not exist yet.</p>";
    $error_msg .= "<p><strong>Solution:</strong> Click the button below to automatically create the database and all required tables.</p>";
    $error_msg .= "<a href='setup_database.php' class='btn'>🚀 Setup Database Now</a>";
    $error_msg .= "<hr style='margin:20px 0;'>";
    $error_msg .= "<p><small>Or manually create the database in phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>Open phpMyAdmin</a></small></p>";
    $error_msg .= "</div></body></html>";
    mysqli_close($conn);
    die($error_msg);
}
?>
