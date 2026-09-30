<?php
/**
 * CarePoint Pro HMS - Admit Patient to Inpatient Ward
 */

$pageTitle = 'Admit Patient to IPD';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$preselectedBedId = (int)($_GET['bed_id'] ?? 0);
$preselectedPatientId = (int)($_GET['patient_id'] ?? 0);

$patients = $pdo->query("SELECT id, full_name, mrn, phone FROM patients ORDER BY full_name ASC")->fetchAll();
$doctors = $pdo->query("SELECT d.id, u.full_name, dep.name as dept_name FROM doctors d JOIN users u ON d.user_id = u.id JOIN departments dep ON d.department_id = dep.id WHERE d.status = 'active' ORDER BY u.full_name ASC")->fetchAll();
$availableBeds = $pdo->query("SELECT b.id, b.bed_number, b.daily_rate, w.name as ward_name, w.floor FROM beds b JOIN wards w ON b.ward_id = w.id WHERE b.status = 'Available' ORDER BY w.name ASC, b.bed_number ASC")->fetchAll();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $patientId = (int)($_POST['patient_id'] ?? 0);
    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $bedId = (int)($_POST['bed_id'] ?? 0);
    $admissionDate = $_POST['admission_date'] ?? date('Y-m-d H:i:s');
    $admissionReason = trim($_POST['admission_reason'] ?? '');
    $initialDiagnosis = trim($_POST['initial_diagnosis'] ?? '');
    $doctorOrders = trim($_POST['doctor_orders'] ?? '');

    if ($patientId <= 0 || $doctorId <= 0 || $bedId <= 0 || empty($admissionReason)) {
        $error = 'Please fill in all mandatory fields (Patient, Doctor, Bed, and Admission Reason).';
    } else {
        try {
            $pdo->beginTransaction();

            $admNumber = 'ADM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            // 1. Insert IPD Admission
            $stmt = $pdo->prepare("INSERT INTO ipd_admissions (admission_number, patient_id, doctor_id, bed_id, admission_date, admission_reason, initial_diagnosis, doctor_orders, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Admitted')");
            $stmt->execute([$admNumber, $patientId, $doctorId, $bedId, $admissionDate, $admissionReason, $initialDiagnosis, $doctorOrders]);
            $admissionId = $pdo->lastInsertId();

            // 2. Update Bed Status to Occupied
            $bStmt = $pdo->prepare("UPDATE beds SET status = 'Occupied', current_patient_id = ? WHERE id = ?");
            $bStmt->execute([$patientId, $bedId]);

            $pdo->commit();
            logActivity('Patient Admitted to IPD', "Admitted patient ID {$patientId} to bed ID {$bedId} (Ref: {$admNumber})");
            setFlash('success', "Patient admitted to IPD successfully! Admission Ref: <strong>{$admNumber}</strong>");
            header('Location: beds.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to admit patient: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <a href="beds.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Bed Matrix
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Inpatient Admission Requisition</h1>
        <p class="text-xs text-slate-500">Allocate hospital bed and assign primary attending physician.</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-6">
        <?= csrfField() ?>

        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-bed text-teal-600 mr-2"></i> Ward Allocation & Clinical Assignment
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Patient (EMR) *</label>
                    <select name="patient_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($preselectedPatientId === (int)$p['id']) ? 'selected' : '' ?>>
                                <?= e($p['full_name']) ?> (MRN: <?= e($p['mrn']) ?>) - <?= e($p['phone']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Attending Doctor *</label>
                    <select name="doctor_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Doctor --</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['full_name']) ?> (<?= e($d['dept_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Available Ward Bed *</label>
                    <select name="bed_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Bed --</option>
                        <?php foreach ($availableBeds as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ($preselectedBedId === (int)$b['id']) ? 'selected' : '' ?>>
                                <?= e($b['bed_number']) ?> &bull; <?= e($b['ward_name']) ?> (<?= formatMoney($b['daily_rate']) ?>/d)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Admission Reason *</label>
                    <textarea name="admission_reason" rows="2" required placeholder="e.g. Acute severe pancreatitis with systemic inflammation, post-operative observation..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Initial Admitting Diagnosis</label>
                    <input type="text" name="initial_diagnosis" placeholder="e.g. Acute Pancreatitis (Biliary etiology)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Doctor Nursing Orders & Protocol</label>
                    <textarea name="doctor_orders" rows="3" placeholder="e.g. Strict NPO, IV Fluids 100ml/hr, Q2H telemetry vitals, notify doctor if SpO2 < 94%..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="beds.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-bed mr-2"></i> Confirm IPD Admission
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
