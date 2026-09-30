<?php
/**
 * CarePoint Pro HMS - Comprehensive Patient Master EMR Profile
 */

$pageTitle = 'Patient Medical Profile & EMR';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$patientId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    setFlash('error', 'Patient not found.');
    header('Location: index.php');
    exit;
}

// Fetch Medical Timeline Records
// 1. Vitals
$vitalsStmt = $pdo->prepare("SELECT v.*, u.full_name as recorded_by_name FROM vitals v LEFT JOIN users u ON v.recorded_by = u.id WHERE v.patient_id = ? ORDER BY v.recorded_at DESC LIMIT 10");
$vitalsStmt->execute([$patientId]);
$vitalsList = $vitalsStmt->fetchAll();
$latestVitals = $vitalsList[0] ?? null;

// 2. OPD Consultations
$opdStmt = $pdo->prepare("SELECT c.*, u.full_name as doctor_name, d.specialization FROM opd_consultations c JOIN doctors d ON c.doctor_id = d.id JOIN users u ON d.user_id = u.id WHERE c.patient_id = ? ORDER BY c.created_at DESC");
$opdStmt->execute([$patientId]);
$consultations = $opdStmt->fetchAll();

// 3. Prescriptions
$rxStmt = $pdo->prepare("SELECT p.*, u.full_name as doctor_name, (SELECT COUNT(*) FROM prescription_items pi WHERE pi.prescription_id = p.id) as total_medicines FROM prescriptions p JOIN doctors d ON p.doctor_id = d.id JOIN users u ON d.user_id = u.id WHERE p.patient_id = ? ORDER BY p.created_at DESC");
$rxStmt->execute([$patientId]);
$prescriptions = $rxStmt->fetchAll();

// 4. Lab Requests
$labStmt = $pdo->prepare("SELECT lr.*, u.full_name as doctor_name, (SELECT COUNT(*) FROM lab_request_items lri WHERE lri.lab_request_id = lr.id) as total_tests FROM lab_requests lr LEFT JOIN doctors d ON lr.doctor_id = d.id LEFT JOIN users u ON d.user_id = u.id WHERE lr.patient_id = ? ORDER BY lr.created_at DESC");
$labStmt->execute([$patientId]);
$labRequests = $labStmt->fetchAll();

// 5. IPD Admissions
$ipdStmt = $pdo->prepare("SELECT adm.*, b.bed_number, w.name as ward_name, u.full_name as doctor_name FROM ipd_admissions adm JOIN beds b ON adm.bed_id = b.id JOIN wards w ON b.ward_id = w.id JOIN doctors d ON adm.doctor_id = d.id JOIN users u ON d.user_id = u.id WHERE adm.patient_id = ? ORDER BY adm.admission_date DESC");
$ipdStmt->execute([$patientId]);
$admissions = $ipdStmt->fetchAll();

// 6. Invoices
$invStmt = $pdo->prepare("SELECT * FROM invoices WHERE patient_id = ? ORDER BY created_at DESC");
$invStmt->execute([$patientId]);
$invoices = $invStmt->fetchAll();

$activeTab = $_GET['tab'] ?? 'summary';
?>

<div class="space-y-6">

    <!-- Top Navigation Breadcrumbs -->
    <div class="flex items-center justify-between">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Patient Directory
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Electronic Medical Record (EMR)</h1>
        </div>
        <div class="flex items-center space-x-2">
            <a href="edit.php?id=<?= $patient['id'] ?>" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                <i class="fa-solid fa-pen-to-square mr-1.5"></i> Edit Profile
            </a>
            <button onclick="window.print()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                <i class="fa-solid fa-print mr-1.5"></i> Print Summary
            </button>
        </div>
    </div>

    <!-- Patient Identification Master Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            
            <div class="flex items-start sm:items-center space-x-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-teal-600 to-sky-600 text-white flex items-center justify-center text-2xl font-black shadow-lg shadow-teal-600/20 shrink-0">
                    <?= strtoupper(substr($patient['full_name'], 0, 1)) ?>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900"><?= e($patient['full_name']) ?></h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-teal-50 text-teal-700 border border-teal-200">
                            <?= e($patient['mrn']) ?>
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $patient['gender'] === 'Male' ? 'bg-sky-50 text-sky-700' : 'bg-pink-50 text-pink-700' ?>">
                            <?= e($patient['gender']) ?> &bull; <?= calculateAge($patient['date_of_birth']) ?>
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-500 mt-2">
                        <span><i class="fa-solid fa-phone text-slate-400 mr-1"></i> <?= e($patient['phone']) ?></span>
                        <?php if (!empty($patient['email'])): ?>
                            <span><i class="fa-solid fa-envelope text-slate-400 mr-1"></i> <?= e($patient['email']) ?></span>
                        <?php endif; ?>
                        <span><i class="fa-solid fa-cake-candles text-slate-400 mr-1"></i> DOB: <?= formatDate($patient['date_of_birth']) ?></span>
                        <?php if (!empty($patient['blood_group']) && $patient['blood_group'] !== 'Unknown'): ?>
                            <span class="font-bold text-rose-600"><i class="fa-solid fa-droplet mr-1"></i> Blood Group: <?= e($patient['blood_group']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Clinical Triggers -->
            <div class="flex flex-wrap items-center gap-2 pt-4 md:pt-0 border-t md:border-t-0 border-slate-100">
                <a href="<?= APP_URL ?>/modules/appointments/book.php?patient_id=<?= $patient['id'] ?>" class="px-3 py-2 bg-sky-50 hover:bg-sky-100 text-sky-700 rounded-xl text-xs font-bold transition border border-sky-200">
                    <i class="fa-solid fa-calendar-plus mr-1"></i> Appt
                </a>
                <a href="<?= APP_URL ?>/modules/prescriptions/create.php?patient_id=<?= $patient['id'] ?>" class="px-3 py-2 bg-teal-50 hover:bg-teal-100 text-teal-700 rounded-xl text-xs font-bold transition border border-teal-200">
                    <i class="fa-solid fa-file-prescription mr-1"></i> Rx
                </a>
                <a href="<?= APP_URL ?>/modules/laboratory/create_request.php?patient_id=<?= $patient['id'] ?>" class="px-3 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-xl text-xs font-bold transition border border-purple-200">
                    <i class="fa-solid fa-flask mr-1"></i> Lab Test
                </a>
                <a href="<?= APP_URL ?>/modules/ipd/admit.php?patient_id=<?= $patient['id'] ?>" class="px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition border border-indigo-200">
                    <i class="fa-solid fa-bed mr-1"></i> Admit IPD
                </a>
            </div>

        </div>

        <!-- Allergy & Safety Alert Strip (Crucial for Medical Safety) -->
        <?php if (!empty($patient['allergies'])): ?>
            <div class="mt-4 p-3 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
                <div>
                    <strong class="font-bold">ALLERGIES RECORDED:</strong> <?= e($patient['allergies']) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- EMR Navigation Tabs -->
    <div class="flex overflow-x-auto space-x-2 border-b border-slate-200 pb-2">
        <a href="?id=<?= $patient['id'] ?>&tab=summary" class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $activeTab === 'summary' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
            <i class="fa-solid fa-notes-medical mr-1.5"></i> Patient Profile & Vitals
        </a>
        <?php if (hasRole(['admin', 'doctor'])): ?>
            <a href="?id=<?= $patient['id'] ?>&tab=consultations" class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $activeTab === 'consultations' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-stethoscope mr-1.5"></i> Clinical Consultations (<?= count($consultations) ?>)
            </a>
        <?php endif; ?>
        <?php if (hasRole(['admin', 'doctor', 'pharmacist'])): ?>
            <a href="?id=<?= $patient['id'] ?>&tab=prescriptions" class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $activeTab === 'prescriptions' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-prescription mr-1.5"></i> Prescriptions (<?= count($prescriptions) ?>)
            </a>
        <?php endif; ?>
        <?php if (hasRole(['admin', 'doctor', 'labtech'])): ?>
            <a href="?id=<?= $patient['id'] ?>&tab=lab" class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $activeTab === 'lab' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-flask-vial mr-1.5"></i> Diagnostic Labs (<?= count($labRequests) ?>)
            </a>
        <?php endif; ?>
        <?php if (hasRole(['admin', 'doctor', 'nurse'])): ?>
            <a href="?id=<?= $patient['id'] ?>&tab=ipd" class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $activeTab === 'ipd' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-bed-pulse mr-1.5"></i> Inpatient Admissions (<?= count($admissions) ?>)
            </a>
        <?php endif; ?>
        <?php if (hasRole(['admin', 'accountant'])): ?>
            <a href="?id=<?= $patient['id'] ?>&tab=billing" class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $activeTab === 'billing' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-file-invoice-dollar mr-1.5"></i> Invoices & Bills (<?= count($invoices) ?>)
            </a>
        <?php endif; ?>
    </div>

    <!-- TAB 1: SUMMARY & VITALS -->
    <?php if ($activeTab === 'summary'): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Latest Vitals Panel -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-heart-pulse text-rose-500 mr-2"></i> Current Vital Signs
                    </h3>
                    <a href="<?= APP_URL ?>/modules/ipd/vitals.php?patient_id=<?= $patient['id'] ?>" class="text-xs text-teal-600 font-bold hover:underline">
                        + Record Vitals
                    </a>
                </div>

                <?php if ($latestVitals): ?>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Blood Pressure</span>
                            <span class="text-base font-black text-slate-800"><?= e($latestVitals['blood_pressure'] ?: '--/--') ?></span>
                            <span class="text-[10px] text-slate-400">mmHg</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Heart / Pulse</span>
                            <span class="text-base font-black text-slate-800"><?= e($latestVitals['pulse_rate'] ?: '--') ?></span>
                            <span class="text-[10px] text-slate-400">bpm</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Temperature</span>
                            <span class="text-base font-black text-slate-800"><?= e($latestVitals['temperature'] ?: '--') ?></span>
                            <span class="text-[10px] text-slate-400">&deg;C</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Oxygen SpO2</span>
                            <span class="text-base font-black text-slate-800"><?= e($latestVitals['spo2'] ?: '--') ?></span>
                            <span class="text-[10px] text-slate-400">%</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Blood Sugar</span>
                            <span class="text-base font-black text-slate-800"><?= e($latestVitals['blood_sugar'] ?: '--') ?></span>
                            <span class="text-[10px] text-slate-400">mg/dL</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Weight / Height</span>
                            <span class="text-sm font-black text-slate-800"><?= e($latestVitals['weight_kg'] ?: '--') ?>kg / <?= e($latestVitals['height_cm'] ?: '--') ?>cm</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400">
                        Recorded on <?= formatDateTime($latestVitals['recorded_at']) ?> by <?= e($latestVitals['recorded_by_name'] ?? 'Staff') ?>
                    </p>
                <?php else: ?>
                    <p class="text-xs text-slate-400 py-4 text-center">No vital signs recorded yet.</p>
                <?php endif; ?>
            </div>

            <!-- Clinical Profile & Emergency Info -->
            <div class="lg:col-span-2 space-y-6">
                
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                        <i class="fa-solid fa-clipboard-check text-teal-600 mr-2"></i> Clinical Background & Next-of-Kin
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 uppercase tracking-wider font-bold text-[10px]">Chronic Diseases / History:</span>
                            <p class="mt-1 font-semibold text-slate-800 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <?= e($patient['chronic_diseases'] ?: 'None Reported') ?>
                            </p>
                        </div>
                        <div>
                            <span class="text-slate-400 uppercase tracking-wider font-bold text-[10px]">Residential Address:</span>
                            <p class="mt-1 font-semibold text-slate-800 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <?= e($patient['address'] ?: 'Not on file') ?>
                            </p>
                        </div>
                        <div>
                            <span class="text-slate-400 uppercase tracking-wider font-bold text-[10px]">Emergency Next-of-Kin:</span>
                            <p class="mt-1 font-semibold text-slate-800 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <?= e($patient['emergency_contact_name'] ?: 'N/A') ?> (<?= e($patient['emergency_contact_relation'] ?: 'Relation N/A') ?>)
                                <br><span class="text-teal-600"><?= e($patient['emergency_contact_phone']) ?></span>
                            </p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    <?php endif; ?>

    <!-- TAB 2: CONSULTATIONS -->
    <?php if ($activeTab === 'consultations'): ?>
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900">OPD Outpatient Consultations</h3>
            <?php if (!empty($consultations)): ?>
                <div class="space-y-4">
                    <?php foreach ($consultations as $c): ?>
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm"><?= e($c['doctor_name']) ?> <span class="text-xs text-slate-400 font-normal">(<?= e($c['specialization']) ?>)</span></h4>
                                    <span class="text-xs text-slate-400"><?= formatDateTime($c['created_at']) ?></span>
                                </div>
                                <span class="badge badge-success">Consultation Completed</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                <div>
                                    <strong class="text-slate-500 uppercase text-[10px]">Chief Complaints:</strong>
                                    <p class="text-slate-800 mt-0.5"><?= nl2br(e($c['chief_complaints'])) ?></p>
                                </div>
                                <div>
                                    <strong class="text-slate-500 uppercase text-[10px]">Diagnosis & ICD:</strong>
                                    <p class="text-teal-700 font-bold mt-0.5"><?= e($c['diagnosis']) ?> <?= !empty($c['icd10_code']) ? "({$c['icd10_code']})" : '' ?></p>
                                </div>
                                <?php if (!empty($c['doctor_notes'])): ?>
                                    <div class="md:col-span-2">
                                        <strong class="text-slate-500 uppercase text-[10px]">Doctor Clinical Notes & Recommendations:</strong>
                                        <p class="text-slate-800 mt-0.5"><?= nl2br(e($c['doctor_notes'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-xs text-slate-400 py-6 text-center">No past consultations on record for this patient.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- TAB 3: PRESCRIPTIONS -->
    <?php if ($activeTab === 'prescriptions'): ?>
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">E-Prescriptions History</h3>
                <a href="<?= APP_URL ?>/modules/prescriptions/create.php?patient_id=<?= $patient['id'] ?>" class="px-3 py-1.5 rounded-xl bg-teal-600 text-white text-xs font-bold hover:bg-teal-700">
                    + New Prescription
                </a>
            </div>

            <?php if (!empty($prescriptions)): ?>
                <div class="space-y-3">
                    <?php foreach ($prescriptions as $rx): ?>
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div>
                                <span class="font-mono font-bold text-teal-700 block text-sm"><?= e($rx['prescription_number']) ?></span>
                                <p class="text-slate-500 mt-0.5">Issued by <strong><?= e($rx['doctor_name']) ?></strong> on <?= formatDate($rx['created_at']) ?></p>
                                <p class="text-slate-700 mt-1"><strong>Diagnosis:</strong> <?= e($rx['diagnosis'] ?: 'General Consult') ?> &bull; <strong><?= $rx['total_medicines'] ?></strong> prescribed drug(s)</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="<?= APP_URL ?>/modules/prescriptions/print.php?id=<?= $rx['id'] ?>" target="_blank" class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-800 border border-slate-200 rounded-xl font-bold transition inline-flex items-center">
                                    <i class="fa-solid fa-print mr-1 text-teal-600"></i> Print Rx
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-xs text-slate-400 py-6 text-center">No digital prescriptions issued yet.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- TAB 4: LAB REPORTS -->
    <?php if ($activeTab === 'lab'): ?>
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">Laboratory & Diagnostic Reports</h3>
                <a href="<?= APP_URL ?>/modules/laboratory/create_request.php?patient_id=<?= $patient['id'] ?>" class="px-3 py-1.5 rounded-xl bg-purple-600 text-white text-xs font-bold hover:bg-purple-700">
                    + Order Diagnostic Test
                </a>
            </div>

            <?php if (!empty($labRequests)): ?>
                <div class="space-y-3">
                    <?php foreach ($labRequests as $lr): ?>
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div>
                                <span class="font-mono font-bold text-purple-700 block text-sm"><?= e($lr['request_number']) ?></span>
                                <p class="text-slate-500 mt-0.5">Ordered on <?= formatDate($lr['requested_date']) ?> &bull; Priority: <strong class="text-slate-800"><?= e($lr['priority']) ?></strong></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="badge <?= $lr['status'] === 'Completed' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= e($lr['status']) ?>
                                </span>
                                <?php if ($lr['status'] === 'Completed'): ?>
                                    <a href="<?= APP_URL ?>/modules/laboratory/print_report.php?id=<?= $lr['id'] ?>" target="_blank" class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-800 border border-slate-200 rounded-xl font-bold transition">
                                        <i class="fa-solid fa-file-lines mr-1 text-purple-600"></i> View Report
                                    </a>
                                <?php else: ?>
                                    <a href="<?= APP_URL ?>/modules/laboratory/enter_result.php?id=<?= $lr['id'] ?>" class="px-3 py-1.5 bg-purple-50 text-purple-700 rounded-xl font-bold hover:bg-purple-100">
                                        Enter Results
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-xs text-slate-400 py-6 text-center">No laboratory orders recorded for this patient.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- TAB 5: IPD ADMISSIONS -->
    <?php if ($activeTab === 'ipd'): ?>
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900">Inpatient Admission History</h3>
            <?php if (!empty($admissions)): ?>
                <div class="space-y-3">
                    <?php foreach ($admissions as $adm): ?>
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div>
                                <span class="font-mono font-bold text-slate-800 block"><?= e($adm['admission_number']) ?> &bull; <?= e($adm['ward_name']) ?> (Bed: <?= e($adm['bed_number']) ?>)</span>
                                <p class="text-slate-500 mt-0.5">Admitted: <?= formatDate($adm['admission_date']) ?> <?= $adm['discharge_date'] ? ' &bull; Discharged: ' . formatDate($adm['discharge_date']) : '' ?></p>
                                <p class="text-slate-700 mt-1"><strong>Doctor:</strong> <?= e($adm['doctor_name']) ?> &bull; <strong>Reason:</strong> <?= e($adm['admission_reason']) ?></p>
                            </div>
                            <div>
                                <span class="badge <?= $adm['status'] === 'Admitted' ? 'badge-info' : 'badge-success' ?>">
                                    <?= e($adm['status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-xs text-slate-400 py-6 text-center">No inpatient admissions on record.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- TAB 6: BILLING & INVOICES -->
    <?php if ($activeTab === 'billing'): ?>
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">Financial Invoices & Payment History</h3>
                <a href="<?= APP_URL ?>/modules/billing/create.php?patient_id=<?= $patient['id'] ?>" class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700">
                    + Generate New Invoice
                </a>
            </div>

            <?php if (!empty($invoices)): ?>
                <div class="space-y-3">
                    <?php foreach ($invoices as $inv): ?>
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div>
                                <span class="font-mono font-bold text-slate-800 block"><?= e($inv['invoice_number']) ?></span>
                                <p class="text-slate-500 mt-0.5">Date: <?= formatDate($inv['created_at']) ?> &bull; Type: <?= e($inv['reference_type']) ?></p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="text-right">
                                    <span class="font-bold text-slate-900 block"><?= formatMoney($inv['total_amount']) ?></span>
                                    <span class="text-[10px] text-slate-400">Paid: <?= formatMoney($inv['paid_amount']) ?></span>
                                </div>
                                <span class="badge <?= $inv['payment_status'] === 'Paid' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= e($inv['payment_status']) ?>
                                </span>
                                <a href="<?= APP_URL ?>/modules/billing/print_invoice.php?id=<?= $inv['id'] ?>" target="_blank" class="p-2 bg-white hover:bg-slate-100 text-slate-700 rounded-xl border border-slate-200" title="Print Invoice">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-xs text-slate-400 py-6 text-center">No billing records found for this patient.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
