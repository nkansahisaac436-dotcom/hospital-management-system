<?php
/**
 * CarePoint Pro HMS - Laboratory & Diagnostics Queue
 */

$pageTitle = 'Laboratory Diagnostics';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT lr.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth,
        u.full_name as doctor_name,
        (SELECT COUNT(*) FROM lab_request_items lri WHERE lri.lab_request_id = lr.id) as test_count
        FROM lab_requests lr
        JOIN patients p ON lr.patient_id = p.id
        LEFT JOIN doctors d ON lr.doctor_id = d.id
        LEFT JOIN users u ON d.user_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND lr.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (lr.request_number LIKE ? OR p.full_name LIKE ? OR p.mrn LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term]);
}

$sql .= " ORDER BY lr.requested_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Laboratory & Diagnostics Management (LIS)</h1>
            <p class="text-xs text-slate-500 mt-1">Manage test orders, specimen sample collection, pathologist result validation, and digital lab reports.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="catalog.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                <i class="fa-solid fa-list-check mr-1.5"></i> Test Catalog
            </a>
            <a href="create_request.php" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                <i class="fa-solid fa-flask-vial mr-1.5"></i> Order Lab Test
            </a>
        </div>
    </div>

    <!-- Status Tabs & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <div class="flex flex-wrap gap-1.5">
            <a href="index.php" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= empty($statusFilter) ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                All Requests
            </a>
            <a href="index.php?status=Pending" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $statusFilter === 'Pending' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Pending Samples
            </a>
            <a href="index.php?status=In Progress" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $statusFilter === 'In Progress' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                In Analysis
            </a>
            <a href="index.php?status=Completed" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $statusFilter === 'Completed' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Completed & Verified
            </a>
        </div>

        <form method="GET" action="" class="flex items-center space-x-2">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search Order # or Patient..." class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-purple-500 focus:bg-white focus:outline-none">
            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-bold">Search</button>
        </form>
    </div>

    <!-- Lab Requests Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Order Ref #</th>
                        <th class="py-3.5 px-6">Patient (EMR)</th>
                        <th class="py-3.5 px-6">Ordered By Doctor</th>
                        <th class="py-3.5 px-6">Tests Count</th>
                        <th class="py-3.5 px-6">Requested Date</th>
                        <th class="py-3.5 px-6">Priority</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $lr): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6 font-mono font-bold text-purple-700">
                                    <?= e($lr['request_number']) ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <a href="<?= APP_URL ?>/modules/patients/view.php?id=<?= $lr['patient_id'] ?>" class="font-bold text-slate-900 hover:text-purple-600 block">
                                        <?= e($lr['patient_name']) ?>
                                    </a>
                                    <span class="text-[11px] text-slate-400 font-mono"><?= e($lr['mrn']) ?> &bull; <?= e($lr['gender']) ?></span>
                                </td>
                                <td class="py-3.5 px-6 font-bold text-slate-800">
                                    <?= e($lr['doctor_name'] ?? 'Direct Walk-In Order') ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        <?= $lr['test_count'] ?> Diagnostic(s)
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 font-mono">
                                    <?= formatDate($lr['requested_date']) ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="badge <?= $lr['priority'] === 'STAT' ? 'badge-danger' : ($lr['priority'] === 'Urgent' ? 'badge-warning' : 'badge-primary') ?>">
                                        <?= e($lr['priority']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="badge <?= $lr['status'] === 'Completed' ? 'badge-success' : ($lr['status'] === 'In Progress' ? 'badge-info' : 'badge-warning') ?>">
                                        <?= e($lr['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-right space-x-1 whitespace-nowrap">
                                    <?php if ($lr['status'] === 'Completed'): ?>
                                        <a href="print_report.php?id=<?= $lr['id'] ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold text-xs transition">
                                            <i class="fa-solid fa-file-lines mr-1"></i> Print Report
                                        </a>
                                    <?php else: ?>
                                        <a href="enter_result.php?id=<?= $lr['id'] ?>" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-sm transition">
                                            <i class="fa-solid fa-pen mr-1"></i> Enter Results
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-flask text-4xl mb-3 block"></i>
                                No laboratory requests found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
