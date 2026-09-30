<?php
/**
 * CarePoint Pro HMS - Security & Operational Audit Log Console
 */

$pageTitle = 'Audit & Security Logs';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth(['admin']);

$pdo = Database::getConnection();

$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// CSV Export Trigger
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=hospital_audit_trail_' . date('Y_m_d_His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Log ID', 'Timestamp', 'User', 'Role', 'Action', 'Description', 'IP Address']);

    $q = $pdo->query("SELECT al.*, u.full_name, u.role FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC");
    while ($row = $q->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $row['full_name'] ?? 'System / Anonymous',
            $row['role'] ?? 'N/A',
            $row['action'],
            $row['description'],
            $row['ip_address']
        ]);
    }
    fclose($output);
    exit;
}

$sql = "SELECT al.*, u.full_name, u.role, u.username
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (al.action LIKE ? OR al.description LIKE ? OR u.full_name LIKE ? OR al.ip_address LIKE ?)";
    $term = "%{$search}%";
    $params = [$term, $term, $term, $term];
}

if (!empty($dateFrom)) {
    $sql .= " AND DATE(al.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $sql .= " AND DATE(al.created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY al.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Security & Clinical Audit Trail</h1>
            <p class="text-xs text-slate-500 mt-1">Immutable activity log tracking clinical orders, dispensary actions, and user security events.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="index.php?export=csv" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center">
                <i class="fa-solid fa-file-csv mr-1.5 text-emerald-400"></i> Export Audit Log (CSV)
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search action, staff member, IP address..." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
            </div>

            <div>
                <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
            </div>

            <div class="flex items-center space-x-2">
                <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Timestamp (UTC)</th>
                        <th class="py-3.5 px-6">User / Actor</th>
                        <th class="py-3.5 px-6">Action Category</th>
                        <th class="py-3.5 px-6">Event Details</th>
                        <th class="py-3.5 px-6 text-right">Client IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6 font-mono text-slate-500 text-[11px] whitespace-nowrap">
                                    <?= formatDateTime($log['created_at']) ?>
                                </td>
                                <td class="py-3.5 px-6 whitespace-nowrap">
                                    <?php if (!empty($log['full_name'])): ?>
                                        <strong class="text-slate-900 block"><?= e($log['full_name']) ?></strong>
                                        <span class="text-[10px] text-teal-700 font-bold uppercase">@<?= e($log['username']) ?> (<?= e($log['role']) ?>)</span>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">System / Anonymous</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-6 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                        <?= e($log['action']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-slate-700 font-medium">
                                    <?= e($log['description']) ?>
                                </td>
                                <td class="py-3.5 px-6 text-right font-mono text-[11px] text-slate-400">
                                    <?= e($log['ip_address'] ?: '127.0.0.1') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-shield-halved text-4xl mb-3 block"></i>
                                No audit events logged yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
