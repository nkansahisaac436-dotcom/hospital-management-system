<?php
/**
 * CarePoint Pro HMS - Prescriptions Master Archive
 */

$pageTitle = 'Prescriptions Archive';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$search = trim($_GET['search'] ?? '');

$sql = "SELECT p.*, pat.full_name as patient_name, pat.mrn, pat.gender, pat.date_of_birth,
        u.full_name as doctor_name, d.specialization,
        (SELECT COUNT(*) FROM prescription_items pi WHERE pi.prescription_id = p.id) as item_count
        FROM prescriptions p
        JOIN patients pat ON p.patient_id = pat.id
        JOIN doctors d ON p.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.prescription_number LIKE ? OR pat.full_name LIKE ? OR pat.mrn LIKE ?)";
    $term = "%{$search}%";
    $params = [$term, $term, $term];
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$prescriptions = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">E-Prescriptions Archive</h1>
            <p class="text-xs text-slate-500 mt-1">Search, review, and print official digital patient prescriptions.</p>
        </div>
        <div>
            <a href="create.php" class="inline-flex items-center px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                <i class="fa-solid fa-file-prescription mr-2"></i> New Prescription
            </a>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="" class="flex items-center space-x-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by Rx Number, Patient Name, or MRN..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
            </div>
            <button type="submit" class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                Search
            </button>
        </form>
    </div>

    <!-- Prescriptions Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Rx Number</th>
                        <th class="py-3.5 px-6">Patient (EMR)</th>
                        <th class="py-3.5 px-6">Attending Doctor</th>
                        <th class="py-3.5 px-6">Diagnosis</th>
                        <th class="py-3.5 px-6">Drugs Count</th>
                        <th class="py-3.5 px-6">Date Issued</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($prescriptions)): ?>
                        <?php foreach ($prescriptions as $rx): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6 font-mono font-bold text-teal-700">
                                    <?= e($rx['prescription_number']) ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <a href="<?= APP_URL ?>/modules/patients/view.php?id=<?= $rx['patient_id'] ?>" class="font-bold text-slate-900 hover:text-teal-600 block">
                                        <?= e($rx['patient_name']) ?>
                                    </a>
                                    <span class="text-[11px] text-slate-400 font-mono"><?= e($rx['mrn']) ?></span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="font-bold text-slate-800 block"><?= e($rx['doctor_name']) ?></span>
                                    <span class="text-[11px] text-slate-400"><?= e($rx['specialization']) ?></span>
                                </td>
                                <td class="py-3.5 px-6 max-w-xs truncate">
                                    <?= e($rx['diagnosis'] ?: 'Clinical Consult') ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                        <?= $rx['item_count'] ?> item(s)
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 font-mono text-slate-500">
                                    <?= formatDate($rx['created_at']) ?>
                                </td>
                                <td class="py-3.5 px-6 text-right space-x-1 whitespace-nowrap">
                                    <a href="print.php?id=<?= $rx['id'] ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-700 font-bold text-xs transition">
                                        <i class="fa-solid fa-print mr-1"></i> Print Rx
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-file-prescription text-4xl mb-3 block"></i>
                                No digital prescriptions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
