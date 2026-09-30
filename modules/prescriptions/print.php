<?php
/**
 * CarePoint Pro HMS - Official Printable Medical Prescription (A4 Format)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$rxId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT p.*, pat.full_name as patient_name, pat.mrn, pat.gender, pat.date_of_birth, pat.phone, pat.blood_group, pat.allergies,
                       u.full_name as doctor_name, d.specialization, d.room_no, dep.name as dept_name
                       FROM prescriptions p
                       JOIN patients pat ON p.patient_id = pat.id
                       JOIN doctors d ON p.doctor_id = d.id
                       JOIN departments dep ON d.department_id = dep.id
                       JOIN users u ON d.user_id = u.id
                       WHERE p.id = ?");
$stmt->execute([$rxId]);
$rx = $stmt->fetch();

if (!$rx) {
    die('Prescription not found.');
}

// Fetch items
$iStmt = $pdo->prepare("SELECT * FROM prescription_items WHERE prescription_id = ? ORDER BY id ASC");
$iStmt->execute([$rxId]);
$items = $iStmt->fetchAll();

$hospitalName = getSetting('hospital_name', DEFAULT_HOSPITAL_NAME);
$hospitalTagline = getSetting('hospital_tagline', DEFAULT_HOSPITAL_TAGLINE);
$hospitalPhone = getSetting('hospital_phone', DEFAULT_HOSPITAL_PHONE);
$hospitalEmail = getSetting('hospital_email', DEFAULT_HOSPITAL_EMAIL);
$hospitalAddress = getSetting('hospital_address', DEFAULT_HOSPITAL_ADDRESS);
$footerNote = getSetting('prescription_footer', 'Please bring this prescription and your previous medical records on your next visit.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription <?= e($rx['prescription_number']) ?> - <?= e($rx['patient_name']) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .rx-symbol { font-family: 'Playfair Display', serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            #prescription-container { border: none !important; box-shadow: none !important; padding: 0 !important; width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4 flex flex-col items-center">

    <!-- Action Toolbar (No Print) -->
    <div class="max-w-4xl w-full flex items-center justify-between mb-4 no-print">
        <a href="index.php" class="px-4 py-2 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold border border-slate-200 transition shadow-sm inline-flex items-center">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back
        </a>
        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="px-6 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center">
                <i class="fa-solid fa-print mr-2"></i> Print Prescription (A4)
            </button>
        </div>
    </div>

    <!-- Official Prescription Sheet Container -->
    <div id="prescription-container" class="max-w-4xl w-full bg-white rounded-3xl shadow-xl border border-slate-200 p-8 sm:p-12 text-slate-800 relative flex flex-col justify-between" style="min-height: 297mm;">

        <div>
            <!-- Hospital Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b-2 border-teal-700 pb-6 gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 rounded-2xl bg-teal-700 text-white flex items-center justify-center text-3xl shadow-inner">
                        <i class="fa-solid fa-hospital"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 uppercase"><?= e($hospitalName) ?></h1>
                        <p class="text-xs text-teal-700 font-semibold tracking-wide"><?= e($hospitalTagline) ?></p>
                        <p class="text-[11px] text-slate-500 mt-1"><?= e($hospitalAddress) ?></p>
                    </div>
                </div>
                <div class="text-left sm:text-right text-xs text-slate-600">
                    <p><i class="fa-solid fa-phone text-teal-600 mr-1"></i> <?= e($hospitalPhone) ?></p>
                    <p><i class="fa-solid fa-envelope text-teal-600 mr-1"></i> <?= e($hospitalEmail) ?></p>
                    <span class="inline-block mt-1 font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded">Rx #: <?= e($rx['prescription_number']) ?></span>
                </div>
            </div>

            <!-- Doctor Information Banner -->
            <div class="py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between text-xs">
                <div>
                    <h2 class="font-bold text-slate-900 text-sm"><?= e($rx['doctor_name']) ?></h2>
                    <p class="text-teal-700 font-semibold"><?= e($rx['specialization']) ?> &bull; <?= e($rx['dept_name']) ?></p>
                </div>
                <div class="text-left sm:text-right text-slate-500 mt-2 sm:mt-0">
                    <p>Consulting Room: <strong class="text-slate-800"><?= e($rx['room_no'] ?: 'OPD Consultation Desk') ?></strong></p>
                    <p>Date: <strong class="text-slate-800 font-mono"><?= formatDate($rx['created_at']) ?></strong></p>
                </div>
            </div>

            <!-- Patient Demographics Bar -->
            <div class="my-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Patient Name</span>
                    <span class="font-bold text-slate-900 text-sm"><?= e($rx['patient_name']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">MRN / Patient ID</span>
                    <span class="font-mono font-bold text-teal-700"><?= e($rx['mrn']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Age / Gender</span>
                    <span class="font-bold text-slate-800"><?= calculateAge($rx['date_of_birth']) ?> &bull; <?= e($rx['gender']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Blood Group</span>
                    <span class="font-bold text-rose-600"><?= e($rx['blood_group']) ?></span>
                </div>
                
                <?php if (!empty($rx['allergies'])): ?>
                    <div class="col-span-full pt-2 border-t border-slate-200 text-rose-700 font-bold">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Allergies Recorded: <?= e($rx['allergies']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Diagnosis Banner -->
            <?php if (!empty($rx['diagnosis'])): ?>
                <div class="mb-6 flex items-center space-x-2 text-xs">
                    <span class="font-bold uppercase text-slate-400 text-[10px]">Clinical Diagnosis:</span>
                    <span class="font-bold text-slate-900 bg-teal-50 text-teal-800 px-3 py-1 rounded-lg border border-teal-200"><?= e($rx['diagnosis']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Universal Rx Symbol Header -->
            <div class="flex items-center space-x-3 mb-4">
                <span class="rx-symbol text-4xl font-bold text-teal-700">℞</span>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 flex-1 pb-1">Prescribed Medication & Dosage Schedule</span>
            </div>

            <!-- Medicines Table -->
            <table class="w-full text-left text-xs mb-8">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-y border-slate-200">
                    <tr>
                        <th class="py-3 px-3">#</th>
                        <th class="py-3 px-3">Medicine / Generic Formulation</th>
                        <th class="py-3 px-3">Dosage</th>
                        <th class="py-3 px-3">Frequency</th>
                        <th class="py-3 px-3">Duration</th>
                        <th class="py-3 px-3">Instructions</th>
                        <th class="py-3 px-3 text-right">Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $idx => $item): ?>
                            <tr>
                                <td class="py-3.5 px-3 text-slate-400 font-bold"><?= $idx + 1 ?></td>
                                <td class="py-3.5 px-3">
                                    <strong class="text-slate-900 text-sm block"><?= e($item['medicine_name']) ?></strong>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-700"><?= e($item['dosage']) ?></td>
                                <td class="py-3.5 px-3 font-bold text-teal-700"><?= e($item['frequency']) ?></td>
                                <td class="py-3.5 px-3 text-slate-700"><?= e($item['duration']) ?></td>
                                <td class="py-3.5 px-3 text-slate-600 italic"><?= e($item['instruction']) ?></td>
                                <td class="py-3.5 px-3 text-right font-bold text-slate-900"><?= $item['quantity'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">No specific oral drugs prescribed. Follow dietary advice below.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Advice & Follow-up Section -->
            <?php if (!empty($rx['advice']) || !empty($rx['follow_up_date'])): ?>
                <div class="p-4 rounded-2xl bg-sky-50/60 border border-sky-100 space-y-2 text-xs mb-8">
                    <?php if (!empty($rx['advice'])): ?>
                        <div>
                            <strong class="text-sky-900 uppercase text-[10px] block">Doctor's Advice & Patient Instructions:</strong>
                            <p class="text-slate-700 mt-0.5"><?= nl2br(e($rx['advice'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($rx['follow_up_date'])): ?>
                        <div class="pt-2 border-t border-sky-200/50 flex items-center space-x-2 text-sky-900">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span class="font-bold">Follow-Up Review: <?= formatDate($rx['follow_up_date']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Signatures & Hospital Seal -->
        <div class="pt-8 border-t border-slate-200 mt-auto">
            <div class="flex items-end justify-between">
                
                <!-- Security QR & Note -->
                <div class="max-w-xs text-[10px] text-slate-400">
                    <p class="leading-relaxed"><?= e($footerNote) ?></p>
                    <p class="mt-1 font-mono">Generated electronically by CarePoint Pro HMS &bull; <?= date('Y-m-d H:i') ?></p>
                </div>

                <!-- Doctor Seal & Signature Block -->
                <div class="text-center w-60">
                    <div class="h-16 flex items-center justify-center">
                        <span class="text-teal-700 font-serif italic text-lg opacity-80 select-none">
                            Dr. <?= explode(' ', $rx['doctor_name'])[1] ?? 'Doctor' ?>
                        </span>
                    </div>
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <p class="text-xs font-bold text-slate-900"><?= e($rx['doctor_name']) ?></p>
                        <p class="text-[10px] text-slate-500 font-semibold"><?= e($rx['specialization']) ?></p>
                        <p class="text-[10px] text-slate-400">Authorized Medical Signature</p>
                    </div>
                </div>

            </div>
        </div>

    </div>

</body>
</html>
