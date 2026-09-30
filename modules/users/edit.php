<?php
/**
 * CarePoint Pro HMS - Edit Staff Member & Security Controls
 */

$pageTitle = 'Edit Staff Member';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth(['admin']);

$pdo = Database::getConnection();
$userId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT u.*, d.department_id, d.specialization, d.qualification, d.consultation_fee
                       FROM users u
                       LEFT JOIN doctors d ON u.id = d.user_id
                       WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'Staff member not found.');
    header('Location: index.php');
    exit;
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? $user['role'];
    $status = $_POST['status'] ?? $user['status'];
    $newPassword = $_POST['new_password'] ?? '';

    // Doctor specific fields
    $deptId = (int)($_POST['department_id'] ?? 0);
    $specialization = trim($_POST['specialization'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $consultationFee = (float)($_POST['consultation_fee'] ?? 50.00);

    if (empty($fullName) || empty($email)) {
        $error = 'Full name and email are required.';
    } else {
        try {
            $pdo->beginTransaction();

            if (!empty($newPassword)) {
                $pwdHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $uStmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, status = ?, password_hash = ? WHERE id = ?");
                $uStmt->execute([$fullName, $email, $phone, $role, $status, $pwdHash, $userId]);
            } else {
                $uStmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?");
                $uStmt->execute([$fullName, $email, $phone, $role, $status, $userId]);
            }

            if ($role === 'doctor') {
                $dCheck = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
                $dCheck->execute([$userId]);
                if ($dCheck->fetch()) {
                    $pdo->prepare("UPDATE doctors SET department_id = ?, specialization = ?, qualification = ?, consultation_fee = ? WHERE user_id = ?")->execute([$deptId, $specialization, $qualification, $consultationFee, $userId]);
                } else {
                    $pdo->prepare("INSERT INTO doctors (user_id, department_id, specialization, qualification, consultation_fee, available_days) VALUES (?, ?, ?, ?, ?, 'Mon, Tue, Wed, Thu, Fri')")->execute([$userId, $deptId, $specialization, $qualification, $consultationFee]);
                }
            }

            $pdo->commit();
            logActivity('User Updated', "Updated staff account {$fullName}", $userId);
            setFlash('success', "Staff details for <strong>{$fullName}</strong> updated successfully.");
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to update staff record: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Staff Directory
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Staff Profile: <?= e($user['full_name']) ?></h1>
        <p class="text-xs text-slate-500">Update account credentials, clinical designations, and security status.</p>
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
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-user-pen text-teal-600 mr-2"></i> Account Identity
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Username (Read-Only)</label>
                    <input type="text" value="<?= e($user['username']) ?>" readonly class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-mono font-bold text-slate-500 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Official Email *</label>
                    <input type="email" name="email" value="<?= e($user['email']) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number</label>
                    <input type="tel" name="phone" value="<?= e($user['phone']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hospital Role</label>
                    <select name="role" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <?php foreach (['doctor' => 'Doctor / Physician', 'nurse' => 'Nurse', 'reception' => 'Receptionist', 'pharmacist' => 'Pharmacist', 'labtech' => 'Laboratory Scientist', 'accountant' => 'Accountant', 'admin' => 'Administrator'] as $rKey => $rLabel): ?>
                            <option value="<?= $rKey ?>" <?= $user['role'] === $rKey ? 'selected' : '' ?>><?= $rLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Account Status</label>
                    <select name="status" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active (Full Access)</option>
                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Suspended)</option>
                    </select>
                </div>

                <div class="sm:col-span-2 pt-2 border-t border-slate-100">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Reset Password (Leave blank to keep unchanged)</label>
                    <input type="password" name="new_password" placeholder="Enter new password to reset..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>
            </div>
        </div>

        <?php if ($user['role'] === 'doctor'): ?>
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-user-doctor text-sky-600 mr-2"></i> Doctor Clinical Scope & Tariffs
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Department</label>
                        <select name="department_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none">
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= ((int)$user['department_id'] === (int)$dept['id']) ? 'selected' : '' ?>><?= e($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Specialization</label>
                        <input type="text" name="specialization" value="<?= e($user['specialization'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Consultation Fee ($)</label>
                        <input type="number" step="0.01" name="consultation_fee" value="<?= e($user['consultation_fee'] ?? '50.00') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none">
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-end space-x-3">
            <a href="index.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-floppy-disk mr-2"></i> Save Changes
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
