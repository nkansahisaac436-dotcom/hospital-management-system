<?php
/**
 * CarePoint Pro HMS - Blood Bank Management & Donor Registry
 */

$pageTitle = 'Blood Bank Management';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

// Fetch Blood Inventory
$inventory = $pdo->query("SELECT * FROM blood_inventory ORDER BY blood_group ASC")->fetchAll();

// Fetch Donors
$donors = $pdo->query("SELECT * FROM blood_donors ORDER BY full_name ASC")->fetchAll();

$error = null;

// Handle Add Donor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_donor'])) {
    verifyCsrf();

    $name = trim($_POST['full_name'] ?? '');
    $gender = $_POST['gender'] ?? 'Male';
    $age = (int)($_POST['age'] ?? 25);
    $bloodGroup = $_POST['blood_group'] ?? 'O+';
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $lastDonation = !empty($_POST['last_donation_date']) ? $_POST['last_donation_date'] : null;

    if (empty($name) || empty($phone)) {
        $error = 'Donor name and phone number are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO blood_donors (full_name, gender, age, blood_group, phone, email, last_donation_date, health_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Eligible')");
            $stmt->execute([$name, $gender, $age, $bloodGroup, $phone, $email, $lastDonation]);
            logActivity('Blood Donor Added', "Registered donor {$name} ({$bloodGroup})");
            setFlash('success', "Donor <strong>{$name}</strong> registered successfully.");
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $error = 'Failed to register donor: ' . $e->getMessage();
        }
    }
}

// Handle Update Inventory Units
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_units'])) {
    verifyCsrf();

    $bg = $_POST['blood_group'] ?? '';
    $units = max(0, (int)($_POST['units_available'] ?? 0));

    if (!empty($bg)) {
        $pdo->prepare("UPDATE blood_inventory SET units_available = ? WHERE blood_group = ?")->execute([$units, $bg]);
        setFlash('success', "Blood group <strong>{$bg}</strong> inventory updated to {$units} units.");
        header('Location: index.php');
        exit;
    }
}
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Blood Bank & Donor Registry</h1>
            <p class="text-xs text-slate-500 mt-1">Live whole-blood inventory tracking, donor eligibility directory, and blood issuance.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Blood Group Live Matrix -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
        <?php foreach ($inventory as $item): ?>
            <?php 
                $isLow = ($item['units_available'] <= 5);
            ?>
            <div class="bg-white rounded-3xl p-4 border border-slate-200 shadow-sm text-center card-hover flex flex-col justify-between space-y-2">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center font-black text-sm mx-auto shadow-inner">
                        <?= e($item['blood_group']) ?>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mt-2"><?= $item['units_available'] ?></h3>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Units in Stock</span>
                </div>
                <div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $isLow ? 'bg-amber-100 text-amber-800' : 'bg-emerald-50 text-emerald-700' ?>">
                        <?= $isLow ? 'Low Reserve' : 'Available' ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Main Grid: Donor Register & Donors List -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Add Donor Form (1 Col) -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4 h-fit">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                <i class="fa-solid fa-user-plus text-rose-600 mr-2"></i> Register Blood Donor
            </h3>

            <form method="POST" action="" class="space-y-3">
                <?= csrfField() ?>
                <input type="hidden" name="add_donor" value="1">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Donor Full Name *</label>
                    <input type="text" name="full_name" required placeholder="e.g. Johnathan Smith" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:bg-white focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Blood Group *</label>
                        <select name="blood_group" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-rose-500 focus:bg-white focus:outline-none">
                            <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                                <option value="<?= $bg ?>"><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Age (Years) *</label>
                        <input type="number" name="age" value="28" min="18" max="65" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-rose-500 focus:bg-white focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Gender *</label>
                        <select name="gender" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:bg-white focus:outline-none">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Last Donation</label>
                        <input type="date" name="last_donation_date" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:bg-white focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Phone Number *</label>
                    <input type="tel" name="phone" required placeholder="+1 555-0199" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
                    <input type="email" name="email" placeholder="donor@example.com" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:bg-white focus:outline-none">
                </div>

                <button type="submit" class="w-full py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                    + Register Blood Donor
                </button>
            </form>
        </div>

        <!-- Donors List Table (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-address-book text-rose-600 mr-2"></i> Registered Donor Directory (<?= count($donors) ?>)
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-6">Donor Name</th>
                            <th class="py-3 px-6">Blood Group</th>
                            <th class="py-3 px-6">Demographics</th>
                            <th class="py-3 px-6">Contact / Phone</th>
                            <th class="py-3 px-6">Last Donation</th>
                            <th class="py-3 px-6">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php if (!empty($donors)): ?>
                            <?php foreach ($donors as $d): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3.5 px-6 font-bold text-slate-900 text-sm">
                                        <?= e($d['full_name']) ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-50 text-rose-700 border border-rose-200">
                                            <i class="fa-solid fa-droplet mr-1 text-[10px] text-rose-500"></i> <?= e($d['blood_group']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <?= e($d['gender']) ?> &bull; <?= $d['age'] ?> yrs
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <span class="font-bold text-slate-800 block"><?= e($d['phone']) ?></span>
                                        <span class="text-[11px] text-slate-400"><?= e($d['email'] ?: 'No email') ?></span>
                                    </td>
                                    <td class="py-3.5 px-6 font-mono text-slate-500">
                                        <?= formatDate($d['last_donation_date']) ?>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <span class="badge badge-success"><?= e($d['health_status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400">No blood donors registered yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
