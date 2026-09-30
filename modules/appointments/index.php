<?php
/**
 * CarePoint Pro HMS - Appointments & Live OPD Queue Scheduler
 */

$pageTitle = 'Appointments & OPD Queue';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$dateFilter = $_GET['date'] ?? date('Y-m-d');
$doctorFilter = (int)($_GET['doctor_id'] ?? 0);
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT a.*, p.full_name as patient_name, p.mrn, p.phone as patient_phone, p.gender, p.date_of_birth,
        u.full_name as doctor_name, d.specialization, d.room_no
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($dateFilter)) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $dateFilter;
}

if ($doctorFilter > 0) {
    $sql .= " AND a.doctor_id = ?";
    $params[] = $doctorFilter;
}

if (!empty($statusFilter)) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY a.appointment_date DESC, a.token_number ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Get list of doctors for filter
$doctorsList = $pdo->query("SELECT d.id, u.full_name, dep.name as dept_name FROM doctors d JOIN users u ON d.user_id = u.id JOIN departments dep ON d.department_id = dep.id WHERE d.status = 'active' ORDER BY u.full_name ASC")->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Appointments & OPD Queue</h1>
            <p class="text-xs text-slate-500 mt-1">Manage outpatient schedules, doctor consultation queues, and live visit status.</p>
        </div>
        <div>
            <a href="book.php" class="inline-flex items-center px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-calendar-plus mr-2"></i> Book Appointment
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Appointment Date</label>
                <input type="date" name="date" value="<?= e($dateFilter) ?>" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Doctor / Specialist</label>
                <select name="doctor_id" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                    <option value="">All Doctors</option>
                    <?php foreach ($doctorsList as $doc): ?>
                        <option value="<?= $doc['id'] ?>" <?= $doctorFilter === (int)$doc['id'] ? 'selected' : '' ?>><?= e($doc['full_name']) ?> (<?= e($doc['dept_name']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Queue Status</label>
                <select name="status" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="Scheduled" <?= $statusFilter === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="Waiting" <?= $statusFilter === 'Waiting' ? 'selected' : '' ?>>Waiting</option>
                    <option value="In-Consultation" <?= $statusFilter === 'In-Consultation' ? 'selected' : '' ?>>In-Consultation</option>
                    <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                    Filter Queue
                </button>
                <a href="index.php" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs transition" title="Clear Filters">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Queue Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Token</th>
                        <th class="py-3.5 px-6">Date & Time</th>
                        <th class="py-3.5 px-6">Patient (EMR)</th>
                        <th class="py-3.5 px-6">Doctor & Room</th>
                        <th class="py-3.5 px-6">Chief Complaint / Reason</th>
                        <th class="py-3.5 px-6">Queue Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($appointments)): ?>
                        <?php foreach ($appointments as $apt): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6">
                                    <span class="w-8 h-8 rounded-xl bg-teal-50 border border-teal-200 text-teal-700 font-black flex items-center justify-center text-xs shadow-sm">
                                        #<?= $apt['token_number'] ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 font-mono">
                                    <span class="font-bold text-slate-800 block"><?= formatDate($apt['appointment_date']) ?></span>
                                    <span class="text-[11px] text-slate-400"><?= date('h:i A', strtotime($apt['appointment_time'])) ?></span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <a href="<?= APP_URL ?>/modules/patients/view.php?id=<?= $apt['patient_id'] ?>" class="font-bold text-slate-900 hover:text-teal-600 block">
                                        <?= e($apt['patient_name']) ?>
                                    </a>
                                    <span class="text-[11px] text-slate-400 font-mono"><?= e($apt['mrn']) ?> &bull; <?= e($apt['gender']) ?></span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="font-bold text-slate-800 block"><?= e($apt['doctor_name']) ?></span>
                                    <span class="text-[11px] text-teal-600 font-semibold"><?= e($apt['specialization']) ?> (<?= e($apt['room_no'] ?: 'OPD') ?>)</span>
                                </td>
                                <td class="py-3.5 px-6 max-w-xs truncate">
                                    <?= e($apt['reason'] ?: 'Routine Consultation') ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <?php 
                                        $badgeClass = match($apt['status']) {
                                            'Waiting' => 'badge-warning',
                                            'In-Consultation' => 'badge-info',
                                            'Completed' => 'badge-success',
                                            'Cancelled' => 'badge-danger',
                                            default => 'badge-primary'
                                        };
                                    ?>
                                    <div class="relative group inline-block">
                                        <span class="badge <?= $badgeClass ?> cursor-pointer">
                                            <?= e($apt['status']) ?> <i class="fa-solid fa-chevron-down ml-1 text-[9px]"></i>
                                        </span>
                                        <!-- Quick Status Dropdown -->
                                        <div class="absolute left-0 mt-1 w-36 bg-white rounded-xl shadow-lg border border-slate-100 py-1 hidden group-hover:block z-20">
                                            <a href="update_status.php?id=<?= $apt['id'] ?>&status=Waiting" class="block px-3 py-1 text-[11px] text-amber-700 hover:bg-amber-50">Mark Waiting</a>
                                            <a href="update_status.php?id=<?= $apt['id'] ?>&status=In-Consultation" class="block px-3 py-1 text-[11px] text-sky-700 hover:bg-sky-50">In-Consultation</a>
                                            <a href="update_status.php?id=<?= $apt['id'] ?>&status=Completed" class="block px-3 py-1 text-[11px] text-emerald-700 hover:bg-emerald-50">Completed</a>
                                            <a href="update_status.php?id=<?= $apt['id'] ?>&status=Cancelled" class="block px-3 py-1 text-[11px] text-rose-700 hover:bg-rose-50">Cancelled</a>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-6 text-right whitespace-nowrap space-x-1">
                                    <?php if (hasRole(['admin', 'doctor'])): ?>
                                        <a href="<?= APP_URL ?>/modules/opd/consultation.php?appointment_id=<?= $apt['id'] ?>" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shadow-sm transition">
                                            <i class="fa-solid fa-stethoscope mr-1"></i> Consult
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= APP_URL ?>/modules/patients/view.php?id=<?= $apt['patient_id'] ?>" class="inline-flex items-center p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs transition" title="Patient Profile">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-calendar-xmark text-4xl mb-3 block"></i>
                                No appointments found for the selected date and filters.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
