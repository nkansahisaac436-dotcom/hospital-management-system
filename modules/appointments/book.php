<?php
/**
 * CarePoint Pro HMS - Book New Appointment
 */

$pageTitle = 'Book Appointment';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$error = null;

$preselectedPatientId = (int)($_GET['patient_id'] ?? 0);
$preselectedDoctorId = (int)($_GET['doctor_id'] ?? 0);

// Fetch active patients & doctors
$patients = $pdo->query("SELECT id, full_name, mrn, phone FROM patients ORDER BY full_name ASC")->fetchAll();
$doctors = $pdo->query("SELECT d.id, u.full_name, dep.name as dept_name, d.department_id, d.consultation_fee, d.room_no 
                         FROM doctors d 
                         JOIN users u ON d.user_id = u.id 
                         JOIN departments dep ON d.department_id = dep.id 
                         WHERE d.status = 'active' ORDER BY u.full_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $patientId = (int)($_POST['patient_id'] ?? 0);
    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $appointmentDate = $_POST['appointment_date'] ?? date('Y-m-d');
    $appointmentTime = $_POST['appointment_time'] ?? date('H:i');
    $reason = trim($_POST['reason'] ?? '');

    if ($patientId <= 0 || $doctorId <= 0 || empty($appointmentDate)) {
        $error = 'Please select a valid patient, doctor, and appointment date.';
    } else {
        try {
            // Get doctor's department
            $docStmt = $pdo->prepare("SELECT department_id FROM doctors WHERE id = ?");
            $docStmt->execute([$doctorId]);
            $deptId = $docStmt->fetch()['department_id'] ?? null;

            // Generate daily token
            $token = generateTokenNumber($doctorId);

            $stmt = $pdo->prepare("INSERT INTO appointments (token_number, patient_id, doctor_id, department_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Waiting')");
            $stmt->execute([$token, $patientId, $doctorId, $deptId, $appointmentDate, $appointmentTime, $reason]);

            logActivity('Appointment Booked', "Booked token #{$token} for patient ID {$patientId} with doctor ID {$doctorId}");
            setFlash('success', "Appointment scheduled successfully! Assigned <strong>Token #{$token}</strong> for {$appointmentDate}.");
            header('Location: index.php?date=' . $appointmentDate);
            exit;
        } catch (Exception $e) {
            $error = 'Failed to book appointment: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Appointments Queue
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Schedule New Appointment</h1>
        <p class="text-xs text-slate-500">Assign a patient to an OPD doctor slot and issue a daily queue token.</p>
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
            <div class="border-b border-slate-100 pb-4 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-calendar-check text-teal-600 mr-2"></i> Appointment Details
                </h3>
                <a href="<?= APP_URL ?>/modules/patients/add.php" class="text-xs text-teal-600 font-bold hover:underline">
                    + Register New Patient
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Patient Selector -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Patient (EMR) *</label>
                    <select name="patient_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($preselectedPatientId === (int)$p['id']) ? 'selected' : '' ?>>
                                <?= e($p['full_name']) ?> (MRN: <?= e($p['mrn']) ?>) - Phone: <?= e($p['phone']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Doctor Selector -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Doctor / Medical Specialist *</label>
                    <select name="doctor_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Doctor --</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($preselectedDoctorId === (int)$d['id']) ? 'selected' : '' ?>>
                                <?= e($d['full_name']) ?> &bull; <?= e($d['dept_name']) ?> (Room: <?= e($d['room_no'] ?: 'OPD') ?> &bull; Fee: <?= formatMoney($d['consultation_fee']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date & Time -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Appointment Date *</label>
                    <input type="date" name="appointment_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Appointment Time *</label>
                    <input type="time" name="appointment_time" value="<?= date('H:i') ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <!-- Reason / Symptoms -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Chief Complaint / Reason for Visit</label>
                    <textarea name="reason" rows="3" placeholder="e.g. Chest pain with shortness of breath, follow-up on medication, routine health check..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                </div>

            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="index.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-calendar-plus mr-2"></i> Confirm & Generate Token
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
