<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_login() {
	if (empty($_SESSION['user_id'])) {
		header('Location: /login.php');
		exit();
	}
}

function require_admin() {
	require_login();
	if (empty($_SESSION['is_admin'])) {
		header('Location: /products.php');
		exit();
	}
}

function is_cashier() {
    $role = $_SESSION['role'] ?? '';
    return !empty($role) && strtolower($role) === 'cashier';
}

function require_cashier_or_admin() {
    require_login();
    if (empty($_SESSION['is_admin']) && !is_cashier()) {
        header('Location: /products.php');
        exit();
    }
}

function csrf_token() {
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
	}
	return $_SESSION['csrf_token'];
}

function verify_csrf() {
	$token = $_POST['csrf'] ?? '';
	if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
		die('Invalid CSRF token');
	}
}
