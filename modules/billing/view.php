<?php
/**
 * CarePoint Pro HMS - Invoice Detail & Cashier Payment Terminal
 */

$pageTitle = 'Invoice Details & Payments';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$invId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT inv.*, p.full_name as patient_name, p.mrn, p.gender, p.date_of_birth, p.phone, p.email, p.address,
                       u.full_name as created_by_name
                       FROM invoices inv
                       JOIN patients p ON inv.patient_id = p.id
                       LEFT JOIN users u ON inv.created_by = u.id
                       WHERE inv.id = ?");
$stmt->execute([$invId]);
$inv = $stmt->fetch();

if (!$inv) {
    setFlash('error', 'Invoice not found.');
    header('Location: index.php');
    exit;
}

// Fetch invoice line items
$iStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
$iStmt->execute([$invId]);
$items = $iStmt->fetchAll();

// Fetch payments made
$pStmt = $pdo->prepare("SELECT pay.*, u.full_name as received_by_name FROM payments pay LEFT JOIN users u ON pay.received_by = u.id WHERE pay.invoice_id = ? ORDER BY pay.payment_date DESC");
$pStmt->execute([$invId]);
$payments = $pStmt->fetchAll();

$error = null;

// Handle Record Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    verifyCsrf();

    $amount = (float)($_POST['amount'] ?? 0.00);
    $method = $_POST['payment_method'] ?? 'Cash';
    $ref = trim($_POST['transaction_reference'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($amount <= 0) {
        $error = 'Please enter a valid payment amount greater than zero.';
    } else {
        try {
            $pdo->beginTransaction();

            $receiptNum = 'REC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            // 1. Insert Payment
            $payStmt = $pdo->prepare("INSERT INTO payments (receipt_number, invoice_id, patient_id, amount, payment_method, transaction_reference, notes, received_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $payStmt->execute([$receiptNum, $invId, $inv['patient_id'], $amount, $method, $ref, $notes, currentUser()['id']]);

            // 2. Recalculate Invoice totals
            $totalPaidStmt = $pdo->prepare("SELECT SUM(amount) as total_paid FROM payments WHERE invoice_id = ?");
            $totalPaidStmt->execute([$invId]);
            $newPaid = (float)$totalPaidStmt->fetch()['total_paid'];

            $newDue = max(0, (float)$inv['total_amount'] - $newPaid);
            $newStatus = ($newDue == 0) ? 'Paid' : 'Partial';

            $uInvStmt = $pdo->prepare("UPDATE invoices SET paid_amount = ?, due_amount = ?, payment_status = ? WHERE id = ?");
            $uInvStmt->execute([$newPaid, $newDue, $newStatus, $invId]);

            $pdo->commit();
            logActivity('Payment Recorded', "Recorded payment of {$amount} for invoice {$inv['invoice_number']}");
            setFlash('success', "Payment of <strong>" . formatMoney($amount) . "</strong> recorded successfully. Receipt Ref: <code>{$receiptNum}</code>");
            header("Location: view.php?id={$invId}");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Payment processing failed: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Invoices
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Invoice Details</h1>
            <p class="text-xs text-slate-500 font-mono">Invoice #: <?= e($inv['invoice_number']) ?> &bull; Created: <?= formatDate($inv['created_at']) ?></p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="print_invoice.php?id=<?= $inv['id'] ?>" target="_blank" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center">
                <i class="fa-solid fa-file-pdf mr-1.5"></i> Printable Invoice
            </a>
            <a href="print_receipt.php?id=<?= $inv['id'] ?>" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center">
                <i class="fa-solid fa-receipt mr-1.5"></i> Thermal Receipt
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Summary Box -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Patient</span>
            <h4 class="font-bold text-slate-900 text-sm mt-0.5"><?= e($inv['patient_name']) ?></h4>
            <span class="text-slate-500 font-mono"><?= e($inv['mrn']) ?></span>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Total Billed</span>
            <span class="font-bold text-slate-900 text-sm font-mono block mt-0.5"><?= formatMoney($inv['total_amount']) ?></span>
            <span class="text-slate-500 text-[11px]">Subtotal: <?= formatMoney($inv['subtotal']) ?></span>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Amount Settled</span>
            <span class="font-bold text-emerald-600 text-sm font-mono block mt-0.5"><?= formatMoney($inv['paid_amount']) ?></span>
            <span class="text-[11px] font-bold text-rose-600">Due: <?= formatMoney($inv['due_amount']) ?></span>
        </div>
        <div>
            <span class="text-slate-400 uppercase font-bold text-[10px]">Status</span>
            <div class="mt-1">
                <span class="badge <?= $inv['payment_status'] === 'Paid' ? 'badge-success' : ($inv['payment_status'] === 'Partial' ? 'badge-warning' : 'badge-danger') ?>">
                    <?= e($inv['payment_status']) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Line Items (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                <i class="fa-solid fa-list-check text-teal-600 mr-2"></i> Itemized Tariffs
            </h3>

            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-2.5 px-3">Service Description</th>
                        <th class="py-2.5 px-3">Category</th>
                        <th class="py-2.5 px-3">Unit Price</th>
                        <th class="py-2.5 px-3">Qty</th>
                        <th class="py-2.5 px-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="py-3 px-3 font-bold text-slate-900"><?= e($item['description']) ?></td>
                            <td class="py-3 px-3">
                                <span class="px-2 py-0.5 rounded text-[11px] bg-slate-100 text-slate-700 font-medium"><?= e($item['category']) ?></span>
                            </td>
                            <td class="py-3 px-3 font-mono"><?= formatMoney($item['unit_price']) ?></td>
                            <td class="py-3 px-3"><?= $item['quantity'] ?></td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900"><?= formatMoney($item['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Payments History List -->
            <div class="pt-6 border-t border-slate-100 space-y-3">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-receipt text-emerald-600 mr-1.5"></i> Payment Settlements History
                </h4>

                <?php if (!empty($payments)): ?>
                    <div class="space-y-2">
                        <?php foreach ($payments as $pay): ?>
                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-mono font-bold text-emerald-700"><?= e($pay['receipt_number']) ?></span>
                                    <span class="text-slate-500 block text-[11px]"><?= formatDateTime($pay['payment_date']) ?> via <strong><?= e($pay['payment_method']) ?></strong></span>
                                </div>
                                <div class="text-right">
                                    <span class="font-black text-slate-900 font-mono text-sm"><?= formatMoney($pay['amount']) ?></span>
                                    <span class="text-[10px] text-slate-400 block">By: <?= e($pay['received_by_name'] ?? 'Cashier') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-xs text-slate-400 py-2">No payments received for this invoice yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cashier Payment Recorder Terminal (1 Col) -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4 h-fit">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                <i class="fa-solid fa-cash-register text-emerald-600 mr-2"></i> Receive Payment
            </h3>

            <?php if ((float)$inv['due_amount'] > 0): ?>
                <form method="POST" action="" class="space-y-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="record_payment" value="1">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Payment Amount ($) *</label>
                        <input type="number" step="0.01" name="amount" value="<?= (float)$inv['due_amount'] ?>" max="<?= (float)$inv['due_amount'] ?>" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none">
                        <span class="text-[11px] text-slate-400 mt-1 block">Maximum Due: <?= formatMoney($inv['due_amount']) ?></span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Payment Mode *</label>
                        <select name="payment_method" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none">
                            <option value="Cash">Cash Counter</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Insurance">Insurance / TPA</option>
                            <option value="Mobile Money">Mobile Money</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Transaction Ref (Optional)</label>
                        <input type="text" name="transaction_reference" placeholder="e.g. TXN-VISA-9948" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Cashier Notes</label>
                        <input type="text" name="notes" placeholder="Settlement at counter..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none">
                    </div>

                    <button type="submit" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-circle-check mr-1.5"></i> Settle Payment Now
                    </button>
                </form>
            <?php else: ?>
                <div class="p-6 bg-emerald-50 border border-emerald-200 rounded-2xl text-center space-y-2">
                    <i class="fa-solid fa-circle-check text-3xl text-emerald-600"></i>
                    <h4 class="font-bold text-emerald-900 text-sm">Invoice Fully Paid</h4>
                    <p class="text-xs text-emerald-700">No outstanding dues remaining on this bill.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
