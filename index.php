<?php
/**
 * CarePoint Pro - Hospital Management System
 * Main Application Router & Entry Point
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

// Check if database is configured
$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . APP_URL . '/install.php');
    exit;
}

// If logged in, go directly to Dashboard
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/modules/dashboard/index.php');
    exit;
}

// Otherwise go to Login Screen
header('Location: ' . APP_URL . '/modules/auth/login.php');
exit;
