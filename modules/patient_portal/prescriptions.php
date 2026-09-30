<?php
/**
 * CarePoint Pro HMS - Patient Portal Digital Prescription Wallet
 */

$pageTitle = 'My Prescriptions';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$patientId = $_SESSION['patient_id'] ?? null;
if (!$patientId) {
    $firstPat = $pdo->query("SELECT id FROM patients LIMIT 1")->fetch();
    $patientId = $firstPat['id'] ?? 1;
}

$stmt = $pdo->prepare("SELECT p.*, u.full_name as doctor_name, d.specialization, dep.name as dept_name,
                       (SELECT COUNT(*) FROM prescription_items pi WHERE pi.prescription_id = p.id) as total_meds
                       FROM prescriptions p
                       JOIN doctors d ON p.doctor_id = d.id
                       JOIN users u ON d.user_id = u.id
                       JOIN departments dep ON d.department_id = dep.id
                       WHERE p.patient_id = ?
                       ORDER BY p.prescription_date DESC");
$stmt->execute([$patientId]);
$prescriptions = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Patient Portal
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">My Digital Prescriptions Wallet</h1>
            <p class="text-xs text-slate-500 mt-1">Verified electronic prescriptions issued by your attending physicians.</p>
        </div>
    </div>

    <!-- Prescriptions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($prescriptions)): ?>
            <?php foreach ($prescriptions as $rx): ?>
                <?php
                    // Fetch items for this prescription
                    $itemStmt = $pdo->prepare("SELECT * FROM prescription_items WHERE prescription_id = ?");
                    $itemStmt->execute([$rx['id']]);
                    $items = $itemStmt->fetchAll();
                ?>
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col justify-between space-y-4 hover:shadow-md transition">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <span class="font-mono font-bold text-teal-700 text-xs">
                                <i class="fa-solid fa-prescription mr-1"></i> <?= e($rx['prescription_number']) ?>
                            </span>
                            <span class="badge <?= $rx['status'] === 'Active' ? 'badge-success' : 'badge-primary' ?> text-[10px]">
                                <?= e($rx['status']) ?>
                            </span>
                        </div>

                        <div class="my-3 space-y-1">
                            <h4 class="font-bold text-slate-900 text-sm">Dr. <?= e($rx['doctor_name']) ?></h4>
                            <p class="text-slate-400 text-xs"><?= e($rx['specialization']) ?> &bull; <?= formatDate($rx['prescription_date']) ?></p>
                            <?php if (!empty($rx['diagnosis'])): ?>
                                <p class="text-slate-600 text-xs font-semibold mt-2">Diagnosis: <span class="text-slate-900"><?= e($rx['diagnosis']) ?></span></p>
                            <?php endif; ?>
                        </div>

                        <!-- Prescribed Medicines List -->
                        <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block tracking-wider">Prescribed Formulations:</span>
                            <?php foreach ($items as $it): ?>
                                <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                    <strong class="text-slate-900 block"><?= e($it['medicine_name']) ?></strong>
                                    <span class="text-[11px] text-teal-700 font-bold"><?= e($it['dosage']) ?> &bull; <?= e($it['frequency']) ?> &bull; <?= e($it['duration']) ?></span>
                                    <?php if (!empty($it['instructions'])): ?>
                                        <p class="text-[10px] text-slate-400 mt-0.5 italic"><?= e($it['instructions']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <a href="<?= APP_URL ?>/modules/prescriptions/print.php?id=<?= $rx['id'] ?>" target="_blank" class="w-full py-2.5 bg-slate-100 hover:bg-teal-600 hover:text-white text-slate-700 font-bold rounded-xl text-xs transition flex items-center justify-center space-x-1.5 shadow-sm">
                            <i class="fa-solid fa-print"></i>
                            <span>Print Official A4 Rx</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-3 text-center py-16 bg-white rounded-3xl border border-slate-200 text-slate-400">
                <i class="fa-solid fa-prescription-bottle-medical text-5xl mb-3 block"></i>
                No medical prescriptions found in your health record.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
