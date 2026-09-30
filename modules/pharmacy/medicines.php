<?php
/**
 * CarePoint Pro HMS - Medicines & Drug Inventory Catalog
 */

$pageTitle = 'Medicine Inventory';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$categoryFilter = (int)($_GET['category_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

$sql = "SELECT m.*, c.name as category_name
        FROM medicines m
        JOIN medicine_categories c ON m.category_id = c.id
        WHERE m.status = 'active'";
$params = [];

if ($categoryFilter > 0) {
    $sql .= " AND m.category_id = ?";
    $params[] = $categoryFilter;
}

if (!empty($search)) {
    $sql .= " AND (m.name LIKE ? OR m.generic_name LIKE ? OR m.brand_name LIKE ? OR m.batch_number LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

$sql .= " ORDER BY m.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM medicine_categories ORDER BY name ASC")->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Pharmacy
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Medicine & Pharmaceutical Catalog</h1>
            <p class="text-xs text-slate-500 mt-1">Manage stock quantities, batch expiry dates, dosage forms, and unit pricing.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="pos.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                <i class="fa-solid fa-cash-register mr-1.5"></i> POS Dispenser
            </a>
            <a href="add_medicine.php" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                <i class="fa-solid fa-pills mr-1.5"></i> Add New Medicine
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by Brand Name, Generic Name, Batch Number..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <select name="category_id" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Medicines Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Medicine / Generic Formulation</th>
                        <th class="py-3.5 px-6">Category</th>
                        <th class="py-3.5 px-6">Dosage Form & Strength</th>
                        <th class="py-3.5 px-6">Batch #</th>
                        <th class="py-3.5 px-6">Stock Level</th>
                        <th class="py-3.5 px-6">Expiry Date</th>
                        <th class="py-3.5 px-6 text-right">Unit Price</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($medicines)): ?>
                        <?php foreach ($medicines as $med): ?>
                            <?php 
                                $isLowStock = ($med['stock_quantity'] <= $med['min_stock_alert']);
                                $isExpiring = (strtotime($med['expiry_date']) <= strtotime('+60 days'));
                            ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6">
                                    <strong class="text-slate-900 text-sm block"><?= e($med['name']) ?></strong>
                                    <span class="text-[11px] text-slate-400 font-mono"><?= e($med['generic_name']) ?> <?= !empty($med['brand_name']) ? "({$med['brand_name']})" : '' ?></span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-700">
                                        <?= e($med['category_name']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="font-bold text-slate-800"><?= e($med['dosage_form']) ?></span>
                                    <span class="text-[11px] text-slate-400 block"><?= e($med['strength']) ?></span>
                                </td>
                                <td class="py-3.5 px-6 font-mono text-slate-600">
                                    <?= e($med['batch_number'] ?: 'N/A') ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-black <?= $isLowStock ? 'bg-amber-100 text-amber-800' : 'bg-emerald-50 text-emerald-700' ?>">
                                        <?= $med['stock_quantity'] ?> in stock
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 font-mono">
                                    <span class="<?= $isExpiring ? 'text-rose-600 font-bold' : 'text-slate-600' ?>">
                                        <?= formatDate($med['expiry_date']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-right font-bold text-slate-900 font-mono">
                                    <?= formatMoney($med['unit_price']) ?>
                                </td>
                                <td class="py-3.5 px-6 text-right space-x-1 whitespace-nowrap">
                                    <a href="add_medicine.php?id=<?= $med['id'] ?>" class="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs transition" title="Edit Medicine">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-pills text-4xl mb-3 block"></i>
                                No medicines found matching your search.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
