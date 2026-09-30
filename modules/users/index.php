<?php
/**
 * CarePoint Pro HMS - Staff & HR User Management
 */

$pageTitle = 'Staff & User Management';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth(['admin']);

$pdo = Database::getConnection();

$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT u.*, 
        d.specialization, d.consultation_fee, dep.name as department_name
        FROM users u
        LEFT JOIN doctors d ON u.id = d.user_id
        LEFT JOIN departments dep ON d.department_id = dep.id
        WHERE 1=1";
$params = [];

if (!empty($roleFilter)) {
    $sql .= " AND u.role = ?";
    $params[] = $roleFilter;
}

if (!empty($statusFilter)) {
    $sql .= " AND u.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

$sql .= " ORDER BY u.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Handle Quick Status Toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    if ($userId !== (int)currentUser()['id']) {
        $uStmt = $pdo->prepare("SELECT status, full_name FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $uRow = $uStmt->fetch();
        if ($uRow) {
            $newStatus = ($uRow['status'] === 'active') ? 'inactive' : 'active';
            $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $userId]);
            logActivity('User Status Changed', "Changed status of {$uRow['full_name']} to {$newStatus}");
            setFlash('success', "Status for <strong>{$uRow['full_name']}</strong> updated to " . strtoupper($newStatus));
        }
    }
    header('Location: index.php');
    exit;
}
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Hospital Staff & User Directory</h1>
            <p class="text-xs text-slate-500 mt-1">Manage clinician profiles, administrative credentials, role permissions, and access status.</p>
        </div>
        <div>
            <a href="add.php" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center">
                <i class="fa-solid fa-user-plus mr-1.5"></i> Add New Staff Member
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-wrap gap-1.5">
            <a href="index.php" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= empty($roleFilter) ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                All Staff
            </a>
            <a href="index.php?role=doctor" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $roleFilter === 'doctor' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Doctors
            </a>
            <a href="index.php?role=nurse" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $roleFilter === 'nurse' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Nurses
            </a>
            <a href="index.php?role=pharmacist" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $roleFilter === 'pharmacist' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Pharmacists
            </a>
            <a href="index.php?role=labtech" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $roleFilter === 'labtech' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Lab Techs
            </a>
            <a href="index.php?role=reception" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $roleFilter === 'reception' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Receptionists
            </a>
            <a href="index.php?role=accountant" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $roleFilter === 'accountant' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Accountants
            </a>
        </div>

        <form method="GET" action="" class="flex items-center space-x-2">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, email, phone..." class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-bold">Search</button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Staff Member</th>
                        <th class="py-3.5 px-6">Assigned Role</th>
                        <th class="py-3.5 px-6">Department / Clinical Scope</th>
                        <th class="py-3.5 px-6">Phone / Contact</th>
                        <th class="py-3.5 px-6">Account Status</th>
                        <th class="py-3.5 px-6">Last Login</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $u): ?>
                            <?php
                                $roleBadge = match($u['role']) {
                                    'admin' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    'doctor' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'nurse' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'reception' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'pharmacist' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'labtech' => 'bg-teal-50 text-teal-700 border-teal-200',
                                    'accountant' => 'bg-green-50 text-green-700 border-green-200',
                                    'patient' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200'
                                };
                            ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center font-bold text-slate-700 uppercase">
                                            <?= strtoupper(substr($u['full_name'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <strong class="text-slate-900 text-sm block"><?= e($u['full_name']) ?></strong>
                                            <span class="text-[11px] text-slate-400 font-mono">@<?= e($u['username']) ?> &bull; <?= e($u['email']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $roleBadge ?>">
                                        <?= strtoupper(e($u['role'])) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <?php if ($u['role'] === 'doctor'): ?>
                                        <span class="font-bold text-slate-800 block"><?= e($u['specialization'] ?: 'General Practice') ?></span>
                                        <span class="text-[11px] text-slate-400"><?= e($u['department_name'] ?: 'Clinical Services') ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-500">General Operations</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-6 font-mono text-slate-700">
                                    <?= e($u['phone'] ?: 'N/A') ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <a href="index.php?toggle_status=1&id=<?= $u['id'] ?>" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold <?= $u['status'] === 'active' ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' ?> transition" title="Click to toggle status">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $u['status'] === 'active' ? 'bg-emerald-500' : 'bg-rose-500' ?> mr-1.5"></span>
                                        <?= ucfirst(e($u['status'])) ?>
                                    </a>
                                </td>
                                <td class="py-3.5 px-6 font-mono text-[11px] text-slate-400">
                                    <?= !empty($u['last_login']) ? formatDateTime($u['last_login']) : 'Never' ?>
                                </td>
                                <td class="py-3.5 px-6 text-right space-x-1 whitespace-nowrap">
                                    <a href="edit.php?id=<?= $u['id'] ?>" class="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs transition" title="Edit Staff Profile">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-user-group text-4xl mb-3 block"></i>
                                No staff accounts found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
