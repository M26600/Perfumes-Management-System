<?php
/**
 * Docker Database Configuration
 * Use this file when running in Docker environment
 * Update db_connect.php to use Docker settings or create environment-based config
 */

// Check if running in Docker
$isDocker = getenv('DOCKER_ENV') === 'true' || file_exists('/.dockerenv');

if ($isDocker) {
    // Docker database configuration
    $host = "db";  // Service name from docker-compose.yml
    $user = "perfume_user";
    $pass = "perfume_pass";
    $dbname = "perfume_db";
} else {
    // Local development configuration
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "perfume_db";
}

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>



