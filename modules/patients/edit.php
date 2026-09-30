<?php
/**
 * CarePoint Pro HMS - Edit Patient Information
 */

$pageTitle = 'Edit Patient Record';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$patientId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    setFlash('error', 'Patient record not found.');
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'Male';
    $dob = $_POST['date_of_birth'] ?? '';
    $bloodGroup = $_POST['blood_group'] ?? 'Unknown';
    $address = trim($_POST['address'] ?? '');
    $emergencyName = trim($_POST['emergency_contact_name'] ?? '');
    $emergencyPhone = trim($_POST['emergency_contact_phone'] ?? '');
    $emergencyRelation = trim($_POST['emergency_contact_relation'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');
    $chronicDiseases = trim($_POST['chronic_diseases'] ?? '');

    if (empty($fullName) || empty($phone) || empty($dob)) {
        $error = 'Please fill in all mandatory fields (Full Name, Phone Number, Date of Birth).';
    } else {
        try {
            $uStmt = $pdo->prepare("UPDATE patients SET full_name = ?, email = ?, phone = ?, gender = ?, date_of_birth = ?, blood_group = ?, address = ?, emergency_contact_name = ?, emergency_contact_phone = ?, emergency_contact_relation = ?, allergies = ?, chronic_diseases = ? WHERE id = ?");
            $uStmt->execute([$fullName, $email, $phone, $gender, $dob, $bloodGroup, $address, $emergencyName, $emergencyPhone, $emergencyRelation, $allergies, $chronicDiseases, $patientId]);

            logActivity('Patient Updated', "Updated EMR for {$fullName} (MRN: {$patient['mrn']})");
            setFlash('success', "Patient record for <strong>{$fullName}</strong> updated successfully.");
            header("Location: view.php?id={$patientId}");
            exit;
        } catch (Exception $e) {
            $error = 'Failed to update patient: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <a href="view.php?id=<?= $patient['id'] ?>" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Patient Profile
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Patient: <?= e($patient['full_name']) ?></h1>
            <p class="text-xs text-slate-500 font-mono">MRN: <?= e($patient['mrn']) ?></p>
        </div>
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
                    <i class="fa-solid fa-user-pen text-teal-600 mr-2"></i> Demographic Information
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" value="<?= e($patient['full_name']) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Gender *</label>
                    <select name="gender" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="Male" <?= $patient['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $patient['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $patient['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Date of Birth *</label>
                    <input type="date" name="date_of_birth" value="<?= e($patient['date_of_birth']) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Blood Group</label>
                    <select name="blood_group" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="Unknown" <?= $patient['blood_group'] === 'Unknown' ? 'selected' : '' ?>>Unknown</option>
                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= $patient['blood_group'] === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number *</label>
                    <input type="tel" name="phone" value="<?= e($patient['phone']) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Address</label>
                    <input type="email" name="email" value="<?= e($patient['email']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Residential Address</label>
                    <textarea name="address" rows="2" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"><?= e($patient['address']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-heart-pulse text-rose-600 mr-2"></i> Clinical Profile & Emergency
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Allergies</label>
                    <textarea name="allergies" rows="3" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:bg-white focus:outline-none"><?= e($patient['allergies']) ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Chronic Diseases</label>
                    <textarea name="chronic_diseases" rows="3" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"><?= e($patient['chronic_diseases']) ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" value="<?= e($patient['emergency_contact_name']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Emergency Contact Phone</label>
                    <input type="tel" name="emergency_contact_phone" value="<?= e($patient['emergency_contact_phone']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="view.php?id=<?= $patient['id'] ?>" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-floppy-disk mr-2"></i> Update Patient Records
            </button>
        </div>

    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
