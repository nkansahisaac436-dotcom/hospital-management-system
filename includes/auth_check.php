<?php
/**
 * CarePoint Pro HMS - Role & Authentication Guard Middleware
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// Check DB connectivity
if (!Database::isConnected()) {
    header('Location: ' . APP_URL . '/install.php');
    exit;
}

// Require login
if (!isLoggedIn()) {
    setFlash('warning', 'Please sign in to access the Hospital Management System.');
    header('Location: ' . APP_URL . '/modules/auth/login.php');
    exit;
}
