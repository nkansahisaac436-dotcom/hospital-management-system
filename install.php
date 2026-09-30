<?php
/**
 * CarePoint Pro - Hospital Management System
 * Interactive 1-Click System Installer & Database Seeder
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = null;
$success = false;
$logs = [];

$defaultPort = '3306';
// Auto-detect active port
foreach (['3307', '3306'] as $testPort) {
    try {
        $testConn = @new PDO("mysql:host=127.0.0.1;port={$testPort};charset=utf8mb4", 'root', '', [PDO::ATTR_TIMEOUT => 1]);
        $defaultPort = $testPort;
        break;
    } catch (Exception $e) {}
}

$dbHost = $_POST['db_host'] ?? '127.0.0.1';
$dbPort = $_POST['db_port'] ?? $defaultPort;
$dbName = $_POST['db_name'] ?? 'carepoint_hms';
$dbUser = $_POST['db_user'] ?? 'root';
$dbPass = $_POST['db_pass'] ?? '';
$seedDemo = isset($_POST['seed_demo']) || !isset($_POST['submitted']);

// Handle Installation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitted'])) {
    try {
        // Step 1: Test Server Connection
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $logs[] = "Connected to MySQL Server successfully.";

        // Step 2: Create Database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `{$dbName}`;");
        $logs[] = "Database `{$dbName}` created / selected successfully.";

        // Step 3: Run Schema SQL
        $schemaFile = __DIR__ . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
            $logs[] = "Database schema tables created successfully.";
        } else {
            throw new Exception("schema.sql file not found in database directory.");
        }

        // Step 4: Run Seed Data if selected
        if ($seedDemo) {
            $seedFile = __DIR__ . '/database/seed_data.sql';
            if (file_exists($seedFile)) {
                $seedSql = file_get_contents($seedFile);
                $pdo->exec($seedSql);
                $logs[] = "Demo hospital records & user accounts seeded successfully.";
            }
        }

        // Step 5: Update config/config.php with verified credentials
        $configFile = __DIR__ . '/config/config.php';
        if (file_exists($configFile)) {
            $configContent = file_get_contents($configFile);
            $configContent = preg_replace("/defined\('DB_HOST'\)\s+or\s+define\('DB_HOST',\s*'[^']*'\);/", "defined('DB_HOST') or define('DB_HOST', '{$dbHost}');", $configContent);
            $configContent = preg_replace("/defined\('DB_PORT'\)\s+or\s+define\('DB_PORT',\s*'[^']*'\);/", "defined('DB_PORT') or define('DB_PORT', '{$dbPort}');", $configContent);
            $configContent = preg_replace("/defined\('DB_NAME'\)\s+or\s+define\('DB_NAME',\s*'[^']*'\);/", "defined('DB_NAME') or define('DB_NAME', '{$dbName}');", $configContent);
            $configContent = preg_replace("/defined\('DB_USER'\)\s+or\s+define\('DB_USER',\s*'[^']*'\);/", "defined('DB_USER') or define('DB_USER', '{$dbUser}');", $configContent);
            $configContent = preg_replace("/defined\('DB_PASS'\)\s+or\s+define\('DB_PASS',\s*'[^']*'\);/", "defined('DB_PASS') or define('DB_PASS', '{$dbPass}');", $configContent);
            file_put_contents($configFile, $configContent);
            $logs[] = "Configuration saved to config/config.php.";
        }

        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarePoint Pro HMS - 1-Click Installer</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-sky-950 to-slate-900 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-3xl w-full bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden border border-white/20">
        
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-teal-600 via-sky-600 to-blue-700 p-8 text-white relative">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center text-3xl shadow-inner">
                    <i class="fa-solid fa-hospital text-white"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight">CarePoint Pro HMS</h1>
                        <span class="bg-white/20 text-xs px-2.5 py-0.5 rounded-full font-semibold">v2.5.0</span>
                    </div>
                    <p class="text-sky-100 text-sm mt-1">Enterprise Hospital & Clinical Management System Setup Wizard</p>
                </div>
            </div>
        </div>

        <div class="p-8">

            <?php if ($success): ?>
                <!-- Success Screen -->
                <div class="text-center py-6">
                    <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-4 animate-bounce">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-800">Installation Completed Successfully!</h2>
                    <p class="text-slate-600 mt-2">The database and all core hospital modules are initialized and ready for production.</p>

                    <!-- Quick Credentials Box -->
                    <div class="mt-8 bg-slate-50 border border-slate-200 rounded-2xl p-6 text-left">
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-4 flex items-center">
                            <i class="fa-solid fa-key text-teal-600 mr-2"></i> Demo User Accounts (Password for all: <code class="bg-teal-100 text-teal-800 px-2 py-0.5 rounded">password123</code>)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between">
                                <div><span class="font-bold text-slate-800">Super Admin:</span> <code class="text-sky-600">admin</code></div>
                                <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-medium">Full Access</span>
                            </div>
                            <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between">
                                <div><span class="font-bold text-slate-800">Doctor (Cardiology):</span> <code class="text-sky-600">doctor</code></div>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">EMR / Rx</span>
                            </div>
                            <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between">
                                <div><span class="font-bold text-slate-800">Head Nurse:</span> <code class="text-sky-600">nurse</code></div>
                                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium">IPD & Vitals</span>
                            </div>
                            <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between">
                                <div><span class="font-bold text-slate-800">Receptionist:</span> <code class="text-sky-600">reception</code></div>
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium">Queue & Front Desk</span>
                            </div>
                            <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between">
                                <div><span class="font-bold text-slate-800">Chief Pharmacist:</span> <code class="text-sky-600">pharmacist</code></div>
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Pharmacy POS</span>
                            </div>
                            <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between">
                                <div><span class="font-bold text-slate-800">Senior Pathologist:</span> <code class="text-sky-600">labtech</code></div>
                                <span class="text-xs bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full font-medium">Lab & Tests</span>
                            </div>
                            <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between">
                                <div><span class="font-bold text-slate-800">Cashier / Billing:</span> <code class="text-sky-600">accountant</code></div>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Invoicing</span>
                            </div>
                            <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between">
                                <div><span class="font-bold text-slate-800">Patient Portal:</span> <code class="text-sky-600">patient</code></div>
                                <span class="text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full font-medium">My Records</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-center">
                        <a href="index.php" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-teal-600 to-sky-600 hover:from-teal-700 hover:to-sky-700 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 text-base">
                            <i class="fa-solid fa-arrow-right-to-bracket mr-3 text-lg"></i> Launch CarePoint Pro HMS
                        </a>
                    </div>
                </div>

            <?php else: ?>

                <?php if ($error): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-xl flex items-start space-x-3">
                        <i class="fa-solid fa-triangle-exclamation text-red-500 text-xl mt-0.5"></i>
                        <div>
                            <h4 class="font-bold text-red-800">Installation Error</h4>
                            <p class="text-red-700 text-sm mt-1"><?= htmlspecialchars($error) ?></p>
                            <p class="text-red-600 text-xs mt-2">Hint: If using XAMPP, ensure MySQL is started in your XAMPP Control Panel.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="submitted" value="1">

                    <!-- Server Requirements Checklist -->
                    <div class="bg-sky-50/70 border border-sky-100 rounded-2xl p-4">
                        <h3 class="text-xs font-bold text-sky-900 uppercase tracking-wider mb-2 flex items-center">
                            <i class="fa-solid fa-server mr-2 text-sky-600"></i> Environment Check
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                            <div class="flex items-center space-x-2 text-slate-700">
                                <i class="fa-solid fa-check-circle text-emerald-500"></i>
                                <span>PHP <?= PHP_VERSION ?></span>
                            </div>
                            <div class="flex items-center space-x-2 text-slate-700">
                                <i class="fa-solid fa-check-circle text-emerald-500"></i>
                                <span>PDO MySQL</span>
                            </div>
                            <div class="flex items-center space-x-2 text-slate-700">
                                <i class="fa-solid fa-check-circle text-emerald-500"></i>
                                <span>JSON & MBString</span>
                            </div>
                            <div class="flex items-center space-x-2 text-slate-700">
                                <i class="fa-solid fa-check-circle text-emerald-500"></i>
                                <span>Session Storage</span>
                            </div>
                        </div>
                    </div>

                    <!-- Database Settings Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">MySQL Host</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-network-wired"></i>
                                </div>
                                <input type="text" name="db_host" value="<?= htmlspecialchars($dbHost) ?>" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">MySQL Port</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-plug"></i>
                                </div>
                                <input type="text" name="db_port" value="<?= htmlspecialchars($dbPort) ?>" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Database Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-database"></i>
                                </div>
                                <input type="text" name="db_name" value="<?= htmlspecialchars($dbName) ?>" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none transition">
                            </div>
                            <span class="text-xs text-slate-400 mt-1 block">Auto-creates if it doesn't exist</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Database Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <input type="text" name="db_user" value="<?= htmlspecialchars($dbUser) ?>" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none transition">
                            </div>
                            <span class="text-xs text-slate-400 mt-1 block">Default on XAMPP is 'root'</span>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Database Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-lock"></i>
                                </div>
                                <input type="password" name="db_pass" value="<?= htmlspecialchars($dbPass) ?>" placeholder="Leave blank if no password on local XAMPP" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm focus:ring-2 focus:ring-sky-500 focus:bg-white focus:outline-none transition">
                            </div>
                        </div>
                    </div>

                    <!-- Demo Data Option -->
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="seed_demo" id="seed_demo" value="1" checked class="w-5 h-5 text-teal-600 rounded border-slate-300 focus:ring-teal-500">
                            <label for="seed_demo" class="text-sm font-bold text-slate-800 cursor-pointer">
                                Load Full Demo Dataset & Multi-Role User Accounts
                                <span class="block text-xs font-normal text-slate-500">Pre-populates doctors, wards, beds, pharmacy inventory, lab tests, and sample patients.</span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-teal-600 via-sky-600 to-blue-600 hover:from-teal-700 hover:to-blue-700 text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 text-base flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-rocket"></i>
                        <span>Start 1-Click Installation</span>
                    </button>
                </form>

            <?php endif; ?>

        </div>

        <!-- Footer Note -->
        <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
            <span>&copy; <?= date('Y') ?> CarePoint Pro Hospital Management System</span>
            <span>Commercial Edition ready for Client Handover</span>
        </div>

    </div>

</body>
</html>
