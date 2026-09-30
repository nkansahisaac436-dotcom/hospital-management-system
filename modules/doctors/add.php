<?php
/**
 * CarePoint Pro HMS - Add Doctor Profile
 */

$pageTitle = 'Add Doctor Profile';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth(['admin']);

$pdo = Database::getConnection();
$error = null;

// Fetch departments
$departments = $pdo->query("SELECT * FROM departments WHERE status = 'active' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $specialization = trim($_POST['specialization'] ?? '');
    $fee = (float)($_POST['consultation_fee'] ?? 50.00);
    $roomNo = trim($_POST['room_no'] ?? '');
    $experience = (int)($_POST['experience_years'] ?? 5);
    $scheduleDays = implode(',', $_POST['schedule_days'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']);
    $timeStart = $_POST['schedule_time_start'] ?? '09:00';
    $timeEnd = $_POST['schedule_time_end'] ?? '17:00';
    $bio = trim($_POST['bio'] ?? '');

    if (empty($fullName) || empty($email) || empty($departmentId) || empty($specialization)) {
        $error = 'Please fill in all mandatory fields.';
    } else {
        try {
            $pdo->beginTransaction();

            $username = 'dr.' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0])) . rand(10, 99);
            $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
            
            $uStmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, role, status) VALUES (?, ?, ?, ?, ?, 'doctor', 'active')");
            $uStmt->execute([$username, $passwordHash, $fullName, $email, $phone]);
            $userId = $pdo->lastInsertId();

            $docStmt = $pdo->prepare("INSERT INTO doctors (user_id, department_id, specialization, consultation_fee, room_no, experience_years, bio, schedule_days, schedule_time_start, schedule_time_end, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $docStmt->execute([$userId, $departmentId, $specialization, $fee, $roomNo, $experience, $bio, $scheduleDays, $timeStart, $timeEnd]);

            $pdo->commit();
            logActivity('Doctor Profile Created', "Created doctor {$fullName} ({$username})");
            setFlash('success', "Doctor <strong>{$fullName}</strong> added successfully with username: <code>{$username}</code> (Default pass: <code>password123</code>)");
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to create doctor: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Doctors Directory
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Add Medical Specialist / Doctor</h1>
        <p class="text-xs text-slate-500">Create doctor profile and clinical consultation schedule.</p>
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
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-user-doctor text-teal-600 mr-2"></i> Doctor Credentials & Specialization
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Doctor Full Name (with Title) *</label>
                    <input type="text" name="full_name" required placeholder="e.g. Dr. Arthur Conan, MD" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Department *</label>
                    <select name="department_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= e($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Clinical Specialization & Degrees *</label>
                    <input type="text" name="specialization" required placeholder="e.g. Senior Consultant Neurologist & Spine Specialist" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Experience (Years)</label>
                    <input type="number" name="experience_years" value="8" min="0" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Address *</label>
                    <input type="email" name="email" required placeholder="doctor@carepoint.com" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number</label>
                    <input type="tel" name="phone" placeholder="+1 555-0199" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Consultation Room / OPD</label>
                    <input type="text" name="room_no" placeholder="e.g. Room 204 - Wing B" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Consultation Fee ($)</label>
                    <input type="number" name="consultation_fee" value="65.00" step="0.01" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Shift Start Time</label>
                    <input type="time" name="schedule_time_start" value="09:00" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Shift End Time</label>
                    <input type="time" name="schedule_time_end" value="17:00" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="index.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-user-plus mr-2"></i> Save Doctor Profile
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
