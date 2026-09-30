<?php
/**
 * CarePoint Pro HMS - Strict Role-Based Isolated Navigation Sidebar
 */

$userRole = currentRole();
$currentScript = $_SERVER['PHP_SELF'];

function isActiveNav(string $keyword): bool {
    global $currentScript;
    return strpos($currentScript, $keyword) !== false;
}
?>

<!-- Mobile Sidebar Backdrop -->
<div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden lg:hidden"></div>

<!-- Sidebar Container -->
<aside id="main-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 flex flex-col transition-transform duration-300 transform -translate-x-full lg:translate-x-0 lg:static lg:inset-auto lg:z-auto no-print">

    <!-- Role Badge Indicator in Sidebar -->
    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-teal-600/10 text-teal-600 flex items-center justify-center font-bold text-sm">
                <?php if ($userRole === 'admin'): ?>
                    <i class="fa-solid fa-shield-halved text-rose-500"></i>
                <?php elseif ($userRole === 'doctor'): ?>
                    <i class="fa-solid fa-user-doctor text-sky-500"></i>
                <?php elseif ($userRole === 'nurse'): ?>
                    <i class="fa-solid fa-user-nurse text-purple-500"></i>
                <?php elseif ($userRole === 'pharmacist'): ?>
                    <i class="fa-solid fa-pills text-teal-500"></i>
                <?php elseif ($userRole === 'labtech'): ?>
                    <i class="fa-solid fa-flask-vial text-indigo-500"></i>
                <?php elseif ($userRole === 'accountant'): ?>
                    <i class="fa-solid fa-file-invoice-dollar text-emerald-500"></i>
                <?php elseif ($userRole === 'reception'): ?>
                    <i class="fa-solid fa-clipboard-user text-amber-500"></i>
                <?php else: ?>
                    <i class="fa-solid fa-circle-user text-slate-500"></i>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Current Workspace</p>
                <p class="text-xs font-bold text-slate-800 capitalize"><?= e($userRole) ?> Console</p>
            </div>
        </div>
    </div>

    <!-- Navigation Links Container -->
    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-1">

        <!-- ========================================================= -->
        <!-- 1. RECEPTIONIST / FRONT DESK MENU (STRICT ISOLATION)       -->
        <!-- ========================================================= -->
        <?php if ($userRole === 'reception'): ?>
            <a href="<?= APP_URL ?>/modules/dashboard/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/dashboard/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                <span>Front Desk Desk</span>
            </a>

            <div class="pt-3 pb-1 px-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Patient Intake</span>
            </div>

            <a href="<?= APP_URL ?>/modules/patients/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/patients/index') || isActiveNav('/patients/view') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-hospital-user w-4 text-center"></i>
                <span>Patient Directory (MRN)</span>
            </a>

            <a href="<?= APP_URL ?>/modules/patients/add.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/patients/add') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-user-plus w-4 text-center"></i>
                <span>Register New Patient</span>
            </a>

            <div class="pt-3 pb-1 px-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Queue & Triage</span>
            </div>

            <a href="<?= APP_URL ?>/modules/appointments/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/appointments/index') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-clock-rotate-left w-4 text-center"></i>
                <span>Live Appointments Queue</span>
            </a>

            <a href="<?= APP_URL ?>/modules/appointments/book.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/appointments/book') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-calendar-plus w-4 text-center"></i>
                <span>Schedule Appointment</span>
            </a>

        <!-- ========================================================= -->
        <!-- 2. DOCTOR MENU                                            -->
        <!-- ========================================================= -->
        <?php elseif ($userRole === 'doctor'): ?>
            <a href="<?= APP_URL ?>/modules/dashboard/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/dashboard/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                <span>Doctor Workspace</span>
            </a>

            <div class="pt-3 pb-1 px-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Clinical Consultations</span>
            </div>

            <a href="<?= APP_URL ?>/modules/opd/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/opd/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-stethoscope w-4 text-center"></i>
                <span>OPD Consultation Room</span>
            </a>

            <a href="<?= APP_URL ?>/modules/prescriptions/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/prescriptions/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-prescription w-4 text-center"></i>
                <span>E-Prescriptions</span>
            </a>

            <a href="<?= APP_URL ?>/modules/laboratory/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/laboratory/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-flask-vial w-4 text-center"></i>
                <span>Order Diagnostic Labs</span>
            </a>

            <div class="pt-3 pb-1 px-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Inpatients</span>
            </div>

            <a href="<?= APP_URL ?>/modules/ipd/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/ipd/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-procedures w-4 text-center"></i>
                <span>My Inpatient Admissions</span>
            </a>

            <a href="<?= APP_URL ?>/modules/patients/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/patients/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-hospital-user w-4 text-center"></i>
                <span>Patients & EMR Lookup</span>
            </a>

        <!-- ========================================================= -->
        <!-- 3. NURSE MENU                                             -->
        <!-- ========================================================= -->
        <?php elseif ($userRole === 'nurse'): ?>
            <a href="<?= APP_URL ?>/modules/dashboard/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/dashboard/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                <span>Nursing Station</span>
            </a>

            <div class="pt-3 pb-1 px-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Ward & Beds</span>
            </div>

            <a href="<?= APP_URL ?>/modules/ipd/beds.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/ipd/beds') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-bed-pulse w-4 text-center"></i>
                <span>Live Bed Matrix Grid</span>
            </a>

            <a href="<?= APP_URL ?>/modules/ipd/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/ipd/index') || isActiveNav('/ipd/admit') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-procedures w-4 text-center"></i>
                <span>Inpatient Admissions</span>
            </a>

            <a href="<?= APP_URL ?>/modules/ipd/admit.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/ipd/admit') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-user-plus w-4 text-center"></i>
                <span>Admit to Ward Bed</span>
            </a>

            <a href="<?= APP_URL ?>/modules/patients/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/patients/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-hospital-user w-4 text-center"></i>
                <span>Patients Roster</span>
            </a>

        <!-- ========================================================= -->
        <!-- 4. PHARMACIST MENU                                        -->
        <!-- ========================================================= -->
        <?php elseif ($userRole === 'pharmacist'): ?>
            <a href="<?= APP_URL ?>/modules/dashboard/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/dashboard/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                <span>Pharmacy Desk</span>
            </a>

            <div class="pt-3 pb-1 px-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">POS & Dispensing</span>
            </div>

            <a href="<?= APP_URL ?>/modules/pharmacy/pos.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/pharmacy/pos') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-cash-register w-4 text-center"></i>
                <span>Point of Sale (POS)</span>
            </a>

            <a href="<?= APP_URL ?>/modules/pharmacy/medicines.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/pharmacy/medicines') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-pills w-4 text-center"></i>
                <span>Drug Stock Catalog</span>
            </a>

            <a href="<?= APP_URL ?>/modules/pharmacy/add_medicine.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/pharmacy/add_medicine') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-plus w-4 text-center"></i>
                <span>Add Drug Formulation</span>
            </a>

            <a href="<?= APP_URL ?>/modules/pharmacy/sales.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/pharmacy/sales') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-receipt w-4 text-center"></i>
                <span>Dispensing Sales History</span>
            </a>

        <!-- ========================================================= -->
        <!-- 5. LAB SCIENTIST MENU                                     -->
        <!-- ========================================================= -->
        <?php elseif ($userRole === 'labtech'): ?>
            <a href="<?= APP_URL ?>/modules/dashboard/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/dashboard/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                <span>Laboratory Desk</span>
            </a>

            <div class="pt-3 pb-1 px-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">LIS Investigations</span>
            </div>

            <a href="<?= APP_URL ?>/modules/laboratory/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/laboratory/index') || isActiveNav('/laboratory/enter_result') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-flask-vial w-4 text-center"></i>
                <span>Work Orders Queue</span>
            </a>

            <a href="<?= APP_URL ?>/modules/laboratory/catalog.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/laboratory/catalog') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-list-check w-4 text-center"></i>
                <span>Diagnostic Test Catalog</span>
            </a>

            <a href="<?= APP_URL ?>/modules/blood_bank/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/blood_bank/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-droplet w-4 text-center text-rose-500"></i>
                <span>Blood Bank Inventory</span>
            </a>

        <!-- ========================================================= -->
        <!-- 6. CASHIER / ACCOUNTANT MENU                              -->
        <!-- ========================================================= -->
        <?php elseif ($userRole === 'accountant'): ?>
            <a href="<?= APP_URL ?>/modules/dashboard/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/dashboard/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                <span>Cashier Terminal</span>
            </a>

            <div class="pt-3 pb-1 px-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Billing & Invoicing</span>
            </div>

            <a href="<?= APP_URL ?>/modules/billing/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/billing/index') || isActiveNav('/billing/view') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-file-invoice-dollar w-4 text-center"></i>
                <span>Invoices & Payments</span>
            </a>

            <a href="<?= APP_URL ?>/modules/billing/create.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/billing/create') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-plus w-4 text-center"></i>
                <span>Generate Custom Invoice</span>
            </a>

        <!-- ========================================================= -->
        <!-- 7. SUPER ADMIN MENU (FULL CONTROL)                        -->
        <!-- ========================================================= -->
        <?php else: ?>
            <a href="<?= APP_URL ?>/modules/dashboard/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/dashboard/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                <span>Command Center</span>
            </a>

            <div class="pt-3 pb-1 px-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Hospital Operations</span>
            </div>

            <a href="<?= APP_URL ?>/modules/patients/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/patients/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-hospital-user w-4 text-center"></i>
                <span>Patients & EMR</span>
            </a>

            <a href="<?= APP_URL ?>/modules/appointments/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/appointments/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-calendar-check w-4 text-center"></i>
                <span>Appointments & Queue</span>
            </a>

            <a href="<?= APP_URL ?>/modules/doctors/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/doctors/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-user-doctor w-4 text-center"></i>
                <span>Doctors & Depts</span>
            </a>

            <a href="<?= APP_URL ?>/modules/ipd/beds.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/ipd/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-bed-pulse w-4 text-center"></i>
                <span>Ward Bed Matrix</span>
            </a>

            <a href="<?= APP_URL ?>/modules/pharmacy/medicines.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/pharmacy/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-pills w-4 text-center"></i>
                <span>Pharmacy & POS</span>
            </a>

            <a href="<?= APP_URL ?>/modules/laboratory/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/laboratory/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-flask-vial w-4 text-center"></i>
                <span>Laboratory (LIS)</span>
            </a>

            <a href="<?= APP_URL ?>/modules/billing/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/billing/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-file-invoice-dollar w-4 text-center"></i>
                <span>Invoices & Cashier</span>
            </a>

            <a href="<?= APP_URL ?>/modules/blood_bank/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/blood_bank/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-droplet w-4 text-center text-rose-500"></i>
                <span>Blood Bank</span>
            </a>

            <div class="pt-3 pb-1 px-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Administration & Governance</span>
            </div>

            <a href="<?= APP_URL ?>/modules/users/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/users/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-users-gear w-4 text-center"></i>
                <span>Staff & HR Directory</span>
            </a>

            <a href="<?= APP_URL ?>/modules/reports/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/reports/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-chart-line w-4 text-center"></i>
                <span>Hospital Analytics</span>
            </a>

            <a href="<?= APP_URL ?>/modules/audit_logs/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/audit_logs/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-shield-halved w-4 text-center"></i>
                <span>Audit Trail</span>
            </a>

            <a href="<?= APP_URL ?>/modules/settings/backup.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/backup') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-database w-4 text-center"></i>
                <span>Database Backup</span>
            </a>

            <a href="<?= APP_URL ?>/modules/settings/index.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition <?= isActiveNav('/settings/') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <i class="fa-solid fa-sliders w-4 text-center"></i>
                <span>System White-Labeling</span>
            </a>
        <?php endif; ?>

    </div>

    <!-- User Mini Profile & Logout in Sidebar Bottom -->
    <div class="p-3 border-t border-slate-100 bg-slate-50 space-y-1">
        <a href="<?= APP_URL ?>/modules/users/profile.php" class="flex items-center space-x-2 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-200/60 transition w-full">
            <i class="fa-solid fa-user-gear text-slate-500"></i>
            <span>My Profile</span>
        </a>
        <a href="<?= APP_URL ?>/modules/auth/logout.php" class="flex items-center space-x-2 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 transition w-full">
            <i class="fa-solid fa-power-off"></i>
            <span>Sign Out</span>
        </a>
    </div>

</aside>
