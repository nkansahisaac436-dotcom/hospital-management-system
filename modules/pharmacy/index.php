<?php
/**
 * CarePoint Pro HMS - Pharmacy Dashboard
 */

$pageTitle = 'Pharmacy & Drug Inventory';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

// Summary metrics
$totalDrugs = $pdo->query("SELECT COUNT(*) as total FROM medicines WHERE status = 'active'")->fetch()['total'] ?? 0;
$lowStock = $pdo->query("SELECT COUNT(*) as total FROM medicines WHERE stock_quantity <= min_stock_alert AND status = 'active'")->fetch()['total'] ?? 0;
$expiredSoon = $pdo->query("SELECT COUNT(*) as total FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND status = 'active'")->fetch()['total'] ?? 0;
$todaySales = $pdo->query("SELECT SUM(total_amount) as total FROM pharmacy_sales WHERE DATE(created_at) = CURDATE()")->fetch()['total'] ?? 0.00;

// Low stock list
$lowStockDrugs = $pdo->query("SELECT m.*, c.name as cat_name FROM medicines m JOIN medicine_categories c ON m.category_id = c.id WHERE m.stock_quantity <= m.min_stock_alert AND m.status = 'active' ORDER BY m.stock_quantity ASC LIMIT 5")->fetchAll();

// Recent sales
$recentSales = $pdo->query("SELECT s.*, u.full_name as sold_by_name FROM pharmacy_sales s LEFT JOIN users u ON s.sold_by = u.id ORDER BY s.created_at DESC LIMIT 5")->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pharmacy & Medication Inventory</h1>
            <p class="text-xs text-slate-500 mt-1">Dispensing counter, Point of Sale (POS), batch tracking, and pharmaceutical inventory control.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="medicines.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                <i class="fa-solid fa-boxes-stacked mr-1.5 text-teal-600"></i> Full Drug Catalog
            </a>
            <a href="pos.php" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                <i class="fa-solid fa-cash-register mr-1.5"></i> Launch Pharmacy POS
            </a>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Formulations</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1"><?= number_format($totalDrugs) ?></h3>
                <span class="text-[11px] font-semibold text-teal-600 inline-flex items-center mt-1">
                    <i class="fa-solid fa-pills mr-1"></i> Active Catalog
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-capsules"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Low Stock Re-Orders</p>
                <h3 class="text-2xl font-black text-amber-600 mt-1"><?= $lowStock ?></h3>
                <span class="text-[11px] font-semibold text-amber-600 inline-flex items-center mt-1">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> Under Threshold
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-arrow-down-short-wide"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Expiring in 60 Days</p>
                <h3 class="text-2xl font-black text-rose-600 mt-1"><?= $expiredSoon ?></h3>
                <span class="text-[11px] font-semibold text-rose-600 inline-flex items-center mt-1">
                    <i class="fa-solid fa-calendar-xmark mr-1"></i> Batch Watchlist
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Today's POS Sales</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1"><?= formatMoney($todaySales) ?></h3>
                <span class="text-[11px] font-semibold text-emerald-600 inline-flex items-center mt-1">
                    <i class="fa-solid fa-circle-check mr-1"></i> Dispensed Today
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
        </div>

    </div>

    <!-- Low Stock Grid & Recent Sales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Low Stock Items -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 mr-2"></i> Low Stock Medicine Alerts
                </h3>
                <a href="medicines.php" class="text-xs text-teal-600 font-bold hover:underline">View All</a>
            </div>

            <div class="space-y-3">
                <?php if (!empty($lowStockDrugs)): ?>
                    <?php foreach ($lowStockDrugs as $d): ?>
                        <div class="p-3 bg-amber-50/50 rounded-2xl border border-amber-100 flex items-center justify-between text-xs">
                            <div>
                                <h4 class="font-bold text-slate-900"><?= e($d['name']) ?></h4>
                                <span class="text-[11px] text-slate-500 font-mono"><?= e($d['generic_name']) ?> &bull; <?= e($d['dosage_form']) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800">
                                    <?= $d['stock_quantity'] ?> left
                                </span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Min: <?= $d['min_stock_alert'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-xs text-slate-400 py-4 text-center">All pharmaceutical inventory levels are optimal.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Dispensed Sales -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-receipt text-emerald-600 mr-2"></i> Recent Pharmacy Sales
                </h3>
                <a href="sales.php" class="text-xs text-teal-600 font-bold hover:underline">Full Sales Log</a>
            </div>

            <div class="space-y-3">
                <?php if (!empty($recentSales)): ?>
                    <?php foreach ($recentSales as $s): ?>
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-mono font-bold text-slate-800"><?= e($s['sale_number']) ?></span>
                                <p class="text-slate-500 mt-0.5"><?= e($s['customer_name']) ?> &bull; <?= formatDate($s['created_at']) ?></p>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-slate-900 text-sm block"><?= formatMoney($s['total_amount']) ?></span>
                                <span class="badge badge-success text-[10px]"><?= e($s['payment_method']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-xs text-slate-400 py-4 text-center">No sales recorded yet today.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
