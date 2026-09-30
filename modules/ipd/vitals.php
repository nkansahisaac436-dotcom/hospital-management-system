<?php
/**
 * CarePoint Pro HMS - Patient Vital Signs Recording Console
 */

$pageTitle = 'Patient Vital Signs Console';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pdo = Database::getConnection();
$patientId = (int)($_GET['patient_id'] ?? 0);

$patients = $pdo->query("SELECT id, full_name, mrn, gender, blood_group FROM patients ORDER BY full_name ASC")->fetchAll();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $pId = (int)($_POST['patient_id'] ?? 0);
    $temperature = !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null;
    $bloodPressure = trim($_POST['blood_pressure'] ?? '');
    $pulseRate = !empty($_POST['pulse_rate']) ? (int)$_POST['pulse_rate'] : null;
    $respiratoryRate = !empty($_POST['respiratory_rate']) ? (int)$_POST['respiratory_rate'] : null;
    $spo2 = !empty($_POST['spo2']) ? (int)$_POST['spo2'] : null;
    $bloodSugar = !empty($_POST['blood_sugar']) ? (float)$_POST['blood_sugar'] : null;
    $weight = !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null;
    $height = !empty($_POST['height_cm']) ? (float)$_POST['height_cm'] : null;
    $notes = trim($_POST['notes'] ?? '');

    if ($pId <= 0) {
        $error = 'Please select a valid patient.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO vitals (patient_id, recorded_by, temperature, blood_pressure, pulse_rate, respiratory_rate, spo2, blood_sugar, weight_kg, height_cm, notes, recorded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$pId, currentUser()['id'], $temperature, $bloodPressure, $pulseRate, $respiratoryRate, $spo2, $bloodSugar, $weight, $height, $notes]);

            logActivity('Vitals Recorded', "Recorded vitals for patient ID {$pId}");
            setFlash('success', 'Vital signs successfully recorded and logged into patient EMR.');
            header("Location: " . APP_URL . "/modules/patients/view.php?id={$pId}&tab=summary");
            exit;
        } catch (Exception $e) {
            $error = 'Failed to record vitals: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Record Patient Vital Signs</h1>
        <p class="text-xs text-slate-500">Nursing and clinical telemetry observations logger.</p>
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
                    <i class="fa-solid fa-heart-pulse text-rose-500 mr-2"></i> Vital Signs Parameters
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                
                <div class="sm:col-span-2 md:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Patient (EMR) *</label>
                    <select name="patient_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none">
                        <option value="">-- Choose Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($patientId === (int)$p['id']) ? 'selected' : '' ?>>
                                <?= e($p['full_name']) ?> (MRN: <?= e($p['mrn']) ?> &bull; <?= e($p['gender']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Blood Pressure (mmHg)</label>
                    <input type="text" name="blood_pressure" placeholder="e.g. 120/80" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pulse Rate (bpm)</label>
                    <input type="number" name="pulse_rate" placeholder="e.g. 72" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Body Temperature (&deg;C)</label>
                    <input type="number" step="0.1" name="temperature" placeholder="e.g. 36.8" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Oxygen Saturation SpO2 (%)</label>
                    <input type="number" name="spo2" placeholder="e.g. 98" max="100" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Blood Sugar (mg/dL)</label>
                    <input type="number" step="0.1" name="blood_sugar" placeholder="e.g. 95.0" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Respiratory Rate (breaths/m)</label>
                    <input type="number" name="respiratory_rate" placeholder="e.g. 16" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Weight (kg)</label>
                    <input type="number" step="0.1" name="weight_kg" placeholder="e.g. 70.5" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Height (cm)</label>
                    <input type="number" step="0.1" name="height_cm" placeholder="e.g. 175" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none font-bold">
                </div>

                <div class="sm:col-span-2 md:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nursing Clinical Observation Notes</label>
                    <textarea name="notes" rows="2" placeholder="Patient alert, oriented to time and place, skin warm and dry..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white focus:outline-none"></textarea>
                </div>

            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <button type="submit" class="px-8 py-3.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-heart-pulse mr-2"></i> Save Vital Signs
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
