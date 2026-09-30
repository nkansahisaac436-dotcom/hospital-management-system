<?php
/**
 * CarePoint Pro HMS - Official Printable Inpatient Discharge Summary (A4)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$admissionId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT adm.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, p.phone, p.blood_group, p.allergies, p.chronic_diseases,
                       u.full_name as doctor_name, d.specialization, d.qualification,
                       w.name as ward_name, b.bed_number
                       FROM ipd_admissions adm
                       JOIN patients p ON adm.patient_id = p.id
                       JOIN doctors d ON adm.doctor_id = d.id
                       JOIN users u ON d.user_id = u.id
                       JOIN beds b ON adm.bed_id = b.id
                       JOIN wards w ON b.ward_id = w.id
                       WHERE adm.id = ?");
$stmt->execute([$admissionId]);
$adm = $stmt->fetch();

if (!$adm) {
    die('Inpatient admission record not found.');
}

// Fetch any prescriptions issued during admission
$rxStmt = $pdo->prepare("SELECT pi.* FROM prescription_items pi JOIN prescriptions p ON pi.prescription_id = p.id WHERE p.patient_id = ? ORDER BY p.prescription_date DESC LIMIT 5");
$rxStmt->execute([$adm['patient_id']]);
$dischargeMeds = $rxStmt->fetchAll();

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
    <title>Discharge Summary - <?= e($adm['patient_name']) ?> (<?= e($adm['mrn']) ?>)</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            #summary-sheet { border: none !important; box-shadow: none !important; padding: 0 !important; width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4 flex flex-col items-center">

    <!-- Action Toolbar (No Print) -->
    <div class="max-w-4xl w-full flex items-center justify-between mb-4 no-print">
        <a href="index.php" class="px-4 py-2 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold border border-slate-200 transition shadow-sm inline-flex items-center">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to IPD
        </a>
        <div class="flex items-center space-x-2">
            <a href="medical_certificate.php?id=<?= $adm['id'] ?>" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center">
                <i class="fa-solid fa-file-certificate mr-1.5"></i> Medical Certificate
            </a>
            <button onclick="window.print()" class="px-6 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center">
                <i class="fa-solid fa-print mr-2"></i> Print Discharge Summary (A4)
            </button>
        </div>
    </div>

    <!-- Official Summary Sheet Container -->
    <div id="summary-sheet" class="max-w-4xl w-full bg-white rounded-3xl shadow-xl border border-slate-200 p-8 sm:p-12 text-slate-800 relative flex flex-col justify-between" style="min-height: 297mm;">

        <div>
            <!-- Hospital Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b-2 border-teal-800 pb-6 gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 rounded-2xl bg-teal-800 text-white flex items-center justify-center text-3xl shadow-inner">
                        <i class="fa-solid fa-hospital"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 uppercase"><?= e($hospitalName) ?></h1>
                        <p class="text-xs text-teal-800 font-bold uppercase tracking-wider">Department of Inpatient Services & Clinical Governance</p>
                        <p class="text-[11px] text-slate-500 mt-0.5"><?= e($hospitalAddress) ?></p>
                    </div>
                </div>
                <div class="text-left sm:text-right text-xs text-slate-600">
                    <h2 class="text-base font-black text-slate-900 uppercase tracking-tight">DISCHARGE SUMMARY</h2>
                    <p class="font-mono font-bold text-teal-800">Adm Ref: #IPD-<?= str_pad($adm['id'], 5, '0', STR_PAD_LEFT) ?></p>
                    <p>Printed: <?= date('Y-m-d H:i') ?></p>
                </div>
            </div>

            <!-- Patient Demographic & Admission Matrix -->
            <div class="my-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Patient Name</span>
                    <strong class="text-slate-900 text-sm block"><?= e($adm['patient_name']) ?></strong>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Patient MRN</span>
                    <span class="font-mono font-bold text-teal-700"><?= e($adm['mrn']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Age / Gender</span>
                    <span class="font-bold text-slate-800"><?= calculateAge($adm['date_of_birth']) ?> yrs &bull; <?= e($adm['gender']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Blood Group</span>
                    <span class="font-bold text-rose-600"><?= e($adm['blood_group']) ?></span>
                </div>

                <div class="pt-2 border-t border-slate-200">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Admission Date</span>
                    <span class="font-bold text-slate-900"><?= formatDate($adm['admission_date']) ?></span>
                </div>
                <div class="pt-2 border-t border-slate-200">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Discharge Date</span>
                    <span class="font-bold text-teal-800"><?= !empty($adm['discharge_date']) ? formatDate($adm['discharge_date']) : 'Pending Clearance' ?></span>
                </div>
                <div class="pt-2 border-t border-slate-200">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Ward / Bed Allocation</span>
                    <span class="font-bold text-slate-900"><?= e($adm['ward_name']) ?> (Bed <?= e($adm['bed_number']) ?>)</span>
                </div>
                <div class="pt-2 border-t border-slate-200">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Attending Physician</span>
                    <span class="font-bold text-slate-900">Dr. <?= e($adm['doctor_name']) ?></span>
                </div>
            </div>

            <!-- Clinical Course Details -->
            <div class="space-y-4 text-xs">
                
                <div class="p-4 rounded-2xl bg-white border border-slate-200 space-y-1">
                    <strong class="text-slate-900 uppercase tracking-wider text-[11px] block text-teal-800">Reason for Admission / Chief Complaints:</strong>
                    <p class="text-slate-700 leading-relaxed"><?= e($adm['reason_for_admission']) ?></p>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-slate-200 space-y-1">
                    <strong class="text-slate-900 uppercase tracking-wider text-[11px] block text-teal-800">Hospital Course & Clinical Management:</strong>
                    <p class="text-slate-700 leading-relaxed">
                        Patient was admitted under the care of Dr. <?= e($adm['doctor_name']) ?> for inpatient therapeutic management and monitoring. Vitals were charted continuously with hemodynamic stabilization. Daily clinical rounds confirmed progressive symptomatic improvement.
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-teal-50/50 border border-teal-100 space-y-1">
                    <strong class="text-teal-900 uppercase tracking-wider text-[11px] block">Discharge Diagnosis & Condition at Discharge:</strong>
                    <p class="text-teal-950 font-bold leading-relaxed">
                        Condition: Clinically stable, afebrile, hemodynamically compensated, ambulating well. Approved for home convalescence.
                    </p>
                </div>

                <!-- Discharge Medications -->
                <?php if (!empty($dischargeMeds)): ?>
                    <div class="p-4 rounded-2xl bg-white border border-slate-200 space-y-2">
                        <strong class="text-slate-900 uppercase tracking-wider text-[11px] block text-teal-800">Discharge Medications & Regimen:</strong>
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="py-2 px-2">Medication</th>
                                    <th class="py-2 px-2">Dosage</th>
                                    <th class="py-2 px-2">Frequency</th>
                                    <th class="py-2 px-2">Duration</th>
                                    <th class="py-2 px-2">Instructions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($dischargeMeds as $dm): ?>
                                    <tr>
                                        <td class="py-2 px-2 font-bold text-slate-900"><?= e($dm['medicine_name']) ?></td>
                                        <td class="py-2 px-2"><?= e($dm['dosage']) ?></td>
                                        <td class="py-2 px-2 font-mono"><?= e($dm['frequency']) ?></td>
                                        <td class="py-2 px-2"><?= e($dm['duration']) ?></td>
                                        <td class="py-2 px-2 text-slate-500 italic"><?= e($dm['instructions'] ?: 'As directed') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-700 flex justify-between items-center">
                    <div>
                        <strong class="block text-slate-900">Follow-Up Review Clinic Date:</strong>
                        <span>7 to 10 days at Outpatient Clinic with Dr. <?= e($adm['doctor_name']) ?>.</span>
                    </div>
                    <span class="px-3 py-1 bg-teal-100 text-teal-800 font-bold rounded-xl text-xs font-mono">
                        Follow-Up: <?= date('Y-m-d', strtotime('+7 days')) ?>
                    </span>
                </div>

            </div>
        </div>

        <!-- Footer Signatures -->
        <div class="pt-8 border-t border-slate-200 mt-auto">
            <div class="flex items-end justify-between">
                <div class="max-w-xs text-[10px] text-slate-400">
                    <p class="font-bold text-slate-600">Discharge Clearance Verification</p>
                    <p>Issued by <?= e($hospitalName) ?>. Valid without alteration.</p>
                </div>

                <div class="text-center w-64">
                    <div class="h-12 flex items-center justify-center">
                        <span class="text-teal-800 font-serif italic text-lg opacity-80 select-none">
                            Dr. <?= e($adm['doctor_name']) ?>
                        </span>
                    </div>
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <p class="text-xs font-bold text-slate-900">Dr. <?= e($adm['doctor_name']) ?></p>
                        <p class="text-[10px] text-slate-500 font-semibold"><?= e($adm['specialization'] ?: 'Attending Specialist Physician') ?></p>
                        <p class="text-[10px] text-slate-400">Licensed Clinical Consultant</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
