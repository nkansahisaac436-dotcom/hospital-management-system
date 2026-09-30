<?php
/**
 * CarePoint Pro HMS - Analytics & Executive Reports
 */

$pageTitle = 'Hospital Analytics & Reports';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth(['admin', 'accountant']);

$pdo = Database::getConnection();

// Financial Summaries
$totalBilled = $pdo->query("SELECT SUM(total_amount) as total FROM invoices")->fetch()['total'] ?? 0.00;
$totalCollected = $pdo->query("SELECT SUM(paid_amount) as total FROM invoices")->fetch()['total'] ?? 0.00;
$pharmacyTotal = $pdo->query("SELECT SUM(total_amount) as total FROM pharmacy_sales")->fetch()['total'] ?? 0.00;
$labTotal = $pdo->query("SELECT SUM(total_amount) as total FROM lab_requests WHERE status = 'Completed'")->fetch()['total'] ?? 0.00;

// Doctor Performance Breakdown
$docPerfSql = "SELECT u.full_name as doctor_name, dep.name as dept_name, d.consultation_fee,
               (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id) as total_appointments,
               (SELECT COUNT(*) FROM opd_consultations c WHERE c.doctor_id = d.id) as completed_consultations,
               (SELECT COUNT(*) FROM ipd_admissions adm WHERE adm.doctor_id = d.id) as total_admissions
               FROM doctors d
               JOIN users u ON d.user_id = u.id
               JOIN departments dep ON d.department_id = dep.id
               ORDER BY total_appointments DESC";
$doctorStats = $pdo->query($docPerfSql)->fetchAll();

// Top Prescribed Medicines
$topDrugsSql = "SELECT pi.medicine_name, COUNT(*) as prescription_count, SUM(pi.quantity) as total_quantity
                FROM prescription_items pi
                GROUP BY pi.medicine_name
                ORDER BY prescription_count DESC
                LIMIT 5";
$topDrugs = $pdo->query($topDrugsSql)->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Executive Analytics & Reports</h1>
            <p class="text-xs text-slate-500 mt-1">Hospital financial performance, clinical operations, department utilization, and doctor workload.</p>
        </div>
        <div>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center">
                <i class="fa-solid fa-print mr-1.5"></i> Print Executive Report
            </button>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Billed Tariffs</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1 font-mono"><?= formatMoney($totalBilled) ?></h3>
                <span class="text-[11px] font-semibold text-slate-500">Gross Billed Value</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Cash Settlements</p>
                <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono"><?= formatMoney($totalCollected) ?></h3>
                <span class="text-[11px] font-semibold text-emerald-600">Net Hospital Revenue</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pharmacy Turnover</p>
                <h3 class="text-2xl font-black text-teal-600 mt-1 font-mono"><?= formatMoney($pharmacyTotal) ?></h3>
                <span class="text-[11px] font-semibold text-teal-600">Dispensary Receipts</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-pills"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Laboratory Output</p>
                <h3 class="text-2xl font-black text-purple-600 mt-1 font-mono"><?= formatMoney($labTotal) ?></h3>
                <span class="text-[11px] font-semibold text-purple-600">Diagnostic Revenue</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-flask-vial"></i>
            </div>
        </div>

    </div>

    <!-- Doctor Performance Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-user-doctor text-teal-600 mr-2"></i> Doctor Workload & Clinical Performance
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-6">Doctor Name</th>
                        <th class="py-3 px-6">Department</th>
                        <th class="py-3 px-6">Consultation Fee</th>
                        <th class="py-3 px-6">Total Appointments</th>
                        <th class="py-3 px-6">Completed OPD Visits</th>
                        <th class="py-3 px-6">IPD Admissions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php foreach ($doctorStats as $d): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-6 font-bold text-slate-900 text-sm"><?= e($d['doctor_name']) ?></td>
                            <td class="py-3.5 px-6">
                                <span class="px-2.5 py-1 rounded-full text-xs bg-sky-50 text-sky-700 font-semibold"><?= e($d['dept_name']) ?></span>
                            </td>
                            <td class="py-3.5 px-6 font-mono font-bold text-emerald-700"><?= formatMoney($d['consultation_fee']) ?></td>
                            <td class="py-3.5 px-6 font-bold text-slate-800"><?= $d['total_appointments'] ?></td>
                            <td class="py-3.5 px-6 font-bold text-teal-700"><?= $d['completed_consultations'] ?></td>
                            <td class="py-3.5 px-6 font-bold text-purple-700"><?= $d['total_admissions'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Prescribed Drugs -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
        <h3 class="text-base font-bold text-slate-900 flex items-center">
            <i class="fa-solid fa-prescription text-sky-600 mr-2"></i> Top Most Prescribed Pharmaceutical Formulations
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <?php foreach ($topDrugs as $drug): ?>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-slate-900 text-xs"><?= e($drug['medicine_name']) ?></h4>
                        <span class="text-[11px] text-slate-400"><?= $drug['prescription_count'] ?> Prescriptions issued</span>
                    </div>
                    <span class="font-mono font-bold text-teal-700 text-xs bg-teal-50 px-2.5 py-1 rounded-xl border border-teal-200">
                        <?= $drug['total_quantity'] ?> units
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
