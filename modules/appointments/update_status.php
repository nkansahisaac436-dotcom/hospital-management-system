<?php
/**
 * CarePoint Pro HMS - Update Appointment Status
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$aptId = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? 'Waiting';

$validStatuses = ['Scheduled', 'Waiting', 'In-Consultation', 'Completed', 'Cancelled'];

if ($aptId > 0 && in_array($status, $validStatuses, true)) {
    try {
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $aptId]);
        setFlash('success', "Appointment status updated to <strong>{$status}</strong>.");
    } catch (Exception $e) {
        setFlash('error', 'Failed to update status.');
    }
}

$referer = $_SERVER['HTTP_REFERER'] ?? (APP_URL . '/modules/appointments/index.php');
header('Location: ' . $referer);
exit;
