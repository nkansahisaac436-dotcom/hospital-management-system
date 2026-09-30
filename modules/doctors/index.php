<?php
/**
 * CarePoint Pro HMS - Doctors Directory & Medical Staff
 */

$pageTitle = 'Doctors & Medical Staff';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$deptFilter = (int)($_GET['department'] ?? 0);

$sql = "SELECT d.*, u.full_name, u.email, u.phone, u.avatar, dep.name as department_name, dep.icon as dept_icon,
        (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id) as total_appointments
        FROM doctors d
        JOIN users u ON d.user_id = u.id
        JOIN departments dep ON d.department_id = dep.id
        WHERE d.status != 'inactive'";
$params = [];

if ($deptFilter > 0) {
    $sql .= " AND d.department_id = ?";
    $params[] = $deptFilter;
}

$sql .= " ORDER BY dep.name ASC, u.full_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

// Get departments for filter
$departments = $pdo->query("SELECT * FROM departments WHERE status = 'active'")->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Doctors & Specialists</h1>
            <p class="text-xs text-slate-500 mt-1">Medical staff directory, specializations, consulting hours, and consultation fees.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="departments.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                <i class="fa-solid fa-sitemap mr-1.5 text-teal-600"></i> Manage Departments
            </a>
            <?php if (hasRole('admin')): ?>
                <a href="add.php" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                    <i class="fa-solid fa-user-doctor mr-1.5"></i> Add Doctor Profile
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Department Quick Filter Tabs -->
    <div class="flex overflow-x-auto space-x-2 pb-2">
        <a href="index.php" class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $deptFilter === 0 ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' ?>">
            All Departments (<?= count($doctors) ?>)
        </a>
        <?php foreach ($departments as $dept): ?>
            <a href="?department=<?= $dept['id'] ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $deptFilter === (int)$dept['id'] ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' ?>">
                <i class="fa-solid <?= e($dept['icon']) ?> mr-1 text-slate-400"></i> <?= e($dept['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Doctors Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($doctors)): ?>
            <?php foreach ($doctors as $doc): ?>
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm card-hover flex flex-col justify-between space-y-4">
                    
                    <div>
                        <!-- Top Department Pill & Status -->
                        <div class="flex items-center justify-between mb-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                <i class="fa-solid <?= e($doc['dept_icon']) ?> mr-1.5"></i> <?= e($doc['department_name']) ?>
                            </span>
                            <span class="badge <?= $doc['status'] === 'active' ? 'badge-success' : 'badge-warning' ?>">
                                <?= e(ucfirst(str_replace('_', ' ', $doc['status']))) ?>
                            </span>
                        </div>

                        <!-- Doctor Info -->
                        <div class="flex items-start space-x-4">
                            <div class="w-14 h-14 rounded-2xl bg-teal-50 border border-teal-200 text-teal-700 flex items-center justify-center text-2xl font-black shadow-inner shrink-0">
                                <i class="fa-solid fa-user-doctor"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-base leading-snug"><?= e($doc['full_name']) ?></h3>
                                <p class="text-xs text-teal-600 font-medium mt-0.5"><?= e($doc['specialization']) ?></p>
                                <p class="text-[11px] text-slate-400 mt-1"><i class="fa-solid fa-award mr-1"></i> <?= $doc['experience_years'] ?>+ Years Experience</p>
                            </div>
                        </div>

                        <!-- Schedule & Room -->
                        <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2.5 bg-slate-50 rounded-xl">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Consulting Room</span>
                                <span class="font-bold text-slate-800"><?= e($doc['room_no'] ?: 'Room TBD') ?></span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-xl">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Fee / Consultation</span>
                                <span class="font-bold text-emerald-600"><?= formatMoney($doc['consultation_fee']) ?></span>
                            </div>
                        </div>

                        <!-- Shift Times -->
                        <div class="mt-2 text-[11px] text-slate-500 flex items-center space-x-2">
                            <i class="fa-solid fa-calendar-days text-slate-400"></i>
                            <span><?= e($doc['schedule_days']) ?> &bull; <?= date('h:i A', strtotime($doc['schedule_time_start'])) ?> - <?= date('h:i A', strtotime($doc['schedule_time_end'])) ?></span>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-400 font-medium"><?= $doc['total_appointments'] ?> Consultations</span>
                        <a href="<?= APP_URL ?>/modules/appointments/book.php?doctor_id=<?= $doc['id'] ?>" class="px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                            <i class="fa-solid fa-calendar-plus mr-1"></i> Book Appt
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full bg-white rounded-3xl p-12 text-center text-slate-400 border border-slate-200">
                <i class="fa-solid fa-user-doctor text-4xl mb-3 block"></i>
                No doctor profiles found in this department.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
