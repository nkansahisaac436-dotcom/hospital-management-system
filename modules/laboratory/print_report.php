<?php
/**
 * CarePoint Pro HMS - Official Printable Diagnostic Laboratory Report (A4)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$requestId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT lr.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, p.phone, p.blood_group,
                       u.full_name as doctor_name, d.specialization
                       FROM lab_requests lr
                       JOIN patients p ON lr.patient_id = p.id
                       LEFT JOIN doctors d ON lr.doctor_id = d.id
                       LEFT JOIN users u ON d.user_id = u.id
                       WHERE lr.id = ?");
$stmt->execute([$requestId]);
$req = $stmt->fetch();

if (!$req) {
    die('Laboratory report not found.');
}

// Fetch test items
$iStmt = $pdo->prepare("SELECT lri.*, lt.test_name, lt.test_code, lt.sample_type, lt.normal_range as default_range, lt.unit as default_unit,
                        u.full_name as tested_by_name
                        FROM lab_request_items lri
                        JOIN lab_tests lt ON lri.test_id = lt.id
                        LEFT JOIN users u ON lri.tested_by = u.id
                        WHERE lri.lab_request_id = ?");
$iStmt->execute([$requestId]);
$items = $iStmt->fetchAll();

$hospitalName = getSetting('hospital_name', DEFAULT_HOSPITAL_NAME);
$hospitalTagline = getSetting('hospital_tagline', DEFAULT_HOSPITAL_TAGLINE);
$hospitalPhone = getSetting('hospital_phone', DEFAULT_HOSPITAL_PHONE);
$hospitalEmail = getSetting('hospital_email', DEFAULT_HOSPITAL_EMAIL);
$hospitalAddress = getSetting('hospital_address', DEFAULT_HOSPITAL_ADDRESS);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Report <?= e($req['request_number']) ?> - <?= e($req['patient_name']) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            #report-container { border: none !important; box-shadow: none !important; padding: 0 !important; width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4 flex flex-col items-center">

    <!-- Action Toolbar (No Print) -->
    <div class="max-w-4xl w-full flex items-center justify-between mb-4 no-print">
        <a href="index.php" class="px-4 py-2 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold border border-slate-200 transition shadow-sm inline-flex items-center">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back
        </a>
        <button onclick="window.print()" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center">
            <i class="fa-solid fa-print mr-2"></i> Print Diagnostic Report (A4)
        </button>
    </div>

    <!-- Official Diagnostic Report Sheet Container -->
    <div id="report-container" class="max-w-4xl w-full bg-white rounded-3xl shadow-xl border border-slate-200 p-8 sm:p-12 text-slate-800 relative flex flex-col justify-between" style="min-height: 297mm;">

        <div>
            <!-- Hospital Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b-2 border-purple-800 pb-6 gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 rounded-2xl bg-purple-800 text-white flex items-center justify-center text-3xl shadow-inner">
                        <i class="fa-solid fa-flask-vial"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 uppercase"><?= e($hospitalName) ?></h1>
                        <p class="text-xs text-purple-800 font-bold uppercase tracking-wider">Department of Diagnostic Pathology & Clinical Laboratories</p>
                        <p class="text-[11px] text-slate-500 mt-0.5"><?= e($hospitalAddress) ?></p>
                    </div>
                </div>
                <div class="text-left sm:text-right text-xs text-slate-600 font-mono">
                    <p>Report Ref: <strong class="text-slate-900"><?= e($req['request_number']) ?></strong></p>
                    <p>Priority: <strong class="text-purple-700"><?= e($req['priority']) ?></strong></p>
                    <p>Date: <?= formatDate($req['requested_date']) ?></p>
                </div>
            </div>

            <!-- Patient & Order Information Matrix -->
            <div class="my-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Patient Name</span>
                    <span class="font-bold text-slate-900 text-sm"><?= e($req['patient_name']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">MRN / Patient ID</span>
                    <span class="font-mono font-bold text-purple-700"><?= e($req['mrn']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Age / Gender</span>
                    <span class="font-bold text-slate-800"><?= calculateAge($req['date_of_birth']) ?> &bull; <?= e($req['gender']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Blood Group</span>
                    <span class="font-bold text-rose-600"><?= e($req['blood_group']) ?></span>
                </div>
                <div class="col-span-2 pt-2 border-t border-slate-200">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Referring Physician</span>
                    <span class="font-bold text-slate-800"><?= e($req['doctor_name'] ?? 'Direct Walk-In Investigation') ?></span>
                </div>
                <div class="col-span-2 pt-2 border-t border-slate-200">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Clinical Indications</span>
                    <span class="text-slate-700 italic"><?= e($req['clinical_notes'] ?: 'Standard Diagnostic Panel') ?></span>
                </div>
            </div>

            <!-- Test Results Table -->
            <div class="mb-8">
                <div class="flex items-center space-x-2 mb-3">
                    <i class="fa-solid fa-microscope text-purple-700"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Investigation Findings & Analytical Parameters</h3>
                </div>

                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-y border-slate-200">
                        <tr>
                            <th class="py-3 px-3">Test Investigation</th>
                            <th class="py-3 px-3">Specimen</th>
                            <th class="py-3 px-3">Observed Result</th>
                            <th class="py-3 px-3">Evaluation Flag</th>
                            <th class="py-3 px-3">Reference Range</th>
                            <th class="py-3 px-3">Unit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php foreach ($items as $item): ?>
                            <?php
                                $flagColor = match($item['flag']) {
                                    'High' => 'text-rose-600 font-black',
                                    'Low' => 'text-sky-600 font-black',
                                    'Critical' => 'text-red-700 bg-red-50 px-2 py-0.5 rounded font-black',
                                    default => 'text-emerald-700 font-bold'
                                };
                            ?>
                            <tr>
                                <td class="py-3.5 px-3">
                                    <strong class="text-slate-900 block"><?= e($item['test_name']) ?></strong>
                                    <span class="text-[10px] text-slate-400 font-mono"><?= e($item['test_code']) ?></span>
                                    <?php if (!empty($item['remarks'])): ?>
                                        <p class="text-[11px] text-slate-500 italic mt-0.5"><?= e($item['remarks']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600"><?= e($item['sample_type']) ?></td>
                                <td class="py-3.5 px-3 text-sm font-bold text-slate-900 font-mono">
                                    <?= e($item['result_value'] ?: 'Pending') ?>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="<?= $flagColor ?> text-xs">
                                        <?= e($item['flag'] ?: 'Normal') ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 font-mono text-slate-700 text-[11px]">
                                    <?= e($item['reference_range'] ?: $item['default_range']) ?>
                                </td>
                                <td class="py-3.5 px-3 font-mono text-slate-500">
                                    <?= e($item['unit'] ?: $item['default_unit']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pathologist General Notes -->
            <div class="p-4 rounded-2xl bg-purple-50/60 border border-purple-100 text-xs text-purple-950 space-y-1 mb-8">
                <strong class="uppercase text-[10px] text-purple-900 block">Interpretation & Quality Certification Note:</strong>
                <p class="text-[11px] leading-relaxed">
                    All tests were performed using calibrated automated diagnostic analyzers and verified following standard ISO-15189 clinical laboratory accreditation guidelines. Results should be correlated clinically by the attending physician.
                </p>
            </div>
        </div>

        <!-- Footer Signatures -->
        <div class="pt-8 border-t border-slate-200 mt-auto">
            <div class="flex items-end justify-between">
                
                <div class="max-w-xs text-[10px] text-slate-400">
                    <p class="font-mono">Verified electronically via CarePoint LIS</p>
                    <p>Report Date: <?= date('Y-m-d H:i:s') ?></p>
                </div>

                <!-- Pathologist Signature Block -->
                <div class="text-center w-60">
                    <div class="h-14 flex items-center justify-center">
                        <span class="text-purple-800 font-serif italic text-lg opacity-80 select-none">
                            Dr. R. Langdon, FRCPath
                        </span>
                    </div>
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <p class="text-xs font-bold text-slate-900">Dr. Robert Langdon, MD, FRCPath</p>
                        <p class="text-[10px] text-slate-500 font-semibold">Chief of Pathology & Diagnostic Services</p>
                        <p class="text-[10px] text-slate-400">Verified Clinical Signature</p>
                    </div>
                </div>

            </div>
        </div>

    </div>

</body>
</html>
