<?php
/**
 * CarePoint Pro HMS - Diagnostic Lab Tests Catalog
 */

$pageTitle = 'Diagnostic Test Catalog';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

$categories = $pdo->query("SELECT * FROM lab_test_categories ORDER BY name ASC")->fetchAll();

$sql = "SELECT lt.*, c.name as category_name
        FROM lab_tests lt
        JOIN lab_test_categories c ON lt.category_id = c.id
        ORDER BY c.name ASC, lt.test_name ASC";
$tests = $pdo->query($sql)->fetchAll();

// Group tests by category
$testsByCategory = [];
foreach ($tests as $t) {
    $testsByCategory[$t['category_name']][] = $t;
}
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Lab Orders
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Diagnostic Tests & Pathology Catalog</h1>
            <p class="text-xs text-slate-500 mt-1">Directory of laboratory investigations, reference ranges, specimen requirements, and tariffs.</p>
        </div>
        <div>
            <a href="create_request.php" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                <i class="fa-solid fa-flask-vial mr-1.5"></i> Order Diagnostic Test
            </a>
        </div>
    </div>

    <!-- Catalog Groups -->
    <div class="space-y-6">
        <?php foreach ($testsByCategory as $catName => $catTests): ?>
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center space-x-2 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-sm font-bold">
                        <i class="fa-solid fa-microscope"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 text-base"><?= e($catName) ?></h3>
                    <span class="text-xs text-slate-400 font-medium">(<?= count($catTests) ?> tests)</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-2.5 px-4">Code</th>
                                <th class="py-2.5 px-4">Test Name & Description</th>
                                <th class="py-2.5 px-4">Sample Specimen</th>
                                <th class="py-2.5 px-4">Standard Normal Reference Range</th>
                                <th class="py-2.5 px-4">Unit</th>
                                <th class="py-2.5 px-4 text-right">Standard Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <?php foreach ($catTests as $test): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-purple-700"><?= e($test['test_code']) ?></td>
                                    <td class="py-3 px-4">
                                        <strong class="text-slate-900 block"><?= e($test['test_name']) ?></strong>
                                        <span class="text-[11px] text-slate-400"><?= e($test['description'] ?: 'Diagnostic investigation') ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px]">
                                            <i class="fa-solid fa-vial mr-1 text-purple-500"></i> <?= e($test['sample_type']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-700 font-mono text-[11px]"><?= e($test['normal_range'] ?: 'See clinical interpretation') ?></td>
                                    <td class="py-3 px-4 font-mono text-slate-500"><?= e($test['unit'] ?: 'N/A') ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900"><?= formatMoney($test['price']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
