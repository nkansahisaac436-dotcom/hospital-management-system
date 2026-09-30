<?php
/**
 * CarePoint Pro HMS - Hospital Departments Management
 */

$pageTitle = 'Medical Departments';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$error = null;

// Handle Add Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-stethoscope');

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO departments (name, description, icon, status) VALUES (?, ?, ?, 'active')");
            $stmt->execute([$name, $description, $icon]);
            logActivity('Department Created', "Added department: {$name}");
            setFlash('success', "Department <strong>{$name}</strong> created successfully.");
            header('Location: departments.php');
            exit;
        } catch (Exception $e) {
            $error = 'Failed to create department: ' . $e->getMessage();
        }
    } else {
        $error = 'Department name is required.';
    }
}

// Fetch all departments with doctor counts
$sql = "SELECT d.*, 
        (SELECT COUNT(*) FROM doctors doc WHERE doc.department_id = d.id AND doc.status = 'active') as doctor_count,
        (SELECT COUNT(*) FROM appointments a WHERE a.department_id = d.id) as appointment_count
        FROM departments d ORDER BY d.name ASC";
$departments = $pdo->query($sql)->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Doctors
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Clinical & Medical Departments</h1>
            <p class="text-xs text-slate-500 mt-1">Manage hospital specialized divisions and clinical units.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Add Department Form -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4 h-fit">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                <i class="fa-solid fa-folder-plus text-teal-600 mr-2"></i> Add New Department
            </h3>

            <form method="POST" action="" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="add_department" value="1">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Department Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Dermatology & Skin Care" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Icon (FontAwesome Class)</label>
                    <select name="icon" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="fa-stethoscope">Stethoscope (General)</option>
                        <option value="fa-heart-pulse">Heart Pulse (Cardiology)</option>
                        <option value="fa-baby">Baby (Pediatrics)</option>
                        <option value="fa-bone">Bone (Orthopedics)</option>
                        <option value="fa-brain">Brain (Neurology)</option>
                        <option value="fa-user-doctor">Doctor (Internal Med)</option>
                        <option value="fa-person-pregnant">Pregnancy (OB-GYN)</option>
                        <option value="fa-truck-medical">Ambulance (Emergency)</option>
                        <option value="fa-x-ray">X-Ray (Radiology)</option>
                        <option value="fa-eye">Eye (Ophthalmology)</option>
                        <option value="fa-tooth">Tooth (Dental)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Description & Scope</label>
                    <textarea name="description" rows="3" placeholder="Clinical scope of the department..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                </div>

                <button type="submit" class="w-full py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                    Create Department
                </button>
            </form>
        </div>

        <!-- Departments List -->
        <div class="lg:col-span-2 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($departments as $dept): ?>
                    <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm card-hover flex flex-col justify-between space-y-3">
                        <div>
                            <div class="flex items-center space-x-3 mb-2">
                                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg shadow-inner">
                                    <i class="fa-solid <?= e($dept['icon']) ?>"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm"><?= e($dept['name']) ?></h4>
                                    <span class="text-[11px] text-teal-600 font-semibold"><?= $dept['doctor_count'] ?> Doctor(s) assigned</span>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 line-clamp-2"><?= e($dept['description'] ?: 'Specialized hospital clinical unit.') ?></p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                            <span><?= $dept['appointment_count'] ?> Consultations</span>
                            <a href="index.php?department=<?= $dept['id'] ?>" class="text-teal-600 font-bold hover:underline">
                                View Doctors &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
