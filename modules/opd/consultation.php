<?php
/**
 * CarePoint Pro HMS - Doctor Clinical Consultation Workbench & E-Rx Builder
 */

$pageTitle = 'Doctor Consultation Workbench';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth(['admin', 'doctor']);

$pdo = Database::getConnection();
$aptId = (int)($_GET['appointment_id'] ?? 0);

// Fetch appointment & patient info
$stmt = $pdo->prepare("SELECT a.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, p.allergies, p.chronic_diseases, p.blood_group,
                       u.full_name as doctor_name, d.specialization, d.consultation_fee
                       FROM appointments a
                       JOIN patients p ON a.patient_id = p.id
                       JOIN doctors d ON a.doctor_id = d.id
                       JOIN users u ON d.user_id = u.id
                       WHERE a.id = ?");
$stmt->execute([$aptId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash('error', 'Appointment not found.');
    header('Location: index.php');
    exit;
}

// Mark status as In-Consultation
$pdo->prepare("UPDATE appointments SET status = 'In-Consultation' WHERE id = ? AND status = 'Waiting'")->execute([$aptId]);

// Fetch Medicines & Lab Tests for dynamic builders
$medicines = $pdo->query("SELECT id, name, generic_name, dosage_form, strength, stock_quantity, unit_price FROM medicines WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$labTests = $pdo->query("SELECT id, test_name, test_code, sample_type, price FROM lab_tests WHERE status = 'active' ORDER BY test_name ASC")->fetchAll();

// Fetch latest vitals
$vStmt = $pdo->prepare("SELECT * FROM vitals WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1");
$vStmt->execute([$appointment['patient_id']]);
$latestVitals = $vStmt->fetch();

$error = null;

// Handle Consultation Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $chiefComplaints = trim($_POST['chief_complaints'] ?? '');
    $clinicalExam = trim($_POST['clinical_examination'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $icdCode = trim($_POST['icd10_code'] ?? '');
    $doctorNotes = trim($_POST['doctor_notes'] ?? '');
    $advice = trim($_POST['advice'] ?? '');
    $followUpDate = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;

    // Prescription Medicines Array
    $medIds = $_POST['med_id'] ?? [];
    $medNames = $_POST['med_name'] ?? [];
    $dosages = $_POST['dosage'] ?? [];
    $frequencies = $_POST['frequency'] ?? [];
    $durations = $_POST['duration'] ?? [];
    $instructions = $_POST['instruction'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    // Lab Tests Array
    $selectedLabTests = $_POST['lab_tests'] ?? [];

    if (empty($chiefComplaints) || empty($diagnosis)) {
        $error = 'Chief complaints and Diagnosis are required to complete consultation.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Record OPD Consultation
            $cStmt = $pdo->prepare("INSERT INTO opd_consultations (appointment_id, patient_id, doctor_id, chief_complaints, clinical_examination, diagnosis, icd10_code, doctor_notes, follow_up_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $cStmt->execute([$aptId, $appointment['patient_id'], $appointment['doctor_id'], $chiefComplaints, $clinicalExam, $diagnosis, $icdCode, $doctorNotes, $followUpDate]);
            $consultationId = $pdo->lastInsertId();

            // 2. Create Prescription (if medicines prescribed or advice given)
            $rxId = null;
            if (!empty($medNames) || !empty($advice)) {
                $rxNumber = generatePrescriptionNumber();
                $rxStmt = $pdo->prepare("INSERT INTO prescriptions (prescription_number, consultation_id, patient_id, doctor_id, diagnosis, advice, follow_up_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')");
                $rxStmt->execute([$rxNumber, $consultationId, $appointment['patient_id'], $appointment['doctor_id'], $diagnosis, $advice, $followUpDate]);
                $rxId = $pdo->lastInsertId();

                if (!empty($medNames)) {
                    $itemStmt = $pdo->prepare("INSERT INTO prescription_items (prescription_id, medicine_id, medicine_name, dosage, frequency, duration, instruction, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    for ($i = 0; $i < count($medNames); $i++) {
                        if (!empty($medNames[$i])) {
                            $mId = !empty($medIds[$i]) ? (int)$medIds[$i] : null;
                            $mName = $medNames[$i];
                            $dos = $dosages[$i] ?? '1 Tab';
                            $freq = $frequencies[$i] ?? '1-0-1';
                            $dur = $durations[$i] ?? '5 Days';
                            $inst = $instructions[$i] ?? 'After Food';
                            $qty = !empty($quantities[$i]) ? (int)$quantities[$i] : 1;
                            $itemStmt->execute([$rxId, $mId, $mName, $dos, $freq, $dur, $inst, $qty]);
                        }
                    }
                }
            }

            // 3. Create Lab Request (if tests selected)
            $labTotal = 0.00;
            if (!empty($selectedLabTests)) {
                $labReqNumber = generateLabRequestNumber();
                
                // Calculate total
                foreach ($selectedLabTests as $tId) {
                    foreach ($labTests as $lt) {
                        if ((int)$lt['id'] === (int)$tId) {
                            $labTotal += (float)$lt['price'];
                        }
                    }
                }

                $lrStmt = $pdo->prepare("INSERT INTO lab_requests (request_number, patient_id, doctor_id, prescription_id, requested_date, status, priority, clinical_notes, total_amount, payment_status) VALUES (?, ?, ?, ?, NOW(), 'Pending', 'Routine', ?, ?, 'Unpaid')");
                $lrStmt->execute([$labReqNumber, $appointment['patient_id'], $appointment['doctor_id'], $rxId, $diagnosis, $labTotal]);
                $labReqId = $pdo->lastInsertId();

                $lriStmt = $pdo->prepare("INSERT INTO lab_request_items (lab_request_id, test_id) VALUES (?, ?)");
                foreach ($selectedLabTests as $tId) {
                    $lriStmt->execute([$labReqId, (int)$tId]);
                }
            }

            // 4. Generate Integrated Invoice (Consultation Fee + Lab Fees)
            $consultationFee = (float)$appointment['consultation_fee'];
            $invoiceSubtotal = $consultationFee + $labTotal;
            $taxRate = (float)getSetting('tax_percentage', 5.00);
            $taxAmount = ($invoiceSubtotal * $taxRate) / 100;
            $invoiceTotal = $invoiceSubtotal + $taxAmount;

            $invNumber = generateInvoiceNumber();
            $invStmt = $pdo->prepare("INSERT INTO invoices (invoice_number, patient_id, reference_type, reference_id, subtotal, discount, tax, total_amount, paid_amount, due_amount, payment_status, due_date, created_by) VALUES (?, ?, 'OPD', ?, ?, 0.00, ?, ?, 0.00, ?, 'Unpaid', CURDATE(), ?)");
            $invStmt->execute([$invNumber, $appointment['patient_id'], $consultationId, $invoiceSubtotal, $taxAmount, $invoiceTotal, $invoiceTotal, currentUser()['id']]);
            $invoiceId = $pdo->lastInsertId();

            // Insert Consultation Invoice Item
            $itemStmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, category, unit_price, quantity, total) VALUES (?, ?, ?, ?, ?, ?)");
            $itemStmt->execute([$invoiceId, "Doctor Consultation Fee ({$appointment['doctor_name']})", 'Consultation', $consultationFee, 1, $consultationFee]);

            // Insert Lab Test Items if any
            if (!empty($selectedLabTests)) {
                foreach ($selectedLabTests as $tId) {
                    foreach ($labTests as $lt) {
                        if ((int)$lt['id'] === (int)$tId) {
                            $itemStmt->execute([$invoiceId, "Lab Diagnostic: {$lt['test_name']} ({$lt['test_code']})", 'Laboratory', $lt['price'], 1, $lt['price']]);
                        }
                    }
                }
            }

            // 5. Update Appointment status to Completed
            $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ?")->execute([$aptId]);

            $pdo->commit();
            logActivity('Consultation Completed', "Completed consultation for patient {$appointment['patient_name']} (MRN: {$appointment['mrn']})");
            setFlash('success', "Consultation completed successfully! Digital Prescription and Billing Invoice <strong>{$invNumber}</strong> generated.");
            
            if ($rxId) {
                header("Location: " . APP_URL . "/modules/prescriptions/print.php?id={$rxId}");
            } else {
                header("Location: " . APP_URL . "/modules/patients/view.php?id={$appointment['patient_id']}");
            }
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to save consultation: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-5xl mx-auto space-y-6">

    <!-- Top Navigation Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to OPD Queue
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Clinical Consultation Workbench</h1>
            <p class="text-xs text-slate-500 font-mono">Token #<?= $appointment['token_number'] ?> &bull; <?= e($appointment['patient_name']) ?> (MRN: <?= e($appointment['mrn']) ?>)</p>
        </div>
        <div>
            <span class="badge badge-info text-xs px-3 py-1">In-Consultation</span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Patient Banner & Safety Flags -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Patient Name</span>
            <h4 class="font-bold text-slate-900 text-sm mt-0.5"><?= e($appointment['patient_name']) ?></h4>
            <span class="text-slate-500"><?= e($appointment['gender']) ?> &bull; <?= calculateAge($appointment['date_of_birth']) ?></span>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Blood Group & History</span>
            <span class="font-bold text-rose-600 block mt-0.5"><i class="fa-solid fa-droplet mr-1"></i> <?= e($appointment['blood_group']) ?></span>
            <span class="text-slate-500"><?= e($appointment['chronic_diseases'] ?: 'No chronic history') ?></span>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Latest Vitals</span>
            <?php if ($latestVitals): ?>
                <div class="font-bold text-slate-800 mt-0.5">
                    BP: <span class="text-teal-700"><?= e($latestVitals['blood_pressure']) ?></span> &bull; Pulse: <?= e($latestVitals['pulse_rate']) ?> bpm
                </div>
                <span class="text-slate-500">Temp: <?= e($latestVitals['temperature']) ?>&deg;C &bull; SpO2: <?= e($latestVitals['spo2']) ?>%</span>
            <?php else: ?>
                <span class="text-slate-400 block mt-0.5">No vitals logged today</span>
            <?php endif; ?>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Critical Allergies</span>
            <?php if (!empty($appointment['allergies'])): ?>
                <div class="mt-0.5 p-1.5 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 font-bold">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> <?= e($appointment['allergies']) ?>
                </div>
            <?php else: ?>
                <span class="text-slate-500 block mt-0.5">No known allergies</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Clinical Form -->
    <form method="POST" action="" class="space-y-6">
        <?= csrfField() ?>

        <!-- Step 1: Clinical Observation & Diagnosis -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                <i class="fa-solid fa-stethoscope text-teal-600 mr-2"></i> 1. Clinical Examination & Diagnosis
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Chief Complaints & History of Present Illness *</label>
                    <textarea name="chief_complaints" rows="3" required placeholder="Patient presents with..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"><?= e($appointment['reason']) ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Clinical Examination & Systemic Findings</label>
                    <textarea name="clinical_examination" rows="3" placeholder="Chest clear, heart sounds normal (S1, S2), no abdominal tenderness..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Clinical Diagnosis *</label>
                    <input type="text" name="diagnosis" required placeholder="e.g. Acute Upper Respiratory Tract Infection (URTI)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">ICD-10 Code (Optional)</label>
                    <input type="text" name="icd10_code" placeholder="e.g. J06.9, I10, E11.9" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Step 2: Digital E-Prescription Builder (Dynamic Rows) -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-prescription-bottle-medical text-sky-600 mr-2"></i> 2. Prescription Builder (Rx)
                </h3>
                <button type="button" onclick="addPrescriptionRow()" class="px-3 py-1.5 bg-sky-50 text-sky-700 hover:bg-sky-100 rounded-xl text-xs font-bold transition border border-sky-200">
                    <i class="fa-solid fa-plus mr-1"></i> Add Medicine Row
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs" id="prescription-table">
                    <thead class="text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-2 px-2">Medicine / Drug</th>
                            <th class="py-2 px-2 w-28">Dosage</th>
                            <th class="py-2 px-2 w-32">Frequency</th>
                            <th class="py-2 px-2 w-28">Duration</th>
                            <th class="py-2 px-2 w-36">Instructions</th>
                            <th class="py-2 px-2 w-20">Qty</th>
                            <th class="py-2 px-2 text-right w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="prescription-tbody">
                        <tr class="prescription-row">
                            <td class="py-2 px-2">
                                <input type="text" name="med_name[]" placeholder="e.g. Amoxicillin 625mg" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
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
                                <button type="button" onclick="removePrescriptionRow(this)" class="text-rose-500 hover:text-rose-700 p-1">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Quick Advice & Lifestyle Notes -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dietary & Lifestyle Advice</label>
                    <textarea name="advice" rows="2" placeholder="Drink plenty of fluids, avoid strenuous exercise, rest for 3 days..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Follow-up Review Date</label>
                    <input type="date" name="follow_up_date" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Step 3: Diagnostic Lab Order Requisition -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                <i class="fa-solid fa-flask-vial text-purple-600 mr-2"></i> 3. Order Diagnostic Lab & Imaging Tests (Optional)
            </h3>
            
            <p class="text-xs text-slate-500">Select any investigative laboratory or radiology tests required for this patient:</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <?php foreach ($labTests as $lt): ?>
                    <label class="p-3 bg-slate-50 hover:bg-purple-50/50 border border-slate-200 rounded-2xl flex items-center justify-between cursor-pointer transition">
                        <div class="flex items-center space-x-2.5">
                            <input type="checkbox" name="lab_tests[]" value="<?= $lt['id'] ?>" class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                            <div>
                                <span class="font-bold text-slate-800 text-xs block"><?= e($lt['test_name']) ?></span>
                                <span class="text-[10px] text-slate-400 font-mono"><?= e($lt['test_code']) ?> &bull; <?= e($lt['sample_type']) ?></span>
                            </div>
                        </div>
                        <span class="font-bold text-purple-700 text-xs"><?= formatMoney($lt['price']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Step 4: Finalize Consultation -->
        <div class="flex items-center justify-between p-4 bg-teal-50 border border-teal-200 rounded-3xl">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-lg">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <div>
                    <h4 class="font-bold text-teal-900 text-sm">Finalize & Sign Clinical Encounter</h4>
                    <p class="text-xs text-teal-700">Will automatically generate printable Prescription and update patient billing.</p>
                </div>
            </div>

            <button type="submit" class="px-8 py-3.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-2xl shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5 flex items-center">
                <i class="fa-solid fa-check-double mr-2"></i> Complete Consultation
            </button>
        </div>

    </form>

</div>

<script>
function addPrescriptionRow() {
    const tbody = document.getElementById('prescription-tbody');
    const tr = document.createElement('tr');
    tr.className = 'prescription-row';
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
            <button type="button" onclick="removePrescriptionRow(this)" class="text-rose-500 hover:text-rose-700 p-1">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

function removePrescriptionRow(btn) {
    const row = btn.closest('tr');
    if (document.querySelectorAll('.prescription-row').length > 1) {
        row.remove();
    } else {
        alert('At least one medicine row is required.');
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
