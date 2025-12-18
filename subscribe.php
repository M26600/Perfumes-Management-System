<?php
require_once __DIR__ . '/includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: index.html');
	exit();
}

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	header('Location: index.html');
	exit();
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS subscribers (
	id INT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(190) UNIQUE,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$stmt = $conn->prepare('INSERT IGNORE INTO subscribers (email) VALUES (?)');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->close();

header('Location: index.html');
exit();



