<?php
/**
 * CarePoint Pro HMS - Add Hospital Staff Account
 */

$pageTitle = 'Add New Staff Member';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth(['admin']);

$pdo = Database::getConnection();
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'nurse';
    $password = $_POST['password'] ?? 'password123';
    $gender = $_POST['gender'] ?? 'Male';
    $address = trim($_POST['address'] ?? '');

    // Doctor specific fields
    $deptId = (int)($_POST['department_id'] ?? 0);
    $specialization = trim($_POST['specialization'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $consultationFee = (float)($_POST['consultation_fee'] ?? 50.00);

    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        $error = 'Full name, username, email, and password are required.';
    } else {
        try {
            // Check username or email uniqueness
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $checkStmt->execute([$username, $email]);
            if ($checkStmt->fetch()) {
                throw new Exception("Username '@{$username}' or email '{$email}' already exists.");
            }

            $pdo->beginTransaction();

            $pwdHash = password_hash($password, PASSWORD_BCRYPT);
            $uStmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $uStmt->execute([$username, $pwdHash, $fullName, $email, $phone, $role]);
            $newUserId = $pdo->lastInsertId();

            // If doctor role, create linked doctor profile
            if ($role === 'doctor' && $deptId > 0) {
                $docStmt = $pdo->prepare("INSERT INTO doctors (user_id, department_id, specialization, qualification, consultation_fee, available_days) VALUES (?, ?, ?, ?, ?, 'Mon, Tue, Wed, Thu, Fri')");
                $docStmt->execute([$newUserId, $deptId, $specialization, $qualification, $consultationFee]);
            }

            $pdo->commit();
            logActivity('User Created', "Created staff account {$fullName} as {$role}", $newUserId);
            setFlash('success', "Staff account for <strong>{$fullName}</strong> created successfully.");
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to create staff account: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Staff Directory
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Add New Hospital Staff Member</h1>
        <p class="text-xs text-slate-500">Register clinicians, nurses, pharmacists, laboratory scientists, and operational staff.</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-6">
        <?= csrfField() ?>

        <!-- Basic User Credentials -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-user-shield text-teal-600 mr-2"></i> Account Identity & Credentials
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" required placeholder="e.g. Dr. Jennifer Adams" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Username *</label>
                    <input type="text" name="username" required placeholder="e.g. jadams" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hospital Role *</label>
                    <select name="role" id="role-selector" onchange="toggleDoctorFields(this.value)" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="doctor">Doctor / Physician</option>
                        <option value="nurse">Nurse / Matron</option>
                        <option value="reception">Receptionist / Front Desk</option>
                        <option value="pharmacist">Pharmacist</option>
                        <option value="labtech">Laboratory Scientist</option>
                        <option value="accountant">Accountant / Cashier</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Official Email *</label>
                    <input type="email" name="email" required placeholder="jadams@hospital.com" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number</label>
                    <input type="tel" name="phone" placeholder="+1 (555) 019-2834" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Initial Password *</label>
                    <input type="password" name="password" value="password123" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                    <span class="text-[11px] text-slate-400 mt-1 block">Default: <code>password123</code></span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Gender</label>
                    <select name="gender" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="Female">Female</option>
                        <option value="Male">Male</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Doctor Clinical Specifics (Toggleable) -->
        <div id="doctor-fields-card" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-user-doctor text-sky-600 mr-2"></i> Doctor Clinical Scope & Tariffs
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Clinical Department</label>
                    <select name="department_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none">
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= e($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Specialization Title</label>
                    <input type="text" name="specialization" placeholder="e.g. Senior Consultant Cardiologist" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Qualifications & Degrees</label>
                    <input type="text" name="qualification" placeholder="e.g. MBBS, MD, FRCP" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">OPD Consultation Fee ($)</label>
                    <input type="number" step="0.01" name="consultation_fee" value="50.00" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-emerald-700 focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none font-mono">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="index.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-user-plus mr-2"></i> Register Staff Account
            </button>
        </div>
    </form>

</div>

<script>
function toggleDoctorFields(role) {
    const card = document.getElementById('doctor-fields-card');
    if (role === 'doctor') {
        card.classList.remove('hidden');
    } else {
        card.classList.add('hidden');
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
