<?php
/**
 * CarePoint Pro HMS - 80mm POS Thermal Receipt Format
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$invId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT inv.*, p.full_name as patient_name, p.mrn, p.phone FROM invoices inv JOIN patients p ON inv.patient_id = p.id WHERE inv.id = ?");
$stmt->execute([$invId]);
$inv = $stmt->fetch();

if (!$inv) {
    die('Receipt not found.');
}

$items = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$items->execute([$invId]);
$lineItems = $items->fetchAll();

$hospitalName = getSetting('hospital_name', DEFAULT_HOSPITAL_NAME);
$hospitalPhone = getSetting('hospital_phone', DEFAULT_HOSPITAL_PHONE);
$hospitalAddress = getSetting('hospital_address', DEFAULT_HOSPITAL_ADDRESS);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt <?= e($inv['invoice_number']) ?></title>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 11px; margin: 0; padding: 10px; background: #eee; }
        .receipt { max-width: 300px; margin: 0 auto; background: white; padding: 15px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .center { text-align: center; }
        .right { text-align: right; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .table { width: 100%; border-collapse: collapse; }
        .table td { padding: 3px 0; }
        @media print {
            body { background: white; padding: 0; }
            .receipt { box-shadow: none; max-width: 100%; width: 80mm; padding: 5px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="text-align: center; margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 6px 16px; font-weight: bold; cursor: pointer;">Print Receipt</button>
        <button onclick="window.close()" style="padding: 6px 16px; margin-left: 5px; cursor: pointer;">Close</button>
    </div>

    <div class="receipt">
        <div class="center">
            <strong style="font-size: 13px;"><?= strtoupper(e($hospitalName)) ?></strong><br>
            <?= e($hospitalAddress) ?><br>
            Tel: <?= e($hospitalPhone) ?>
        </div>

        <div class="line"></div>

        <div>
            Receipt #: <?= e($inv['invoice_number']) ?><br>
            Date: <?= date('Y-m-d H:i') ?><br>
            Patient: <?= e($inv['patient_name']) ?><br>
            MRN: <?= e($inv['mrn']) ?>
        </div>

        <div class="line"></div>

        <table class="table">
            <thead>
                <tr>
                    <td><strong>Item</strong></td>
                    <td class="center"><strong>Qty</strong></td>
                    <td class="right"><strong>Amt</strong></td>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lineItems as $item): ?>
                    <tr>
                        <td><?= e($item['description']) ?></td>
                        <td class="center"><?= $item['quantity'] ?></td>
                        <td class="right"><?= formatMoney($item['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="line"></div>

        <table class="table">
            <tr>
                <td>Subtotal:</td>
                <td class="right"><?= formatMoney($inv['subtotal']) ?></td>
            </tr>
            <?php if ((float)$inv['discount'] > 0): ?>
                <tr>
                    <td>Discount:</td>
                    <td class="right">-<?= formatMoney($inv['discount']) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td>Tax:</td>
                <td class="right"><?= formatMoney($inv['tax']) ?></td>
            </tr>
            <tr>
                <td><strong>Total:</strong></td>
                <td class="right"><strong><?= formatMoney($inv['total_amount']) ?></strong></td>
            </tr>
            <tr>
                <td>Paid Amount:</td>
                <td class="right"><strong><?= formatMoney($inv['paid_amount']) ?></strong></td>
            </tr>
            <tr>
                <td>Balance Due:</td>
                <td class="right"><strong><?= formatMoney($inv['due_amount']) ?></strong></td>
            </tr>
        </table>

        <div class="line"></div>

        <div class="center">
            Status: <strong><?= strtoupper($inv['payment_status']) ?></strong><br><br>
            Thank you for choosing<br><?= e($hospitalName) ?>!<br>
            <em>Get Well Soon!</em>
        </div>
    </div>

</body>
</html>
