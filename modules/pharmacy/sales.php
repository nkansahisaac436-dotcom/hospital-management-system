<?php
/**
 * CarePoint Pro HMS - Pharmacy Sales History & Thermal Receipt Print
 */

$pageTitle = 'Pharmacy Sales History';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$search = trim($_GET['search'] ?? '');
$sql = "SELECT s.*, u.full_name as sold_by_name,
        (SELECT COUNT(*) FROM pharmacy_sale_items psi WHERE psi.sale_id = s.id) as item_count
        FROM pharmacy_sales s
        LEFT JOIN users u ON s.sold_by = u.id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (s.sale_number LIKE ? OR s.customer_name LIKE ? OR s.customer_phone LIKE ?)";
    $term = "%{$search}%";
    $params = [$term, $term, $term];
}

$sql .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Pharmacy
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pharmacy Sales & Dispensing Log</h1>
            <p class="text-xs text-slate-500 mt-1">Audit trail of all dispensed medications, payments, and customer receipts.</p>
        </div>
        <div>
            <a href="pos.php" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                <i class="fa-solid fa-cash-register mr-1.5"></i> Launch POS
            </a>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Sale Receipt #</th>
                        <th class="py-3.5 px-6">Customer / Patient</th>
                        <th class="py-3.5 px-6">Dispensed Items</th>
                        <th class="py-3.5 px-6">Payment Method</th>
                        <th class="py-3.5 px-6">Date & Time</th>
                        <th class="py-3.5 px-6">Dispensed By</th>
                        <th class="py-3.5 px-6 text-right">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($sales)): ?>
                        <?php foreach ($sales as $s): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6 font-mono font-bold text-teal-700">
                                    <?= e($s['sale_number']) ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <strong class="text-slate-900 text-sm block"><?= e($s['customer_name']) ?></strong>
                                    <span class="text-[11px] text-slate-400"><?= e($s['customer_phone'] ?: 'Counter walk-in') ?></span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                        <?= $s['item_count'] ?> item(s)
                                    </span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="badge badge-success"><?= e($s['payment_method']) ?></span>
                                </td>
                                <td class="py-3.5 px-6 font-mono text-slate-500">
                                    <?= formatDateTime($s['created_at']) ?>
                                </td>
                                <td class="py-3.5 px-6 font-bold text-slate-800">
                                    <?= e($s['sold_by_name'] ?? 'Staff') ?>
                                </td>
                                <td class="py-3.5 px-6 text-right font-black text-slate-900 text-sm font-mono">
                                    <?= formatMoney($s['total_amount']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-receipt text-4xl mb-3 block"></i>
                                No pharmacy sales recorded yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
