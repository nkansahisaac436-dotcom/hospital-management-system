# CarePoint Pro HMS - Enterprise Hospital Management System

**CarePoint Pro HMS** is a modern, commercial-ready, multi-role Hospital Management System (HMS) built natively with **PHP 8.x + MySQL + Tailwind CSS**.

It is completely self-contained, lightweight, zero-dependency, and built specifically for easy client handovers and 1-click deployments on **XAMPP, WAMP, LAMP, cPanel, or VPS** servers.

---

## Key Modules & Features

1. **Role-Based Access Control (RBAC)**:
   - **Super Admin**: Hospital administration, departments, staff, financial oversight, white-label branding, audit logs.
   - **Doctor / Physician**: OPD consultation queue, EMR/EHR, ICD-10 diagnoses, smart digital e-prescriptions, diagnostic orders.
   - **Nurse**: Inpatient bed monitor, vital signs recorder (BP, Pulse, SpO2, Temperature, Blood Sugar, Weight), inpatient telemetry.
   - **Receptionist / Front Desk**: Patient registration, unique MRN generation, OPD token queue, appointment scheduling.
   - **Pharmacist**: Medicine inventory, batch tracking, expiry monitoring, e-prescription fulfillment, POS retail cashier.
   - **Laboratory Scientist**: Diagnostic test catalog, sample collection tracking, result entry, reference ranges, certified lab reports.
   - **Accountant / Cashier**: Consolidated billing (OPD, IPD, Bed stay, Pharmacy, Lab, Surgery), payments recording, thermal receipts, A4 tax invoices.
   - **Patient Portal**: Medical history, appointments, digital prescriptions, lab reports, billing statements.

2. **Core Clinical Workflows**:
   - **1-Click Demo Role Switcher**: Instant role switching banner on every screen for effortless client demonstrations.
   - **Master EMR / EHR System**: Unified medical timeline with vital signs, past consultations, prescriptions, lab results, admissions, and billing.
   - **Live Bed & Ward Matrix**: Visual interactive grid showing Available, Occupied, and Maintenance beds in real-time.
   - **Printable Medical Reports**: Print-ready formats for A4 Prescriptions (with doctor seal), Diagnostic Reports, A4 Tax Invoices, and 80mm Thermal Receipts.
   - **Blood Bank Management**: Whole-blood inventory (A+, A-, B+, B-, O+, O-, AB+, AB-), donor registry, and issue logs.
   - **Hospital Analytics**: Interactive Chart.js charts for patient influx, bed occupancy, doctor workload, and revenue breakdown.
   - **White-label Customizer**: Customize hospital name, slogan, contact info, currency symbol, tax rate, and disclaimers.

---

## Instant Installation & Setup

### Option 1: 1-Click Web Installer (Recommended)
1. Ensure your local server (XAMPP / WAMP / Apache / MySQL) is running.
2. Open your web browser and navigate to:
   ```
   http://localhost/hospital%20management%20system/install.php
   ```
3. Enter your MySQL database credentials (default on XAMPP is Host: `127.0.0.1`, User: `root`, Password: ``).
4. Click **"Start 1-Click Installation"**. The system will automatically create the database `carepoint_hms`, execute all schema tables, seed realistic demo records, and save your configuration!

### Option 2: Manual Database Import
1. Create a MySQL database named `carepoint_hms`.
2. Import `database/schema.sql` followed by `database/seed_data.sql`.
3. Check `config/config.php` to ensure your database credentials match.

---

## Demo User Accounts

All demo accounts use the standard password: **`password123`**

| Role | Username | Password | Purpose |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin` | `password123` | Full administrative control & hospital settings |
| **Doctor (Cardiology)** | `doctor` | `password123` | Clinical OPD consultations & e-prescriptions |
| **Doctor (Pediatrics)** | `doctor2` | `password123` | Child health consultations & orders |
| **Doctor (Orthopedics)** | `doctor3` | `password123` | Musculoskeletal surgery & admissions |
| **Head Nurse** | `nurse` | `password123` | Inpatient bed monitoring & vital signs logging |
| **Front Desk / Reception** | `reception` | `password123` | Patient registration & appointment queue |
| **Chief Pharmacist** | `pharmacist` | `password123` | Medicine inventory & POS dispensing counter |
| **Pathologist / Lab** | `labtech` | `password123` | Lab test catalog & diagnostic result entry |
| **Cashier / Billing** | `accountant` | `password123` | Patient invoices, settlements & receipts |
| **Patient Portal** | `patient` | `password123` | Personal health profile & lab results |

---

## Client Handover & Deployment Guide

To hand this system over to a client:
1. **Compress / Zip** the entire `hospital management system` folder.
2. Upload and extract to the client's cPanel `public_html` (or subfolder) or VPS directory.
3. Visit `http://client-domain.com/install.php` to initialize the database in 5 seconds.
4. Go to **System Settings** (`/modules/settings/index.php`) as Admin to set the client's Hospital Name, Address, Logo, Currency, and Tax rate.

---

## Security & Architectural Standards
- **PDO Prepared Statements**: 100% SQL injection immunity across all queries.
- **CSRF Token Protection**: Secure tokens validated on all state-changing POST actions.
- **XSS Sanitization**: Automated output escaping helper `e()`.
- **Bcrypt Password Hashing**: Modern, secure password encryption.
- **Session Guards**: Role-level authorization middleware (`requireAuth()`).
