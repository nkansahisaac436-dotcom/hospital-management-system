<?php
/**
 * CarePoint Pro HMS - New Patient Registration
 */

$pageTitle = 'Register New Patient';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$generatedMRN = generateMRN();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $mrn = trim($_POST['mrn'] ?? $generatedMRN);
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
    $createPortalAccount = isset($_POST['create_portal_account']);

    if (empty($fullName) || empty($phone) || empty($dob)) {
        $error = 'Please fill in all mandatory fields (Full Name, Phone Number, Date of Birth).';
    } else {
        try {
            $pdo->beginTransaction();

            $userId = null;
            if ($createPortalAccount && !empty($email)) {
                $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0])) . rand(10, 99);
                $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
                $uStmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, role, status) VALUES (?, ?, ?, ?, ?, 'patient', 'active')");
                $uStmt->execute([$username, $passwordHash, $fullName, $email, $phone]);
                $userId = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("INSERT INTO patients (user_id, mrn, full_name, email, phone, gender, date_of_birth, blood_group, address, emergency_contact_name, emergency_contact_phone, emergency_contact_relation, allergies, chronic_diseases) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $mrn, $fullName, $email, $phone, $gender, $dob, $bloodGroup, $address, $emergencyName, $emergencyPhone, $emergencyRelation, $allergies, $chronicDiseases]);
            $patientId = $pdo->lastInsertId();

            $pdo->commit();
            logActivity('Patient Registered', "Registered {$fullName} (MRN: {$mrn})");
            setFlash('success', "Patient <strong>{$fullName}</strong> successfully registered with MRN: <code>{$mrn}</code>");
            header("Location: view.php?id={$patientId}");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to register patient: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Patients Directory
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Register New Patient</h1>
            <p class="text-xs text-slate-500">Create a permanent Electronic Medical Record (EMR) profile.</p>
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

        <!-- Section 1: Demographics -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-id-card text-teal-600 mr-2"></i> Patient Identification & Demographics
                    </h3>
                </div>
                <span class="text-xs bg-slate-100 text-slate-600 px-3 py-1 rounded-full font-mono font-bold">Auto MRN: <?= e($generatedMRN) ?></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Medical Record Number (MRN) *</label>
                    <input type="text" name="mrn" value="<?= e($generatedMRN) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold text-teal-700 focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name (First, Middle, Last) *</label>
                    <input type="text" name="full_name" required placeholder="e.g. Johnathan Doe" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Gender *</label>
                    <select name="gender" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Date of Birth *</label>
                    <input type="date" name="date_of_birth" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Blood Group</label>
                    <select name="blood_group" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="Unknown">Unknown / Not Tested</option>
                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                            <option value="<?= $bg ?>"><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number *</label>
                    <input type="tel" name="phone" required placeholder="e.g. +1 555-0199" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Address (Optional)</label>
                    <input type="email" name="email" placeholder="patient@example.com" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Residential Home Address</label>
                    <textarea name="address" rows="2" placeholder="Street Address, City, State, Postal Code" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                </div>
            </div>
        </div>

        <!-- Section 2: Clinical Medical History & Allergies -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-notes-medical text-rose-600 mr-2"></i> Clinical Profile, Allergies & Chronic Conditions
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Known Allergies (Drugs, Foods, Latex, etc.)</label>
                    <textarea name="allergies" rows="3" placeholder="e.g. Penicillin, Aspirin, Peanuts, Shellfish..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:bg-white focus:outline-none"></textarea>
                    <span class="text-[11px] text-slate-400">Critical safety alert will be highlighted across all consultations and pharmacy dispensing.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Known Chronic Diseases / Medical History</label>
                    <textarea name="chronic_diseases" rows="3" placeholder="e.g. Type 2 Diabetes, Hypertension, Asthma, Epilepsy..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                    <span class="text-[11px] text-slate-400">Pre-existing clinical background for doctors and specialists.</span>
                </div>
            </div>
        </div>

        <!-- Section 3: Emergency Contact -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-phone-volume text-sky-600 mr-2"></i> Emergency Next-of-Kin Contact
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" placeholder="e.g. Jane Doe" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Emergency Contact Phone</label>
                    <input type="tel" name="emergency_contact_phone" placeholder="e.g. +1 555-0198" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Relationship to Patient</label>
                    <input type="text" name="emergency_contact_relation" placeholder="e.g. Spouse, Parent, Sibling, Child" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none">
                </div>
            </div>

            <!-- Portal Account Checkbox -->
            <div class="pt-4 border-t border-slate-100 flex items-center space-x-3">
                <input type="checkbox" name="create_portal_account" id="create_portal_account" value="1" class="w-4 h-4 text-teal-600 rounded border-slate-300 focus:ring-teal-500">
                <label for="create_portal_account" class="text-xs font-bold text-slate-800 cursor-pointer">
                    Create Patient Portal Online Login Account
                    <span class="block text-[11px] font-normal text-slate-400">Allows patient to sign in, view prescriptions, test results, and book appointments. (Default password: <code>password123</code>)</span>
                </label>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end space-x-3">
            <a href="index.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-floppy-disk mr-2"></i> Save & Register Patient
            </button>
        </div>

    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
