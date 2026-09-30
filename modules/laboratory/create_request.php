<?php
/**
 * CarePoint Pro HMS - Order Diagnostic Lab Tests
 */

$pageTitle = 'Order Diagnostic Lab Test';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$preselectedPatientId = (int)($_GET['patient_id'] ?? 0);

$patients = $pdo->query("SELECT id, full_name, mrn, phone FROM patients ORDER BY full_name ASC")->fetchAll();
$doctors = $pdo->query("SELECT d.id, u.full_name, dep.name as dept_name FROM doctors d JOIN users u ON d.user_id = u.id JOIN departments dep ON d.department_id = dep.id WHERE d.status = 'active' ORDER BY u.full_name ASC")->fetchAll();
$labTests = $pdo->query("SELECT lt.*, c.name as cat_name FROM lab_tests lt JOIN lab_test_categories c ON lt.category_id = c.id WHERE lt.status = 'active' ORDER BY c.name ASC, lt.test_name ASC")->fetchAll();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $patientId = (int)($_POST['patient_id'] ?? 0);
    $doctorId = !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;
    $priority = $_POST['priority'] ?? 'Routine';
    $notes = trim($_POST['clinical_notes'] ?? '');
    $selectedTests = $_POST['tests'] ?? [];

    if ($patientId <= 0 || empty($selectedTests)) {
        $error = 'Please select a patient and at least one diagnostic test.';
    } else {
        try {
            $pdo->beginTransaction();

            $reqNumber = generateLabRequestNumber();

            // Calculate total price
            $totalAmount = 0.00;
            foreach ($selectedTests as $tId) {
                foreach ($labTests as $lt) {
                    if ((int)$lt['id'] === (int)$tId) {
                        $totalAmount += (float)$lt['price'];
                    }
                }
            }

            // 1. Insert Lab Request
            $stmt = $pdo->prepare("INSERT INTO lab_requests (request_number, patient_id, doctor_id, requested_date, status, priority, clinical_notes, total_amount, payment_status) VALUES (?, ?, ?, NOW(), 'Pending', ?, ?, ?, 'Unpaid')");
            $stmt->execute([$reqNumber, $patientId, $doctorId, $priority, $notes, $totalAmount]);
            $labReqId = $pdo->lastInsertId();

            // 2. Insert Test Items
            $itemStmt = $pdo->prepare("INSERT INTO lab_request_items (lab_request_id, test_id) VALUES (?, ?)");
            foreach ($selectedTests as $tId) {
                $itemStmt->execute([$labReqId, (int)$tId]);
            }

            // 3. Generate Laboratory Invoice
            $taxRate = (float)getSetting('tax_percentage', 5.00);
            $taxAmount = ($totalAmount * $taxRate) / 100;
            $invTotal = $totalAmount + $taxAmount;

            $invNumber = generateInvoiceNumber();
            $invStmt = $pdo->prepare("INSERT INTO invoices (invoice_number, patient_id, reference_type, reference_id, subtotal, discount, tax, total_amount, paid_amount, due_amount, payment_status, due_date, created_by) VALUES (?, ?, 'Laboratory', ?, ?, 0.00, ?, ?, 0.00, ?, 'Unpaid', CURDATE(), ?)");
            $invStmt->execute([$invNumber, $patientId, $labReqId, $totalAmount, $taxAmount, $invTotal, $invTotal, currentUser()['id']]);
            $invoiceId = $pdo->lastInsertId();

            $invItemStmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, category, unit_price, quantity, total) VALUES (?, ?, 'Laboratory', ?, 1, ?)");
            foreach ($selectedTests as $tId) {
                foreach ($labTests as $lt) {
                    if ((int)$lt['id'] === (int)$tId) {
                        $invItemStmt->execute([$invoiceId, "Diagnostic Test: {$lt['test_name']} ({$lt['test_code']})", $lt['price'], $lt['price']]);
                    }
                }
            }

            $pdo->commit();
            logActivity('Lab Test Ordered', "Ordered lab requisition {$reqNumber} for patient ID {$patientId}");
            setFlash('success', "Lab Requisition <strong>{$reqNumber}</strong> created successfully with invoice {$invNumber}.");
            header("Location: enter_result.php?id={$labReqId}");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to order lab tests: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Lab Orders
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Order Diagnostic & Pathology Tests</h1>
        <p class="text-xs text-slate-500">Create a clinical laboratory work order and schedule specimen collection.</p>
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
                    <i class="fa-solid fa-user-tag text-purple-600 mr-2"></i> Patient & Clinical Order Details
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Patient (EMR) *</label>
                    <select name="patient_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-purple-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($preselectedPatientId === (int)$p['id']) ? 'selected' : '' ?>>
                                <?= e($p['full_name']) ?> (MRN: <?= e($p['mrn']) ?>) - <?= e($p['phone']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Referring Doctor</label>
                    <select name="doctor_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-purple-500 focus:bg-white focus:outline-none">
                        <option value="">-- Direct Patient Order --</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['full_name']) ?> (<?= e($d['dept_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Urgency / Priority *</label>
                    <select name="priority" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-purple-500 focus:bg-white focus:outline-none">
                        <option value="Routine">Routine (Standard)</option>
                        <option value="Urgent">Urgent (Within 4 Hours)</option>
                        <option value="STAT">STAT (Immediate Critical)</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Clinical Indications / Suspected Diagnosis</label>
                    <input type="text" name="clinical_notes" placeholder="e.g. Rule out bacterial infection, check electrolyte disturbance..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-purple-500 focus:bg-white focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Diagnostic Tests Multi-Select Grid -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-flask-vial text-purple-600 mr-2"></i> Select Diagnostic Tests to Perform *
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <?php foreach ($labTests as $lt): ?>
                    <label class="p-3 bg-slate-50 hover:bg-purple-50/60 border border-slate-200 rounded-2xl flex items-center justify-between cursor-pointer transition">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="tests[]" value="<?= $lt['id'] ?>" class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                            <div>
                                <span class="font-bold text-slate-900 text-xs block"><?= e($lt['test_name']) ?></span>
                                <span class="text-[10px] text-slate-400 font-mono"><?= e($lt['test_code']) ?> &bull; <?= e($lt['sample_type']) ?></span>
                            </div>
                        </div>
                        <span class="font-bold text-purple-700 text-xs"><?= formatMoney($lt['price']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="index.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-lg shadow-purple-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-flask mr-2"></i> Submit & Generate Work Order
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
