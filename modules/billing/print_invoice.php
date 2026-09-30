<?php
/**
 * CarePoint Pro HMS - Official Printable Tax Invoice (A4 Format)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$invId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT inv.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, p.phone, p.email, p.address,
                       u.full_name as created_by_name
                       FROM invoices inv
                       JOIN patients p ON inv.patient_id = p.id
                       LEFT JOIN users u ON inv.created_by = u.id
                       WHERE inv.id = ?");
$stmt->execute([$invId]);
$inv = $stmt->fetch();

if (!$inv) {
    die('Invoice not found.');
}

$iStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
$iStmt->execute([$invId]);
$items = $iStmt->fetchAll();

$pStmt = $pdo->prepare("SELECT pay.*, u.full_name as received_by_name FROM payments pay LEFT JOIN users u ON pay.received_by = u.id WHERE pay.invoice_id = ? ORDER BY pay.payment_date ASC");
$pStmt->execute([$invId]);
$payments = $pStmt->fetchAll();

$hospitalName = getSetting('hospital_name', DEFAULT_HOSPITAL_NAME);
$hospitalTagline = getSetting('hospital_tagline', DEFAULT_HOSPITAL_TAGLINE);
$hospitalPhone = getSetting('hospital_phone', DEFAULT_HOSPITAL_PHONE);
$hospitalEmail = getSetting('hospital_email', DEFAULT_HOSPITAL_EMAIL);
$hospitalAddress = getSetting('hospital_address', DEFAULT_HOSPITAL_ADDRESS);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= e($inv['invoice_number']) ?> - <?= e($inv['patient_name']) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            #invoice-container { border: none !important; box-shadow: none !important; padding: 0 !important; width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4 flex flex-col items-center">

    <!-- Action Toolbar (No Print) -->
    <div class="max-w-4xl w-full flex items-center justify-between mb-4 no-print">
        <a href="index.php" class="px-4 py-2 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold border border-slate-200 transition shadow-sm inline-flex items-center">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back
        </a>
        <div class="flex items-center space-x-2">
            <a href="print_receipt.php?id=<?= $inv['id'] ?>" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center">
                <i class="fa-solid fa-receipt mr-2"></i> Thermal Receipt
            </a>
            <button onclick="window.print()" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center">
                <i class="fa-solid fa-print mr-2"></i> Print Official Invoice (A4)
            </button>
        </div>
    </div>

    <!-- Official Invoice Sheet Container -->
    <div id="invoice-container" class="max-w-4xl w-full bg-white rounded-3xl shadow-xl border border-slate-200 p-8 sm:p-12 text-slate-800 relative flex flex-col justify-between" style="min-height: 297mm;">

        <div>
            <!-- Hospital Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b-2 border-emerald-700 pb-6 gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-700 text-white flex items-center justify-center text-3xl shadow-inner">
                        <i class="fa-solid fa-hospital"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 uppercase"><?= e($hospitalName) ?></h1>
                        <p class="text-xs text-emerald-800 font-bold uppercase tracking-wider">Patient Accounts & Financial Cashier Department</p>
                        <p class="text-[11px] text-slate-500 mt-0.5"><?= e($hospitalAddress) ?></p>
                    </div>
                </div>
                <div class="text-left sm:text-right text-xs text-slate-600">
                    <h2 class="text-lg font-black text-slate-900 uppercase tracking-tight">TAX INVOICE</h2>
                    <p class="font-mono font-bold text-emerald-700">Inv #: <?= e($inv['invoice_number']) ?></p>
                    <p>Date: <?= formatDate($inv['created_at']) ?></p>
                </div>
            </div>

            <!-- Billed To & Account Info Matrix -->
            <div class="my-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="sm:col-span-2">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Billed To (Patient)</span>
                    <strong class="text-slate-900 text-sm block"><?= e($inv['patient_name']) ?></strong>
                    <span class="text-slate-500"><?= e($inv['address'] ?: 'Metro City') ?> &bull; <?= e($inv['phone']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Patient MRN</span>
                    <span class="font-mono font-bold text-teal-700 text-sm"><?= e($inv['mrn']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Payment Status</span>
                    <span class="inline-block mt-1 font-bold text-xs <?= $inv['payment_status'] === 'Paid' ? 'text-emerald-700' : 'text-rose-700' ?>">
                        <?= strtoupper($inv['payment_status']) ?>
                    </span>
                </div>
            </div>

            <!-- Itemized Table -->
            <div class="mb-8">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-y border-slate-200">
                        <tr>
                            <th class="py-3 px-3">#</th>
                            <th class="py-3 px-3">Service / Procedure / Tariff Description</th>
                            <th class="py-3 px-3">Category</th>
                            <th class="py-3 px-3">Unit Price</th>
                            <th class="py-3 px-3">Qty</th>
                            <th class="py-3 px-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php foreach ($items as $idx => $item): ?>
                            <tr>
                                <td class="py-3.5 px-3 text-slate-400 font-bold"><?= $idx + 1 ?></td>
                                <td class="py-3.5 px-3 font-bold text-slate-900 text-sm"><?= e($item['description']) ?></td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px]"><?= e($item['category']) ?></span>
                                </td>
                                <td class="py-3.5 px-3 font-mono"><?= formatMoney($item['unit_price']) ?></td>
                                <td class="py-3.5 px-3 font-bold"><?= $item['quantity'] ?></td>
                                <td class="py-3.5 px-3 text-right font-mono font-bold text-slate-900"><?= formatMoney($item['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Financial Computation Breakdown -->
            <div class="flex flex-col sm:flex-row justify-between items-start gap-6 pt-4 border-t border-slate-200">
                
                <!-- Payment Settlements History Log -->
                <div class="flex-1 text-xs space-y-2">
                    <strong class="uppercase text-[10px] text-slate-400 block font-bold">Payments Settlement Log:</strong>
                    <?php if (!empty($payments)): ?>
                        <div class="space-y-1.5 font-mono">
                            <?php foreach ($payments as $pay): ?>
                                <div class="p-2 bg-emerald-50/60 rounded-xl border border-emerald-100 flex items-center justify-between">
                                    <span><?= e($pay['receipt_number']) ?> (<?= e($pay['payment_method']) ?>) - <?= formatDate($pay['payment_date']) ?></span>
                                    <strong class="text-emerald-800"><?= formatMoney($pay['amount']) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-slate-400 italic">No payments applied yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Totals Column -->
                <div class="w-full sm:w-72 p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Gross Subtotal:</span>
                        <span class="font-mono font-bold text-slate-900"><?= formatMoney($inv['subtotal']) ?></span>
                    </div>
                    <?php if ((float)$inv['discount'] > 0): ?>
                        <div class="flex justify-between text-emerald-600 font-bold">
                            <span>Discount Applied:</span>
                            <span class="font-mono">- <?= formatMoney($inv['discount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-slate-600">
                        <span>Tax Amount:</span>
                        <span class="font-mono font-bold text-slate-900"><?= formatMoney($inv['tax']) ?></span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 flex justify-between font-black text-slate-900 text-sm">
                        <span>Total Payable:</span>
                        <span class="font-mono text-emerald-700"><?= formatMoney($inv['total_amount']) ?></span>
                    </div>
                    <div class="flex justify-between text-emerald-700 font-bold">
                        <span>Total Paid:</span>
                        <span class="font-mono"><?= formatMoney($inv['paid_amount']) ?></span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 flex justify-between font-black <?= (float)$inv['due_amount'] > 0 ? 'text-rose-600 text-sm' : 'text-slate-400 text-xs' ?>">
                        <span>Balance Due:</span>
                        <span class="font-mono"><?= formatMoney($inv['due_amount']) ?></span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer Signatures -->
        <div class="pt-8 border-t border-slate-200 mt-auto">
            <div class="flex items-end justify-between">
                <div class="max-w-xs text-[10px] text-slate-400">
                    <p class="font-bold text-slate-600 uppercase">Payment Terms & Instructions:</p>
                    <p>All cheques/transfers payable to <?= e($hospitalName) ?>. Generated electronically by CarePoint Pro HMS.</p>
                </div>

                <div class="text-center w-60">
                    <div class="h-14 flex items-center justify-center">
                        <span class="text-emerald-800 font-serif italic text-lg opacity-80 select-none">
                            Finance & Billing Desk
                        </span>
                    </div>
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <p class="text-xs font-bold text-slate-900">Authorized Cashier Officer</p>
                        <p class="text-[10px] text-slate-400">Official Hospital Finance Seal</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
