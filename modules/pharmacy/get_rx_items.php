<?php
/**
 * CarePoint Pro HMS - AJAX Handler: Get Prescription Items for POS
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$rxId = (int)($_GET['id'] ?? 0);

if ($rxId <= 0) {
    jsonResponse(['error' => 'Invalid Prescription ID'], 400);
}

$stmt = $pdo->prepare("SELECT pi.*, m.unit_price, m.stock_quantity
                       FROM prescription_items pi
                       LEFT JOIN medicines m ON pi.medicine_id = m.id
                       WHERE pi.prescription_id = ?");
$stmt->execute([$rxId]);
$items = $stmt->fetchAll();

jsonResponse(['items' => $items]);
