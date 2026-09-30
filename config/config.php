<?php
/**
 * CarePoint Pro - Hospital Management System
 * System Configuration & Security Bootstrap File
 */

// Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Security Headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Error reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application Timezone
date_default_timezone_set('UTC');

// Base URL Definition
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Determine App Root & Web Path Dynamically & Accurately
$rootPath = str_replace('\\', '/', dirname(__DIR__));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');

$subFolder = '';
if (!empty($docRoot) && strpos($rootPath, $docRoot) === 0) {
    $subFolder = substr($rootPath, strlen($docRoot));
} else {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $parts = explode('/modules/', $script);
    if (count($parts) > 1) {
        $subFolder = $parts[0];
    } else {
        $subFolder = dirname($script);
    }
}
$subFolder = '/' . trim($subFolder, '/');
if ($subFolder === '/') $subFolder = '';

$baseUrl = $protocol . $host . $subFolder;

defined('APP_NAME')        or define('APP_NAME', 'CarePoint Pro HMS');
defined('APP_VERSION')     or define('APP_VERSION', '2.5.0');
defined('APP_URL')         or define('APP_URL', $baseUrl);
defined('ROOT_PATH')       or define('ROOT_PATH', dirname(__DIR__));

// Database Configuration Defaults
defined('DB_HOST') or define('DB_HOST', '127.0.0.1');
defined('DB_PORT') or define('DB_PORT', '3307');
defined('DB_NAME') or define('DB_NAME', 'carepoint_hms');
defined('DB_USER') or define('DB_USER', 'root');
defined('DB_PASS') or define('DB_PASS', '');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

// Default Hospital Branding Settings
defined('DEFAULT_CURRENCY') or define('DEFAULT_CURRENCY', '$');
defined('DEFAULT_HOSPITAL_NAME') or define('DEFAULT_HOSPITAL_NAME', 'CarePoint Medical Center & Specialist Hospital');
defined('DEFAULT_HOSPITAL_TAGLINE') or define('DEFAULT_HOSPITAL_TAGLINE', 'Compassionate Care, Advanced Medical Excellence');
defined('DEFAULT_HOSPITAL_PHONE') or define('DEFAULT_HOSPITAL_PHONE', '+1 (555) 234-5678 / +1 (555) 876-5432');
defined('DEFAULT_HOSPITAL_EMAIL') or define('DEFAULT_HOSPITAL_EMAIL', 'info@carepointmedical.com');
defined('DEFAULT_HOSPITAL_ADDRESS') or define('DEFAULT_HOSPITAL_ADDRESS', '450 Health Avenue, Suite 100, Metro City, MC 90210');
