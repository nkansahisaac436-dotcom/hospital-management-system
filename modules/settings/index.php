<?php
/**
 * CarePoint Pro HMS - System Settings & White-Label Customization
 */

$pageTitle = 'System Settings';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth(['admin']);

$pdo = Database::getConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $settings = [
        'hospital_name'       => trim($_POST['hospital_name'] ?? DEFAULT_HOSPITAL_NAME),
        'hospital_tagline'    => trim($_POST['hospital_tagline'] ?? DEFAULT_HOSPITAL_TAGLINE),
        'hospital_phone'      => trim($_POST['hospital_phone'] ?? DEFAULT_HOSPITAL_PHONE),
        'hospital_email'      => trim($_POST['hospital_email'] ?? DEFAULT_HOSPITAL_EMAIL),
        'hospital_address'    => trim($_POST['hospital_address'] ?? DEFAULT_HOSPITAL_ADDRESS),
        'currency_symbol'     => trim($_POST['currency_symbol'] ?? '$'),
        'tax_percentage'      => (float)($_POST['tax_percentage'] ?? 5.00),
        'prescription_footer' => trim($_POST['prescription_footer'] ?? ''),
        'system_timezone'     => $_POST['system_timezone'] ?? 'UTC',
    ];

    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        foreach ($settings as $k => $v) {
            $stmt->execute([$k, $v]);
        }

        logActivity('Settings Updated', 'Updated hospital branding & configuration settings');
        setFlash('success', 'System settings and hospital branding updated successfully.');
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $error = 'Failed to save settings: ' . $e->getMessage();
    }
}

// Fetch current values
$currentSettings = [];
$sStmt = $pdo->query("SELECT `key`, `value` FROM system_settings");
while ($row = $sStmt->fetch()) {
    $currentSettings[$row['key']] = $row['value'];
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">System Settings & Hospital White-Labeling</h1>
        <p class="text-xs text-slate-500">Customize hospital name, branding, currency, tax rates, contact information, and print templates.</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-6">
        <?= csrfField() ?>

        <!-- Hospital Identity -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-hospital text-teal-600 mr-2"></i> Hospital Identity & Contact Information
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hospital / Clinic Name *</label>
                    <input type="text" name="hospital_name" value="<?= e($currentSettings['hospital_name'] ?? DEFAULT_HOSPITAL_NAME) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hospital Tagline / Slogan</label>
                    <input type="text" name="hospital_tagline" value="<?= e($currentSettings['hospital_tagline'] ?? DEFAULT_HOSPITAL_TAGLINE) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Official Phone Number(s)</label>
                    <input type="text" name="hospital_phone" value="<?= e($currentSettings['hospital_phone'] ?? DEFAULT_HOSPITAL_PHONE) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Official Email Address</label>
                    <input type="email" name="hospital_email" value="<?= e($currentSettings['hospital_email'] ?? DEFAULT_HOSPITAL_EMAIL) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Physical Hospital Address</label>
                    <textarea name="hospital_address" rows="2" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"><?= e($currentSettings['hospital_address'] ?? DEFAULT_HOSPITAL_ADDRESS) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Financial & Localization Settings -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-coins text-emerald-600 mr-2"></i> Financial & Regional Localization
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Currency Symbol *</label>
                    <input type="text" name="currency_symbol" value="<?= e($currentSettings['currency_symbol'] ?? DEFAULT_CURRENCY) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-mono">
                    <span class="text-[11px] text-slate-400 mt-1 block">e.g. $, €, £, ₦, ₹, KSh, etc.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hospital Sales Tax / VAT (%)</label>
                    <input type="number" step="0.01" name="tax_percentage" value="<?= e($currentSettings['tax_percentage'] ?? '5.00') ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">System Timezone</label>
                    <select name="system_timezone" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="UTC" <?= (($currentSettings['system_timezone'] ?? 'UTC') === 'UTC') ? 'selected' : '' ?>>UTC (Standard)</option>
                        <option value="America/New_York" <?= (($currentSettings['system_timezone'] ?? '') === 'America/New_York') ? 'selected' : '' ?>>America / New York (EST)</option>
                        <option value="America/Chicago" <?= (($currentSettings['system_timezone'] ?? '') === 'America/Chicago') ? 'selected' : '' ?>>America / Chicago (CST)</option>
                        <option value="America/Los_Angeles" <?= (($currentSettings['system_timezone'] ?? '') === 'America/Los_Angeles') ? 'selected' : '' ?>>America / Los Angeles (PST)</option>
                        <option value="Europe/London" <?= (($currentSettings['system_timezone'] ?? '') === 'Europe/London') ? 'selected' : '' ?>>Europe / London (GMT)</option>
                        <option value="Africa/Lagos" <?= (($currentSettings['system_timezone'] ?? '') === 'Africa/Lagos') ? 'selected' : '' ?>>Africa / Lagos (WAT)</option>
                        <option value="Africa/Nairobi" <?= (($currentSettings['system_timezone'] ?? '') === 'Africa/Nairobi') ? 'selected' : '' ?>>Africa / Nairobi (EAT)</option>
                        <option value="Asia/Dubai" <?= (($currentSettings['system_timezone'] ?? '') === 'Asia/Dubai') ? 'selected' : '' ?>>Asia / Dubai (GST)</option>
                        <option value="Asia/Kolkata" <?= (($currentSettings['system_timezone'] ?? '') === 'Asia/Kolkata') ? 'selected' : '' ?>>Asia / Kolkata (IST)</option>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Prescription & Report Footer Disclaimer</label>
                    <textarea name="prescription_footer" rows="2" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"><?= e($currentSettings['prescription_footer'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <button type="submit" class="px-8 py-3.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-floppy-disk mr-2"></i> Save Hospital Settings
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
