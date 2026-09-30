<?php
/**
 * CarePoint Pro HMS - Inpatient (IPD) Admissions List
 */

$pageTitle = 'IPD Admissions';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$statusFilter = $_GET['status'] ?? 'Admitted';

$sql = "SELECT adm.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, p.phone,
        b.bed_number, w.name as ward_name, u.full_name as doctor_name, d.specialization
        FROM ipd_admissions adm
        JOIN patients p ON adm.patient_id = p.id
        JOIN beds b ON adm.bed_id = b.id
        JOIN wards w ON b.ward_id = w.id
        JOIN doctors d ON adm.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND adm.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY adm.admission_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$admissions = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Inpatient Department (IPD) Admissions</h1>
            <p class="text-xs text-slate-500 mt-1">Track admitted patients, attending doctors, ward beds, and discharge clearance.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="beds.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                <i class="fa-solid fa-bed mr-1.5"></i> Bed Matrix Grid
            </a>
            <a href="admit.php" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                <i class="fa-solid fa-user-plus mr-1.5"></i> Admit New Patient
            </a>
        </div>
    </div>

    <!-- Status Filter Tabs -->
    <div class="flex space-x-2">
        <a href="index.php?status=Admitted" class="px-4 py-2 rounded-xl text-xs font-bold transition <?= $statusFilter === 'Admitted' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
            Currently Admitted
        </a>
        <a href="index.php?status=Discharged" class="px-4 py-2 rounded-xl text-xs font-bold transition <?= $statusFilter === 'Discharged' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
            Discharged Records
        </a>
        <a href="index.php?status=" class="px-4 py-2 rounded-xl text-xs font-bold transition <?= empty($statusFilter) ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
            All Records
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Admission #</th>
                        <th class="py-3.5 px-6">Patient (EMR)</th>
                        <th class="py-3.5 px-6">Ward & Bed</th>
                        <th class="py-3.5 px-6">Attending Physician</th>
                        <th class="py-3.5 px-6">Admitted Date</th>
                        <th class="py-3.5 px-6">Reason / Diagnosis</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($admissions)): ?>
                        <?php foreach ($admissions as $adm): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6 font-mono font-bold text-teal-700">
                                    <?= e($adm['admission_number']) ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <a href="<?= APP_URL ?>/modules/patients/view.php?id=<?= $adm['patient_id'] ?>" class="font-bold text-slate-900 hover:text-teal-600 block">
                                        <?= e($adm['patient_name']) ?>
                                    </a>
                                    <span class="text-[11px] text-slate-400 font-mono"><?= e($adm['mrn']) ?> &bull; <?= e($adm['gender']) ?></span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        <i class="fa-solid fa-bed mr-1"></i> <?= e($adm['bed_number']) ?>
                                    </span>
                                    <span class="text-[11px] text-slate-400 block"><?= e($adm['ward_name']) ?></span>
                                </td>
                                <td class="py-3.5 px-6 font-bold text-slate-800">
                                    <?= e($adm['doctor_name']) ?>
                                </td>
                                <td class="py-3.5 px-6 font-mono">
                                    <?= formatDate($adm['admission_date']) ?>
                                </td>
                                <td class="py-3.5 px-6 max-w-xs truncate">
                                    <?= e($adm['initial_diagnosis'] ?: $adm['admission_reason']) ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="badge <?= $adm['status'] === 'Admitted' ? 'badge-info' : 'badge-success' ?>">
                                        <?= e($adm['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-right space-x-1 whitespace-nowrap">
                                    <?php if ($adm['status'] === 'Admitted'): ?>
                                        <a href="discharge.php?id=<?= $adm['id'] ?>" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs transition">
                                            <i class="fa-solid fa-door-open mr-1"></i> Discharge
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= APP_URL ?>/modules/patients/view.php?id=<?= $adm['patient_id'] ?>&tab=ipd" class="inline-flex items-center p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs transition" title="View Patient EMR">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-bed-pulse text-4xl mb-3 block"></i>
                                No inpatient admission records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
