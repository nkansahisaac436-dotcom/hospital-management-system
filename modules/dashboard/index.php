<?php
/**
 * CarePoint Pro HMS - Role-Dedicated Adaptive Hospital Dashboard
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$role = currentRole();
$userId = (int)currentUser()['id'];

// If patient, redirect to dedicated patient portal
if ($role === 'patient') {
    header('Location: ' . APP_URL . '/modules/patient_portal/index.php');
    exit;
}
?>

<div class="space-y-6">

    <!-- ========================================================================= -->
    <!-- 1. FRONT DESK / RECEPTIONIST DASHBOARD                                    -->
    <!-- ========================================================================= -->
    <?php if ($role === 'reception'): ?>
        <?php
            $todayPatients = $pdo->query("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?? 0;
            $todayAppts = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn() ?? 0;
            $waitingQueue = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status = 'Waiting'")->fetchColumn() ?? 0;
            $inConsultation = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status = 'In-Consultation'")->fetchColumn() ?? 0;

            // Today's Live Appointments Queue
            $queueStmt = $pdo->query("SELECT a.*, p.full_name as patient_name, p.mrn, p.phone, p.gender, p.date_of_birth,
                                      u.full_name as doctor_name, dep.name as dept_name
                                      FROM appointments a
                                      JOIN patients p ON a.patient_id = p.id
                                      JOIN doctors d ON a.doctor_id = d.id
                                      JOIN users u ON d.user_id = u.id
                                      JOIN departments dep ON d.department_id = dep.id
                                      WHERE a.appointment_date = CURDATE()
                                      ORDER BY a.appointment_time ASC");
            $todayQueue = $queueStmt->fetchAll();

            // Recently Registered Patients
            $recentPatients = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT 5")->fetchAll();
        ?>

        <!-- Reception Header Banner -->
        <div class="bg-gradient-to-r from-teal-800 via-teal-700 to-sky-800 rounded-3xl p-6 text-white shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/20 text-white backdrop-blur-md">
                    Front Desk Reception & Triage Desk
                </span>
                <h1 class="text-2xl font-black tracking-tight mt-1.5">Welcome, <?= e(currentUser()['full_name']) ?></h1>
                <p class="text-xs text-teal-100 mt-0.5">Manage patient admissions, queue tokens, check-ins, and outpatient scheduling.</p>
            </div>
            <div class="flex items-center space-x-2 shrink-0">
                <a href="<?= APP_URL ?>/modules/patients/add.php" class="px-4 py-2.5 bg-white hover:bg-teal-50 text-teal-900 rounded-2xl text-xs font-bold shadow-md transition flex items-center">
                    <i class="fa-solid fa-user-plus mr-1.5 text-teal-600"></i> Register New Patient
                </a>
                <a href="<?= APP_URL ?>/modules/appointments/book.php" class="px-4 py-2.5 bg-teal-600/80 hover:bg-teal-600 text-white border border-white/20 rounded-2xl text-xs font-bold transition flex items-center">
                    <i class="fa-solid fa-calendar-plus mr-1.5"></i> Book Appointment
                </a>
            </div>
        </div>

        <!-- Reception KPI Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Patients Registered Today</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1"><?= $todayPatients ?></h3>
                    <span class="text-[11px] font-semibold text-teal-600">New Intakes</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Today's Total Appointments</span>
                    <h3 class="text-2xl font-black text-sky-600 mt-1"><?= $todayAppts ?></h3>
                    <span class="text-[11px] font-semibold text-slate-500">Scheduled for Today</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Waiting in Lobby Queue</span>
                    <h3 class="text-2xl font-black text-amber-600 mt-1"><?= $waitingQueue ?></h3>
                    <span class="text-[11px] font-semibold text-amber-600">Awaiting Consultation</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">In-Consultation Rooms</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1"><?= $inConsultation ?></h3>
                    <span class="text-[11px] font-semibold text-emerald-600">With Attending Doctors</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-stethoscope"></i>
                </div>
            </div>

        </div>

        <!-- Main Live OPD Queue & Fast Check-In Table -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-clock-rotate-left text-teal-600 mr-2"></i> Today's Live OPD Patient Queue & Triage Status
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Live token tracking and quick queue status advancement.</p>
                </div>
                <a href="<?= APP_URL ?>/modules/appointments/index.php" class="text-xs text-teal-600 font-bold hover:underline">
                    Manage Full Queue &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-6">Queue Token</th>
                            <th class="py-3 px-6">Patient Name</th>
                            <th class="py-3 px-6">Assigned Physician</th>
                            <th class="py-3 px-6">Scheduled Time</th>
                            <th class="py-3 px-6">Queue Status</th>
                            <th class="py-3 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php if (!empty($todayQueue)): ?>
                            <?php foreach ($todayQueue as $item): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3.5 px-6 font-mono font-bold text-teal-700 text-sm">
                                        <?= e($item['token_number']) ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <a href="<?= APP_URL ?>/modules/patients/view.php?id=<?= $item['patient_id'] ?>" class="font-bold text-slate-900 hover:text-teal-600 block">
                                            <?= e($item['patient_name']) ?>
                                        </a>
                                        <span class="text-[11px] text-slate-400 font-mono"><?= e($item['mrn']) ?> &bull; <?= e($item['phone']) ?></span>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <strong class="text-slate-800 block">Dr. <?= e($item['doctor_name']) ?></strong>
                                        <span class="text-[11px] text-slate-400"><?= e($item['dept_name']) ?></span>
                                    </td>
                                    <td class="py-3.5 px-6 font-mono font-bold text-slate-800">
                                        <?= date('h:i A', strtotime($item['appointment_time'])) ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <span class="badge <?= $item['status'] === 'Completed' ? 'badge-success' : ($item['status'] === 'In-Consultation' ? 'badge-primary' : ($item['status'] === 'Waiting' ? 'badge-warning' : 'badge-danger')) ?>">
                                            <?= e($item['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6 text-right space-x-1 whitespace-nowrap">
                                        <?php if ($item['status'] === 'Scheduled'): ?>
                                            <a href="<?= APP_URL ?>/modules/appointments/update_status.php?id=<?= $item['id'] ?>&status=Waiting" class="px-3 py-1 rounded-xl bg-amber-50 text-amber-800 hover:bg-amber-100 font-bold text-[11px] transition border border-amber-200">
                                                Mark Arrived / Waiting
                                            </a>
                                        <?php elseif ($item['status'] === 'Waiting'): ?>
                                            <span class="text-[11px] text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded-lg">In Lobby</span>
                                        <?php else: ?>
                                            <span class="text-[11px] text-slate-400"><?= e($item['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-10 text-slate-400">
                                    No patients scheduled in the queue today. Click "Book Appointment" to add patients.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ========================================================================= -->
    <!-- 2. DOCTOR / PHYSICIAN DASHBOARD                                           -->
    <!-- ========================================================================= -->
    <?php elseif ($role === 'doctor'): ?>
        <?php
            $doctorId = $_SESSION['doctor_id'] ?? null;
            if (!$doctorId) {
                $docRow = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
                $docRow->execute([$userId]);
                $doctorId = $docRow->fetchColumn() ?? 1;
            }

            $myWaiting = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE() AND status = 'Waiting'");
            $myWaiting->execute([$doctorId]);
            $totalWaiting = $myWaiting->fetchColumn() ?? 0;

            $myCompletedToday = $pdo->prepare("SELECT COUNT(*) FROM opd_consultations WHERE doctor_id = ? AND DATE(created_at) = CURDATE()");
            $myCompletedToday->execute([$doctorId]);
            $totalCompleted = $myCompletedToday->fetchColumn() ?? 0;

            $myAdmissions = $pdo->prepare("SELECT COUNT(*) FROM ipd_admissions WHERE doctor_id = ? AND status = 'Admitted'");
            $myAdmissions->execute([$doctorId]);
            $totalAdmitted = $myAdmissions->fetchColumn() ?? 0;

            // My Today's Queue
            $qStmt = $pdo->prepare("SELECT a.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, p.blood_group, p.allergies
                                    FROM appointments a
                                    JOIN patients p ON a.patient_id = p.id
                                    WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
                                    ORDER BY FIELD(a.status, 'In-Consultation', 'Waiting', 'Scheduled', 'Completed', 'Cancelled'), a.appointment_time ASC");
            $qStmt->execute([$doctorId]);
            $myQueue = $qStmt->fetchAll();
        ?>

        <!-- Doctor Banner -->
        <div class="bg-gradient-to-r from-sky-900 via-sky-800 to-slate-900 rounded-3xl p-6 text-white shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/20 text-white backdrop-blur-md">
                    Doctor Clinical Consultation Suite
                </span>
                <h1 class="text-2xl font-black tracking-tight mt-1.5">Dr. <?= e(currentUser()['full_name']) ?></h1>
                <p class="text-xs text-sky-200 mt-0.5">Manage patient examinations, digital e-prescriptions, diagnostic orders, and admissions.</p>
            </div>
            <div>
                <a href="<?= APP_URL ?>/modules/opd/index.php" class="px-5 py-3 bg-sky-500 hover:bg-sky-400 text-white rounded-2xl text-xs font-bold shadow-lg transition flex items-center">
                    <i class="fa-solid fa-stethoscope mr-2"></i> Open OPD Consultation Room
                </a>
            </div>
        </div>

        <!-- Doctor KPI Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Patients Waiting in Queue</span>
                    <h3 class="text-2xl font-black text-amber-600 mt-1"><?= $totalWaiting ?></h3>
                    <span class="text-[11px] font-semibold text-amber-600">Ready for Consultation</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-user-clock"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Consultations Completed Today</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1"><?= $totalCompleted ?></h3>
                    <span class="text-[11px] font-semibold text-emerald-600">Prescriptions & Care Issued</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">My Inpatients (Ward)</span>
                    <h3 class="text-2xl font-black text-purple-600 mt-1"><?= $totalAdmitted ?></h3>
                    <span class="text-[11px] font-semibold text-purple-600">Under Active Inpatient Care</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-bed-pulse"></i>
                </div>
            </div>

        </div>

        <!-- Doctor's Active Queue Table -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-list-check text-sky-600 mr-2"></i> My Today's Clinical Queue
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-6">Queue Token</th>
                            <th class="py-3 px-6">Patient Name</th>
                            <th class="py-3 px-6">Age / Gender</th>
                            <th class="py-3 px-6">Allergies / Flags</th>
                            <th class="py-3 px-6">Status</th>
                            <th class="py-3 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php if (!empty($myQueue)): ?>
                            <?php foreach ($myQueue as $item): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3.5 px-6 font-mono font-bold text-sky-700 text-sm">
                                        <?= e($item['token_number']) ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <strong class="text-slate-900 text-sm block"><?= e($item['patient_name']) ?></strong>
                                        <span class="text-[11px] text-slate-400 font-mono"><?= e($item['mrn']) ?></span>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <?= calculateAge($item['date_of_birth']) ?> yrs &bull; <?= e($item['gender']) ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <?php if (!empty($item['allergies'])): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= e($item['allergies']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-[11px]">No known allergies</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <span class="badge <?= $item['status'] === 'In-Consultation' ? 'badge-primary animate-pulse' : ($item['status'] === 'Waiting' ? 'badge-warning' : ($item['status'] === 'Completed' ? 'badge-success' : 'badge-danger')) ?>">
                                            <?= e($item['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6 text-right">
                                        <?php if ($item['status'] !== 'Completed'): ?>
                                            <a href="<?= APP_URL ?>/modules/opd/consultation.php?appointment_id=<?= $item['id'] ?>&patient_id=<?= $item['patient_id'] ?>" class="px-3.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center">
                                                <i class="fa-solid fa-stethoscope mr-1.5"></i> Consult &rarr;
                                            </a>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-xs font-bold"><i class="fa-solid fa-check mr-1 text-emerald-500"></i> Done</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-10 text-slate-400">
                                    No patients assigned to your queue today.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ========================================================================= -->
    <!-- 3. NURSE / WARD TELEMETRY DASHBOARD                                       -->
    <!-- ========================================================================= -->
    <?php elseif ($role === 'nurse'): ?>
        <?php
            $totalBeds = $pdo->query("SELECT COUNT(*) FROM beds")->fetchColumn() ?? 0;
            $occupiedBeds = $pdo->query("SELECT COUNT(*) FROM beds WHERE status = 'Occupied'")->fetchColumn() ?? 0;
            $availableBeds = $pdo->query("SELECT COUNT(*) FROM beds WHERE status = 'Available'")->fetchColumn() ?? 0;
            $activeInpatients = $pdo->query("SELECT COUNT(*) FROM ipd_admissions WHERE status = 'Admitted'")->fetchColumn() ?? 0;

            // Inpatients List
            $admStmt = $pdo->query("SELECT adm.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, p.blood_group,
                                    w.name as ward_name, b.bed_number, u.full_name as doctor_name
                                    FROM ipd_admissions adm
                                    JOIN patients p ON adm.patient_id = p.id
                                    JOIN beds b ON adm.bed_id = b.id
                                    JOIN wards w ON b.ward_id = w.id
                                    JOIN doctors d ON adm.doctor_id = d.id
                                    JOIN users u ON d.user_id = u.id
                                    WHERE adm.status = 'Admitted'
                                    ORDER BY adm.admission_date DESC");
            $inpatients = $admStmt->fetchAll();
        ?>

        <!-- Nurse Banner -->
        <div class="bg-gradient-to-r from-purple-900 via-purple-800 to-indigo-900 rounded-3xl p-6 text-white shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/20 text-white backdrop-blur-md">
                    Nursing Station & Inpatient Telemetry
                </span>
                <h1 class="text-2xl font-black tracking-tight mt-1.5">Matron <?= e(currentUser()['full_name']) ?></h1>
                <p class="text-xs text-purple-200 mt-0.5">Manage ward bed allocations, vital signs charting, and inpatient care.</p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="<?= APP_URL ?>/modules/ipd/beds.php" class="px-4 py-2.5 bg-white hover:bg-purple-50 text-purple-900 rounded-2xl text-xs font-bold shadow-md transition flex items-center">
                    <i class="fa-solid fa-bed-pulse mr-1.5 text-purple-600"></i> Live Bed Matrix
                </a>
                <a href="<?= APP_URL ?>/modules/ipd/admit.php" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-500 text-white rounded-2xl text-xs font-bold shadow-md transition flex items-center">
                    <i class="fa-solid fa-procedures mr-1.5"></i> Admit Inpatient
                </a>
            </div>
        </div>

        <!-- Nurse KPI Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Ward Beds</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1"><?= $totalBeds ?></h3>
                    <span class="text-[11px] font-semibold text-slate-500">Across 5 Wards</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-bed"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Occupied Beds</span>
                    <h3 class="text-2xl font-black text-rose-600 mt-1"><?= $occupiedBeds ?></h3>
                    <span class="text-[11px] font-semibold text-rose-600">Admitted Patients</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Available Free Beds</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1"><?= $availableBeds ?></h3>
                    <span class="text-[11px] font-semibold text-emerald-600">Ready for Admission</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Inpatients</span>
                    <h3 class="text-2xl font-black text-purple-700 mt-1"><?= $activeInpatients ?></h3>
                    <span class="text-[11px] font-semibold text-purple-700">Under Telemetry</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-heart-pulse"></i>
                </div>
            </div>
        </div>

        <!-- Inpatient Telemetry Roster -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-procedures text-purple-600 mr-2"></i> Current Ward Inpatients Roster
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-6">Ward & Bed</th>
                            <th class="py-3 px-6">Patient Name (EMR)</th>
                            <th class="py-3 px-6">Admission Date</th>
                            <th class="py-3 px-6">Attending Physician</th>
                            <th class="py-3 px-6">Reason for Admission</th>
                            <th class="py-3 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php if (!empty($inpatients)): ?>
                            <?php foreach ($inpatients as $ip): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3.5 px-6">
                                        <span class="px-2.5 py-1 rounded-xl text-xs font-black bg-purple-50 text-purple-700 border border-purple-200">
                                            <?= e($ip['ward_name']) ?> (Bed <?= e($ip['bed_number']) ?>)
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <strong class="text-slate-900 text-sm block"><?= e($ip['patient_name']) ?></strong>
                                        <span class="text-[11px] text-slate-400 font-mono"><?= e($ip['mrn']) ?> &bull; <?= e($ip['blood_group']) ?></span>
                                    </td>
                                    <td class="py-3.5 px-6 font-mono font-bold text-slate-800">
                                        <?= formatDate($ip['admission_date']) ?>
                                    </td>
                                    <td class="py-3.5 px-6 font-bold text-slate-800">
                                        Dr. <?= e($ip['doctor_name']) ?>
                                    </td>
                                    <td class="py-3.5 px-6 text-slate-600">
                                        <?= e($ip['reason_for_admission']) ?>
                                    </td>
                                    <td class="py-3.5 px-6 text-right space-x-1 whitespace-nowrap">
                                        <a href="<?= APP_URL ?>/modules/ipd/vitals.php?patient_id=<?= $ip['patient_id'] ?>&admission_id=<?= $ip['id'] ?>" class="px-3 py-1.5 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-700 transition shadow-sm inline-flex items-center">
                                            <i class="fa-solid fa-heart-pulse mr-1"></i> Chart Vitals
                                        </a>
                                        <a href="<?= APP_URL ?>/modules/ipd/discharge.php?id=<?= $ip['id'] ?>" class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200 transition">
                                            Discharge
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-10 text-slate-400">No active inpatients admitted currently.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ========================================================================= -->
    <!-- 4. PHARMACIST DASHBOARD                                                   -->
    <!-- ========================================================================= -->
    <?php elseif ($role === 'pharmacist'): ?>
        <?php
            $totalDrugs = $pdo->query("SELECT COUNT(*) FROM medicines WHERE status = 'active'")->fetchColumn() ?? 0;
            $lowStock = $pdo->query("SELECT COUNT(*) FROM medicines WHERE stock_quantity <= min_stock_alert AND status = 'active'")->fetchColumn() ?? 0;
            $todaySales = $pdo->query("SELECT SUM(total_amount) FROM pharmacy_sales WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?? 0.00;
            $activeRxs = $pdo->query("SELECT COUNT(*) FROM prescriptions WHERE status = 'Active'")->fetchColumn() ?? 0;

            // Low stock list
            $lowStockDrugs = $pdo->query("SELECT m.*, c.name as cat_name FROM medicines m JOIN medicine_categories c ON m.category_id = c.id WHERE m.stock_quantity <= m.min_stock_alert AND m.status = 'active' ORDER BY m.stock_quantity ASC LIMIT 5")->fetchAll();
        ?>

        <!-- Pharmacist Banner -->
        <div class="bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-900 rounded-3xl p-6 text-white shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/20 text-white backdrop-blur-md">
                    Pharmacy & Medication Dispensary
                </span>
                <h1 class="text-2xl font-black tracking-tight mt-1.5">Welcome, <?= e(currentUser()['full_name']) ?></h1>
                <p class="text-xs text-teal-100 mt-0.5">Dispense doctor electronic prescriptions, track stock levels, and operate the POS counter.</p>
            </div>
            <div>
                <a href="<?= APP_URL ?>/modules/pharmacy/pos.php" class="px-6 py-3 bg-emerald-500 hover:bg-emerald-400 text-white rounded-2xl text-xs font-bold shadow-lg transition flex items-center">
                    <i class="fa-solid fa-cash-register mr-2"></i> Launch Pharmacy POS Counter
                </a>
            </div>
        </div>

        <!-- Pharmacy Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Formulations</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1"><?= $totalDrugs ?></h3>
                    <span class="text-[11px] font-semibold text-teal-600">Active Inventory</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-capsules"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Prescriptions Ready</span>
                    <h3 class="text-2xl font-black text-sky-600 mt-1"><?= $activeRxs ?></h3>
                    <span class="text-[11px] font-semibold text-sky-600">Awaiting Dispense</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-prescription"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Low Stock Reorders</span>
                    <h3 class="text-2xl font-black text-amber-600 mt-1"><?= $lowStock ?></h3>
                    <span class="text-[11px] font-semibold text-amber-600">Under Min Limit</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-arrow-down-short-wide"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Today's POS Sales</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono"><?= formatMoney($todaySales) ?></h3>
                    <span class="text-[11px] font-semibold text-emerald-600">Dispensed Cash</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 mr-2"></i> Low Stock Pharmaceuticals (Needs Re-Order)
                </h3>
                <a href="<?= APP_URL ?>/modules/pharmacy/medicines.php" class="text-xs text-teal-600 font-bold hover:underline">View All Formulations</a>
            </div>

            <div class="space-y-3">
                <?php if (!empty($lowStockDrugs)): ?>
                    <?php foreach ($lowStockDrugs as $d): ?>
                        <div class="p-3 bg-amber-50/60 rounded-2xl border border-amber-100 flex items-center justify-between text-xs">
                            <div>
                                <h4 class="font-bold text-slate-900 text-sm"><?= e($d['name']) ?></h4>
                                <span class="text-slate-500 font-mono"><?= e($d['generic_name']) ?> &bull; <?= e($d['dosage_form']) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800">
                                    <?= $d['stock_quantity'] ?> remaining
                                </span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Threshold: <?= $d['min_stock_alert'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-xs text-slate-400 py-4 text-center">All pharmaceutical inventory levels are optimal.</p>
                <?php endif; ?>
            </div>
        </div>

    <!-- ========================================================================= -->
    <!-- 5. LAB SCIENTIST DASHBOARD                                                -->
    <!-- ========================================================================= -->
    <?php elseif ($role === 'labtech'): ?>
        <?php
            $pendingTests = $pdo->query("SELECT COUNT(*) FROM lab_requests WHERE status = 'Pending'")->fetchColumn() ?? 0;
            $inProgressTests = $pdo->query("SELECT COUNT(*) FROM lab_requests WHERE status = 'In Progress'")->fetchColumn() ?? 0;
            $completedToday = $pdo->query("SELECT COUNT(*) FROM lab_requests WHERE status = 'Completed' AND DATE(requested_date) = CURDATE()")->fetchColumn() ?? 0;

            // Lab Requests Queue
            $labQueue = $pdo->query("SELECT lr.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth,
                                    u.full_name as doctor_name,
                                    (SELECT COUNT(*) FROM lab_request_items lri WHERE lri.lab_request_id = lr.id) as total_tests
                                    FROM lab_requests lr
                                    JOIN patients p ON lr.patient_id = p.id
                                    LEFT JOIN doctors d ON lr.doctor_id = d.id
                                    LEFT JOIN users u ON d.user_id = u.id
                                    WHERE lr.status != 'Completed'
                                    ORDER BY lr.requested_date ASC")->fetchAll();
        ?>

        <!-- Lab Banner -->
        <div class="bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 rounded-3xl p-6 text-white shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/20 text-white backdrop-blur-md">
                    Diagnostic Pathology & Clinical Laboratories
                </span>
                <h1 class="text-2xl font-black tracking-tight mt-1.5">Welcome, <?= e(currentUser()['full_name']) ?></h1>
                <p class="text-xs text-purple-200 mt-0.5">Analyze clinical specimens, enter observed findings, and issue certified diagnostic reports.</p>
            </div>
            <div>
                <a href="<?= APP_URL ?>/modules/laboratory/index.php" class="px-5 py-3 bg-purple-500 hover:bg-purple-400 text-white rounded-2xl text-xs font-bold shadow-md transition flex items-center">
                    <i class="fa-solid fa-microscope mr-2"></i> Open Laboratory Workbench
                </a>
            </div>
        </div>

        <!-- Lab Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pending Test Requisitions</span>
                    <h3 class="text-2xl font-black text-amber-600 mt-1"><?= $pendingTests ?></h3>
                    <span class="text-[11px] font-semibold text-amber-600">Awaiting Specimen Analysis</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Tests In-Progress</span>
                    <h3 class="text-2xl font-black text-sky-600 mt-1"><?= $inProgressTests ?></h3>
                    <span class="text-[11px] font-semibold text-sky-600">On Diagnostic Analyzers</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-flask-vial"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Reports Certified Today</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1"><?= $completedToday ?></h3>
                    <span class="text-[11px] font-semibold text-emerald-600">Released to Physicians</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>

        <!-- Pending Work Order Queue -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-list-check text-purple-600 mr-2"></i> Diagnostic Requisition Work Orders
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-6">Requisition #</th>
                            <th class="py-3 px-6">Patient Name</th>
                            <th class="py-3 px-6">Referring Doctor</th>
                            <th class="py-3 px-6">Total Tests</th>
                            <th class="py-3 px-6">Priority</th>
                            <th class="py-3 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php if (!empty($labQueue)): ?>
                            <?php foreach ($labQueue as $l): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3.5 px-6 font-mono font-bold text-purple-700 text-sm">
                                        <?= e($l['request_number']) ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <strong class="text-slate-900 text-sm block"><?= e($l['patient_name']) ?></strong>
                                        <span class="text-[11px] text-slate-400 font-mono"><?= e($l['mrn']) ?></span>
                                    </td>
                                    <td class="py-3.5 px-6 font-bold text-slate-800">
                                        Dr. <?= e($l['doctor_name'] ?? 'Direct Walk-In') ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700">
                                            <?= $l['total_tests'] ?> Tests
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <span class="badge <?= $l['priority'] === 'Urgent' ? 'badge-danger' : 'badge-primary' ?>">
                                            <?= e($l['priority']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6 text-right">
                                        <a href="<?= APP_URL ?>/modules/laboratory/enter_result.php?id=<?= $l['id'] ?>" class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center">
                                            <i class="fa-solid fa-flask mr-1"></i> Enter Results &rarr;
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-10 text-slate-400">All laboratory test orders are completed!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ========================================================================= -->
    <!-- 6. CASHIER / ACCOUNTANT DASHBOARD                                         -->
    <!-- ========================================================================= -->
    <?php elseif ($role === 'accountant'): ?>
        <?php
            $todayCollected = $pdo->query("SELECT SUM(amount) FROM payments WHERE DATE(payment_date) = CURDATE()")->fetchColumn() ?? 0.00;
            $unpaidCount = $pdo->query("SELECT COUNT(*) FROM invoices WHERE payment_status = 'Unpaid'")->fetchColumn() ?? 0;
            $unpaidTotal = $pdo->query("SELECT SUM(due_amount) FROM invoices WHERE payment_status = 'Unpaid'")->fetchColumn() ?? 0.00;
            $totalInvoices = $pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn() ?? 0;

            // Unsettled Invoices
            $unsettled = $pdo->query("SELECT inv.*, p.full_name as patient_name, p.mrn, p.phone
                                      FROM invoices inv
                                      JOIN patients p ON inv.patient_id = p.id
                                      WHERE inv.payment_status != 'Paid'
                                      ORDER BY inv.created_at DESC LIMIT 6")->fetchAll();
        ?>

        <!-- Cashier Banner -->
        <div class="bg-gradient-to-r from-emerald-900 via-teal-800 to-slate-900 rounded-3xl p-6 text-white shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/20 text-white backdrop-blur-md">
                    Billing, Invoicing & Cashier Terminal
                </span>
                <h1 class="text-2xl font-black tracking-tight mt-1.5">Welcome, <?= e(currentUser()['full_name']) ?></h1>
                <p class="text-xs text-emerald-100 mt-0.5">Collect patient payments, record settlements, generate invoices, and issue receipts.</p>
            </div>
            <div>
                <a href="<?= APP_URL ?>/modules/billing/create.php" class="px-5 py-3 bg-emerald-500 hover:bg-emerald-400 text-white rounded-2xl text-xs font-bold shadow-md transition flex items-center">
                    <i class="fa-solid fa-file-invoice-dollar mr-2"></i> Create Custom Invoice
                </a>
            </div>
        </div>

        <!-- Cashier Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Cash Collected Today</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono"><?= formatMoney($todayCollected) ?></h3>
                    <span class="text-[11px] font-semibold text-emerald-600">Settled In Cash / Card / POS</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Unsettled Unpaid Invoices</span>
                    <h3 class="text-2xl font-black text-rose-600 mt-1 font-mono"><?= formatMoney($unpaidTotal) ?></h3>
                    <span class="text-[11px] font-semibold text-rose-600"><?= $unpaidCount ?> Pending Bills</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Invoices Billed</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1"><?= $totalInvoices ?></h3>
                    <span class="text-[11px] font-semibold text-slate-500">Gross Hospital Invoices</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
            </div>
        </div>

        <!-- Pending Invoices Table -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-credit-card text-emerald-600 mr-2"></i> Outstanding Patient Bills Requiring Payment
                </h3>
                <a href="<?= APP_URL ?>/modules/billing/index.php" class="text-xs text-teal-600 font-bold hover:underline">Full Billing Log &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-6">Invoice #</th>
                            <th class="py-3 px-6">Patient Name</th>
                            <th class="py-3 px-6">Department</th>
                            <th class="py-3 px-6">Total Billed</th>
                            <th class="py-3 px-6">Balance Due</th>
                            <th class="py-3 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php if (!empty($unsettled)): ?>
                            <?php foreach ($unsettled as $inv): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3.5 px-6 font-mono font-bold text-emerald-700 text-sm">
                                        <?= e($inv['invoice_number']) ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <strong class="text-slate-900 text-sm block"><?= e($inv['patient_name']) ?></strong>
                                        <span class="text-[11px] text-slate-400 font-mono"><?= e($inv['mrn']) ?> &bull; <?= e($inv['phone']) ?></span>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                            <?= e($inv['reference_type']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6 font-mono font-bold text-slate-900">
                                        <?= formatMoney($inv['total_amount']) ?>
                                    </td>
                                    <td class="py-3.5 px-6 font-mono font-bold text-rose-600 text-sm">
                                        <?= formatMoney($inv['due_amount']) ?>
                                    </td>
                                    <td class="py-3.5 px-6 text-right">
                                        <a href="<?= APP_URL ?>/modules/billing/view.php?id=<?= $inv['id'] ?>" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center">
                                            <i class="fa-solid fa-credit-card mr-1"></i> Receive Payment &rarr;
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-10 text-slate-400">All patient bills are fully settled.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ========================================================================= -->
    <!-- 7. SUPER ADMIN EXECUTIVE DASHBOARD                                        -->
    <!-- ========================================================================= -->
    <?php else: ?>
        <?php
            $totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn() ?? 0;
            $totalDoctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn() ?? 0;
            $todayAppts = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn() ?? 0;
            $occupiedBeds = $pdo->query("SELECT COUNT(*) FROM beds WHERE status = 'Occupied'")->fetchColumn() ?? 0;
            $totalBeds = $pdo->query("SELECT COUNT(*) FROM beds")->fetchColumn() ?? 1;
            $totalRevenue = $pdo->query("SELECT SUM(paid_amount) FROM invoices")->fetchColumn() ?? 0.00;

            // Low stock
            $lowStockDrugs = $pdo->query("SELECT m.*, c.name as cat_name FROM medicines m JOIN medicine_categories c ON m.category_id = c.id WHERE m.stock_quantity <= m.min_stock_alert AND m.status = 'active' ORDER BY m.stock_quantity ASC LIMIT 5")->fetchAll();
        ?>

        <!-- Admin Executive Banner -->
        <div class="bg-gradient-to-r from-slate-900 via-teal-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-6 border border-white/10">
            <div>
                <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-teal-500/20 text-teal-300 border border-teal-500/30">
                    Executive Administration & Operations
                </span>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight mt-2">Hospital Command Center</h1>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mt-1">
                    Live operational telemetry, clinical capacity, revenue collections, and hospital staff governance.
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="<?= APP_URL ?>/modules/reports/index.php" class="px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-900 font-bold rounded-2xl text-xs shadow-md transition flex items-center">
                    <i class="fa-solid fa-chart-line mr-1.5 text-teal-600"></i> Full Analytics
                </a>
                <a href="<?= APP_URL ?>/modules/settings/backup.php" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-2xl text-xs shadow-md transition flex items-center">
                    <i class="fa-solid fa-database mr-1.5"></i> Backup DB
                </a>
            </div>
        </div>

        <!-- 4 Executive KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Registered Patients</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1"><?= number_format($totalPatients) ?></h3>
                    <span class="text-[11px] font-semibold text-teal-600">Master EMR Records</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Revenue Collected</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono"><?= formatMoney($totalRevenue) ?></h3>
                    <span class="text-[11px] font-semibold text-emerald-600">Gross Hospital Cash</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Ward Bed Occupancy</span>
                    <h3 class="text-2xl font-black text-purple-600 mt-1"><?= $occupiedBeds ?> / <?= $totalBeds ?></h3>
                    <span class="text-[11px] font-semibold text-purple-600"><?= round(($occupiedBeds / max(1, $totalBeds)) * 100) ?>% Capacity</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-bed-pulse"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Doctors / Staff</span>
                    <h3 class="text-2xl font-black text-sky-600 mt-1"><?= $totalDoctors ?></h3>
                    <span class="text-[11px] font-semibold text-sky-600">Clinicians On Duty</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
            </div>

        </div>

        <!-- Admin Quick Action Links -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <a href="<?= APP_URL ?>/modules/users/index.php" class="p-6 bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition flex items-center space-x-4">
                <div class="w-14 h-14 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">Staff & HR Management</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Manage doctor profiles, nurses, and role access.</p>
                </div>
            </a>

            <a href="<?= APP_URL ?>/modules/audit_logs/index.php" class="p-6 bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition flex items-center space-x-4">
                <div class="w-14 h-14 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">Audit & Security Logs</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Trace all medical orders, logins, and IP addresses.</p>
                </div>
            </a>

            <a href="<?= APP_URL ?>/modules/settings/index.php" class="p-6 bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition flex items-center space-x-4">
                <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">Hospital White-Labeling</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Customize hospital name, logo, currency, and tax.</p>
                </div>
            </a>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
