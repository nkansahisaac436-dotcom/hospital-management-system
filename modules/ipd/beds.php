<?php
/**
 * CarePoint Pro HMS - Live Inpatient Bed & Ward Matrix
 */

$pageTitle = 'Live Bed Matrix & Wards';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

// Fetch Wards and their Beds
$wards = $pdo->query("SELECT * FROM wards ORDER BY floor ASC, name ASC")->fetchAll();

$bedsSql = "SELECT b.*, w.name as ward_name, w.type as ward_type, p.full_name as patient_name, p.mrn, p.gender,
            adm.admission_number, adm.admission_date, u.full_name as doctor_name
            FROM beds b
            JOIN wards w ON b.ward_id = w.id
            LEFT JOIN patients p ON b.current_patient_id = p.id
            LEFT JOIN ipd_admissions adm ON (adm.bed_id = b.id AND adm.status = 'Admitted')
            LEFT JOIN doctors d ON adm.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            ORDER BY w.name ASC, b.bed_number ASC";
$beds = $pdo->query($bedsSql)->fetchAll();

// Group beds by ward
$bedsByWard = [];
foreach ($beds as $bed) {
    $bedsByWard[$bed['ward_id']][] = $bed;
}

// Summary Statistics
$totalBeds = count($beds);
$occupiedBeds = count(array_filter($beds, fn($b) => $b['status'] === 'Occupied'));
$availableBeds = count(array_filter($beds, fn($b) => $b['status'] === 'Available'));
$maintenanceBeds = count(array_filter($beds, fn($b) => $b['status'] === 'Maintenance'));
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Live Bed Matrix & Ward Grid</h1>
            <p class="text-xs text-slate-500 mt-1">Real-time inpatient bed occupancy, ward telemetry, and quick admission triggers.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="index.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                <i class="fa-solid fa-list-check mr-1.5"></i> Admissions List
            </a>
            <a href="admit.php" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                <i class="fa-solid fa-bed mr-1.5"></i> Admit Patient to Bed
            </a>
        </div>
    </div>

    <!-- Bed Status Legend Counters -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase text-slate-400">Total Capacity</span>
                <h4 class="text-xl font-black text-slate-900 mt-0.5"><?= $totalBeds ?> Beds</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-base">
                <i class="fa-solid fa-hospital"></i>
            </div>
        </div>

        <div class="bg-emerald-50/60 rounded-2xl p-4 border border-emerald-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase text-emerald-600">Available / Free</span>
                <h4 class="text-xl font-black text-emerald-700 mt-0.5"><?= $availableBeds ?> Beds</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold text-base">
                <i class="fa-solid fa-check"></i>
            </div>
        </div>

        <div class="bg-rose-50/60 rounded-2xl p-4 border border-rose-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase text-rose-600">Occupied</span>
                <h4 class="text-xl font-black text-rose-700 mt-0.5"><?= $occupiedBeds ?> Beds</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center font-bold text-base">
                <i class="fa-solid fa-user-injured"></i>
            </div>
        </div>

        <div class="bg-amber-50/60 rounded-2xl p-4 border border-amber-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase text-amber-600">Under Maintenance</span>
                <h4 class="text-xl font-black text-amber-700 mt-0.5"><?= $maintenanceBeds ?> Beds</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-base">
                <i class="fa-solid fa-screwdriver-wrench"></i>
            </div>
        </div>

    </div>

    <!-- Wards and Bed Grid -->
    <div class="space-y-6">
        <?php foreach ($wards as $ward): ?>
            <?php $wardBeds = $bedsByWard[$ward['id']] ?? []; ?>
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                
                <!-- Ward Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-3 gap-2">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-lg font-bold">
                            <i class="fa-solid fa-procedures"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-base"><?= e($ward['name']) ?></h3>
                            <p class="text-xs text-slate-400"><?= e($ward['floor']) ?> &bull; Type: <strong class="text-teal-700"><?= e($ward['type']) ?></strong> &bull; Daily Rate: <strong class="text-slate-800"><?= formatMoney($ward['daily_rate']) ?>/day</strong></p>
                        </div>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-600 px-3 py-1 bg-slate-100 rounded-full">
                            <?= count($wardBeds) ?> Total Beds
                        </span>
                    </div>
                </div>

                <!-- Bed Tiles Matrix -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 pt-2">
                    <?php if (!empty($wardBeds)): ?>
                        <?php foreach ($wardBeds as $bed): ?>
                            <?php 
                                $isOccupied = ($bed['status'] === 'Occupied');
                                $isAvailable = ($bed['status'] === 'Available');
                                $isMaintenance = ($bed['status'] === 'Maintenance');
                            ?>
                            <div class="p-4 rounded-2xl border transition relative flex flex-col justify-between space-y-3 <?= $isOccupied ? 'bg-rose-50/50 border-rose-200' : ($isAvailable ? 'bg-emerald-50/40 border-emerald-200' : 'bg-amber-50/40 border-amber-200') ?>">
                                
                                <div>
                                    <!-- Bed Number & Status Pill -->
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-900 text-sm flex items-center">
                                            <i class="fa-solid fa-bed mr-1.5 <?= $isOccupied ? 'text-rose-500' : ($isAvailable ? 'text-emerald-500' : 'text-amber-500') ?>"></i>
                                            <?= e($bed['bed_number']) ?>
                                        </span>
                                        <span class="badge <?= $isOccupied ? 'badge-danger' : ($isAvailable ? 'badge-success' : 'badge-warning') ?> text-[10px]">
                                            <?= e($bed['status']) ?>
                                        </span>
                                    </div>

                                    <!-- Patient / Occupancy Details -->
                                    <?php if ($isOccupied && !empty($bed['patient_name'])): ?>
                                        <div class="mt-2 text-xs space-y-0.5">
                                            <p class="font-bold text-slate-900 truncate"><?= e($bed['patient_name']) ?></p>
                                            <p class="text-[11px] text-slate-500 font-mono"><?= e($bed['mrn']) ?></p>
                                            <p class="text-[11px] text-teal-700">Dr. <?= e($bed['doctor_name']) ?></p>
                                            <p class="text-[10px] text-slate-400">Admitted: <?= formatDate($bed['admission_date']) ?></p>
                                        </div>
                                    <?php elseif ($isAvailable): ?>
                                        <div class="mt-2 text-xs text-emerald-700 font-medium">
                                            Ready for patient admission
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-2 text-xs text-amber-700 font-medium">
                                            Sanitizing / Maintenance
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Buttons -->
                                <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-700 font-mono text-[11px]"><?= formatMoney($bed['daily_rate']) ?>/d</span>
                                    <?php if ($isAvailable): ?>
                                        <a href="admit.php?bed_id=<?= $bed['id'] ?>" class="px-2.5 py-1 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-lg text-[11px] transition">
                                            Admit
                                        </a>
                                    <?php elseif ($isOccupied): ?>
                                        <a href="discharge.php?bed_id=<?= $bed['id'] ?>" class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-[11px] transition">
                                            Discharge
                                        </a>
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-full py-4 text-center text-slate-400 text-xs">
                            No beds configured in this ward yet.
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
