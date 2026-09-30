<?php
/**
 * CarePoint Pro HMS - Pharmacy Point of Sale (POS) & Prescription Dispenser
 */

$pageTitle = 'Pharmacy POS Dispenser';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();

// Fetch Active Prescriptions
$prescriptions = $pdo->query("SELECT p.*, pat.full_name as patient_name, pat.mrn, u.full_name as doctor_name
                              FROM prescriptions p
                              JOIN patients pat ON p.patient_id = pat.id
                              JOIN doctors d ON p.doctor_id = d.id
                              JOIN users u ON d.user_id = u.id
                              WHERE p.status = 'Active'
                              ORDER BY p.created_at DESC")->fetchAll();

// Fetch Medicines Catalog for Direct POS Cart
$medicines = $pdo->query("SELECT * FROM medicines WHERE status = 'active' AND stock_quantity > 0 ORDER BY name ASC")->fetchAll();
$taxRate = (float)getSetting('tax_percentage', 5.00);

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $customerName = trim($_POST['customer_name'] ?? 'Walk-In Customer');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $prescriptionId = !empty($_POST['prescription_id']) ? (int)$_POST['prescription_id'] : null;
    $patientId = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
    $paymentMethod = $_POST['payment_method'] ?? 'Cash';
    $discount = (float)($_POST['discount'] ?? 0.00);

    $medIds = $_POST['med_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unitPrices = $_POST['unit_price'] ?? [];

    if (empty($medIds)) {
        $error = 'Cart is empty. Please add at least one medicine.';
    } else {
        try {
            $pdo->beginTransaction();

            $subtotal = 0.00;
            $saleItems = [];

            for ($i = 0; $i < count($medIds); $i++) {
                $mId = (int)$medIds[$i];
                $qty = max(1, (int)$quantities[$i]);
                $price = (float)$unitPrices[$i];
                $itemSubtotal = $qty * $price;
                $subtotal += $itemSubtotal;

                // Find medicine details
                $mStmt = $pdo->prepare("SELECT name, batch_number, stock_quantity FROM medicines WHERE id = ? FOR UPDATE");
                $mStmt->execute([$mId]);
                $mRow = $mStmt->fetch();

                if (!$mRow || $mRow['stock_quantity'] < $qty) {
                    throw new Exception("Insufficient stock for medicine: " . ($mRow['name'] ?? "ID #{$mId}"));
                }

                $saleItems[] = [
                    'medicine_id' => $mId,
                    'medicine_name' => $mRow['name'],
                    'batch_number' => $mRow['batch_number'],
                    'unit_price' => $price,
                    'quantity' => $qty,
                    'subtotal' => $itemSubtotal
                ];

                // Deduct stock
                $pdo->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$qty, $mId]);
            }

            $discountedSubtotal = max(0, $subtotal - $discount);
            $taxAmount = ($discountedSubtotal * $taxRate) / 100;
            $totalAmount = $discountedSubtotal + $taxAmount;

            $saleNumber = 'POS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            // 1. Insert pharmacy sale
            $sStmt = $pdo->prepare("INSERT INTO pharmacy_sales (sale_number, prescription_id, patient_id, customer_name, customer_phone, subtotal, discount, tax, total_amount, payment_method, status, sold_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid', ?)");
            $sStmt->execute([$saleNumber, $prescriptionId, $patientId, $customerName, $customerPhone, $subtotal, $discount, $taxAmount, $totalAmount, $paymentMethod, currentUser()['id']]);
            $saleId = $pdo->lastInsertId();

            // 2. Insert Sale Items
            $siStmt = $pdo->prepare("INSERT INTO pharmacy_sale_items (sale_id, medicine_id, medicine_name, batch_number, unit_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($saleItems as $sItem) {
                $siStmt->execute([$saleId, $sItem['medicine_id'], $sItem['medicine_name'], $sItem['batch_number'], $sItem['unit_price'], $sItem['quantity'], $sItem['subtotal']]);
            }

            // 3. If linked to prescription, mark prescription as Dispensed
            if ($prescriptionId) {
                $pdo->prepare("UPDATE prescriptions SET status = 'Dispensed' WHERE id = ?")->execute([$prescriptionId]);
            }

            $pdo->commit();
            logActivity('Pharmacy POS Sale', "Dispensed sale {$saleNumber} (Total: {$totalAmount})");
            setFlash('success', "Sale completed successfully! Receipt <strong>{$saleNumber}</strong> generated.");
            header("Location: sales.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Sale processing failed: ' . $e->getMessage();
        }
    }
}
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pharmacy Point of Sale (POS) & Dispenser</h1>
            <p class="text-xs text-slate-500 mt-1">Dispense doctor e-prescriptions or process direct over-the-counter drug sales.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="sales.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                <i class="fa-solid fa-receipt mr-1.5 text-teal-600"></i> Sales History
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- 1-Click Load Doctor E-Prescription Accordion -->
    <?php if (!empty($prescriptions)): ?>
        <div class="bg-gradient-to-r from-sky-50 to-teal-50 rounded-3xl p-5 border border-teal-200 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-teal-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-wand-magic-sparkles text-teal-600 mr-2"></i> Active Doctor Prescriptions Ready to Dispense (<?= count($prescriptions) ?>)
                </h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                <?php foreach ($prescriptions as $pRx): ?>
                    <button type="button" onclick="loadPrescription(<?= $pRx['id'] ?>, '<?= addslashes($pRx['patient_name']) ?>', '<?= $pRx['patient_id'] ?>')" class="p-3 bg-white hover:bg-teal-100/50 rounded-2xl border border-teal-200 text-left transition shadow-sm flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-teal-700 text-xs block"><?= e($pRx['prescription_number']) ?></span>
                            <span class="font-bold text-slate-800 text-xs block"><?= e($pRx['patient_name']) ?></span>
                            <span class="text-[10px] text-slate-400">By Dr. <?= e($pRx['doctor_name']) ?></span>
                        </div>
                        <span class="px-2.5 py-1 rounded-xl bg-teal-50 text-teal-700 font-bold text-[11px]">Load &rarr;</span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- POS Main Interface Grid -->
    <form method="POST" action="" id="pos-form" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <?= csrfField() ?>
        <input type="hidden" name="prescription_id" id="pos_prescription_id" value="">
        <input type="hidden" name="patient_id" id="pos_patient_id" value="">

        <!-- Left: Product Search & Quick Drug Catalog (2 Cols) -->
        <div class="lg:col-span-2 space-y-4">
            
            <!-- Quick Add Drug Dropdown -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-magnifying-glass text-teal-600 mr-2"></i> Quick Medicine Selector
                    </h3>
                </div>

                <div class="flex items-center space-x-3">
                    <select id="catalog-select" class="flex-1 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Medicine to Add to Cart --</option>
                        <?php foreach ($medicines as $m): ?>
                            <option value="<?= $m['id'] ?>" data-name="<?= e($m['name']) ?>" data-price="<?= $m['unit_price'] ?>" data-stock="<?= $m['stock_quantity'] ?>">
                                <?= e($m['name']) ?> (<?= e($m['strength']) ?>) - <?= formatMoney($m['unit_price']) ?> [Stock: <?= $m['stock_quantity'] ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="addSelectedMedicine()" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                        + Add to Cart
                    </button>
                </div>
            </div>

            <!-- Active Cart Table -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-cart-shopping text-sky-600 mr-2"></i> Dispensing Cart Items
                    </h3>
                    <span id="cart-item-count" class="badge badge-primary text-[10px]">0 item(s)</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs" id="cart-table">
                        <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-2.5 px-3">Medicine Formulation</th>
                                <th class="py-2.5 px-3 w-28">Unit Price</th>
                                <th class="py-2.5 px-3 w-28">Quantity</th>
                                <th class="py-2.5 px-3 text-right w-28">Subtotal</th>
                                <th class="py-2.5 px-3 text-right w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium" id="cart-tbody">
                            <tr id="empty-cart-row">
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-basket-shopping text-3xl mb-2 block"></i>
                                    Cart is empty. Select medicines from the catalog above.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Right: Customer Info & Checkout Summary (1 Col) -->
        <div class="space-y-4">
            
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                    <i class="fa-solid fa-user-check text-teal-600 mr-2"></i> Customer Details
                </h3>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Customer / Patient Name</label>
                    <input type="text" name="customer_name" id="pos_customer_name" value="Walk-In Customer" required class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Phone Number (Optional)</label>
                    <input type="tel" name="customer_phone" placeholder="+1 555-0199" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="Cash">Cash Counter</option>
                        <option value="Card">Debit / Credit Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Insurance">Insurance / TPA</option>
                    </select>
                </div>
            </div>

            <!-- Billing Summary Box -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                    <i class="fa-solid fa-file-invoice-dollar text-emerald-600 mr-2"></i> Payment Calculation
                </h3>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Items Subtotal:</span>
                        <span class="font-bold font-mono text-slate-900" id="calc-subtotal">$0.00</span>
                    </div>

                    <div class="flex justify-between items-center text-slate-600">
                        <span>Discount ($):</span>
                        <input type="number" step="0.01" name="discount" id="pos-discount" value="0.00" min="0" oninput="calculateTotals()" class="w-20 px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-right font-mono text-xs focus:outline-none">
                    </div>

                    <div class="flex justify-between text-slate-600">
                        <span>Sales Tax (<?= $taxRate ?>%):</span>
                        <span class="font-bold font-mono text-slate-900" id="calc-tax">$0.00</span>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-between text-base font-black text-slate-900">
                        <span>Total Payable:</span>
                        <span class="text-teal-700 font-mono" id="calc-total">$0.00</span>
                    </div>
                </div>

                <button type="submit" class="w-full py-4 bg-gradient-to-r from-teal-600 to-sky-600 hover:from-teal-700 hover:to-sky-700 text-white font-bold rounded-2xl shadow-lg transition transform hover:-translate-y-0.5 text-xs flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-circle-check text-sm"></i>
                    <span>Dispense & Complete Sale</span>
                </button>
            </div>

        </div>

    </form>

</div>

<script>
const taxPercent = <?= $taxRate ?>;

function addSelectedMedicine() {
    const select = document.getElementById('catalog-select');
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;

    const medId = opt.value;
    const name = opt.getAttribute('data-name');
    const price = parseFloat(opt.getAttribute('data-price') || 0);

    addCartRow(medId, name, price, 1);
    select.selectedIndex = 0;
}

function addCartRow(medId, name, price, qty) {
    const emptyRow = document.getElementById('empty-cart-row');
    if (emptyRow) emptyRow.remove();

    const tbody = document.getElementById('cart-tbody');
    
    // Check if already in cart
    const existing = tbody.querySelector(`tr[data-med-id="${medId}"]`);
    if (existing) {
        const qtyInput = existing.querySelector('.qty-input');
        qtyInput.value = parseInt(qtyInput.value) + qty;
        updateRowSubtotal(existing);
        calculateTotals();
        return;
    }

    const tr = document.createElement('tr');
    tr.setAttribute('data-med-id', medId);
    tr.className = 'cart-item-row hover:bg-slate-50 transition';
    tr.innerHTML = `
        <td class="py-3 px-3">
            <input type="hidden" name="med_id[]" value="${medId}">
            <strong class="text-slate-900 text-xs block">${name}</strong>
        </td>
        <td class="py-3 px-3">
            <input type="number" step="0.01" name="unit_price[]" value="${price.toFixed(2)}" readonly class="w-20 px-2 py-1 bg-slate-50 rounded font-mono text-xs font-bold text-slate-700">
        </td>
        <td class="py-3 px-3">
            <input type="number" name="quantity[]" value="${qty}" min="1" oninput="updateRowSubtotal(this.closest('tr')); calculateTotals();" class="qty-input w-16 px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-bold focus:outline-none">
        </td>
        <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 item-subtotal">
            $${(price * qty).toFixed(2)}
        </td>
        <td class="py-3 px-3 text-right">
            <button type="button" onclick="this.closest('tr').remove(); calculateTotals();" class="text-rose-500 hover:text-rose-700 p-1">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    calculateTotals();
}

function updateRowSubtotal(tr) {
    const price = parseFloat(tr.querySelector('input[name="unit_price[]"]').value || 0);
    const qty = parseInt(tr.querySelector('.qty-input').value || 1);
    const subtotalEl = tr.querySelector('.item-subtotal');
    subtotalEl.innerText = '$' + (price * qty).toFixed(2);
}

function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('.cart-item-row').forEach(tr => {
        const price = parseFloat(tr.querySelector('input[name="unit_price[]"]').value || 0);
        const qty = parseInt(tr.querySelector('.qty-input').value || 1);
        subtotal += (price * qty);
    });

    const discount = parseFloat(document.getElementById('pos-discount').value || 0);
    const discounted = Math.max(0, subtotal - discount);
    const tax = (discounted * taxPercent) / 100;
    const total = discounted + tax;

    document.getElementById('calc-subtotal').innerText = '$' + subtotal.toFixed(2);
    document.getElementById('calc-tax').innerText = '$' + tax.toFixed(2);
    document.getElementById('calc-total').innerText = '$' + total.toFixed(2);

    const count = document.querySelectorAll('.cart-item-row').length;
    document.getElementById('cart-item-count').innerText = count + ' item(s)';
}

function loadPrescription(rxId, patientName, patientId) {
    document.getElementById('pos_prescription_id').value = rxId;
    document.getElementById('pos_patient_id').value = patientId;
    document.getElementById('pos_customer_name').value = patientName;

    // Load prescription items dynamically via AJAX
    fetch('get_rx_items.php?id=' + rxId)
        .then(res => res.json())
        .then(data => {
            if (data && data.items) {
                const tbody = document.getElementById('cart-tbody');
                tbody.innerHTML = '';
                data.items.forEach(item => {
                    addCartRow(item.medicine_id || 1, item.medicine_name, parseFloat(item.unit_price || 15.00), parseInt(item.quantity || 1));
                });
                showToast('success', 'Prescription loaded into cart.');
            }
        });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
