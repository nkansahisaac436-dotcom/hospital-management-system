<?php
/**
 * CarePoint Pro HMS - Inpatient Discharge Clearance Workflow
 */

$pageTitle = 'IPD Discharge Clearance';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$admissionId = (int)($_GET['id'] ?? 0);
$bedId = (int)($_GET['bed_id'] ?? 0);

if ($admissionId > 0) {
    $stmt = $pdo->prepare("SELECT adm.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, b.bed_number, b.daily_rate, w.name as ward_name, u.full_name as doctor_name
                           FROM ipd_admissions adm
                           JOIN patients p ON adm.patient_id = p.id
                           JOIN beds b ON adm.bed_id = b.id
                           JOIN wards w ON b.ward_id = w.id
                           JOIN doctors d ON adm.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           WHERE adm.id = ?");
    $stmt->execute([$admissionId]);
} else {
    $stmt = $pdo->prepare("SELECT adm.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, b.bed_number, b.daily_rate, w.name as ward_name, u.full_name as doctor_name
                           FROM ipd_admissions adm
                           JOIN patients p ON adm.patient_id = p.id
                           JOIN beds b ON adm.bed_id = b.id
                           JOIN wards w ON b.ward_id = w.id
                           JOIN doctors d ON adm.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           WHERE adm.bed_id = ? AND adm.status = 'Admitted'");
    $stmt->execute([$bedId]);
}

$adm = $stmt->fetch();

if (!$adm) {
    setFlash('error', 'Active admission record not found.');
    header('Location: beds.php');
    exit;
}

// Calculate Days Stayed
$admitDate = new DateTime($adm['admission_date']);
$now = new DateTime();
$diff = $admitDate->diff($now);
$daysStayed = max(1, $diff->days);
$totalBedCharges = $daysStayed * (float)$adm['daily_rate'];

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $dischargeSummary = trim($_POST['discharge_summary'] ?? '');
    $dischargeCondition = $_POST['discharge_condition'] ?? 'Recovered & Stable';
    $customDays = max(1, (int)($_POST['total_bed_days'] ?? $daysStayed));
    $finalBedCharges = $customDays * (float)$adm['daily_rate'];

    try {
        $pdo->beginTransaction();

        // 1. Update Admission record
        $uStmt = $pdo->prepare("UPDATE ipd_admissions SET discharge_date = NOW(), discharge_summary = ?, discharge_condition = ?, total_bed_days = ?, total_bed_charges = ?, status = 'Discharged' WHERE id = ?");
        $uStmt->execute([$dischargeSummary, $dischargeCondition, $customDays, $finalBedCharges, $adm['id']]);

        // 2. Free the Bed
        $bStmt = $pdo->prepare("UPDATE beds SET status = 'Available', current_patient_id = NULL WHERE id = ?");
        $bStmt->execute([$adm['bed_id']]);

        // 3. Generate Bed Stay Invoice
        $taxRate = (float)getSetting('tax_percentage', 5.00);
        $taxAmount = ($finalBedCharges * $taxRate) / 100;
        $totalAmount = $finalBedCharges + $taxAmount;

        $invNumber = generateInvoiceNumber();
        $invStmt = $pdo->prepare("INSERT INTO invoices (invoice_number, patient_id, reference_type, reference_id, subtotal, discount, tax, total_amount, paid_amount, due_amount, payment_status, due_date, created_by) VALUES (?, ?, 'IPD', ?, ?, 0.00, ?, ?, 0.00, ?, 'Unpaid', CURDATE(), ?)");
        $invStmt->execute([$invNumber, $adm['patient_id'], $adm['id'], $finalBedCharges, $taxAmount, $totalAmount, $totalAmount, currentUser()['id']]);
        $invoiceId = $pdo->lastInsertId();

        // Add Invoice Item
        $itemStmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, category, unit_price, quantity, total) VALUES (?, ?, 'Bed', ?, ?, ?)");
        $itemStmt->execute([$invoiceId, "Inpatient Accommodation ({$adm['ward_name']} - {$adm['bed_number']})", $adm['daily_rate'], $customDays, $finalBedCharges]);

        $pdo->commit();
        logActivity('Patient Discharged from IPD', "Discharged patient {$adm['patient_name']} from bed {$adm['bed_number']}");
        setFlash('success', "Patient <strong>{$adm['patient_name']}</strong> successfully discharged. Inpatient Bed Invoice <strong>{$invNumber}</strong> generated.");
        header("Location: " . APP_URL . "/modules/billing/print_invoice.php?id={$invoiceId}");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Failed to process discharge: ' . $e->getMessage();
    }
}
?>

<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <a href="beds.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Bed Matrix
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Patient Discharge Clearance</h1>
        <p class="text-xs text-slate-500 font-mono">Admission Ref: <?= e($adm['admission_number']) ?></p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Stay Summary Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Patient</span>
            <h4 class="font-bold text-slate-900 text-sm mt-0.5"><?= e($adm['patient_name']) ?></h4>
            <span class="text-slate-500 font-mono"><?= e($adm['mrn']) ?></span>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Ward & Bed</span>
            <span class="font-bold text-purple-700 block mt-0.5"><?= e($adm['bed_number']) ?></span>
            <span class="text-slate-500"><?= e($adm['ward_name']) ?></span>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Admission Date</span>
            <span class="font-bold text-slate-800 block mt-0.5"><?= formatDate($adm['admission_date']) ?></span>
            <span class="text-slate-500 font-mono"><?= date('h:i A', strtotime($adm['admission_date'])) ?></span>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Estimated Stay</span>
            <span class="font-bold text-teal-700 text-sm block mt-0.5"><?= $daysStayed ?> Day(s)</span>
            <span class="text-slate-500 font-mono"><?= formatMoney($totalBedCharges) ?></span>
        </div>
    </div>

    <form method="POST" action="" class="space-y-6">
        <?= csrfField() ?>

        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-clipboard-check text-teal-600 mr-2"></i> Discharge Summary & Billing Clearance
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Total Days Admitted *</label>
                    <input type="number" name="total_bed_days" value="<?= $daysStayed ?>" min="1" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                    <span class="text-[11px] text-slate-400">Rate: <?= formatMoney($adm['daily_rate']) ?>/day</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Condition at Discharge *</label>
                    <select name="discharge_condition" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                        <option value="Recovered & Stable">Recovered & Stable</option>
                        <option value="Improved / Transferred">Improved / Transferred</option>
                        <option value="Against Medical Advice (AMA)">Against Medical Advice (AMA)</option>
                        <option value="Referred to Tertiary Hospital">Referred to Tertiary Hospital</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Clinical Discharge Summary & Take-Home Instructions</label>
                    <textarea name="discharge_summary" rows="4" placeholder="Patient vitals stabilized, symptoms resolved. Prescribed oral medications for 7 days. Return to clinic in 2 weeks for follow-up evaluation..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="beds.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-lg shadow-rose-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-door-open mr-2"></i> Confirm Discharge & Generate Bill
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
