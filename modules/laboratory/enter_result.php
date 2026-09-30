<?php
/**
 * CarePoint Pro HMS - Laboratory Test Results Entry & Validation
 */

$pageTitle = 'Enter Laboratory Results';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$requestId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT lr.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, p.blood_group,
                       u.full_name as doctor_name
                       FROM lab_requests lr
                       JOIN patients p ON lr.patient_id = p.id
                       LEFT JOIN doctors d ON lr.doctor_id = d.id
                       LEFT JOIN users u ON d.user_id = u.id
                       WHERE lr.id = ?");
$stmt->execute([$requestId]);
$req = $stmt->fetch();

if (!$req) {
    setFlash('error', 'Lab request not found.');
    header('Location: index.php');
    exit;
}

// Fetch Test Items
$iStmt = $pdo->prepare("SELECT lri.*, lt.test_name, lt.test_code, lt.sample_type, lt.normal_range, lt.unit
                        FROM lab_request_items lri
                        JOIN lab_tests lt ON lri.test_id = lt.id
                        WHERE lri.lab_request_id = ?");
$iStmt->execute([$requestId]);
$items = $iStmt->fetchAll();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $itemIds = $_POST['item_id'] ?? [];
    $resultValues = $_POST['result_value'] ?? [];
    $flags = $_POST['flag'] ?? [];
    $remarks = $_POST['remarks'] ?? [];
    $markCompleted = isset($_POST['mark_completed']);

    try {
        $pdo->beginTransaction();

        $uStmt = $pdo->prepare("UPDATE lab_request_items SET result_value = ?, reference_range = ?, unit = ?, flag = ?, remarks = ?, tested_by = ?, tested_at = NOW() WHERE id = ?");
        
        for ($i = 0; $i < count($itemIds); $i++) {
            $itemId = (int)$itemIds[$i];
            $val = trim($resultValues[$i] ?? '');
            $flag = $flags[$i] ?? 'Normal';
            $rem = trim($remarks[$i] ?? '');

            // Find matching item metadata
            $refRange = '';
            $unit = '';
            foreach ($items as $item) {
                if ((int)$item['id'] === $itemId) {
                    $refRange = $item['normal_range'];
                    $unit = $item['unit'];
                    break;
                }
            }

            $uStmt->execute([$val, $refRange, $unit, $flag, $rem, currentUser()['id'], $itemId]);
        }

        // Update overall request status
        $newStatus = $markCompleted ? 'Completed' : 'In Progress';
        $pdo->prepare("UPDATE lab_requests SET status = ? WHERE id = ?")->execute([$newStatus, $requestId]);

        $pdo->commit();
        logActivity('Lab Results Entered', "Entered results for lab request {$req['request_number']}");
        setFlash('success', "Lab results successfully updated. Status: <strong>{$newStatus}</strong>");

        if ($markCompleted) {
            header("Location: print_report.php?id={$requestId}");
        } else {
            header("Location: index.php");
        }
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Failed to save results: ' . $e->getMessage();
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Lab Queue
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Enter Diagnostic Laboratory Results</h1>
            <p class="text-xs text-slate-500 font-mono">Work Order: <?= e($req['request_number']) ?> &bull; Priority: <?= e($req['priority']) ?></p>
        </div>
        <div>
            <span class="badge <?= $req['status'] === 'Completed' ? 'badge-success' : 'badge-warning' ?>">
                <?= e($req['status']) ?>
            </span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Patient Header Card -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Patient</span>
            <h4 class="font-bold text-slate-900 text-sm mt-0.5"><?= e($req['patient_name']) ?></h4>
            <span class="text-slate-500 font-mono"><?= e($req['mrn']) ?></span>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Age / Gender</span>
            <span class="font-bold text-slate-800 block mt-0.5"><?= calculateAge($req['date_of_birth']) ?> &bull; <?= e($req['gender']) ?></span>
            <span class="text-rose-600 font-bold">Blood: <?= e($req['blood_group']) ?></span>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Referring Doctor</span>
            <span class="font-bold text-slate-800 block mt-0.5"><?= e($req['doctor_name'] ?? 'Direct Walk-In') ?></span>
            <span class="text-slate-500 font-mono"><?= formatDate($req['requested_date']) ?></span>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Clinical Indication</span>
            <p class="text-slate-700 mt-0.5 italic"><?= e($req['clinical_notes'] ?: 'Routine Diagnostic Workup') ?></p>
        </div>
    </div>

    <!-- Results Entry Form -->
    <form method="POST" action="" class="space-y-6">
        <?= csrfField() ?>

        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-microscope text-purple-600 mr-2"></i> Analytical Findings & Parameter Observations
                </h3>
            </div>

            <div class="space-y-4">
                <?php foreach ($items as $item): ?>
                    <input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200 pb-2">
                            <div>
                                <h4 class="font-bold text-slate-900 text-sm"><?= e($item['test_name']) ?> <span class="font-mono text-purple-700 text-xs font-semibold">(<?= e($item['test_code']) ?>)</span></h4>
                                <span class="text-[11px] text-slate-500">Specimen: <strong class="text-slate-700"><?= e($item['sample_type']) ?></strong> &bull; Unit: <strong class="text-slate-700"><?= e($item['unit'] ?: 'N/A') ?></strong></span>
                            </div>
                            <div class="text-left sm:text-right text-xs">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Normal Reference Range</span>
                                <span class="font-mono font-bold text-slate-700"><?= e($item['normal_range'] ?: 'Descriptive / Negative') ?></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Observed Test Value / Quantitative Result *</label>
                                <input type="text" name="result_value[]" value="<?= e($item['result_value']) ?>" required placeholder="e.g. 14.5 or Positive or Normal Sinus Rhythm" class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Clinical Evaluation Flag *</label>
                                <select name="flag[]" class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                                    <option value="Normal" <?= $item['flag'] === 'Normal' ? 'selected' : '' ?>>Normal (Optimal)</option>
                                    <option value="High" <?= $item['flag'] === 'High' ? 'selected' : '' ?>>High (Elevated)</option>
                                    <option value="Low" <?= $item['flag'] === 'Low' ? 'selected' : '' ?>>Low (Decreased)</option>
                                    <option value="Critical" <?= $item['flag'] === 'Critical' ? 'selected' : '' ?>>Critical (Alert)</option>
                                </select>
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Pathologist Remarks / Microscopic Impression</label>
                                <input type="text" name="remarks[]" value="<?= e($item['remarks']) ?>" placeholder="e.g. Normocytic, normochromic RBCs seen. Adequate platelets." class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Verification Option -->
            <div class="p-4 bg-purple-50 border border-purple-200 rounded-2xl flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <input type="checkbox" name="mark_completed" id="mark_completed" value="1" checked class="w-5 h-5 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
                    <label for="mark_completed" class="text-xs font-bold text-purple-900 cursor-pointer">
                        Validate and Sign Diagnostic Report (Mark Completed)
                        <span class="block text-[11px] font-normal text-purple-700">Applies digital lab certification and generates printable report.</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="index.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-lg shadow-purple-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-check-double mr-2"></i> Save & Certify Results
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
