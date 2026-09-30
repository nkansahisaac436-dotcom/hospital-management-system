<?php
/**
 * CarePoint Pro HMS - Logged-in User Account Profile Settings
 */

$pageTitle = 'My Profile & Security';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$userId = (int)currentUser()['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($fullName) || empty($email)) {
        $error = 'Full name and email are required.';
    } else {
        try {
            $pdo->beginTransaction();

            if (!empty($newPassword)) {
                if (empty($currentPassword) || !password_verify($currentPassword, $user['password_hash'])) {
                    throw new Exception("Current password verification failed.");
                }
                if ($newPassword !== $confirmPassword) {
                    throw new Exception("New passwords do not match.");
                }
                $pwdHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $uStmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?");
                $uStmt->execute([$fullName, $email, $phone, $pwdHash, $userId]);
            } else {
                $uStmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $uStmt->execute([$fullName, $email, $phone, $userId]);
            }

            // Update active session variables
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;

            $pdo->commit();
            logActivity('Profile Updated', "Updated personal profile settings", $userId);
            setFlash('success', 'Profile and security settings updated successfully.');
            header('Location: profile.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">My Account & Security Profile</h1>
        <p class="text-xs text-slate-500">Manage your personal credentials, contact info, and security password.</p>
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
            <div class="flex items-center space-x-4 border-b border-slate-100 pb-5">
                <div class="w-16 h-16 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-2xl font-black shadow-md">
                    <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900"><?= e($user['full_name']) ?></h3>
                    <p class="text-xs text-slate-500 font-mono">@<?= e($user['username']) ?> &bull; <span class="badge badge-primary text-[10px]"><?= strtoupper(e($user['role'])) ?></span></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Address *</label>
                    <input type="email" name="email" value="<?= e($user['email']) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number</label>
                    <input type="tel" name="phone" value="<?= e($user['phone']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>
            </div>

            <!-- Password Change Section -->
            <div class="pt-6 border-t border-slate-100 space-y-4">
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-key text-teal-600 mr-2"></i> Update Security Password
                </h4>
                <p class="text-[11px] text-slate-400">Leave these fields blank if you do not wish to change your password.</p>

                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Current Password</label>
                        <input type="password" name="current_password" placeholder="Enter current password..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">New Password</label>
                            <input type="password" name="new_password" placeholder="Enter new password..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="Re-type new password..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <button type="submit" class="px-8 py-3.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-floppy-disk mr-2"></i> Save Profile Settings
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
