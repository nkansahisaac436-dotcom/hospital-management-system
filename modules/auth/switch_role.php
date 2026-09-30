<?php
/**
 * CarePoint Pro HMS - 1-Click Demo Role Switcher
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . APP_URL . '/install.php');
    exit;
}

$roleMap = [
    'admin'       => 'admin',
    'doctor'      => 'doctor',
    'nurse'       => 'nurse',
    'reception'   => 'reception',
    'pharmacist'  => 'pharmacist',
    'labtech'     => 'labtech',
    'accountant'  => 'accountant',
    'patient'     => 'patient',
];

$requested = $_GET['role'] ?? 'admin';
$username = $roleMap[$requested] ?? 'admin';

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_avatar']= $user['avatar'];

    // If doctor, load doctor_id
    if ($user['role'] === 'doctor') {
        $docStmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
        $docStmt->execute([$user['id']]);
        $doc = $docStmt->fetch();
        $_SESSION['doctor_id'] = $doc['id'] ?? null;
    }

    // If patient, load patient_id
    if ($user['role'] === 'patient') {
        $patStmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
        $patStmt->execute([$user['id']]);
        $pat = $patStmt->fetch();
        $_SESSION['patient_id'] = $pat['id'] ?? null;
    }

    setFlash('info', "Switched workspace role to: <strong>" . strtoupper($user['role']) . "</strong> ({$user['full_name']})");
    logActivity('Role Switched', "Switched to {$user['username']}", $user['id']);
}

header('Location: ' . APP_URL . '/modules/dashboard/index.php');
exit;
