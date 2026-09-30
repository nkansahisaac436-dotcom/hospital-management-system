<?php
/**
 * CarePoint Pro HMS - Patient Self-Service Portal Dashboard
 */

$pageTitle = 'Patient Portal';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

// Get Patient ID for logged in user (or fallback to sample patient #1)
$patientId = $_SESSION['patient_id'] ?? null;
if (!$patientId) {
    // If logged in as admin/other role previewing, grab the first patient
    $firstPat = $pdo->query("SELECT id FROM patients LIMIT 1")->fetch();
    $patientId = $firstPat['id'] ?? 1;
}

// Fetch Patient Master Record
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    die('Patient record not found.');
}

// Summary Metrics
$activePrescriptions = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ? AND status = 'Active'");
$activePrescriptions->execute([$patientId]);
$totalActiveRx = $activePrescriptions->fetchColumn();

$upcomingAppts = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND appointment_date >= CURDATE() AND status != 'Cancelled'");
$upcomingAppts->execute([$patientId]);
$totalUpcomingAppts = $upcomingAppts->fetchColumn();

$pendingLabs = $pdo->prepare("SELECT COUNT(*) FROM lab_requests WHERE patient_id = ? AND status != 'Completed'");
$pendingLabs->execute([$patientId]);
$totalPendingLabs = $pendingLabs->fetchColumn();

$outstandingDue = $pdo->prepare("SELECT SUM(due_amount) FROM invoices WHERE patient_id = ? AND payment_status != 'Paid'");
$outstandingDue->execute([$patientId]);
$totalDue = $outstandingDue->fetchColumn() ?? 0.00;

// Recent Appointments
$appts = $pdo->prepare("SELECT a.*, u.full_name as doctor_name, d.specialization, dep.name as dept_name
                        FROM appointments a
                        JOIN doctors d ON a.doctor_id = d.id
                        JOIN users u ON d.user_id = u.id
                        JOIN departments dep ON d.department_id = dep.id
                        WHERE a.patient_id = ?
                        ORDER BY a.appointment_date DESC, a.appointment_time ASC LIMIT 3");
$appts->execute([$patientId]);
$recentAppts = $appts->fetchAll();

// Recent Prescriptions
$rxs = $pdo->prepare("SELECT p.*, u.full_name as doctor_name
                      FROM prescriptions p
                      JOIN doctors d ON p.doctor_id = d.id
                      JOIN users u ON d.user_id = u.id
                      WHERE p.patient_id = ?
                      ORDER BY p.prescription_date DESC LIMIT 3");
$rxs->execute([$patientId]);
$recentRxs = $rxs->fetchAll();
?>

<div class="space-y-6">

    <!-- Welcome Patient Banner -->
    <div class="bg-gradient-to-r from-teal-700 via-teal-800 to-sky-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-6 relative overflow-hidden">
        <div class="space-y-2 relative z-10">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-white/20 text-white backdrop-blur-md">
                <i class="fa-solid fa-hospital-user mr-1.5"></i> Patient Health Portal
            </span>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Welcome, <?= e($patient['full_name']) ?></h1>
            <p class="text-xs sm:text-sm text-teal-100/90 max-w-xl">
                Access your medical timeline, schedule doctor consultations, view lab test findings, and download digital prescriptions.
            </p>
            <div class="flex flex-wrap gap-2 pt-2 text-xs text-teal-200">
                <span class="font-mono bg-black/20 px-2.5 py-1 rounded-xl">MRN: <strong><?= e($patient['mrn']) ?></strong></span>
                <span class="bg-black/20 px-2.5 py-1 rounded-xl">Blood Group: <strong><?= e($patient['blood_group']) ?></strong></span>
                <span class="bg-black/20 px-2.5 py-1 rounded-xl">Age: <strong><?= calculateAge($patient['date_of_birth']) ?> yrs</strong></span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 relative z-10 shrink-0">
            <a href="appointments.php" class="px-5 py-3 bg-white hover:bg-teal-50 text-teal-900 font-bold rounded-2xl text-xs shadow-lg transition flex items-center justify-center">
                <i class="fa-solid fa-calendar-plus mr-2 text-teal-600"></i> Book Appointment
            </a>
            <a href="prescriptions.php" class="px-5 py-3 bg-teal-600/60 hover:bg-teal-600 border border-white/20 text-white font-bold rounded-2xl text-xs transition flex items-center justify-center">
                <i class="fa-solid fa-prescription mr-2"></i> My Prescriptions
            </a>
        </div>

        <i class="fa-solid fa-heart-pulse absolute -right-6 -bottom-10 text-9xl text-white/5 pointer-events-none"></i>
    </div>

    <!-- Quick Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Upcoming Visits</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1"><?= $totalUpcomingAppts ?></h3>
                <a href="appointments.php" class="text-[11px] font-semibold text-teal-600 hover:underline inline-flex items-center mt-1">
                    View Appointments &rarr;
                </a>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Prescriptions</p>
                <h3 class="text-2xl font-black text-teal-700 mt-1"><?= $totalActiveRx ?></h3>
                <a href="prescriptions.php" class="text-[11px] font-semibold text-teal-600 hover:underline inline-flex items-center mt-1">
                    Medicine Cabinet &rarr;
                </a>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-pills"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pending Lab Tests</p>
                <h3 class="text-2xl font-black text-purple-700 mt-1"><?= $totalPendingLabs ?></h3>
                <a href="lab_reports.php" class="text-[11px] font-semibold text-purple-600 hover:underline inline-flex items-center mt-1">
                    Diagnostic Reports &rarr;
                </a>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-flask-vial"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Outstanding Balance</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1 font-mono"><?= formatMoney($totalDue) ?></h3>
                <a href="invoices.php" class="text-[11px] font-semibold text-rose-600 hover:underline inline-flex items-center mt-1">
                    Billing Statements &rarr;
                </a>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
        </div>

    </div>

    <!-- Active Health Profile & Care Plan -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Upcoming Appointments & Care Timeline (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Appointments Card -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-calendar-days text-sky-600 mr-2"></i> My Upcoming Doctor Consultations
                    </h3>
                    <a href="appointments.php" class="text-xs text-teal-600 font-bold hover:underline">View All</a>
                </div>

                <div class="space-y-3">
                    <?php if (!empty($recentAppts)): ?>
                        <?php foreach ($recentAppts as $ap): ?>
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs">
                                <div>
                                    <strong class="text-slate-900 text-sm block">Dr. <?= e($ap['doctor_name']) ?></strong>
                                    <span class="text-teal-700 font-semibold"><?= e($ap['specialization']) ?> (<?= e($ap['dept_name']) ?>)</span>
                                    <p class="text-slate-400 mt-0.5">Token: <strong class="font-mono text-slate-800"><?= e($ap['token_number']) ?></strong> &bull; Reason: <?= e($ap['reason'] ?: 'Routine Health Review') ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="font-bold text-slate-900 block"><?= formatDate($ap['appointment_date']) ?></span>
                                    <span class="text-slate-500 font-mono"><?= date('h:i A', strtotime($ap['appointment_time'])) ?></span>
                                    <span class="badge badge-primary text-[10px] block mt-1"><?= e($ap['status']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-xs text-slate-400 py-6 text-center">No upcoming appointments scheduled.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active E-Prescriptions -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-prescription text-teal-600 mr-2"></i> Prescribed Medication Regimens
                    </h3>
                    <a href="prescriptions.php" class="text-xs text-teal-600 font-bold hover:underline">Full Rx Wallet</a>
                </div>

                <div class="space-y-3">
                    <?php if (!empty($recentRxs)): ?>
                        <?php foreach ($recentRxs as $rx): ?>
                            <div class="p-4 rounded-2xl bg-teal-50/40 border border-teal-100 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-mono font-bold text-teal-800"><?= e($rx['prescription_number']) ?></span>
                                    <h4 class="font-bold text-slate-900 mt-0.5">By Dr. <?= e($rx['doctor_name']) ?></h4>
                                    <p class="text-slate-500 mt-0.5"><?= e($rx['diagnosis'] ?: 'Clinical Assessment') ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="text-slate-400 block"><?= formatDate($rx['prescription_date']) ?></span>
                                    <a href="<?= APP_URL ?>/modules/prescriptions/print.php?id=<?= $rx['id'] ?>" target="_blank" class="mt-1 inline-flex items-center px-3 py-1 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-[11px] shadow-sm transition">
                                        <i class="fa-solid fa-print mr-1"></i> Print Rx
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-xs text-slate-400 py-6 text-center">No active medical prescriptions on file.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Right: Patient Safety & Clinical Flags (1 Col) -->
        <div class="space-y-6">
            
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                    <i class="fa-solid fa-shield-heart text-rose-600 mr-2"></i> Safety & Medical Alerts
                </h3>

                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Known Drug Allergies</span>
                    <?php if (!empty($patient['allergies'])): ?>
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-2xl text-xs font-bold text-rose-700">
                            <i class="fa-solid fa-triangle-exclamation mr-1.5 text-rose-500"></i> <?= e($patient['allergies']) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-xs text-slate-400">No known drug allergies reported.</p>
                    <?php endif; ?>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Chronic Conditions</span>
                    <?php if (!empty($patient['chronic_diseases'])): ?>
                        <div class="p-3 bg-sky-50 border border-sky-200 rounded-2xl text-xs font-semibold text-sky-800">
                            <?= e($patient['chronic_diseases']) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-xs text-slate-400">No chronic medical conditions recorded.</p>
                    <?php endif; ?>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Emergency Contact</span>
                    <strong class="text-slate-900 text-xs block"><?= e($patient['emergency_contact_name'] ?: 'Family Contact') ?></strong>
                    <span class="text-slate-500 text-xs font-mono"><?= e($patient['emergency_contact_phone'] ?: $patient['phone']) ?></span>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-6 text-white shadow-md space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-teal-400">Need Immediate Help?</h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    If you are experiencing a medical emergency, please call our 24/7 Hospital Emergency Desk immediately.
                </p>
                <a href="tel:<?= getSetting('hospital_phone', DEFAULT_HOSPITAL_PHONE) ?>" class="inline-flex items-center text-xs font-bold text-teal-300 hover:text-white mt-1">
                    <i class="fa-solid fa-phone-volume mr-1.5"></i> <?= getSetting('hospital_phone', DEFAULT_HOSPITAL_PHONE) ?>
                </a>
            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
