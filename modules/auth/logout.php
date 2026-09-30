<?php
/**
 * CarePoint Pro HMS - Logout Action
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';

if (isLoggedIn()) {
    logActivity('User Logout', 'User logged out', $_SESSION['user_id'] ?? null);
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

session_start();
setFlash('info', 'You have been signed out safely.');
header('Location: ' . APP_URL . '/modules/auth/login.php');
exit;
