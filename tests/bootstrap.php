<?php
/**
 * PHPUnit Bootstrap File
 * This file is executed before running tests
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Include database connection
require_once BASE_PATH . '/includes/db_connect.php';

// Include repository classes
require_once BASE_PATH . '/includes/Repository/ProductRepository.php';
require_once BASE_PATH . '/includes/Repository/OrderRepository.php';
require_once BASE_PATH . '/includes/Repository/OrderItemRepository.php';

// Set test database if needed
if (getenv('TEST_DB')) {
    // Use test database
    $GLOBALS['test_db'] = getenv('TEST_DB');
}



