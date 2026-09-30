<?php
/**
 * CarePoint Pro HMS - Create Standalone Prescription
 */

$pageTitle = 'Write E-Prescription';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth(['admin', 'doctor']);

$pdo = Database::getConnection();
$preselectedPatientId = (int)($_GET['patient_id'] ?? 0);

$patients = $pdo->query("SELECT id, full_name, mrn, phone, allergies, date_of_birth FROM patients ORDER BY full_name ASC")->fetchAll();
$doctors = $pdo->query("SELECT d.id, u.full_name, dep.name as dept_name FROM doctors d JOIN users u ON d.user_id = u.id JOIN departments dep ON d.department_id = dep.id WHERE d.status = 'active' ORDER BY u.full_name ASC")->fetchAll();
$medicines = $pdo->query("SELECT id, name, generic_name, dosage_form, strength, stock_quantity FROM medicines WHERE status = 'active' ORDER BY name ASC")->fetchAll();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $patientId = (int)($_POST['patient_id'] ?? 0);
    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $advice = trim($_POST['advice'] ?? '');
    $followUpDate = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;

    $medNames = $_POST['med_name'] ?? [];
    $dosages = $_POST['dosage'] ?? [];
    $frequencies = $_POST['frequency'] ?? [];
    $durations = $_POST['duration'] ?? [];
    $instructions = $_POST['instruction'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if ($patientId <= 0 || $doctorId <= 0) {
        $error = 'Please select a valid patient and doctor.';
    } else {
        try {
            $pdo->beginTransaction();

            $rxNumber = generatePrescriptionNumber();
            $stmt = $pdo->prepare("INSERT INTO prescriptions (prescription_number, patient_id, doctor_id, diagnosis, advice, follow_up_date, status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
            $stmt->execute([$rxNumber, $patientId, $doctorId, $diagnosis, $advice, $followUpDate]);
            $rxId = $pdo->lastInsertId();

            if (!empty($medNames)) {
                $itemStmt = $pdo->prepare("INSERT INTO prescription_items (prescription_id, medicine_name, dosage, frequency, duration, instruction, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
                for ($i = 0; $i < count($medNames); $i++) {
                    if (!empty($medNames[$i])) {
                        $itemStmt->execute([$rxId, $medNames[$i], $dosages[$i] ?? '1 Tab', $frequencies[$i] ?? '1-0-1', $durations[$i] ?? '5 Days', $instructions[$i] ?? 'After Food', (int)($quantities[$i] ?? 1)]);
                    }
                }
            }

            $pdo->commit();
            logActivity('Prescription Created', "Created prescription {$rxNumber}");
            setFlash('success', "Prescription <strong>{$rxNumber}</strong> issued successfully.");
            header("Location: print.php?id={$rxId}");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to create prescription: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Prescriptions
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Issue New E-Prescription</h1>
        <p class="text-xs text-slate-500">Draft digital medical prescription with drug dosage schedule and instructions.</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-6">
        <?= csrfField() ?>

        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-user-doctor text-teal-600 mr-2"></i> Doctor & Patient
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Patient (EMR) *</label>
                    <select name="patient_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($preselectedPatientId === (int)$p['id']) ? 'selected' : '' ?>>
                                <?= e($p['full_name']) ?> (MRN: <?= e($p['mrn']) ?>) <?= !empty($p['allergies']) ? ' - Allergy: ' . e($p['allergies']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Prescribing Doctor *</label>
                    <select name="doctor_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Doctor --</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['full_name']) ?> (<?= e($d['dept_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Diagnosis / Medical Condition</label>
                    <input type="text" name="diagnosis" placeholder="e.g. Acute Bronchitis, Essential Hypertension" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Medicine Table -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-pills text-sky-600 mr-2"></i> Prescribed Medicines & Dosages
                </h3>
                <button type="button" onclick="addMedRow()" class="px-3 py-1.5 bg-sky-50 text-sky-700 rounded-xl text-xs font-bold hover:bg-sky-100 transition">
                    + Add Drug
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs" id="med-table">
                    <thead class="text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-2 px-2">Drug Name</th>
                            <th class="py-2 px-2 w-28">Dosage</th>
                            <th class="py-2 px-2 w-32">Frequency</th>
                            <th class="py-2 px-2 w-28">Duration</th>
                            <th class="py-2 px-2 w-36">Instruction</th>
                            <th class="py-2 px-2 w-20">Qty</th>
                            <th class="py-2 px-2 text-right w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="med-tbody">
                        <tr class="med-row">
                            <td class="py-2 px-2">
                                <input type="text" name="med_name[]" placeholder="e.g. Paracetamol 500mg" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
                            </td>
                            <td class="py-2 px-2">
                                <input type="text" name="dosage[]" value="1 Tab" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
                            </td>
                            <td class="py-2 px-2">
                                <select name="frequency[]" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
                                    <option value="1-0-1 (Twice Daily)">1-0-1 (Twice Daily)</option>
                                    <option value="1-1-1 (Thrice Daily)">1-1-1 (Thrice Daily)</option>
                                    <option value="1-0-0 (Morning)">1-0-0 (Morning)</option>
                                    <option value="0-0-1 (Night)">0-0-1 (Night)</option>
                                    <option value="STAT (Immediately)">STAT (Immediately)</option>
                                    <option value="SOS (As needed)">SOS (As needed)</option>
                                </select>
                            </td>
                            <td class="py-2 px-2">
                                <input type="text" name="duration[]" value="5 Days" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
                            </td>
                            <td class="py-2 px-2">
                                <select name="instruction[]" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
                                    <option value="After Food">After Food</option>
                                    <option value="Before Food">Before Food</option>
                                    <option value="With Milk">With Milk</option>
                                    <option value="At Bedtime">At Bedtime</option>
                                </select>
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" name="quantity[]" value="10" min="1" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
                            </td>
                            <td class="py-2 px-2 text-right">
                                <button type="button" onclick="this.closest('tr').remove()" class="text-rose-500 hover:text-rose-700 p-1">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Special Advice / Instructions</label>
                    <textarea name="advice" rows="2" placeholder="Drink water, avoid strenuous physical activities..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Follow-up Date</label>
                    <input type="date" name="follow_up_date" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="index.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-print mr-2"></i> Save & Print Prescription
            </button>
        </div>
    </form>

</div>

<script>
function addMedRow() {
    const tbody = document.getElementById('med-tbody');
    const tr = document.createElement('tr');
    tr.className = 'med-row';
    tr.innerHTML = `
        <td class="py-2 px-2">
            <input type="text" name="med_name[]" placeholder="e.g. Paracetamol 500mg" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
        </td>
        <td class="py-2 px-2">
            <input type="text" name="dosage[]" value="1 Tab" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
        </td>
        <td class="py-2 px-2">
            <select name="frequency[]" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
                <option value="1-0-1 (Twice Daily)">1-0-1 (Twice Daily)</option>
                <option value="1-1-1 (Thrice Daily)">1-1-1 (Thrice Daily)</option>
                <option value="1-0-0 (Morning)">1-0-0 (Morning)</option>
                <option value="0-0-1 (Night)">0-0-1 (Night)</option>
                <option value="STAT (Immediately)">STAT (Immediately)</option>
                <option value="SOS (As needed)">SOS (As needed)</option>
            </select>
        </td>
        <td class="py-2 px-2">
            <input type="text" name="duration[]" value="5 Days" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
        </td>
        <td class="py-2 px-2">
            <select name="instruction[]" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
                <option value="After Food">After Food</option>
                <option value="Before Food">Before Food</option>
                <option value="With Milk">With Milk</option>
                <option value="At Bedtime">At Bedtime</option>
            </select>
        </td>
        <td class="py-2 px-2">
            <input type="number" name="quantity[]" value="10" min="1" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
        </td>
        <td class="py-2 px-2 text-right">
            <button type="button" onclick="this.closest('tr').remove()" class="text-rose-500 hover:text-rose-700 p-1">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
