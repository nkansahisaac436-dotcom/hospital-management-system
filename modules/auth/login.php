<?php
/**
 * CarePoint Pro HMS - Next-Generation Healthcare Authentication Portal
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . APP_URL . '/install.php');
    exit;
}

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/modules/dashboard/index.php');
    exit;
}

$error = null;
$flash = getFlash();
$hospitalName = getSetting('hospital_name', DEFAULT_HOSPITAL_NAME);
$hospitalTagline = getSetting('hospital_tagline', DEFAULT_HOSPITAL_TAGLINE);

// Rate Limiting Protection (5 failed attempts = 60s cooldown)
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$lockoutKey = 'login_lockout_' . md5($ip);
$attemptsKey = 'login_attempts_' . md5($ip);

if (isset($_SESSION[$lockoutKey]) && time() < $_SESSION[$lockoutKey]) {
    $remaining = $_SESSION[$lockoutKey] - time();
    $error = "Too many failed attempts. Security cooldown active: please wait {$remaining} seconds.";
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter your username/email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Reset failed attempts
            unset($_SESSION[$attemptsKey], $_SESSION[$lockoutKey]);

            // Regenerate session ID for security against session fixation
            session_regenerate_id(true);

            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_avatar']= $user['avatar'];

            if ($user['role'] === 'doctor') {
                $docStmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
                $docStmt->execute([$user['id']]);
                $doc = $docStmt->fetch();
                $_SESSION['doctor_id'] = $doc['id'] ?? null;
            }

            if ($user['role'] === 'patient') {
                $patStmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
                $patStmt->execute([$user['id']]);
                $pat = $patStmt->fetch();
                $_SESSION['patient_id'] = $pat['id'] ?? null;
            }

            logActivity('Login Success', "Logged in as {$user['role']}", $user['id']);
            setFlash('success', "Welcome back, <strong>{$user['full_name']}</strong>!");
            header('Location: ' . APP_URL . '/modules/dashboard/index.php');
            exit;
        } else {
            // Track failed attempts
            $_SESSION[$attemptsKey] = ($_SESSION[$attemptsKey] ?? 0) + 1;
            if ($_SESSION[$attemptsKey] >= 5) {
                $_SESSION[$lockoutKey] = time() + 60; // 60s lock
                $error = 'Too many failed login attempts. Security lock activated for 60 seconds.';
            } else {
                $remainingAttempts = 5 - $_SESSION[$attemptsKey];
                $error = "Invalid credentials. {$remainingAttempts} attempt(s) remaining before temporary lockout.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Sign In | <?= e($hospitalName) ?></title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdfa',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                        },
                        clinical: {
                            500: '#0284c7',
                            600: '#0369a1',
                            700: '#075985',
                        }
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome 6 Pro CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Font: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .glow-effect {
            box-shadow: 0 0 50px -10px rgba(20, 184, 166, 0.3);
        }
    </style>
</head>
<body class="h-full antialiased text-slate-100 flex items-center justify-center p-0 sm:p-4 md:p-6 bg-slate-950 relative overflow-x-hidden">

    <!-- Ambient Glowing Background Spheres -->
    <div class="fixed top-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-teal-600/20 blur-[120px] pointer-events-none"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-[500px] h-[500px] rounded-full bg-sky-600/20 blur-[120px] pointer-events-none"></div>

    <!-- Main Container -->
    <div class="w-full max-w-6xl min-h-[640px] bg-slate-900/90 rounded-none sm:rounded-[2.5rem] shadow-2xl border border-white/10 overflow-hidden grid grid-cols-1 lg:grid-cols-12 relative z-10 backdrop-blur-2xl">

        <!-- LEFT PANEL: Hero & Trust Branding (5 Cols) -->
        <div class="lg:col-span-5 bg-gradient-to-br from-teal-900/80 via-slate-900 to-sky-950/80 p-8 sm:p-12 flex flex-col justify-between border-b lg:border-b-0 lg:border-r border-white/10 relative overflow-hidden">
            
            <!-- Hospital Brand Header -->
            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-teal-500 to-sky-500 text-white flex items-center justify-center text-2xl shadow-lg shadow-teal-500/30">
                        <i class="fa-solid fa-hospital-user"></i>
                    </div>
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-widest text-teal-400 block leading-none">Enterprise Platform</span>
                        <h2 class="text-xl font-black text-white tracking-tight leading-tight mt-1"><?= e($hospitalName) ?></h2>
                    </div>
                </div>

                <p class="text-xs text-sky-200/80 leading-relaxed max-w-sm">
                    <?= e($hospitalTagline) ?>. Advanced healthcare management, clinical telemetry, and patient electronic records.
                </p>
            </div>

            <!-- Visual Feature Highlights -->
            <div class="my-8 space-y-3 relative z-10">
                <div class="glass-card p-3.5 rounded-2xl flex items-center space-x-3 transition hover:bg-white/10">
                    <div class="w-9 h-9 rounded-xl bg-teal-500/20 text-teal-400 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white">HIPAA / GDPR Ready EMR</h4>
                        <p class="text-[11px] text-slate-400">Strict end-to-end encrypted medical logs</p>
                    </div>
                </div>

                <div class="glass-card p-3.5 rounded-2xl flex items-center space-x-3 transition hover:bg-white/10">
                    <div class="w-9 h-9 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white">Live OPD & Bed Telemetry</h4>
                        <p class="text-[11px] text-slate-400">Real-time room occupancy and token queue</p>
                    </div>
                </div>

                <div class="glass-card p-3.5 rounded-2xl flex items-center space-x-3 transition hover:bg-white/10">
                    <div class="w-9 h-9 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-flask-vial"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white">Integrated Pharmacy & LIS</h4>
                        <p class="text-[11px] text-slate-400">Instant e-Rx dispensing & certified lab reports</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Operational Status -->
            <div class="pt-6 border-t border-white/10 flex items-center justify-between text-xs text-slate-400 relative z-10">
                <span class="inline-flex items-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-pulse"></span>
                    24/7 Clinical Emergency Wing Online
                </span>
                <span class="font-mono text-[10px] text-slate-500">v<?= APP_VERSION ?></span>
            </div>

            <!-- Background Watermark Icon -->
            <i class="fa-solid fa-staff-snake absolute -bottom-12 -right-8 text-9xl text-white/[0.03] pointer-events-none"></i>
        </div>

        <!-- RIGHT PANEL: Authentication Form & Interactive 1-Click Role Switcher (7 Cols) -->
        <div class="lg:col-span-7 bg-white p-8 sm:p-12 text-slate-800 flex flex-col justify-between">
            
            <div>
                <!-- Top Form Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">Sign In to Console</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Enter your staff or patient portal credentials below.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold font-mono">
                        <i class="fa-solid fa-lock text-teal-600 mr-1"></i> SSL 256-Bit
                    </span>
                </div>

                <!-- Flash & Error Alert -->
                <?php if ($flash): ?>
                    <div class="mb-5 rounded-2xl p-4 text-xs font-semibold <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' ?>">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-5 rounded-2xl p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center space-x-2">
                        <i class="fa-solid fa-circle-exclamation text-rose-500 text-base shrink-0"></i>
                        <span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="" class="space-y-4">
                    <?= csrfField() ?>

                    <div>
                        <label for="username" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">Username or Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <input id="username" name="username" type="text" autocomplete="username" required value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>" placeholder="e.g. admin, doctor, or nurse" class="block w-full pl-10 pr-3 py-3 border border-slate-200 rounded-2xl text-slate-900 placeholder-slate-400 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-slate-50/80 transition">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                                <i class="fa-solid fa-key"></i>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="current-password" required value="password123" placeholder="••••••••" class="block w-full pl-10 pr-10 py-3 border border-slate-200 rounded-2xl text-slate-900 placeholder-slate-400 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-slate-50/80 transition">
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                <i id="eye-icon" class="fa-solid fa-eye text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center text-slate-600 cursor-pointer">
                            <input type="checkbox" checked class="h-4 w-4 text-teal-600 rounded border-slate-300 focus:ring-teal-500">
                            <span class="ml-2 font-medium">Keep session active</span>
                        </label>
                        <span class="text-slate-400 text-[11px]">Demo Pass: <code class="text-teal-700 font-bold bg-teal-50 px-1.5 py-0.5 rounded">password123</code></span>
                    </div>

                    <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-teal-600 via-sky-600 to-teal-700 hover:from-teal-700 hover:to-sky-700 text-white font-bold rounded-2xl shadow-lg shadow-teal-600/20 text-xs transition transform hover:-translate-y-0.5 flex items-center justify-center space-x-2">
                        <span>Authenticate & Sign In</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>

            <!-- Interactive 1-Click Role Switcher Demo Console -->
            <div class="mt-8 pt-6 border-t border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 flex items-center">
                        <i class="fa-solid fa-wand-magic-sparkles text-amber-500 mr-1.5"></i> 1-Click Demo Profile Switcher
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">Click to login immediately</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    
                    <a href="switch_role.php?role=admin" class="p-2.5 rounded-2xl bg-slate-50 hover:bg-teal-50 border border-slate-200/80 hover:border-teal-300 text-center transition group">
                        <i class="fa-solid fa-shield-halved text-rose-500 text-sm block mb-1 group-hover:scale-110 transition-transform"></i>
                        <span class="font-bold text-slate-800 text-[11px] block leading-tight">Super Admin</span>
                        <span class="text-[9px] text-slate-400">Full Control</span>
                    </a>

                    <a href="switch_role.php?role=doctor" class="p-2.5 rounded-2xl bg-slate-50 hover:bg-teal-50 border border-slate-200/80 hover:border-teal-300 text-center transition group">
                        <i class="fa-solid fa-user-doctor text-sky-500 text-sm block mb-1 group-hover:scale-110 transition-transform"></i>
                        <span class="font-bold text-slate-800 text-[11px] block leading-tight">Doctor</span>
                        <span class="text-[9px] text-slate-400">Cardiology Rx</span>
                    </a>

                    <a href="switch_role.php?role=nurse" class="p-2.5 rounded-2xl bg-slate-50 hover:bg-teal-50 border border-slate-200/80 hover:border-teal-300 text-center transition group">
                        <i class="fa-solid fa-user-nurse text-purple-500 text-sm block mb-1 group-hover:scale-110 transition-transform"></i>
                        <span class="font-bold text-slate-800 text-[11px] block leading-tight">Head Nurse</span>
                        <span class="text-[9px] text-slate-400">IPD & Vitals</span>
                    </a>

                    <a href="switch_role.php?role=reception" class="p-2.5 rounded-2xl bg-slate-50 hover:bg-teal-50 border border-slate-200/80 hover:border-teal-300 text-center transition group">
                        <i class="fa-solid fa-clipboard-user text-emerald-500 text-sm block mb-1 group-hover:scale-110 transition-transform"></i>
                        <span class="font-bold text-slate-800 text-[11px] block leading-tight">Reception</span>
                        <span class="text-[9px] text-slate-400">Queue & EMR</span>
                    </a>

                    <a href="switch_role.php?role=pharmacist" class="p-2.5 rounded-2xl bg-slate-50 hover:bg-teal-50 border border-slate-200/80 hover:border-teal-300 text-center transition group">
                        <i class="fa-solid fa-pills text-amber-500 text-sm block mb-1 group-hover:scale-110 transition-transform"></i>
                        <span class="font-bold text-slate-800 text-[11px] block leading-tight">Pharmacist</span>
                        <span class="text-[9px] text-slate-400">POS Dispense</span>
                    </a>

                    <a href="switch_role.php?role=labtech" class="p-2.5 rounded-2xl bg-slate-50 hover:bg-teal-50 border border-slate-200/80 hover:border-teal-300 text-center transition group">
                        <i class="fa-solid fa-flask text-teal-500 text-sm block mb-1 group-hover:scale-110 transition-transform"></i>
                        <span class="font-bold text-slate-800 text-[11px] block leading-tight">Lab Scientist</span>
                        <span class="text-[9px] text-slate-400">Pathology LIS</span>
                    </a>

                    <a href="switch_role.php?role=accountant" class="p-2.5 rounded-2xl bg-slate-50 hover:bg-teal-50 border border-slate-200/80 hover:border-teal-300 text-center transition group">
                        <i class="fa-solid fa-file-invoice-dollar text-green-600 text-sm block mb-1 group-hover:scale-110 transition-transform"></i>
                        <span class="font-bold text-slate-800 text-[11px] block leading-tight">Cashier</span>
                        <span class="text-[9px] text-slate-400">Invoicing</span>
                    </a>

                    <a href="switch_role.php?role=patient" class="p-2.5 rounded-2xl bg-slate-50 hover:bg-teal-50 border border-slate-200/80 hover:border-teal-300 text-center transition group">
                        <i class="fa-solid fa-circle-user text-indigo-500 text-sm block mb-1 group-hover:scale-110 transition-transform"></i>
                        <span class="font-bold text-slate-800 text-[11px] block leading-tight">Patient</span>
                        <span class="text-[9px] text-slate-400">My Health</span>
                    </a>

                </div>
            </div>

        </div>

    </div>

    <!-- Interactive Script -->
    <script>
    function togglePasswordVisibility() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eye-icon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>

</body>
</html>
