<?php
/**
 * CarePoint Pro HMS - Official Printable Medical Certificate & Sick Leave (A4)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$admissionId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT adm.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, p.phone,
                       u.full_name as doctor_name, d.specialization, d.qualification
                       FROM ipd_admissions adm
                       JOIN patients p ON adm.patient_id = p.id
                       JOIN doctors d ON adm.doctor_id = d.id
                       JOIN users u ON d.user_id = u.id
                       WHERE adm.id = ?");
$stmt->execute([$admissionId]);
$adm = $stmt->fetch();

if (!$adm) {
    die('Record not found.');
}

$hospitalName = getSetting('hospital_name', DEFAULT_HOSPITAL_NAME);
$hospitalTagline = getSetting('hospital_tagline', DEFAULT_HOSPITAL_TAGLINE);
$hospitalAddress = getSetting('hospital_address', DEFAULT_HOSPITAL_ADDRESS);
$hospitalPhone = getSetting('hospital_phone', DEFAULT_HOSPITAL_PHONE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Certificate - <?= e($adm['patient_name']) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .serif-title { font-family: 'Playfair Display', serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            #cert-container { border: 2px solid #0f766e !important; box-shadow: none !important; padding: 30px !important; width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4 flex flex-col items-center">

    <!-- Action Toolbar (No Print) -->
    <div class="max-w-4xl w-full flex items-center justify-between mb-4 no-print">
        <a href="discharge_summary.php?id=<?= $adm['id'] ?>" class="px-4 py-2 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold border border-slate-200 transition shadow-sm inline-flex items-center">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Summary
        </a>
        <button onclick="window.print()" class="px-6 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center">
            <i class="fa-solid fa-print mr-2"></i> Print Medical Certificate (A4)
        </button>
    </div>

    <!-- Official Certificate Frame -->
    <div id="cert-container" class="max-w-4xl w-full bg-white rounded-3xl shadow-xl border-4 border-teal-800 p-8 sm:p-14 text-slate-800 relative flex flex-col justify-between" style="min-height: 297mm;">

        <div>
            <!-- Hospital Letterhead -->
            <div class="text-center border-b-2 border-teal-800 pb-6">
                <div class="w-16 h-16 rounded-2xl bg-teal-800 text-white flex items-center justify-center text-3xl mx-auto mb-3 shadow-inner">
                    <i class="fa-solid fa-staff-snake"></i>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 uppercase"><?= e($hospitalName) ?></h1>
                <p class="text-xs text-teal-800 font-bold uppercase tracking-wider"><?= e($hospitalTagline) ?></p>
                <p class="text-[11px] text-slate-500 mt-1"><?= e($hospitalAddress) ?> &bull; Tel: <?= e($hospitalPhone) ?></p>
            </div>

            <!-- Certificate Title -->
            <div class="text-center my-10">
                <h2 class="serif-title text-3xl font-bold text-teal-950 uppercase tracking-widest underline decoration-teal-600 underline-offset-8">
                    Medical Fitness & Sick Leave Certificate
                </h2>
                <span class="text-xs font-mono font-bold text-slate-400 block mt-3">Ref #: MED-CERT-<?= date('Y') ?>-<?= str_pad($adm['id'], 4, '0', STR_PAD_LEFT) ?></span>
            </div>

            <!-- Certificate Body Statement -->
            <div class="space-y-6 text-sm text-slate-800 leading-loose max-w-2xl mx-auto">
                <p>
                    This is to certify that <strong><?= e($adm['patient_name']) ?></strong>, 
                    MRN: <strong class="font-mono text-teal-800"><?= e($adm['mrn']) ?></strong>, 
                    aged <strong><?= calculateAge($adm['date_of_birth']) ?> years</strong>, 
                    was admitted and placed under my direct medical care and treatment at <?= e($hospitalName) ?> from 
                    <strong><?= formatDate($adm['admission_date']) ?></strong> to 
                    <strong><?= !empty($adm['discharge_date']) ? formatDate($adm['discharge_date']) : date('Y-m-d') ?></strong>.
                </p>

                <p>
                    <strong>Medical Diagnosis:</strong><br>
                    <span class="p-3 rounded-xl bg-slate-50 border border-slate-200 block text-xs font-semibold text-slate-900 mt-1">
                        <?= e($adm['reason_for_admission']) ?>
                    </span>
                </p>

                <p>
                    In my professional clinical opinion, the patient was incapacitated and unfit to attend to occupational duties or academic studies during the hospital stay. I hereby recommend a further convalescence rest period of 
                    <strong class="text-teal-900">7 (Seven) Days</strong> commencing from the date of discharge.
                </p>

                <p class="text-xs text-slate-500 italic">
                    The patient is expected to be fit to resume normal duties on <strong><?= date('F d, Y', strtotime('+7 days')) ?></strong>, subject to satisfactory recovery.
                </p>
            </div>
        </div>

        <!-- Authorized Signature & Seal -->
        <div class="pt-10 border-t border-slate-200 mt-auto">
            <div class="flex items-end justify-between">
                
                <!-- Hospital Stamp Block -->
                <div class="text-center w-48 border-2 border-dashed border-teal-600/50 p-4 rounded-2xl">
                    <i class="fa-solid fa-certificate text-teal-800 text-2xl mb-1 opacity-70"></i>
                    <p class="text-[10px] font-bold uppercase text-teal-950">Official Hospital Seal</p>
                    <span class="text-[9px] text-slate-400 font-mono"><?= date('Y-m-d') ?></span>
                </div>

                <!-- Doctor Signature Block -->
                <div class="text-center w-64">
                    <div class="h-12 flex items-center justify-center">
                        <span class="text-teal-800 font-serif italic text-xl select-none">
                            Dr. <?= e($adm['doctor_name']) ?>
                        </span>
                    </div>
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <p class="text-xs font-bold text-slate-900">Dr. <?= e($adm['doctor_name']) ?></p>
                        <p class="text-[10px] text-slate-500 font-semibold"><?= e($adm['specialization'] ?: 'Consultant Physician') ?></p>
                        <p class="text-[10px] text-slate-400">Authorized Medical Practitioner</p>
                    </div>
                </div>

            </div>
        </div>

    </div>

</body>
</html>
