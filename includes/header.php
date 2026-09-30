<?php
/**
 * CarePoint Pro HMS - Global Layout Header
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// Auto-check DB connection & redirect to installer if not configured
if (!Database::isConnected()) {
    header('Location: ' . APP_URL . '/install.php');
    exit;
}

$user = currentUser();
$hospitalName = getSetting('hospital_name', DEFAULT_HOSPITAL_NAME);
$hospitalTagline = getSetting('hospital_tagline', DEFAULT_HOSPITAL_TAGLINE);
$currency = getSetting('currency_symbol', DEFAULT_CURRENCY);
$flash = getFlash();

$pageTitle = $pageTitle ?? 'Hospital Management System';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e($hospitalName) ?></title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0284c7',
                            600: '#0369a1',
                            700: '#075985',
                        },
                        medical: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                        }
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome 6 Pro CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery & DataTables CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="h-full antialiased text-slate-800 bg-slate-100 flex flex-col">

    <!-- Quick Role Switcher Banner (For Effortless Client Demos - Collapsible) -->
    <div id="demo-bar" class="bg-slate-900 text-slate-300 text-xs px-4 py-1.5 flex flex-wrap items-center justify-between border-b border-slate-800 no-print transition-all duration-200">
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded bg-teal-500/20 text-teal-400 font-semibold uppercase tracking-wider text-[10px]">
                <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Demo Role Switcher:
            </span>
            <span class="hidden sm:inline text-slate-400">Switch workspace:</span>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 py-0.5">
            <a href="<?= APP_URL ?>/modules/auth/switch_role.php?role=admin" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition font-medium text-[11px]">Super Admin</a>
            <a href="<?= APP_URL ?>/modules/auth/switch_role.php?role=doctor" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition font-medium text-[11px]">Doctor</a>
            <a href="<?= APP_URL ?>/modules/auth/switch_role.php?role=nurse" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition font-medium text-[11px]">Nurse</a>
            <a href="<?= APP_URL ?>/modules/auth/switch_role.php?role=reception" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition font-medium text-[11px]">Reception</a>
            <a href="<?= APP_URL ?>/modules/auth/switch_role.php?role=pharmacist" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition font-medium text-[11px]">Pharmacy</a>
            <a href="<?= APP_URL ?>/modules/auth/switch_role.php?role=labtech" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition font-medium text-[11px]">Laboratory</a>
            <a href="<?= APP_URL ?>/modules/auth/switch_role.php?role=accountant" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition font-medium text-[11px]">Cashier</a>
            <a href="<?= APP_URL ?>/modules/auth/switch_role.php?role=patient" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition font-medium text-[11px]">Patient</a>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm no-print">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Left: Mobile Menu Toggle & Brand -->
                <div class="flex items-center space-x-3">
                    <button id="mobile-menu-toggle" type="button" class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>

                    <a href="<?= APP_URL ?>/modules/dashboard/index.php" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-teal-600 to-sky-600 text-white flex items-center justify-center text-xl shadow-md group-hover:shadow-teal-500/20 transition">
                            <i class="fa-solid fa-hospital"></i>
                        </div>
                        <div>
                            <span class="text-base font-bold text-slate-900 tracking-tight block leading-tight group-hover:text-teal-600 transition"><?= e($hospitalName) ?></span>
                            <span class="text-[11px] text-slate-400 font-medium block leading-none"><?= e($hospitalTagline) ?></span>
                        </div>
                    </a>
                </div>

                <!-- Right: Active Role & User Profile -->
                <div class="flex items-center space-x-3">
                    
                    <!-- Quick Actions for Front Desk / Admin -->
                    <?php if (hasRole(['admin', 'reception'])): ?>
                        <div class="hidden md:flex items-center space-x-2 mr-2">
                            <a href="<?= APP_URL ?>/modules/patients/add.php" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-teal-50 text-teal-700 hover:bg-teal-100 text-xs font-semibold transition border border-teal-200">
                                <i class="fa-solid fa-user-plus mr-1.5"></i> Register Patient
                            </a>
                            <a href="<?= APP_URL ?>/modules/appointments/book.php" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-sky-50 text-sky-700 hover:bg-sky-100 text-xs font-semibold transition border border-sky-200">
                                <i class="fa-solid fa-calendar-plus mr-1.5"></i> Book Appointment
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- User Account Pill -->
                    <?php if ($user): ?>
                        <div class="flex items-center space-x-3 pl-3 border-l border-slate-200">
                            <div class="text-right hidden sm:block">
                                <span class="text-xs font-bold text-slate-800 block leading-tight"><?= e($user['name']) ?></span>
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-teal-100 text-teal-800">
                                    <?= e($user['role']) ?>
                                </span>
                            </div>
                            <div class="relative group">
                                <button class="w-10 h-10 rounded-full bg-slate-200 border-2 border-teal-500/40 text-slate-700 flex items-center justify-center font-bold text-sm overflow-hidden focus:outline-none shadow-sm">
                                    <i class="fa-solid fa-user text-slate-600"></i>
                                </button>
                                
                                <!-- Dropdown Menu -->
                                <div class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 hidden group-hover:block transition z-50">
                                    <div class="px-4 py-2 border-b border-slate-100">
                                        <p class="text-xs font-bold text-slate-800"><?= e($user['name']) ?></p>
                                        <p class="text-[11px] text-slate-400 truncate"><?= e($user['email']) ?></p>
                                    </div>
                                    <a href="<?= APP_URL ?>/modules/users/profile.php" class="block px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-teal-600">
                                        <i class="fa-solid fa-user-gear mr-2 text-slate-400"></i> My Profile
                                    </a>
                                    <?php if (hasRole('admin')): ?>
                                        <a href="<?= APP_URL ?>/modules/settings/index.php" class="block px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-teal-600">
                                            <i class="fa-solid fa-sliders mr-2 text-slate-400"></i> System Settings
                                        </a>
                                    <?php endif; ?>
                                    <div class="border-t border-slate-100 my-1"></div>
                                    <a href="<?= APP_URL ?>/modules/auth/logout.php" class="block px-4 py-2 text-xs text-red-600 hover:bg-red-50">
                                        <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i> Sign Out
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= APP_URL ?>/modules/auth/login.php" class="px-4 py-2 rounded-xl bg-teal-600 text-white font-semibold text-xs hover:bg-teal-700 transition">
                            Sign In
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar Inclusion -->
        <?php if ($user): ?>
            <?php include_once __DIR__ . '/sidebar.php'; ?>
        <?php endif; ?>

        <!-- Main Content Wrapper -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            
            <!-- Global Flash Messages -->
            <?php if ($flash): ?>
                <div class="mb-6 rounded-2xl p-4 flex items-start space-x-3 shadow-sm no-print <?= $flash['type'] === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : ($flash['type'] === 'error' ? 'bg-rose-50 border border-rose-200 text-rose-800' : 'bg-amber-50 border border-amber-200 text-amber-800') ?>">
                    <div class="text-xl">
                        <?php if ($flash['type'] === 'success'): ?>
                            <i class="fa-solid fa-circle-check text-emerald-600"></i>
                        <?php elseif ($flash['type'] === 'error'): ?>
                            <i class="fa-solid fa-circle-xmark text-rose-600"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                        <?php endif; ?>
                    </div>
                    <div class="text-sm font-medium pt-0.5">
                        <?= e($flash['message']) ?>
                    </div>
                </div>
            <?php endif; ?>
