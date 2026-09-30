<?php
/**
 * CarePoint Pro HMS - Billing, Invoices & Financial Cashier
 */

$pageTitle = 'Invoices & Cashier';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

// Financial Summary KPIs
$totalBilled = $pdo->query("SELECT SUM(total_amount) as total FROM invoices")->fetch()['total'] ?? 0.00;
$totalCollected = $pdo->query("SELECT SUM(paid_amount) as total FROM invoices")->fetch()['total'] ?? 0.00;
$totalOutstanding = $pdo->query("SELECT SUM(due_amount) as total FROM invoices WHERE payment_status != 'Paid'")->fetch()['total'] ?? 0.00;

$sql = "SELECT inv.*, p.full_name as patient_name, p.mrn, p.phone as patient_phone,
        u.full_name as created_by_name
        FROM invoices inv
        JOIN patients p ON inv.patient_id = p.id
        LEFT JOIN users u ON inv.created_by = u.id
        WHERE 1=1";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND inv.payment_status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (inv.invoice_number LIKE ? OR p.full_name LIKE ? OR p.mrn LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term]);
}

$sql .= " ORDER BY inv.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Hospital Billing & Cashier Invoicing</h1>
            <p class="text-xs text-slate-500 mt-1">Consolidated invoices across OPD, IPD, Diagnostics, Pharmacy, and Procedure tariffs.</p>
        </div>
        <div>
            <a href="create.php" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                <i class="fa-solid fa-file-invoice-dollar mr-1.5"></i> Generate Custom Invoice
            </a>
        </div>
    </div>

    <!-- Financial KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Billed Invoices</span>
                <h3 class="text-2xl font-black text-slate-900 mt-1 font-mono"><?= formatMoney($totalBilled) ?></h3>
                <span class="text-[11px] font-semibold text-slate-500">Gross Hospital Invoicing</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Revenue Collected</span>
                <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono"><?= formatMoney($totalCollected) ?></h3>
                <span class="text-[11px] font-semibold text-emerald-600">Settled & Cash In-Hand</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Outstanding Unpaid Dues</span>
                <h3 class="text-2xl font-black text-rose-600 mt-1 font-mono"><?= formatMoney($totalOutstanding) ?></h3>
                <span class="text-[11px] font-semibold text-rose-600">Pending Patient Balances</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>

    </div>

    <!-- Filters & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <div class="flex flex-wrap gap-1.5">
            <a href="index.php" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= empty($statusFilter) ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                All Invoices
            </a>
            <a href="index.php?status=Paid" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $statusFilter === 'Paid' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Paid (Settled)
            </a>
            <a href="index.php?status=Partial" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $statusFilter === 'Partial' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Partial Balance
            </a>
            <a href="index.php?status=Unpaid" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $statusFilter === 'Unpaid' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Unpaid Dues
            </a>
        </div>

        <form method="GET" action="" class="flex items-center space-x-2">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search Invoice # or Patient..." class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none">
            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-bold">Search</button>
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Invoice #</th>
                        <th class="py-3.5 px-6">Patient (EMR)</th>
                        <th class="py-3.5 px-6">Department Type</th>
                        <th class="py-3.5 px-6">Date Created</th>
                        <th class="py-3.5 px-6">Total Amount</th>
                        <th class="py-3.5 px-6">Paid Amount</th>
                        <th class="py-3.5 px-6">Balance Due</th>
                        <th class="py-3.5 px-6">Payment Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($invoices)): ?>
                        <?php foreach ($invoices as $inv): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6 font-mono font-bold text-emerald-700">
                                    <a href="view.php?id=<?= $inv['id'] ?>" class="hover:underline">
                                        <?= e($inv['invoice_number']) ?>
                                    </a>
                                </td>
                                <td class="py-3.5 px-6">
                                    <a href="<?= APP_URL ?>/modules/patients/view.php?id=<?= $inv['patient_id'] ?>" class="font-bold text-slate-900 hover:text-teal-600 block">
                                        <?= e($inv['patient_name']) ?>
                                    </a>
                                    <span class="text-[11px] text-slate-400 font-mono"><?= e($inv['mrn']) ?></span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
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
                                    <a href="view.php?id=<?= $inv['id'] ?>" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                                        <i class="fa-solid fa-credit-card mr-1"></i> Pay / View
                                    </a>
                                    <a href="print_invoice.php?id=<?= $inv['id'] ?>" target="_blank" class="inline-flex items-center p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs transition" title="Print Invoice">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-12 text-slate-400">
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
