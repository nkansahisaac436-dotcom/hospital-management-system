<?php
/**
 * CarePoint Pro HMS - Patients Directory & Master EMR
 */

$pageTitle = 'Patients Directory & EMR';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

// Search & Filter Parameters
$search = trim($_GET['search'] ?? '');
$bloodGroup = trim($_GET['blood_group'] ?? '');
$gender = trim($_GET['gender'] ?? '');

$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM appointments a WHERE a.patient_id = p.id) as total_visits,
        (SELECT MAX(appointment_date) FROM appointments a WHERE a.patient_id = p.id) as last_visit
        FROM patients p WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.full_name LIKE ? OR p.mrn LIKE ? OR p.phone LIKE ? OR p.email LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

if (!empty($bloodGroup)) {
    $sql .= " AND p.blood_group = ?";
    $params[] = $bloodGroup;
}

if (!empty($gender)) {
    $sql .= " AND p.gender = ?";
    $params[] = $gender;
}

$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Patient Directory & EMR</h1>
            <p class="text-xs text-slate-500 mt-1">Master Electronic Medical Records (EMR) and Patient Profiles</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= APP_URL ?>/modules/patients/add.php" class="inline-flex items-center px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-user-plus mr-2"></i> Register New Patient
            </a>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by Patient Name, MRN, Phone Number..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none transition">
                </div>
            </div>

            <div>
                <select name="blood_group" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none transition">
                    <option value="">All Blood Groups</option>
                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                        <option value="<?= $bg ?>" <?= $bloodGroup === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <select name="gender" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none transition">
                    <option value="">All Genders</option>
                    <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                    Filter
                </button>
                <?php if (!empty($search) || !empty($bloodGroup) || !empty($gender)): ?>
                    <a href="index.php" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs transition" title="Clear Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Patients List Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Patient MRN</th>
                        <th class="py-3.5 px-6">Full Name & Demographics</th>
                        <th class="py-3.5 px-6">Contact / Phone</th>
                        <th class="py-3.5 px-6">Blood Group</th>
                        <th class="py-3.5 px-6">Total Visits</th>
                        <th class="py-3.5 px-6">Last Visit</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($patients)): ?>
                        <?php foreach ($patients as $p): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6 font-mono font-bold text-teal-700">
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-teal-50 border border-teal-200 text-xs">
                                        <?= e($p['mrn']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <a href="view.php?id=<?= $p['id'] ?>" class="font-bold text-slate-900 hover:text-teal-600 text-sm block">
                                        <?= e($p['full_name']) ?>
                                    </a>
                                    <span class="text-[11px] text-slate-400">
                                        <?= e($p['gender']) ?> &bull; <?= calculateAge($p['date_of_birth']) ?> (DOB: <?= formatDate($p['date_of_birth']) ?>)
                                    </span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="font-bold text-slate-800 block"><i class="fa-solid fa-phone text-slate-400 mr-1 text-[10px]"></i> <?= e($p['phone']) ?></span>
                                    <span class="text-[11px] text-slate-400 truncate max-w-[160px] block"><?= e($p['email'] ?: 'No email on file') ?></span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold <?= $p['blood_group'] !== 'Unknown' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-600' ?>">
                                        <i class="fa-solid fa-droplet mr-1 text-[10px] text-rose-500"></i> <?= e($p['blood_group']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="font-bold text-slate-700"><?= $p['total_visits'] ?></span> visits
                                </td>
                                <td class="py-3.5 px-6 font-mono text-slate-500">
                                    <?= formatDate($p['last_visit']) ?>
                                </td>
                                <td class="py-3.5 px-6 text-right space-x-1 whitespace-nowrap">
                                    <a href="view.php?id=<?= $p['id'] ?>" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-700 font-bold text-xs transition">
                                        <i class="fa-solid fa-folder-open mr-1"></i> EMR Record
                                    </a>
                                    <a href="edit.php?id=<?= $p['id'] ?>" class="inline-flex items-center p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs transition" title="Edit Patient">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-user-xmark text-4xl mb-3 block"></i>
                                No patient records found matching the query.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
