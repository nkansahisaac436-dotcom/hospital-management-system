<?php
/**
 * CarePoint Pro HMS - Patient Portal Diagnostic Lab Reports
 */

$pageTitle = 'My Laboratory Reports';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$patientId = $_SESSION['patient_id'] ?? null;
if (!$patientId) {
    $firstPat = $pdo->query("SELECT id FROM patients LIMIT 1")->fetch();
    $patientId = $firstPat['id'] ?? 1;
}

$stmt = $pdo->prepare("SELECT lr.*, u.full_name as doctor_name,
                       (SELECT COUNT(*) FROM lab_request_items lri WHERE lri.lab_request_id = lr.id) as test_count
                       FROM lab_requests lr
                       LEFT JOIN doctors d ON lr.doctor_id = d.id
                       LEFT JOIN users u ON d.user_id = u.id
                       WHERE lr.patient_id = ?
                       ORDER BY lr.requested_date DESC");
$stmt->execute([$patientId]);
$reports = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Patient Portal
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Diagnostic & Pathology Lab Results</h1>
            <p class="text-xs text-slate-500 mt-1">Certified diagnostic investigation reports and analytical findings.</p>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Requisition Ref</th>
                        <th class="py-3.5 px-6">Date Ordered</th>
                        <th class="py-3.5 px-6">Referring Physician</th>
                        <th class="py-3.5 px-6">Total Tests</th>
                        <th class="py-3.5 px-6">Report Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($reports)): ?>
                        <?php foreach ($reports as $r): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6 font-mono font-bold text-purple-700">
                                    <?= e($r['request_number']) ?>
                                </td>
                                <td class="py-3.5 px-6 font-mono text-slate-500">
                                    <?= formatDate($r['requested_date']) ?>
                                </td>
                                <td class="py-3.5 px-6 font-bold text-slate-800">
                                    Dr. <?= e($r['doctor_name'] ?? 'Clinical Direct Request') ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700">
                                        <?= $r['test_count'] ?> Diagnostic Test(s)
                                    </span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="badge <?= $r['status'] === 'Completed' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= e($r['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-right whitespace-nowrap">
                                    <?php if ($r['status'] === 'Completed'): ?>
                                        <a href="<?= APP_URL ?>/modules/laboratory/print_report.php?id=<?= $r['id'] ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-600 hover:text-white text-purple-700 font-bold text-xs transition border border-purple-200">
                                            <i class="fa-solid fa-file-pdf mr-1.5"></i> Download Report (A4)
                                        </a>
                                    <?php else: ?>
                                        <span class="text-[11px] text-slate-400 italic">Processing in Lab</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-flask-vial text-4xl mb-3 block"></i>
                                No diagnostic laboratory investigations found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
