-- CarePoint Pro Hospital Management System
-- Comprehensive Seed Data

SET FOREIGN_KEY_CHECKS = 0;

-- 1. System Settings
INSERT INTO `system_settings` (`key`, `value`, `group`) VALUES
('hospital_name', 'CarePoint Medical Center & Specialist Hospital', 'general'),
('hospital_tagline', 'Compassionate Care, Advanced Medical Excellence', 'general'),
('hospital_phone', '+1 (555) 234-5678 / +1 (555) 876-5432', 'general'),
('hospital_email', 'contact@carepointmedical.com', 'general'),
('hospital_address', '450 Health Avenue, Suite 100, Metro City, MC 90210', 'general'),
('currency_symbol', '$', 'financial'),
('tax_percentage', '5.00', 'financial'),
('invoice_prefix', 'INV-', 'financial'),
('prescription_footer', 'Please bring this prescription and your previous medical records on your next visit. In case of acute symptoms, visit our 24/7 Emergency Wing immediately.', 'clinical'),
('system_timezone', 'UTC', 'general'),
('logo_url', 'assets/images/logo.png', 'branding')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- 2. Users (Password for all accounts is: password123)
-- Hash: $2y$10$qdnvZZAP.yzVNW/YZ7WMaOcbazLyFO7GgRsoHeJy3fzzZguJeDGN.
INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$qdnvZZAP.yzVNW/YZ7WMaOcbazLyFO7GgRsoHeJy3fzzZguJeDGN.', 'Dr. Sarah Jenkins', 'admin@carepoint.com', '+1 555-0100', 'admin', 'active'),
(2, 'doctor', '$2y$10$qdnvZZAP.yzVNW/YZ7WMaOcbazLyFO7GgRsoHeJy3fzzZguJeDGN.', 'Dr. Alexander Hayes, MD', 'hayes@carepoint.com', '+1 555-0101', 'doctor', 'active'),
(3, 'doctor2', '$2y$10$qdnvZZAP.yzVNW/YZ7WMaOcbazLyFO7GgRsoHeJy3fzzZguJeDGN.', 'Dr. Emily Watson, MD', 'watson@carepoint.com', '+1 555-0102', 'doctor', 'active'),
(4, 'doctor3', '$2y$10$qdnvZZAP.yzVNW/YZ7WMaOcbazLyFO7GgRsoHeJy3fzzZguJeDGN.', 'Dr. Marcus Vance, MS', 'vance@carepoint.com', '+1 555-0103', 'doctor', 'active'),
(5, 'nurse', '$2y$10$qdnvZZAP.yzVNW/YZ7WMaOcbazLyFO7GgRsoHeJy3fzzZguJeDGN.', 'Nurse Clara Oswald, RN', 'clara@carepoint.com', '+1 555-0104', 'nurse', 'active'),
(6, 'reception', '$2y$10$qdnvZZAP.yzVNW/YZ7WMaOcbazLyFO7GgRsoHeJy3fzzZguJeDGN.', 'Rachel Green (Front Desk)', 'reception@carepoint.com', '+1 555-0105', 'receptionist', 'active'),
(7, 'pharmacist', '$2y$10$qdnvZZAP.yzVNW/YZ7WMaOcbazLyFO7GgRsoHeJy3fzzZguJeDGN.', 'David Miller, PharmD', 'pharmacy@carepoint.com', '+1 555-0106', 'pharmacist', 'active'),
(8, 'labtech', '$2y$10$qdnvZZAP.yzVNW/YZ7WMaOcbazLyFO7GgRsoHeJy3fzzZguJeDGN.', 'Dr. Robert Langdon (Pathologist)', 'lab@carepoint.com', '+1 555-0107', 'lab_tech', 'active'),
(9, 'accountant', '$2y$10$qdnvZZAP.yzVNW/YZ7WMaOcbazLyFO7GgRsoHeJy3fzzZguJeDGN.', 'Arthur Pendelton (Billing Officer)', 'billing@carepoint.com', '+1 555-0108', 'accountant', 'active'),
(10, 'patient', '$2y$10$qdnvZZAP.yzVNW/YZ7WMaOcbazLyFO7GgRsoHeJy3fzzZguJeDGN.', 'Johnathan Doe', 'john.doe@example.com', '+1 555-0199', 'patient', 'active')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- 3. Departments
INSERT INTO `departments` (`id`, `name`, `description`, `icon`, `status`) VALUES
(1, 'Cardiology', 'Comprehensive cardiovascular diagnostics, interventions, and heart disease management.', 'fa-heart-pulse', 'active'),
(2, 'Pediatrics & Child Health', 'Specialized care for infants, children, and adolescents from birth to 18 years.', 'fa-baby', 'active'),
(3, 'Orthopedics & Joint Care', 'Surgical and non-surgical musculoskeletal trauma, joints, and spine care.', 'fa-bone', 'active'),
(4, 'Neurology & Brain Sciences', 'Diagnosis and therapy for brain, spine, and central nervous system disorders.', 'fa-brain', 'active'),
(5, 'General Internal Medicine', 'Preventative healthcare, chronic disease management, and primary diagnostics.', 'fa-user-doctor', 'active'),
(6, 'Obstetrics & Gynecology (OB-GYN)', 'Maternal-fetal medicine, prenatal care, women health and labor delivery.', 'fa-person-pregnant', 'active'),
(7, 'Emergency & Trauma Center', '24/7 acute trauma stabilization, resuscitation, and rapid critical care triage.', 'fa-truck-medical', 'active'),
(8, 'Radiology & Imaging', 'MRI, CT Scan, Ultrasound, Digital Fluoroscopy and Digital X-Rays.', 'fa-x-ray', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 4. Doctors
INSERT INTO `doctors` (`id`, `user_id`, `department_id`, `specialization`, `consultation_fee`, `room_no`, `experience_years`, `bio`, `schedule_days`, `schedule_time_start`, `schedule_time_end`, `status`) VALUES
(1, 2, 1, 'Senior Consultant Interventional Cardiologist', 75.00, 'Room 201 - Wing A', 14, 'Board-certified cardiologist with fellowship training in coronary interventions and heart failure.', 'Mon,Tue,Wed,Thu,Fri', '09:00:00', '16:00:00', 'active'),
(2, 3, 2, 'Consultant Pediatrician & Neonatologist', 55.00, 'Room 105 - Wing B', 9, 'Dedicated child health expert specializing in neonatal intensive care and pediatric developmental health.', 'Mon,Tue,Wed,Thu,Sat', '08:30:00', '15:00:00', 'active'),
(3, 4, 3, 'Chief Orthopedic & Arthroscopy Surgeon', 80.00, 'Room 304 - Wing C', 16, 'Specialist in robotic knee/hip arthroplasty, sports ligament reconstructions, and complex fractures.', 'Mon,Wed,Fri,Sat', '10:00:00', '17:00:00', 'active')
ON DUPLICATE KEY UPDATE `specialization` = VALUES(`specialization`);

-- 5. Patients Master
INSERT INTO `patients` (`id`, `user_id`, `mrn`, `full_name`, `email`, `phone`, `gender`, `date_of_birth`, `blood_group`, `address`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relation`, `allergies`, `chronic_diseases`) VALUES
(1, 10, 'MRN-2026-0001', 'Johnathan Doe', 'john.doe@example.com', '+1 555-0199', 'Male', '1988-06-14', 'O+', '742 Evergreen Terrace, Springfield', 'Jane Doe', '+1 555-0198', 'Spouse', 'Penicillin, Shellfish', 'Hypertension (Mild)'),
(2, NULL, 'MRN-2026-0002', 'Eleanor Vance', 'eleanor.vance@example.com', '+1 555-0245', 'Female', '1995-11-23', 'A+', '128 Willow Creek Road, Metro City', 'Thomas Vance', '+1 555-0246', 'Father', 'None Known', 'None'),
(3, NULL, 'MRN-2026-0003', 'Robert K. Sterling', 'robert.sterling@example.com', '+1 555-0377', 'Male', '1965-03-08', 'B+', '88 Pinecrest Blvd, Metro City', 'Margaret Sterling', '+1 555-0378', 'Spouse', 'Aspirin, NSAIDs', 'Type 2 Diabetes, Coronary Artery Disease'),
(4, NULL, 'MRN-2026-0004', 'Sophia Martinez', 'sophia.m@example.com', '+1 555-0412', 'Female', '2019-08-19', 'AB+', '305 Palm Avenue, Metro City', 'Carlos Martinez', '+1 555-0413', 'Father', 'Peanuts', 'Asthma (Pediatric)'),
(5, NULL, 'MRN-2026-0005', 'William H. Davis', 'william.davis@example.com', '+1 555-0551', 'Male', '1979-12-02', 'O-', '512 Beacon Hill Road, Metro City', 'Grace Davis', '+1 555-0552', 'Sister', 'Sulfa Drugs', 'Hypercholesterolemia')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- 6. Hospital Wards
INSERT INTO `wards` (`id`, `name`, `type`, `floor`, `total_beds`, `daily_rate`, `description`) VALUES
(1, 'General Medical Ward (North Wing)', 'General', '2nd Floor', 20, 80.00, 'Shared spacious inpatient recovery ward with 24/7 nursing station and telemetry.'),
(2, 'Pediatric Inpatient Unit', 'Pediatric', '1st Floor', 12, 110.00, 'Child-friendly recovery rooms with parent sleeper couches and play area.'),
(3, 'Executive Deluxe Suite Ward', 'Deluxe', '4th Floor', 8, 250.00, 'Private luxury rooms with ensuite bathroom, smart TV, sofa, and dedicated nurse call.'),
(4, 'Intensive Care Unit (ICU / CCU)', 'ICU', '3rd Floor', 10, 450.00, 'High-acuity life support monitors, ventilators, central hemodynamics, and 1:1 nurse ratio.'),
(5, 'Emergency & Trauma Triage', 'Emergency', 'Ground Floor', 8, 120.00, 'Immediate acute stabilization and emergency observation bays.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 7. Hospital Beds
INSERT INTO `beds` (`id`, `ward_id`, `bed_number`, `status`, `daily_rate`, `current_patient_id`) VALUES
(1, 1, 'GEN-101', 'Available', 80.00, NULL),
(2, 1, 'GEN-102', 'Occupied', 80.00, 3),
(3, 1, 'GEN-103', 'Available', 80.00, NULL),
(4, 1, 'GEN-104', 'Maintenance', 80.00, NULL),
(5, 2, 'PED-201', 'Available', 110.00, NULL),
(6, 2, 'PED-202', 'Available', 110.00, NULL),
(7, 3, 'DLX-401', 'Occupied', 250.00, 1),
(8, 3, 'DLX-402', 'Available', 250.00, NULL),
(9, 4, 'ICU-301', 'Occupied', 450.00, 5),
(10, 4, 'ICU-302', 'Available', 450.00, NULL),
(11, 5, 'EMG-01', 'Available', 120.00, NULL),
(12, 5, 'EMG-02', 'Available', 120.00, NULL)
ON DUPLICATE KEY UPDATE `bed_number` = VALUES(`bed_number`);

-- 8. Medicine Categories
INSERT INTO `medicine_categories` (`id`, `name`, `description`) VALUES
(1, 'Antibiotics & Antimicrobials', 'Broad-spectrum and targeted bacterial infection treatments.'),
(2, 'Analgesics, Antipyretics & Pain Relief', 'Pain management, anti-inflammatory and fever relief.'),
(3, 'Cardiovascular & Antihypertensives', 'Blood pressure regulation, heart rate control, and lipid management.'),
(4, 'Respiratory & Bronchodilators', 'Asthma, COPD, cough suppressants, and respiratory inhalers.'),
(5, 'Gastrointestinal & Antacids', 'Proton pump inhibitors, antiemetics, and digestive relief.'),
(6, 'Endocrine & Antidiabetic', 'Glucose control, insulin analogues, and thyroid therapeutics.'),
(7, 'Vitamins, Minerals & IV Fluids', 'Nutritional support, IV normal saline, and electrolyte supplements.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 9. Medicines Catalog
INSERT INTO `medicines` (`id`, `category_id`, `name`, `generic_name`, `brand_name`, `dosage_form`, `strength`, `unit_price`, `cost_price`, `stock_quantity`, `min_stock_alert`, `batch_number`, `expiry_date`, `manufacturer`, `status`) VALUES
(1, 1, 'Amoxicillin / Clavulanate 625mg', 'Amoxicillin + Clavulanic Acid', 'Augmentin', 'Tablet', '625mg', 18.50, 11.00, 240, 30, 'BATCH-AUG-991', '2027-08-30', 'GSK Pharmaceuticals', 'active'),
(2, 1, 'Azithromycin 500mg', 'Azithromycin Dihydrate', 'Zithromax', 'Tablet', '500mg', 15.00, 8.50, 180, 25, 'BATCH-AZI-442', '2027-05-15', 'Pfizer Inc.', 'active'),
(3, 2, 'Paracetamol / Acetaminophen 500mg', 'Paracetamol', 'Panadol Extra', 'Tablet', '500mg', 4.50, 1.80, 850, 100, 'BATCH-PAN-109', '2028-01-20', 'Haleon Healthcare', 'active'),
(4, 2, 'Ibuprofen 400mg', 'Ibuprofen', 'Brufen', 'Tablet', '400mg', 6.00, 2.75, 420, 50, 'BATCH-IBU-312', '2027-11-10', 'Abbott Laboratories', 'active'),
(5, 3, 'Amlodipine Besylate 5mg', 'Amlodipine', 'Norvasc', 'Tablet', '5mg', 12.00, 6.20, 310, 40, 'BATCH-NOR-778', '2027-10-05', 'Pfizer Inc.', 'active'),
(6, 3, 'Atorvastatin Calcium 20mg', 'Atorvastatin', 'Lipitor', 'Tablet', '20mg', 22.00, 13.50, 260, 35, 'BATCH-LIP-650', '2027-09-18', 'Viatris Global', 'active'),
(7, 4, 'Salbutamol Inhaler 100mcg', 'Albuterol / Salbutamol', 'Ventolin Evohaler', 'Inhaler', '100mcg/dose', 28.00, 16.00, 75, 15, 'BATCH-VEN-204', '2027-04-12', 'GSK Pharmaceuticals', 'active'),
(8, 5, 'Omeprazole 20mg Delayed-Release', 'Omeprazole', 'Prilosec / Losec', 'Capsule', '20mg', 14.00, 7.00, 390, 40, 'BATCH-OME-883', '2027-12-01', 'AstraZeneca', 'active'),
(9, 6, 'Metformin Hydrochloride 500mg', 'Metformin HCl', 'Glucophage', 'Tablet', '500mg', 8.50, 3.80, 500, 60, 'BATCH-GLU-552', '2028-02-14', 'Merck KGaA', 'active'),
(10, 7, 'Normal Saline IV 0.9% 500ml', 'Sodium Chloride 0.9%', 'Baxter NS Bag', 'IV Fluid', '500ml', 10.00, 4.50, 150, 20, 'BATCH-NS-901', '2028-06-30', 'Baxter Healthcare', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 10. Diagnostic Lab Test Categories
INSERT INTO `lab_test_categories` (`id`, `name`, `description`) VALUES
(1, 'Hematology & Complete Blood Count', 'Blood cell counts, hemoglobin, platelets, differential and ESR.'),
(2, 'Clinical Biochemistry & Metabolism', 'Liver function, kidney profile, lipid panel, and blood glucose.'),
(3, 'Cardiology Diagnostics', 'Cardiac enzymes, Troponin I/T, ECG, and echocardiography.'),
(4, 'Microbiology & Serology', 'Infectious disease serology, bacterial cultures, and viral panels.'),
(5, 'Diagnostic Radiology & Imaging', 'Digital X-Rays, Ultrasound sonograms, CT scans, and MRI.'),
(6, 'Urinalysis & Body Fluids', 'Routine urine microscopy, specific gravity, protein, and glucose.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 11. Diagnostic Lab Tests Catalog
INSERT INTO `lab_tests` (`id`, `category_id`, `test_name`, `test_code`, `sample_type`, `price`, `normal_range`, `unit`, `description`, `status`) VALUES
(1, 1, 'Complete Blood Count (CBC with 5-Part Diff)', 'CBC-01', 'Whole Blood (EDTA)', 35.00, 'WBC: 4.5-11.0, RBC: 4.2-5.9, Hb: 13.5-17.5', '10^3/uL', 'Evaluates overall health and detects anemia, infection, and leukemia.', 'active'),
(2, 2, 'Comprehensive Metabolic Panel (CMP)', 'CMP-02', 'Serum (SST)', 55.00, 'Glucose: 70-99, BUN: 7-20, Creatinine: 0.6-1.2', 'mg/dL', 'Evaluates kidney function, liver status, electrolyte and fluid balance.', 'active'),
(3, 2, 'Lipid Profile (Cholesterol, HDL, LDL, Triglycerides)', 'LIPID-03', 'Serum (Fasting)', 45.00, 'Total Chol: <200, HDL: >40, LDL: <100, Trig: <150', 'mg/dL', 'Measures circulating cardiovascular risk fats and lipids.', 'active'),
(4, 2, 'Fasting Blood Glucose (FBS)', 'FBS-04', 'Fluoride Plasma', 15.00, '70 - 99', 'mg/dL', 'Primary diagnostic test for prediabetes and diabetes mellitus.', 'active'),
(5, 2, 'HbA1c (Glycated Hemoglobin)', 'HBA1C-05', 'Whole Blood (EDTA)', 38.00, '< 5.7% (Normal), 5.7-6.4% (Prediabetes), >=6.5% (Diabetes)', '%', 'Reflects average blood sugar levels over the past 3 months.', 'active'),
(6, 3, 'High-Sensitivity Troponin I (hs-cTnI)', 'TROP-06', 'Plasma / Serum', 60.00, '< 0.04 (Negative for myocardial necrosis)', 'ng/mL', 'Gold-standard biomarker for rapid myocardial infarction diagnosis.', 'active'),
(7, 3, '12-Lead Diagnostic Electrocardiogram (ECG)', 'ECG-07', 'Non-Invasive Tracing', 40.00, 'Normal Sinus Rhythm, Normal Axis & Intervals', 'N/A', 'Records electrical cardiac activity to detect arrhythmias and ischemia.', 'active'),
(8, 5, 'Digital Chest X-Ray (PA View)', 'CXR-08', 'Radiological Exposure', 50.00, 'Clear lung fields, normal cardiothoracic ratio (<50%)', 'N/A', 'Evaluates lungs, heart contour, ribs, and mediastinum.', 'active'),
(9, 6, 'Routine Urinalysis (Physical, Chemical, Microscopic)', 'UA-09', 'Clean-catch Urine', 20.00, 'Color: Pale Yellow, Protein: Neg, Glucose: Neg, WBC: 0-5', '/hpf', 'Detects urinary tract infections, renal disease, and metabolic disorders.', 'active')
ON DUPLICATE KEY UPDATE `test_name` = VALUES(`test_name`);

-- 12. Blood Bank Inventory Units
INSERT INTO `blood_inventory` (`blood_group`, `units_available`) VALUES
('A+', 18),
('A-', 6),
('B+', 14),
('B-', 4),
('O+', 25),
('O-', 8),
('AB+', 9),
('AB-', 3)
ON DUPLICATE KEY UPDATE `units_available` = VALUES(`units_available`);

-- 13. Blood Donors
INSERT INTO `blood_donors` (`id`, `full_name`, `gender`, `age`, `blood_group`, `phone`, `email`, `last_donation_date`, `health_status`) VALUES
(1, 'Michael Chang', 'Male', 32, 'O+', '+1 555-0711', 'michael.chang@example.com', '2026-05-10', 'Eligible'),
(2, 'Sarah Connor', 'Female', 28, 'A+', '+1 555-0722', 's.connor@example.com', '2026-06-18', 'Eligible'),
(3, 'David Beckham', 'Male', 40, 'O-', '+1 555-0733', 'david.b@example.com', '2026-07-02', 'Eligible')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- 14. Sample Appointments
INSERT INTO `appointments` (`id`, `token_number`, `patient_id`, `doctor_id`, `department_id`, `appointment_date`, `appointment_time`, `reason`, `status`) VALUES
(1, 1, 1, 1, 1, CURDATE(), '09:30:00', 'Routine cardiology review and blood pressure evaluation.', 'Waiting'),
(2, 2, 2, 2, 2, CURDATE(), '10:15:00', 'Pediatric seasonal allergies and recurring mild wheezing checkup.', 'Waiting'),
(3, 3, 3, 1, 1, CURDATE(), '11:00:00', 'Follow-up for chest discomfort and post-medication assessment.', 'In-Consultation'),
(4, 4, 4, 2, 2, CURDATE(), '11:45:00', 'Vaccination booster shot and developmental milestone growth check.', 'Scheduled'),
(5, 5, 5, 3, 3, CURDATE(), '14:00:00', 'Right knee pain upon climbing stairs and joint stiffness.', 'Scheduled')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- 15. Sample Patient Vitals
INSERT INTO `vitals` (`id`, `patient_id`, `recorded_by`, `temperature`, `blood_pressure`, `pulse_rate`, `respiratory_rate`, `spo2`, `blood_sugar`, `weight_kg`, `height_cm`, `notes`) VALUES
(1, 1, 5, 36.8, '130/85', 74, 16, 98, 102.0, 78.5, 178.0, 'Patient alert, comfortable. Blood pressure slightly elevated.'),
(2, 3, 5, 37.1, '142/90', 82, 18, 97, 138.0, 86.0, 172.0, 'Mild shortness of breath on exertion. Mild pedal edema noted.')
ON DUPLICATE KEY UPDATE `notes` = VALUES(`notes`);

-- 16. Sample Inpatient Admission
INSERT INTO `ipd_admissions` (`id`, `admission_number`, `patient_id`, `doctor_id`, `bed_id`, `admission_date`, `admission_reason`, `initial_diagnosis`, `doctor_orders`, `status`, `total_bed_days`, `total_bed_charges`) VALUES
(1, 'ADM-2026-001', 1, 1, 7, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Severe acute hypertension with palpitations requiring cardiac observation.', 'Stage 2 Hypertension with atypical chest pain', 'Continuous telemetry, Low sodium diet, Amlodipine 5mg OD, Daily vitals Q4H', 'Admitted', 2, 500.00),
(2, 'ADM-2026-002', 3, 1, 2, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Congestive exacerbation and blood glucose instability.', 'Decompensated Heart Failure (NYHA II) & T2D', 'Strict fluid balance chart, IV Furosemide, sliding scale insulin', 'Admitted', 1, 80.00)
ON DUPLICATE KEY UPDATE `admission_number` = VALUES(`admission_number`);

-- 17. Sample Prescriptions
INSERT INTO `prescriptions` (`id`, `prescription_number`, `patient_id`, `doctor_id`, `diagnosis`, `advice`, `follow_up_date`, `status`) VALUES
(1, 'RX-2026-001', 1, 1, 'Essential Hypertension (Stage 1)', 'Maintain low sodium DASH diet. Exercise 30 minutes daily. Avoid smoking & alcohol.', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'Active')
ON DUPLICATE KEY UPDATE `prescription_number` = VALUES(`prescription_number`);

INSERT INTO `prescription_items` (`id`, `prescription_id`, `medicine_id`, `medicine_name`, `dosage`, `frequency`, `duration`, `instruction`, `quantity`) VALUES
(1, 1, 5, 'Amlodipine Besylate 5mg', '1 Tablet', '1-0-0 (Morning)', '30 Days', 'After breakfast with water', 30),
(2, 1, 6, 'Atorvastatin Calcium 20mg', '1 Tablet', '0-0-1 (Night)', '30 Days', 'At bedtime after meals', 30)
ON DUPLICATE KEY UPDATE `medicine_name` = VALUES(`medicine_name`);

-- 18. Sample Lab Request & Results
INSERT INTO `lab_requests` (`id`, `request_number`, `patient_id`, `doctor_id`, `prescription_id`, `requested_date`, `status`, `priority`, `clinical_notes`, `total_amount`, `payment_status`) VALUES
(1, 'LAB-2026-001', 1, 1, 1, NOW(), 'Completed', 'Routine', 'Baseline lipid and metabolic panel for hypertension workup.', 100.00, 'Paid')
ON DUPLICATE KEY UPDATE `request_number` = VALUES(`request_number`);

INSERT INTO `lab_request_items` (`id`, `lab_request_id`, `test_id`, `result_value`, `reference_range`, `unit`, `flag`, `remarks`, `tested_by`, `tested_at`) VALUES
(1, 1, 2, 'Glucose: 94, Creatinine: 0.95', 'Glucose: 70-99, Creatinine: 0.6-1.2', 'mg/dL', 'Normal', 'Renal parameters and fasting glucose within optimal clinical limits.', 8, NOW()),
(2, 1, 3, 'Total: 215, HDL: 42, LDL: 138, Trig: 175', 'Total: <200, HDL: >40, LDL: <100, Trig: <150', 'mg/dL', 'High', 'Mild hyperlipidemia noted. Statin therapy and dietary lifestyle recommended.', 8, NOW())
ON DUPLICATE KEY UPDATE `result_value` = VALUES(`result_value`);

-- 19. Sample Invoices & Billing
INSERT INTO `invoices` (`id`, `invoice_number`, `patient_id`, `reference_type`, `reference_id`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `due_amount`, `payment_status`, `due_date`, `created_by`) VALUES
(1, 'INV-2026-001', 1, 'General', 1, 175.00, 10.00, 8.25, 173.25, 173.25, 0.00, 'Paid', CURDATE(), 9)
ON DUPLICATE KEY UPDATE `invoice_number` = VALUES(`invoice_number`);

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `category`, `unit_price`, `quantity`, `total`) VALUES
(1, 1, 'Cardiology Specialist Consultation (Dr. Alexander Hayes)', 'Consultation', 75.00, 1, 75.00),
(2, 1, 'Complete Metabolic Panel (CMP-02)', 'Laboratory', 55.00, 1, 55.00),
(3, 1, 'Lipid Profile Comprehensive (LIPID-03)', 'Laboratory', 45.00, 1, 45.00)
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

INSERT INTO `payments` (`id`, `receipt_number`, `invoice_id`, `patient_id`, `amount`, `payment_method`, `transaction_reference`, `notes`, `received_by`) VALUES
(1, 'REC-2026-001', 1, 1, 173.25, 'Credit Card', 'TXN-VISA-994821', 'Full payment settled at cashier counter.', 9)
ON DUPLICATE KEY UPDATE `receipt_number` = VALUES(`receipt_number`);

SET FOREIGN_KEY_CHECKS = 1;
