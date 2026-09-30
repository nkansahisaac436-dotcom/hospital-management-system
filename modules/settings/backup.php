<?php
/**
 * CarePoint Pro HMS - 1-Click Database Backup & Export Utility
 */

$pageTitle = 'Database Backup & Restore';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth(['admin']);

$pdo = Database::getConnection();
$backupDir = ROOT_PATH . '/backups';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0777, true);
}

$error = null;

// Handle Trigger Backup
if (isset($_GET['action']) && $_GET['action'] === 'create_backup') {
    try {
        $tables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sqlDump = "-- CarePoint Pro HMS - Automated Database Backup\n";
        $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "-- Database: " . DB_NAME . "\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // Table structure
            $createTableStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sqlDump .= $createTableStmt[1] . ";\n\n";

            // Table Data
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $sqlDump .= "INSERT INTO `{$table}` VALUES \n";
                $valRows = [];
                foreach ($rows as $row) {
                    $vals = array_map(function($v) use ($pdo) {
                        return ($v === null) ? 'NULL' : $pdo->quote($v);
                    }, array_values($row));
                    $valRows[] = "(" . implode(", ", $vals) . ")";
                }
                $sqlDump .= implode(",\n", $valRows) . ";\n\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        $filename = 'carepoint_backup_' . date('Y_m_d_His') . '.sql';
        $filepath = $backupDir . '/' . $filename;
        file_put_contents($filepath, $sqlDump);

        logActivity('Database Backup', "Generated backup file {$filename}");
        setFlash('success', "Database backup <strong>{$filename}</strong> created successfully (" . round(strlen($sqlDump) / 1024, 2) . " KB).");
        header('Location: backup.php');
        exit;
    } catch (Exception $e) {
        $error = 'Backup failed: ' . $e->getMessage();
    }
}

// Handle Download
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $filepath = $backupDir . '/' . $file;
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}

// List Backups
$backupFiles = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $f) {
        if (str_ends_with($f, '.sql')) {
            $backupFiles[] = [
                'name' => $f,
                'size' => round(filesize($backupDir . '/' . $f) / 1024, 2),
                'date' => date('Y-m-d H:i:s', filemtime($backupDir . '/' . $f))
            ];
        }
    }
    // Sort newest first
    usort($backupFiles, fn($a, $b) => strcmp($b['date'], $a['date']));
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to System Settings
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Database Backup & Recovery Utility</h1>
            <p class="text-xs text-slate-500">1-Click full SQL dump generator to ensure medical data safety and offsite backups.</p>
        </div>
        <div>
            <a href="backup.php?action=create_backup" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center">
                <i class="fa-solid fa-download mr-2"></i> Generate New SQL Backup
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Information Card -->
    <div class="bg-gradient-to-r from-teal-50 to-sky-50 rounded-3xl p-6 border border-teal-200 shadow-sm flex items-start space-x-4">
        <div class="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-xl shrink-0 shadow-md">
            <i class="fa-solid fa-database"></i>
        </div>
        <div>
            <h3 class="font-bold text-teal-950 text-sm">Enterprise Data Security & Disaster Recovery</h3>
            <p class="text-xs text-teal-900/80 leading-relaxed mt-1">
                Backups generated include the entire schema structure, foreign keys, user accounts, patient electronic health records (EMR), laboratory results, pharmacy stock logs, and financial invoices. Store backups securely in accordance with local medical record compliance standards.
            </p>
        </div>
    </div>

    <!-- Backups Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                <i class="fa-solid fa-hard-drive text-teal-600 mr-2"></i> Backup Archives in Storage (<?= count($backupFiles) ?>)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-6">Backup File Name</th>
                        <th class="py-3 px-6">File Size</th>
                        <th class="py-3 px-6">Date Created</th>
                        <th class="py-3 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (!empty($backupFiles)): ?>
                        <?php foreach ($backupFiles as $bf): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-6 font-mono font-bold text-slate-900">
                                    <i class="fa-solid fa-file-code text-teal-600 mr-2"></i> <?= e($bf['name']) ?>
                                </td>
                                <td class="py-3.5 px-6 font-mono text-slate-700">
                                    <?= $bf['size'] ?> KB
                                </td>
                                <td class="py-3.5 px-6 font-mono text-slate-500">
                                    <?= $bf['date'] ?>
                                </td>
                                <td class="py-3.5 px-6 text-right whitespace-nowrap">
                                    <a href="backup.php?download=<?= urlencode($bf['name']) ?>" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-600 hover:text-white text-teal-700 font-bold text-xs transition border border-teal-200">
                                        <i class="fa-solid fa-cloud-arrow-down mr-1.5"></i> Download .SQL
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-box-open text-4xl mb-3 block"></i>
                                No backup archives generated yet. Click "Generate New SQL Backup" above.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
