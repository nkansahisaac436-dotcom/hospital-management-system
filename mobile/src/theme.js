export const COLORS = {
  primary: '#0d9488', // Teal-600
  primaryDark: '#0f766e',
  primaryLight: '#ccfbf1',
  primarySoft: '#f0fdfa',
  secondary: '#0284c7', // Sky-600
  secondaryDark: '#0369a1',
  secondaryLight: '#e0f2fe',
  accent: '#7c3aed', // Purple-600
  accentLight: '#f3e8ff',
  success: '#10b981', // Emerald-500
  successLight: '#d1fae5',
  warning: '#f59e0b', // Amber-500
  warningLight: '#fef3c7',
  danger: '#ef4444', // Rose-500
  dangerLight: '#fee2e2',
  background: '#f8fafc', // Slate-50
  card: '#ffffff',
  text: '#0f172a', // Slate-900
  textMuted: '#64748b', // Slate-500
  textLight: '#94a3b8',
  border: '#e2e8f0', // Slate-200
  borderDark: '#cbd5e1',
};

export const ROLES = [
  { id: 'admin', name: 'Super Admin', icon: 'shield-outline', color: '#ef4444', badge: 'ADMIN' },
  { id: 'doctor', name: 'Doctor / Physician', icon: 'medical-outline', color: '#0284c7', badge: 'DOC' },
  { id: 'nurse', name: 'Nursing Station', icon: 'heart-outline', color: '#7c3aed', badge: 'NURSE' },
  { id: 'reception', name: 'Front Desk / Triage', icon: 'people-outline', color: '#f59e0b', badge: 'DESK' },
  { id: 'pharmacist', name: 'Pharmacy Dispensary', icon: 'flask-outline', color: '#0d9488', badge: 'PHARM' },
  { id: 'labtech', name: 'Pathology & LIS', icon: 'pulse-outline', color: '#6366f1', badge: 'LAB' },
  { id: 'accountant', name: 'Billing & Cashier', icon: 'cash-outline', color: '#10b981', badge: 'BILL' },
  { id: 'patient', name: 'Patient Portal', icon: 'person-outline', color: '#0ea5e9', badge: 'PATIENT' },
];
