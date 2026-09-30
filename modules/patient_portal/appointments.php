<?php
/**
 * CarePoint Pro HMS - Patient Portal Appointments & Online Booking
 */

$pageTitle = 'My Appointments & Booking';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$patientId = $_SESSION['patient_id'] ?? null;
if (!$patientId) {
    $firstPat = $pdo->query("SELECT id FROM patients LIMIT 1")->fetch();
    $patientId = $firstPat['id'] ?? 1;
}

$doctors = $pdo->query("SELECT d.*, u.full_name, dep.name as dept_name
                        FROM doctors d
                        JOIN users u ON d.user_id = u.id
                        JOIN departments dep ON d.department_id = dep.id
                        WHERE u.status = 'active'
                        ORDER BY u.full_name ASC")->fetchAll();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? '09:00:00';
    $reason = trim($_POST['reason'] ?? 'Self-Scheduled Consultation');

    if ($doctorId <= 0 || empty($date)) {
        $error = 'Please select a specialist doctor and appointment date.';
    } else {
        try {
            $token = generateTokenNumber($doctorId, $date);
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, token_number, reason, status) VALUES (?, ?, ?, ?, ?, ?, 'Scheduled')");
            $stmt->execute([$patientId, $doctorId, $date, $time, $token, $reason]);

            logActivity('Patient Appt Booked', "Patient booked appointment token {$token}", currentUser()['id']);
            setFlash('success', "Appointment booked successfully! Your queue token is: <strong>{$token}</strong>");
            header('Location: appointments.php');
            exit;
        } catch (Exception $e) {
            $error = 'Booking failed: ' . $e->getMessage();
        }
    }
}

// Fetch all patient appointments
$stmt = $pdo->prepare("SELECT a.*, u.full_name as doctor_name, d.specialization, d.consultation_fee, dep.name as dept_name
                       FROM appointments a
                       JOIN doctors d ON a.doctor_id = d.id
                       JOIN users u ON d.user_id = u.id
                       JOIN departments dep ON d.department_id = dep.id
                       WHERE a.patient_id = ?
                       ORDER BY a.appointment_date DESC, a.appointment_time ASC");
$stmt->execute([$patientId]);
$appointments = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Patient Portal
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">My Doctor Appointments</h1>
            <p class="text-xs text-slate-500 mt-1">Schedule new consultations and track live clinical queue token numbers.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Booking Form (1 Col) -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4 h-fit">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                <i class="fa-solid fa-calendar-plus text-teal-600 mr-2"></i> Book Specialist Consultation
            </h3>

            <form method="POST" action="" class="space-y-3">
                <?= csrfField() ?>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Select Physician *</label>
                    <select name="doctor_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Specialist Doctor --</option>
                        <?php foreach ($doctors as $doc): ?>
                            <option value="<?= $doc['id'] ?>">
                                Dr. <?= e($doc['full_name']) ?> (<?= e($doc['specialization']) ?> - <?= e($doc['dept_name']) ?>) - <?= formatMoney($doc['consultation_fee']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Preferred Date *</label>
                    <input type="date" name="appointment_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Preferred Time *</label>
                    <select name="appointment_time" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="09:00:00">09:00 AM (Morning Slot)</option>
                        <option value="10:30:00">10:30 AM (Morning Slot)</option>
                        <option value="12:00:00">12:00 PM (Mid-Day Slot)</option>
                        <option value="14:00:00">02:00 PM (Afternoon Slot)</option>
                        <option value="15:30:00">03:30 PM (Afternoon Slot)</option>
                        <option value="17:00:00">05:00 PM (Evening Slot)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Reason for Visit / Symptoms</label>
                    <textarea name="reason" rows="2" placeholder="Describe symptoms or reasons for consultation..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                </div>

                <button type="submit" class="w-full py-3.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-2xl text-xs shadow-md transition transform hover:-translate-y-0.5">
                    Confirm Consultation Booking
                </button>
            </form>
        </div>

        <!-- Appointment History (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-clock-rotate-left text-sky-600 mr-2"></i> All Scheduled Appointments (<?= count($appointments) ?>)
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-6">Queue Token</th>
                            <th class="py-3 px-6">Physician / Specialization</th>
                            <th class="py-3 px-6">Date & Time</th>
                            <th class="py-3 px-6">Reason for Visit</th>
                            <th class="py-3 px-6">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php if (!empty($appointments)): ?>
                            <?php foreach ($appointments as $ap): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3.5 px-6 font-mono font-bold text-teal-700 text-sm">
                                        <?= e($ap['token_number']) ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <strong class="text-slate-900 text-sm block">Dr. <?= e($ap['doctor_name']) ?></strong>
                                        <span class="text-[11px] text-slate-400"><?= e($ap['specialization']) ?> (<?= e($ap['dept_name']) ?>)</span>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <span class="font-bold text-slate-800 block"><?= formatDate($ap['appointment_date']) ?></span>
                                        <span class="text-[11px] text-slate-400 font-mono"><?= date('h:i A', strtotime($ap['appointment_time'])) ?></span>
                                    </td>
                                    <td class="py-3.5 px-6 text-slate-600">
                                        <?= e($ap['reason'] ?: 'Routine Health Review') ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <span class="badge <?= $ap['status'] === 'Completed' ? 'badge-success' : ($ap['status'] === 'Waiting' ? 'badge-warning' : 'badge-primary') ?>">
                                            <?= e($ap['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-10 text-slate-400">No appointments scheduled.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
