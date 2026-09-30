<?php
/**
 * CarePoint Pro HMS - Generate Custom Unified Hospital Invoice
 */

$pageTitle = 'Generate Hospital Invoice';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$preselectedPatientId = (int)($_GET['patient_id'] ?? 0);

$patients = $pdo->query("SELECT id, full_name, mrn, phone FROM patients ORDER BY full_name ASC")->fetchAll();
$taxRate = (float)getSetting('tax_percentage', 5.00);

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $patientId = (int)($_POST['patient_id'] ?? 0);
    $refType = $_POST['reference_type'] ?? 'General';
    $notes = trim($_POST['notes'] ?? '');
    $discount = (float)($_POST['discount'] ?? 0.00);
    $paidAmount = (float)($_POST['paid_amount'] ?? 0.00);
    $paymentMethod = $_POST['payment_method'] ?? 'Cash';

    $descriptions = $_POST['item_desc'] ?? [];
    $categories = $_POST['item_cat'] ?? [];
    $unitPrices = $_POST['unit_price'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if ($patientId <= 0 || empty($descriptions)) {
        $error = 'Please select a patient and add at least one billable item.';
    } else {
        try {
            $pdo->beginTransaction();

            $subtotal = 0.00;
            $items = [];

            for ($i = 0; $i < count($descriptions); $i++) {
                if (!empty($descriptions[$i])) {
                    $desc = trim($descriptions[$i]);
                    $cat = $categories[$i] ?? 'Other';
                    $uPrice = (float)($unitPrices[$i] ?? 0.00);
                    $qty = max(1, (int)($quantities[$i] ?? 1));
                    $itemTotal = $uPrice * $qty;
                    $subtotal += $itemTotal;

                    $items[] = [
                        'desc' => $desc,
                        'cat'  => $cat,
                        'price'=> $uPrice,
                        'qty'  => $qty,
                        'total'=> $itemTotal
                    ];
                }
            }

            $discountedSubtotal = max(0, $subtotal - $discount);
            $taxAmount = ($discountedSubtotal * $taxRate) / 100;
            $totalAmount = $discountedSubtotal + $taxAmount;

            $paid = min($totalAmount, max(0, $paidAmount));
            $due = max(0, $totalAmount - $paid);
            $status = ($due == 0) ? 'Paid' : (($paid > 0) ? 'Partial' : 'Unpaid');

            $invNumber = generateInvoiceNumber();

            // 1. Insert Invoice
            $invStmt = $pdo->prepare("INSERT INTO invoices (invoice_number, patient_id, reference_type, subtotal, discount, tax, total_amount, paid_amount, due_amount, payment_status, due_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)");
            $invStmt->execute([$invNumber, $patientId, $refType, $subtotal, $discount, $taxAmount, $totalAmount, $paid, $due, $status, $notes, currentUser()['id']]);
            $invoiceId = $pdo->lastInsertId();

            // 2. Insert Invoice Items
            $itemStmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, category, unit_price, quantity, total) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $itemStmt->execute([$invoiceId, $item['desc'], $item['cat'], $item['price'], $item['qty'], $item['total']]);
            }

            // 3. Record Payment if paid > 0
            if ($paid > 0) {
                $receiptNumber = 'REC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $pStmt = $pdo->prepare("INSERT INTO payments (receipt_number, invoice_id, patient_id, amount, payment_method, notes, received_by) VALUES (?, ?, ?, ?, ?, 'Initial payment upon invoice generation', ?)");
                $pStmt->execute([$receiptNumber, $invoiceId, $patientId, $paid, $paymentMethod, currentUser()['id']]);
            }

            $pdo->commit();
            logActivity('Invoice Created', "Created invoice {$invNumber} (Total: {$totalAmount})");
            setFlash('success', "Invoice <strong>{$invNumber}</strong> created successfully.");
            header("Location: print_invoice.php?id={$invoiceId}");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to create invoice: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <a href="index.php" class="text-xs text-slate-500 hover:text-teal-600 font-bold inline-flex items-center mb-1">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Invoices
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Generate Hospital Billing Invoice</h1>
        <p class="text-xs text-slate-500">Create itemized hospital charges, consultation fees, procedures, or pharmacy tariffs.</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-6">
        <?= csrfField() ?>

        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-file-invoice text-emerald-600 mr-2"></i> Invoice Target & Account
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Patient (EMR) *</label>
                    <select name="patient_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($preselectedPatientId === (int)$p['id']) ? 'selected' : '' ?>>
                                <?= e($p['full_name']) ?> (MRN: <?= e($p['mrn']) ?>) - <?= e($p['phone']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Department / Reference Type *</label>
                    <select name="reference_type" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none font-bold">
                        <option value="General">General Hospital Care</option>
                        <option value="OPD">OPD Consultation Desk</option>
                        <option value="IPD">Inpatient Hospitalization (IPD)</option>
                        <option value="Laboratory">Diagnostic Pathology (LIS)</option>
                        <option value="Pharmacy">Pharmacy & Medical Supplies</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-list-ol text-teal-600 mr-2"></i> Itemized Tariffs & Services
                </h3>
                <button type="button" onclick="addInvoiceRow()" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl text-xs font-bold transition border border-emerald-200">
                    + Add Charge Line
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs" id="invoice-items-table">
                    <thead class="text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-2 px-2">Service / Item Description</th>
                            <th class="py-2 px-2 w-36">Category</th>
                            <th class="py-2 px-2 w-28">Unit Price</th>
                            <th class="py-2 px-2 w-20">Qty</th>
                            <th class="py-2 px-2 text-right w-24">Total</th>
                            <th class="py-2 px-2 text-right w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="invoice-items-tbody">
                        <tr class="inv-row">
                            <td class="py-2 px-2">
                                <input type="text" name="item_desc[]" value="Specialist Clinical Consultation Fee" required class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold focus:bg-white focus:outline-none">
                            </td>
                            <td class="py-2 px-2">
                                <select name="item_cat[]" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
                                    <option value="Consultation">Consultation</option>
                                    <option value="Bed">Bed / Room</option>
                                    <option value="Laboratory">Laboratory</option>
                                    <option value="Pharmacy">Pharmacy</option>
                                    <option value="Procedure">Procedure / Surgery</option>
                                    <option value="Nursing">Nursing Care</option>
                                    <option value="Other">Other Tariff</option>
                                </select>
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" step="0.01" name="unit_price[]" value="50.00" oninput="calcInvTotal()" class="inv-price w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold focus:bg-white focus:outline-none">
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" name="quantity[]" value="1" min="1" oninput="calcInvTotal()" class="inv-qty w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold focus:bg-white focus:outline-none">
                            </td>
                            <td class="py-2 px-2 text-right font-mono font-bold text-slate-900 inv-subtotal">
                                $50.00
                            </td>
                            <td class="py-2 px-2 text-right">
                                <button type="button" onclick="this.closest('tr').remove(); calcInvTotal();" class="text-rose-500 hover:text-rose-700 p-1">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Financial Computation Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Invoice Memo / Notes</label>
                    <textarea name="notes" rows="3" placeholder="Notes for patient invoice..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none"></textarea>
                </div>

                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Items Subtotal:</span>
                        <span class="font-bold font-mono text-slate-900" id="inv-calc-subtotal">$50.00</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Discount ($):</span>
                        <input type="number" step="0.01" name="discount" id="inv-discount" value="0.00" oninput="calcInvTotal()" class="w-24 px-2 py-1 bg-white border border-slate-200 rounded-lg text-right font-mono text-xs font-bold">
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Tax (<?= $taxRate ?>%):</span>
                        <span class="font-bold font-mono text-slate-900" id="inv-calc-tax">$2.50</span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 flex justify-between font-black text-slate-900 text-sm">
                        <span>Net Total:</span>
                        <span class="text-emerald-700 font-mono" id="inv-calc-total">$52.50</span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Amount Paid Now ($)</label>
                            <input type="number" step="0.01" name="paid_amount" value="52.50" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg font-mono text-xs font-bold text-emerald-700">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Payment Method</label>
                            <select name="payment_method" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold">
                                <option value="Cash">Cash</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Debit Card">Debit Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Insurance">Insurance</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="index.php" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-lg shadow-emerald-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-file-invoice-dollar mr-2"></i> Save & Generate Invoice
            </button>
        </div>
    </form>

</div>

<script>
const taxPercent = <?= $taxRate ?>;

function addInvoiceRow() {
    const tbody = document.getElementById('invoice-items-tbody');
    const tr = document.createElement('tr');
    tr.className = 'inv-row';
    tr.innerHTML = `
        <td class="py-2 px-2">
            <input type="text" name="item_desc[]" required placeholder="Service / Procedure Name" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold focus:bg-white focus:outline-none">
        </td>
        <td class="py-2 px-2">
            <select name="item_cat[]" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:outline-none">
                <option value="Consultation">Consultation</option>
                <option value="Bed">Bed / Room</option>
                <option value="Laboratory">Laboratory</option>
                <option value="Pharmacy">Pharmacy</option>
                <option value="Procedure">Procedure / Surgery</option>
                <option value="Nursing">Nursing Care</option>
                <option value="Other">Other Tariff</option>
            </select>
        </td>
        <td class="py-2 px-2">
            <input type="number" step="0.01" name="unit_price[]" value="25.00" oninput="calcInvTotal()" class="inv-price w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold focus:bg-white focus:outline-none">
        </td>
        <td class="py-2 px-2">
            <input type="number" name="quantity[]" value="1" min="1" oninput="calcInvTotal()" class="inv-qty w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold focus:bg-white focus:outline-none">
        </td>
        <td class="py-2 px-2 text-right font-mono font-bold text-slate-900 inv-subtotal">
            $25.00
        </td>
        <td class="py-2 px-2 text-right">
            <button type="button" onclick="this.closest('tr').remove(); calcInvTotal();" class="text-rose-500 hover:text-rose-700 p-1">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    calcInvTotal();
}

function calcInvTotal() {
    let subtotal = 0;
    document.querySelectorAll('.inv-row').forEach(tr => {
        const price = parseFloat(tr.querySelector('.inv-price').value || 0);
        const qty = parseInt(tr.querySelector('.inv-qty').value || 1);
        const itemTotal = price * qty;
        tr.querySelector('.inv-subtotal').innerText = '$' + itemTotal.toFixed(2);
        subtotal += itemTotal;
    });

    const discount = parseFloat(document.getElementById('inv-discount').value || 0);
    const discounted = Math.max(0, subtotal - discount);
    const tax = (discounted * taxPercent) / 100;
    const total = discounted + tax;

    document.getElementById('inv-calc-subtotal').innerText = '$' + subtotal.toFixed(2);
    document.getElementById('inv-calc-tax').innerText = '$' + tax.toFixed(2);
    document.getElementById('inv-calc-total').innerText = '$' + total.toFixed(2);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
