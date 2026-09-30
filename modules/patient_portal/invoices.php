<?php
/**
 * CarePoint Pro HMS - Patient Portal Invoices & Billing Statements
 */

$pageTitle = 'My Billing Statements';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$patientId = $_SESSION['patient_id'] ?? null;
if (!$patientId) {
    $firstPat = $pdo->query("SELECT id FROM patients LIMIT 1")->fetch();
    $patientId = $firstPat['id'] ?? 1;
}

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE patient_id = ? ORDER BY created_at DESC");
$stmt->execute([$patientId]);
$invoices = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Patient Portal
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">My Billing Statements & Invoices</h1>
            <p class="text-xs text-slate-500 mt-1">Review hospital care charges, settlement receipts, and outstanding dues.</p>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Invoice #</th>
                        <th class="py-3.5 px-6">Department</th>
                        <th class="py-3.5 px-6">Billing Date</th>
                        <th class="py-3.5 px-6">Total Amount</th>
                        <th class="py-3.5 px-6">Paid</th>
                        <th class="py-3.5 px-6">Due Balance</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($invoices)): ?>
                        <?php foreach ($invoices as $inv): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6 font-mono font-bold text-emerald-700">
                                    <?= e($inv['invoice_number']) ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        <?= e($inv['reference_type']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 font-mono text-slate-500">
                                    <?= formatDate($inv['created_at']) ?>
                                </td>
                                <td class="py-3.5 px-6 font-mono font-bold text-slate-900 text-sm">
                                    <?= formatMoney($inv['total_amount']) ?>
                                </td>
                                <td class="py-3.5 px-6 font-mono text-emerald-700 font-bold">
                                    <?= formatMoney($inv['paid_amount']) ?>
                                </td>
                                <td class="py-3.5 px-6 font-mono font-bold <?= (float)$inv['due_amount'] > 0 ? 'text-rose-600' : 'text-slate-400' ?>">
                                    <?= formatMoney($inv['due_amount']) ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="badge <?= $inv['payment_status'] === 'Paid' ? 'badge-success' : ($inv['payment_status'] === 'Partial' ? 'badge-warning' : 'badge-danger') ?>">
                                        <?= e($inv['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-right space-x-1 whitespace-nowrap">
                                    <a href="<?= APP_URL ?>/modules/billing/print_invoice.php?id=<?= $inv['id'] ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                                        <i class="fa-solid fa-print mr-1"></i> Invoice
                                    </a>
                                    <a href="<?= APP_URL ?>/modules/billing/print_receipt.php?id=<?= $inv['id'] ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs transition">
                                        <i class="fa-solid fa-receipt mr-1"></i> Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-file-invoice-dollar text-4xl mb-3 block"></i>
                                No billing records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
