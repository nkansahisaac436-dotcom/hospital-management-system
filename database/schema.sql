-- CarePoint Pro Hospital Management System
-- Relational Database Schema

SET FOREIGN_KEY_CHECKS = 0;

-- 1. System Settings
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT NULL,
  `group` VARCHAR(50) DEFAULT 'general',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users & RBAC
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `phone` VARCHAR(30) NULL,
  `role` ENUM('admin', 'doctor', 'nurse', 'receptionist', 'pharmacist', 'lab_tech', 'accountant', 'patient') NOT NULL DEFAULT 'patient',
  `avatar` VARCHAR(255) NULL,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Medical Departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `icon` VARCHAR(50) DEFAULT 'fa-stethoscope',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Doctors Profile
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `department_id` INT NOT NULL,
  `specialization` VARCHAR(150) NOT NULL,
  `consultation_fee` DECIMAL(10,2) NOT NULL DEFAULT 50.00,
  `room_no` VARCHAR(30) NULL,
  `experience_years` INT DEFAULT 5,
  `bio` TEXT NULL,
  `schedule_days` VARCHAR(100) DEFAULT 'Mon,Tue,Wed,Thu,Fri',
  `schedule_time_start` TIME DEFAULT '09:00:00',
  `schedule_time_end` TIME DEFAULT '17:00:00',
  `status` ENUM('active', 'on_leave', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Patients Profile & EMR Master
CREATE TABLE IF NOT EXISTS `patients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL UNIQUE,
  `mrn` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(120) NULL,
  `phone` VARCHAR(30) NOT NULL,
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
  `date_of_birth` DATE NOT NULL,
  `blood_group` ENUM('A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-', 'Unknown') DEFAULT 'Unknown',
  `address` TEXT NULL,
  `emergency_contact_name` VARCHAR(150) NULL,
  `emergency_contact_phone` VARCHAR(30) NULL,
  `emergency_contact_relation` VARCHAR(60) NULL,
  `allergies` TEXT NULL,
  `chronic_diseases` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Appointments & OPD Queue
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token_number` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `department_id` INT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `reason` TEXT NULL,
  `status` ENUM('Scheduled', 'Waiting', 'In-Consultation', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Patient Vital Signs Tracking
CREATE TABLE IF NOT EXISTS `vitals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `recorded_by` INT NULL,
  `temperature` DECIMAL(4,1) NULL COMMENT '°C',
  `blood_pressure` VARCHAR(20) NULL COMMENT 'mmHg (e.g. 120/80)',
  `pulse_rate` INT NULL COMMENT 'bpm',
  `respiratory_rate` INT NULL COMMENT 'breaths/min',
  `spo2` INT NULL COMMENT '% oxygen saturation',
  `blood_sugar` DECIMAL(5,1) NULL COMMENT 'mg/dL or mmol/L',
  `weight_kg` DECIMAL(5,2) NULL,
  `height_cm` DECIMAL(5,2) NULL,
  `notes` TEXT NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. OPD Consultations & Clinical EHR Record
CREATE TABLE IF NOT EXISTS `opd_consultations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `appointment_id` INT NULL UNIQUE,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `chief_complaints` TEXT NOT NULL,
  `clinical_examination` TEXT NULL,
  `diagnosis` TEXT NOT NULL,
  `icd10_code` VARCHAR(50) NULL,
  `doctor_notes` TEXT NULL,
  `follow_up_date` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Inpatient Hospital Wards
CREATE TABLE IF NOT EXISTS `wards` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('General', 'Semi-Private', 'Deluxe', 'ICU', 'CCU', 'Emergency', 'Pediatric', 'Maternity', 'Surgical') DEFAULT 'General',
  `floor` VARCHAR(30) DEFAULT '1st Floor',
  `total_beds` INT NOT NULL DEFAULT 10,
  `daily_rate` DECIMAL(10,2) NOT NULL DEFAULT 100.00,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Inpatient Bed Master
CREATE TABLE IF NOT EXISTS `beds` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ward_id` INT NOT NULL,
  `bed_number` VARCHAR(30) NOT NULL,
  `status` ENUM('Available', 'Occupied', 'Reserved', 'Maintenance') DEFAULT 'Available',
  `daily_rate` DECIMAL(10,2) NOT NULL DEFAULT 100.00,
  `current_patient_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ward_id`) REFERENCES `wards`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`current_patient_id`) REFERENCES `patients`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. IPD Admissions & Bed Occupancy
CREATE TABLE IF NOT EXISTS `ipd_admissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admission_number` VARCHAR(50) NOT NULL UNIQUE,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `bed_id` INT NOT NULL,
  `admission_date` DATETIME NOT NULL,
  `discharge_date` DATETIME NULL,
  `admission_reason` TEXT NOT NULL,
  `initial_diagnosis` TEXT NULL,
  `doctor_orders` TEXT NULL,
  `discharge_summary` TEXT NULL,
  `discharge_condition` VARCHAR(100) NULL,
  `status` ENUM('Admitted', 'Discharged', 'Transferred') DEFAULT 'Admitted',
  `total_bed_days` INT DEFAULT 0,
  `total_bed_charges` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`bed_id`) REFERENCES `beds`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Medicine Categories
CREATE TABLE IF NOT EXISTS `medicine_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Medicines & Pharmacy Catalog
CREATE TABLE IF NOT EXISTS `medicines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `generic_name` VARCHAR(150) NULL,
  `brand_name` VARCHAR(150) NULL,
  `dosage_form` ENUM('Tablet', 'Capsule', 'Syrup', 'Injection', 'Ointment', 'Drops', 'Inhaler', 'Suspension', 'IV Fluid') DEFAULT 'Tablet',
  `strength` VARCHAR(50) NULL COMMENT 'e.g. 500mg, 10ml, 250mg/5ml',
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 5.00,
  `cost_price` DECIMAL(10,2) NOT NULL DEFAULT 3.00,
  `stock_quantity` INT NOT NULL DEFAULT 100,
  `min_stock_alert` INT NOT NULL DEFAULT 20,
  `batch_number` VARCHAR(50) NULL,
  `expiry_date` DATE NOT NULL,
  `manufacturer` VARCHAR(150) NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `medicine_categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Prescriptions
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `prescription_number` VARCHAR(50) NOT NULL UNIQUE,
  `consultation_id` INT NULL,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `diagnosis` TEXT NULL,
  `advice` TEXT NULL,
  `follow_up_date` DATE NULL,
  `status` ENUM('Active', 'Dispensed', 'Cancelled') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`consultation_id`) REFERENCES `opd_consultations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Prescription Item Rows
CREATE TABLE IF NOT EXISTS `prescription_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `prescription_id` INT NOT NULL,
  `medicine_id` INT NULL,
  `medicine_name` VARCHAR(150) NOT NULL,
  `dosage` VARCHAR(50) NOT NULL COMMENT 'e.g. 1 tablet, 5ml',
  `frequency` VARCHAR(50) NOT NULL COMMENT 'e.g. 1-0-1, TDS, BD, STAT',
  `duration` VARCHAR(50) NOT NULL COMMENT 'e.g. 5 days, 1 month',
  `instruction` VARCHAR(100) NULL COMMENT 'e.g. After meals, Before bed',
  `quantity` INT NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Lab Test Categories
CREATE TABLE IF NOT EXISTS `lab_test_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. Diagnostic Lab Test Catalog
CREATE TABLE IF NOT EXISTS `lab_tests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `test_name` VARCHAR(150) NOT NULL,
  `test_code` VARCHAR(50) NOT NULL UNIQUE,
  `sample_type` VARCHAR(80) DEFAULT 'Blood',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 25.00,
  `normal_range` VARCHAR(255) NULL,
  `unit` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  FOREIGN KEY (`category_id`) REFERENCES `lab_test_categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. Lab Requests / Orders
CREATE TABLE IF NOT EXISTS `lab_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_number` VARCHAR(50) NOT NULL UNIQUE,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NULL,
  `prescription_id` INT NULL,
  `requested_date` DATETIME NOT NULL,
  `status` ENUM('Pending', 'Sample Collected', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
  `priority` ENUM('Routine', 'Urgent', 'STAT') DEFAULT 'Routine',
  `clinical_notes` TEXT NULL,
  `total_amount` DECIMAL(10,2) DEFAULT 0.00,
  `payment_status` ENUM('Unpaid', 'Paid') DEFAULT 'Unpaid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. Lab Request Test Items & Results
CREATE TABLE IF NOT EXISTS `lab_request_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lab_request_id` INT NOT NULL,
  `test_id` INT NOT NULL,
  `result_value` VARCHAR(255) NULL,
  `reference_range` VARCHAR(255) NULL,
  `unit` VARCHAR(50) NULL,
  `flag` ENUM('Normal', 'Low', 'High', 'Critical') DEFAULT 'Normal',
  `remarks` TEXT NULL,
  `tested_by` INT NULL,
  `tested_at` DATETIME NULL,
  FOREIGN KEY (`lab_request_id`) REFERENCES `lab_requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`test_id`) REFERENCES `lab_tests`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`tested_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20. Pharmacy Sales / POS
CREATE TABLE IF NOT EXISTS `pharmacy_sales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sale_number` VARCHAR(50) NOT NULL UNIQUE,
  `prescription_id` INT NULL,
  `patient_id` INT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(30) NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('Cash', 'Card', 'Bank Transfer', 'Insurance', 'Other') DEFAULT 'Cash',
  `status` ENUM('Paid', 'Pending', 'Cancelled') DEFAULT 'Paid',
  `sold_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`sold_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 21. Pharmacy Sale Items
CREATE TABLE IF NOT EXISTS `pharmacy_sale_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sale_id` INT NOT NULL,
  `medicine_id` INT NOT NULL,
  `medicine_name` VARCHAR(150) NOT NULL,
  `batch_number` VARCHAR(50) NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`sale_id`) REFERENCES `pharmacy_sales`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 22. Invoices & Billing
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `patient_id` INT NOT NULL,
  `reference_type` ENUM('OPD', 'IPD', 'Laboratory', 'Pharmacy', 'General') DEFAULT 'General',
  `reference_id` INT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('Unpaid', 'Partial', 'Paid') DEFAULT 'Unpaid',
  `due_date` DATE NULL,
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 23. Itemized Invoice Rows
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `category` ENUM('Consultation', 'Bed', 'Laboratory', 'Pharmacy', 'Procedure', 'Nursing', 'Other') DEFAULT 'Other',
  `unit_price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `total` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 24. Invoice Payments Record
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
  `invoice_id` INT NOT NULL,
  `patient_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('Cash', 'Credit Card', 'Debit Card', 'Bank Transfer', 'Insurance', 'Mobile Money') DEFAULT 'Cash',
  `transaction_reference` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `received_by` INT NULL,
  `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`received_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 25. Blood Bank Donors
CREATE TABLE IF NOT EXISTS `blood_donors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(150) NOT NULL,
  `gender` ENUM('Male', 'Female') NOT NULL,
  `age` INT NOT NULL,
  `blood_group` ENUM('A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-') NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(120) NULL,
  `address` TEXT NULL,
  `last_donation_date` DATE NULL,
  `health_status` VARCHAR(100) DEFAULT 'Eligible',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 26. Blood Bank Inventory
CREATE TABLE IF NOT EXISTS `blood_inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `blood_group` ENUM('A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-') NOT NULL UNIQUE,
  `units_available` INT NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 27. Blood Bank Issue Requests
CREATE TABLE IF NOT EXISTS `blood_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_number` VARCHAR(50) NOT NULL UNIQUE,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NULL,
  `blood_group` ENUM('A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-') NOT NULL,
  `units_requested` INT NOT NULL DEFAULT 1,
  `urgency` ENUM('Normal', 'Urgent', 'Emergency') DEFAULT 'Normal',
  `status` ENUM('Pending', 'Approved', 'Issued', 'Cancelled') DEFAULT 'Pending',
  `notes` TEXT NULL,
  `request_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 28. System Audit & Activity Logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(150) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(50) DEFAULT '127.0.0.1',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
