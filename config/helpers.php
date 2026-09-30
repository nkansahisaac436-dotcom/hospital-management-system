<?php
/**
 * CarePoint Pro - Hospital Management System
 * General Utility & Security Helper Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

/**
 * Escape output for XSS prevention
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate or retrieve CSRF Token
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF HTML field
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/**
 * Verify CSRF Token
 */
function verifyCsrf(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            die('Invalid CSRF Token. Please refresh the page.');
        }
    }
    return true;
}

/**
 * Set a Flash notification message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Get and clear Flash notification message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user array
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'         => $_SESSION['user_id'],
        'username'   => $_SESSION['username'] ?? '',
        'name'       => $_SESSION['user_name'] ?? 'User',
        'email'      => $_SESSION['user_email'] ?? '',
        'role'       => $_SESSION['user_role'] ?? 'guest',
        'avatar'     => $_SESSION['user_avatar'] ?? null,
        'doctor_id'  => $_SESSION['doctor_id'] ?? null,
        'patient_id' => $_SESSION['patient_id'] ?? null,
    ];
}

/**
 * Get current user role
 */
function currentRole(): string {
    return $_SESSION['user_role'] ?? 'guest';
}

/**
 * Check if current user has any of the specified roles
 */
function hasRole($roles): bool {
    if (!isLoggedIn()) return false;
    $userRole = currentRole();
    if ($userRole === 'admin') return true; // Super admin has access to everything
    
    if (is_array($roles)) {
        return in_array($userRole, $roles, true);
    }
    return $userRole === $roles;
}

/**
 * Require authentication and optional role guard
 */
function requireAuth(array $allowedRoles = []): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please login to access this section.');
        header('Location: ' . APP_URL . '/modules/auth/login.php');
        exit;
    }

    if (!empty($allowedRoles)) {
        $userRole = currentRole();
        if ($userRole !== 'admin' && !in_array($userRole, $allowedRoles, true)) {
            setFlash('error', 'Access denied. You do not have permission to access that area.');
            header('Location: ' . APP_URL . '/modules/dashboard/index.php');
            exit;
        }
    }
}

/**
 * Get dynamic system setting from database with fallback
 */
function getSetting(string $key, $default = null) {
    static $settingsCache = null;
    
    $pdo = Database::getConnection();
    if (!$pdo) return $default;

    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $stmt = $pdo->query("SELECT `key`, `value` FROM system_settings");
            while ($row = $stmt->fetch()) {
                $settingsCache[$row['key']] = $row['value'];
            }
        } catch (Exception $e) {
            // Table might not exist yet during install
            return $default;
        }
    }

    return $settingsCache[$key] ?? $default;
}

/**
 * Format currency amount
 */
function formatMoney($amount): string {
    $currency = getSetting('currency_symbol', DEFAULT_CURRENCY);
    $formatted = number_format((float)($amount ?? 0), 2);
    return $currency . ' ' . $formatted;
}

/**
 * Format date
 */
function formatDate(?string $dateString, string $format = 'M d, Y'): string {
    if (!$dateString || $dateString === '0000-00-00') return 'N/A';
    try {
        $dt = new DateTime($dateString);
        return $dt->format($format);
    } catch (Exception $e) {
        return $dateString;
    }
}

/**
 * Format Date & Time
 */
function formatDateTime(?string $dateTimeString, string $format = 'M d, Y - h:i A'): string {
    if (!$dateTimeString || $dateTimeString === '0000-00-00 00:00:00') return 'N/A';
    try {
        $dt = new DateTime($dateTimeString);
        return $dt->format($format);
    } catch (Exception $e) {
        return $dateTimeString;
    }
}

/**
 * Calculate age from date of birth
 */
function calculateAge(?string $dob): string {
    if (!$dob) return 'N/A';
    try {
        $bday = new DateTime($dob);
        $today = new DateTime('today');
        $diff = $today->diff($bday);
        return $diff->y . ' yrs (' . $diff->m . ' mos)';
    } catch (Exception $e) {
        return 'N/A';
    }
}

/**
 * Generate unique Patient MRN (Medical Record Number)
 */
function generateMRN(): string {
    $pdo = Database::getConnection();
    $prefix = 'MRN-' . date('Ym') . '-';
    $random = strtoupper(bin2hex(random_bytes(2)));
    $count = 1;
    
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
            $count = ((int)$stmt->fetch()['total']) + 1;
        } catch (Exception $e) {}
    }
    
    return $prefix . str_pad((string)$count, 4, '0', STR_PAD_LEFT) . '-' . $random;
}

/**
 * Generate unique Invoice Number
 */
function generateInvoiceNumber(): string {
    return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

/**
 * Generate unique Prescription Number
 */
function generatePrescriptionNumber(): string {
    return 'RX-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

/**
 * Generate unique Lab Request Reference
 */
function generateLabRequestNumber(): string {
    return 'LAB-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

/**
 * Generate next OPD Daily Token Number
 */
function generateTokenNumber(?int $doctorId = null): int {
    $pdo = Database::getConnection();
    $today = date('Y-m-d');
    $token = 1;

    if ($pdo) {
        try {
            $sql = "SELECT MAX(token_number) as max_token FROM appointments WHERE appointment_date = ?";
            $params = [$today];
            if ($doctorId) {
                $sql .= " AND doctor_id = ?";
                $params[] = $doctorId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $res = $stmt->fetch();
            if (!empty($res['max_token'])) {
                $token = ((int)$res['max_token']) + 1;
            }
        } catch (Exception $e) {}
    }
    return $token;
}

/**
 * Log System Activity
 */
function logActivity(string $action, string $details = '', ?int $userId = null): void {
    $pdo = Database::getConnection();
    if (!$pdo) return;

    $userId = $userId ?? ($_SESSION['user_id'] ?? null);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        // Suppress logging error to avoid breaking main workflow
    }
}

/**
 * Send JSON Response
 */
function jsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
