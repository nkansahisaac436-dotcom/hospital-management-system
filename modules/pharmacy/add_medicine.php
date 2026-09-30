<?php
/**
 * CarePoint Pro HMS - Add / Edit Medicine
 */

$pageTitle = 'Add / Edit Medicine';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$medId = (int)($_GET['id'] ?? 0);

$med = null;
if ($medId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
    $stmt->execute([$medId]);
    $med = $stmt->fetch();
}

$categories = $pdo->query("SELECT * FROM medicine_categories ORDER BY name ASC")->fetchAll();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name = trim($_POST['name'] ?? '');
    $genericName = trim($_POST['generic_name'] ?? '');
    $brandName = trim($_POST['brand_name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $dosageForm = $_POST['dosage_form'] ?? 'Tablet';
    $strength = trim($_POST['strength'] ?? '');
    $unitPrice = (float)($_POST['unit_price'] ?? 0.00);
    $costPrice = (float)($_POST['cost_price'] ?? 0.00);
    $stockQty = (int)($_POST['stock_quantity'] ?? 0);
    $minStockAlert = (int)($_POST['min_stock_alert'] ?? 20);
    $batchNumber = trim($_POST['batch_number'] ?? '');
    $expiryDate = $_POST['expiry_date'] ?? '';
    $manufacturer = trim($_POST['manufacturer'] ?? '');

    if (empty($name) || $categoryId <= 0 || empty($expiryDate)) {
        $error = 'Please fill in all required fields (Medicine Name, Category, and Expiry Date).';
    } else {
        try {
            if ($med) {
                // Update
                $uStmt = $pdo->prepare("UPDATE medicines SET name = ?, generic_name = ?, brand_name = ?, category_id = ?, dosage_form = ?, strength = ?, unit_price = ?, cost_price = ?, stock_quantity = ?, min_stock_alert = ?, batch_number = ?, expiry_date = ?, manufacturer = ? WHERE id = ?");
                $uStmt->execute([$name, $genericName, $brandName, $categoryId, $dosageForm, $strength, $unitPrice, $costPrice, $stockQty, $minStockAlert, $batchNumber, $expiryDate, $manufacturer, $medId]);
                logActivity('Medicine Updated', "Updated medicine {$name}");
                setFlash('success', "Medicine <strong>{$name}</strong> updated successfully.");
            } else {
                // Insert
                $iStmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, brand_name, category_id, dosage_form, strength, unit_price, cost_price, stock_quantity, min_stock_alert, batch_number, expiry_date, manufacturer, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $iStmt->execute([$name, $genericName, $brandName, $categoryId, $dosageForm, $strength, $unitPrice, $costPrice, $stockQty, $minStockAlert, $batchNumber, $expiryDate, $manufacturer]);
                logActivity('Medicine Added', "Added medicine {$name}");
                setFlash('success', "Medicine <strong>{$name}</strong> added to inventory successfully.");
            }
            header('Location: medicines.php');
            exit;
        } catch (Exception $e) {
            $error = 'Failed to save medicine: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <a href="medicines.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Medicine Inventory
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight"><?= $med ? 'Edit Medicine Formulation' : 'Add New Medicine Formulation' ?></h1>
        <p class="text-xs text-slate-500">Configure drug pharmaceutical inventory, batch numbers, and selling prices.</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-6">
        <?= csrfField() ?>

        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-pills text-teal-600 mr-2"></i> Medicine Details & Formulation
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Medicine Name (Brand / Formulation) *</label>
                    <input type="text" name="name" value="<?= e($med['name'] ?? '') ?>" required placeholder="e.g. Augmentin 625mg Tablet" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Therapeutic Category *</label>
                    <select name="category_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ((int)($med['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Generic Molecule Name</label>
                    <input type="text" name="generic_name" value="<?= e($med['generic_name'] ?? '') ?>" placeholder="e.g. Amoxicillin + Clavulanic Acid" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dosage Form</label>
                    <select name="dosage_form" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                        <?php foreach (['Tablet', 'Capsule', 'Syrup', 'Injection', 'Ointment', 'Drops', 'Inhaler', 'Suspension', 'IV Fluid'] as $df): ?>
                            <option value="<?= $df ?>" <?= (($med['dosage_form'] ?? 'Tablet') === $df) ? 'selected' : '' ?>><?= $df ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Strength / Specification</label>
                    <input type="text" name="strength" value="<?= e($med['strength'] ?? '') ?>" placeholder="e.g. 500mg, 100ml, 5mg/ml" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Retail Selling Price ($) *</label>
                    <input type="number" step="0.01" name="unit_price" value="<?= e($med['unit_price'] ?? '15.00') ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold text-emerald-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Cost Purchase Price ($)</label>
                    <input type="number" step="0.01" name="cost_price" value="<?= e($med['cost_price'] ?? '8.00') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Initial Stock Quantity *</label>
                    <input type="number" name="stock_quantity" value="<?= e($med['stock_quantity'] ?? '100') ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Low Stock Reorder Alert</label>
                    <input type="number" name="min_stock_alert" value="<?= e($med['min_stock_alert'] ?? '20') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Batch / Lot Number</label>
                    <input type="text" name="batch_number" value="<?= e($med['batch_number'] ?? 'BATCH-' . strtoupper(bin2hex(random_bytes(2)))) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Expiry Date *</label>
                    <input type="date" name="expiry_date" value="<?= e($med['expiry_date'] ?? date('Y-m-d', strtotime('+1 year'))) ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Manufacturer / Supplier</label>
                    <input type="text" name="manufacturer" value="<?= e($med['manufacturer'] ?? '') ?>" placeholder="e.g. Pfizer, GSK, Novartis, Abbott" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="medicines.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-floppy-disk mr-2"></i> Save Medicine Record
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
