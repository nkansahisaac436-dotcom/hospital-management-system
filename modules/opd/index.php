<?php
/**
 * CarePoint Pro HMS - OPD Consultations Queue
 */

$pageTitle = 'OPD Consultations Queue';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth(['admin', 'doctor']);

$pdo = Database::getConnection();
$currentUser = currentUser();
$doctorId = $currentUser['doctor_id'] ?? 0;

$date = $_GET['date'] ?? date('Y-m-d');

$sql = "SELECT a.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, p.allergies, p.blood_group,
        u.full_name as doctor_name, d.specialization, d.room_no
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        WHERE a.appointment_date = ?";
$params = [$date];

if ($doctorId > 0 && currentRole() === 'doctor') {
    $sql .= " AND a.doctor_id = ?";
    $params[] = $doctorId;
}

$sql .= " ORDER BY FIELD(a.status, 'In-Consultation', 'Waiting', 'Scheduled', 'Completed', 'Cancelled'), a.token_number ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$queue = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Outpatient (OPD) Consultation Desk</h1>
            <p class="text-xs text-slate-500 mt-1">Manage live patient queue, examination, clinical EHR recording, and digital prescriptions.</p>
        </div>
        <div class="flex items-center space-x-2">
            <form method="GET" class="flex items-center space-x-2">
                <input type="date" name="date" value="<?= e($date) ?>" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none shadow-sm" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    <!-- OPD Queue Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($queue)): ?>
            <?php foreach ($queue as $apt): ?>
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm card-hover flex flex-col justify-between space-y-4 <?= $apt['status'] === 'In-Consultation' ? 'ring-2 ring-sky-500 bg-sky-50/20' : '' ?>">
                    
                    <div>
                        <!-- Token & Status -->
                        <div class="flex items-center justify-between mb-3">
                            <span class="w-9 h-9 rounded-xl bg-teal-600 text-white font-black flex items-center justify-center text-xs shadow-md shadow-teal-600/20">
                                #<?= $apt['token_number'] ?>
                            </span>
                            <span class="badge <?= $apt['status'] === 'Waiting' ? 'badge-warning' : ($apt['status'] === 'In-Consultation' ? 'badge-info' : ($apt['status'] === 'Completed' ? 'badge-success' : 'badge-primary')) ?>">
                                <?= e($apt['status']) ?>
                            </span>
                        </div>

                        <!-- Patient Info -->
                        <div class="space-y-1">
                            <h3 class="font-bold text-slate-900 text-base leading-tight"><?= e($apt['patient_name']) ?></h3>
                            <p class="text-xs text-slate-400 font-mono"><?= e($apt['mrn']) ?> &bull; <?= e($apt['gender']) ?> (<?= calculateAge($apt['date_of_birth']) ?>)</p>
                            
                            <?php if (!empty($apt['blood_group']) && $apt['blood_group'] !== 'Unknown'): ?>
                                <span class="inline-block text-[11px] font-bold text-rose-600 mt-1">
                                    <i class="fa-solid fa-droplet text-rose-500 mr-1"></i> Blood: <?= e($apt['blood_group']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($apt['allergies'])): ?>
                                <div class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-xl text-[11px] text-rose-800 font-medium">
                                    <i class="fa-solid fa-triangle-exclamation text-rose-600 mr-1"></i> <?= e($apt['allergies']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Complaint -->
                        <div class="mt-4 p-3 bg-slate-50 rounded-2xl border border-slate-100 text-xs">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Chief Complaint / Symptoms:</span>
                            <p class="text-slate-700 italic"><?= e($apt['reason'] ?: 'Routine medical checkup') ?></p>
                        </div>
                    </div>

                    <!-- Consultation Action Button -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-400 font-mono"><?= date('h:i A', strtotime($apt['appointment_time'])) ?></span>
                        
                        <?php if ($apt['status'] === 'Completed'): ?>
                            <a href="<?= APP_URL ?>/modules/patients/view.php?id=<?= $apt['patient_id'] ?>&tab=consultations" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                                <i class="fa-solid fa-eye mr-1"></i> View Record
                            </a>
                        <?php else: ?>
                            <a href="consultation.php?appointment_id=<?= $apt['id'] ?>" class="px-5 py-2.5 bg-gradient-to-r from-teal-600 to-sky-600 hover:from-teal-700 hover:to-sky-700 text-white rounded-xl text-xs font-bold shadow-md transition transform hover:-translate-y-0.5 flex items-center">
                                <i class="fa-solid fa-stethoscope mr-1.5"></i> Start Consultation
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full bg-white rounded-3xl p-12 text-center text-slate-400 border border-slate-200">
                <i class="fa-solid fa-stethoscope text-4xl mb-3 block"></i>
                No patients in queue for <?= formatDate($date) ?>.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
