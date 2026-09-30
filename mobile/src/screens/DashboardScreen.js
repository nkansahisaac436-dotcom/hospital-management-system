import React from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../theme';

export default function DashboardScreen({ roleId, data, onNavigate, onOpenRoleSwitcher }) {
  const { stats, patients, beds, medicines, labRequests, invoices } = data;

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      
      {/* 1. FRONT DESK / RECEPTION VIEW */}
      {roleId === 'reception' && (
        <View style={styles.section}>
          <View style={[styles.banner, { backgroundColor: '#0f766e' }]}>
            <View style={styles.badgeRow}>
              <View style={styles.bannerBadge}>
                <Text style={styles.bannerBadgeText}>FRONT DESK & INTAKE</Text>
              </View>
            </View>
            <Text style={styles.bannerTitle}>Patient Triage & Queue</Text>
            <Text style={styles.bannerSub}>Register new admissions, issue tokens, and manage lobby flow.</Text>
            
            <View style={styles.actionRow}>
              <TouchableOpacity style={styles.primaryBtn} onPress={() => onNavigate('patients')}>
                <Ionicons name="person-add" size={16} color="#0f766e" style={{ marginRight: 6 }} />
                <Text style={styles.primaryBtnText}>Register Patient</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.secondaryBtn} onPress={() => onNavigate('patients')}>
                <Ionicons name="calendar" size={16} color="#ffffff" style={{ marginRight: 6 }} />
                <Text style={styles.secondaryBtnText}>Book Token</Text>
              </TouchableOpacity>
            </View>
          </View>

          {/* Metrics */}
          <View style={styles.grid2}>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Patients Registered</Text>
              <Text style={[styles.cardVal, { color: COLORS.primary }]}>{stats.patientsToday}</Text>
              <Text style={styles.cardSub}>Today's New Intakes</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Scheduled Visits</Text>
              <Text style={[styles.cardVal, { color: COLORS.secondary }]}>{stats.appointmentsToday}</Text>
              <Text style={styles.cardSub}>Appointments Today</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Waiting in Lobby</Text>
              <Text style={[styles.cardVal, { color: COLORS.warning }]}>{stats.waitingQueue}</Text>
              <Text style={styles.cardSub}>Ready for Doctor</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Total Active EMR</Text>
              <Text style={[styles.cardVal, { color: COLORS.text }]}>{stats.totalPatients}</Text>
              <Text style={styles.cardSub}>Master Database</Text>
            </View>
          </View>
        </View>
      )}

      {/* 2. DOCTOR / CLINICAL VIEW */}
      {roleId === 'doctor' && (
        <View style={styles.section}>
          <View style={[styles.banner, { backgroundColor: '#0369a1' }]}>
            <View style={styles.badgeRow}>
              <View style={styles.bannerBadge}>
                <Text style={styles.bannerBadgeText}>DOCTOR CONSULTATION</Text>
              </View>
            </View>
            <Text style={styles.bannerTitle}>Clinical Workbench</Text>
            <Text style={styles.bannerSub}>OPD consultations, ICD-10 diagnoses, and digital prescriptions.</Text>
            
            <View style={styles.actionRow}>
              <TouchableOpacity style={styles.primaryBtn} onPress={() => onNavigate('patients')}>
                <Ionicons name="stethoscope" size={16} color="#0369a1" style={{ marginRight: 6 }} />
                <Text style={[styles.primaryBtnText, { color: '#0369a1' }]}>Start Next Consult</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.secondaryBtn} onPress={() => onNavigate('clinical')}>
                <Ionicons name="document-text" size={16} color="#ffffff" style={{ marginRight: 6 }} />
                <Text style={styles.secondaryBtnText}>Write Rx</Text>
              </TouchableOpacity>
            </View>
          </View>

          <View style={styles.grid2}>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>My Waiting Queue</Text>
              <Text style={[styles.cardVal, { color: COLORS.warning }]}>{stats.waitingQueue}</Text>
              <Text style={styles.cardSub}>Patients in Lobby</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Consultations Done</Text>
              <Text style={[styles.cardVal, { color: COLORS.success }]}>5</Text>
              <Text style={styles.cardSub}>Completed Today</Text>
            </View>
          </View>
        </View>
      )}

      {/* 3. NURSE VIEW */}
      {roleId === 'nurse' && (
        <View style={styles.section}>
          <View style={[styles.banner, { backgroundColor: '#6d28d9' }]}>
            <View style={styles.badgeRow}>
              <View style={styles.bannerBadge}>
                <Text style={styles.bannerBadgeText}>NURSING STATION</Text>
              </View>
            </View>
            <Text style={styles.bannerTitle}>Ward & Telemetry Station</Text>
            <Text style={styles.bannerSub}>Bed occupancy matrix, vital signs monitoring, and inpatient care.</Text>
            
            <View style={styles.actionRow}>
              <TouchableOpacity style={styles.primaryBtn} onPress={() => onNavigate('clinical')}>
                <Ionicons name="bed" size={16} color="#6d28d9" style={{ marginRight: 6 }} />
                <Text style={[styles.primaryBtnText, { color: '#6d28d9' }]}>View Bed Matrix</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.secondaryBtn} onPress={() => onNavigate('clinical')}>
                <Ionicons name="heart" size={16} color="#ffffff" style={{ marginRight: 6 }} />
                <Text style={styles.secondaryBtnText}>Chart Vitals</Text>
              </TouchableOpacity>
            </View>
          </View>

          <View style={styles.grid2}>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Occupied Beds</Text>
              <Text style={[styles.cardVal, { color: COLORS.danger }]}>{stats.occupiedBeds}</Text>
              <Text style={styles.cardSub}>Admitted Inpatients</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Available Beds</Text>
              <Text style={[styles.cardVal, { color: COLORS.success }]}>{stats.totalBeds - stats.occupiedBeds}</Text>
              <Text style={styles.cardSub}>Free for Admission</Text>
            </View>
          </View>
        </View>
      )}

      {/* 4. PHARMACIST VIEW */}
      {roleId === 'pharmacist' && (
        <View style={styles.section}>
          <View style={[styles.banner, { backgroundColor: '#0f766e' }]}>
            <View style={styles.badgeRow}>
              <View style={styles.bannerBadge}>
                <Text style={styles.bannerBadgeText}>PHARMACY DISPENSARY</Text>
              </View>
            </View>
            <Text style={styles.bannerTitle}>Drug Inventory & POS</Text>
            <Text style={styles.bannerSub}>Fulfill e-prescriptions, track low stock batches, and POS cashiering.</Text>
            
            <View style={styles.actionRow}>
              <TouchableOpacity style={styles.primaryBtn} onPress={() => onNavigate('web')}>
                <Ionicons name="cash" size={16} color="#0f766e" style={{ marginRight: 6 }} />
                <Text style={styles.primaryBtnText}>Launch POS Counter</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.secondaryBtn} onPress={() => onNavigate('clinical')}>
                <Ionicons name="warning" size={16} color="#ffffff" style={{ marginRight: 6 }} />
                <Text style={styles.secondaryBtnText}>Low Stock ({stats.lowStockDrugs})</Text>
              </TouchableOpacity>
            </View>
          </View>

          <View style={styles.grid2}>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Low Stock Alert</Text>
              <Text style={[styles.cardVal, { color: COLORS.danger }]}>{stats.lowStockDrugs}</Text>
              <Text style={styles.cardSub}>Reorders Required</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Today's POS Sales</Text>
              <Text style={[styles.cardVal, { color: COLORS.success }]}>${stats.todaySales.toFixed(2)}</Text>
              <Text style={styles.cardSub}>Dispensed Retail Cash</Text>
            </View>
          </View>
        </View>
      )}

      {/* 5. SUPER ADMIN VIEW */}
      {roleId === 'admin' && (
        <View style={styles.section}>
          <View style={[styles.banner, { backgroundColor: '#0f172a' }]}>
            <View style={styles.badgeRow}>
              <View style={styles.bannerBadge}>
                <Text style={styles.bannerBadgeText}>COMMAND CENTER</Text>
              </View>
            </View>
            <Text style={styles.bannerTitle}>Hospital Operations</Text>
            <Text style={styles.bannerSub}>Multi-department telemetry, staff governance, and hospital analytics.</Text>
            
            <View style={styles.actionRow}>
              <TouchableOpacity style={styles.primaryBtn} onPress={() => onNavigate('web')}>
                <Ionicons name="globe" size={16} color="#0f172a" style={{ marginRight: 6 }} />
                <Text style={[styles.primaryBtnText, { color: '#0f172a' }]}>Full Web Portal</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.secondaryBtn} onPress={onOpenRoleSwitcher}>
                <Ionicons name="swap-horizontal" size={16} color="#ffffff" style={{ marginRight: 6 }} />
                <Text style={styles.secondaryBtnText}>Switch Role</Text>
              </TouchableOpacity>
            </View>
          </View>

          <View style={styles.grid2}>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Total Patients</Text>
              <Text style={[styles.cardVal, { color: COLORS.primary }]}>{stats.totalPatients}</Text>
              <Text style={styles.cardSub}>Registered in HMS</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Gross Revenue</Text>
              <Text style={[styles.cardVal, { color: COLORS.success }]}>${stats.totalRevenue.toFixed(0)}</Text>
              <Text style={styles.cardSub}>Collected Billings</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Bed Occupancy</Text>
              <Text style={[styles.cardVal, { color: COLORS.accent }]}>{stats.occupiedBeds}/{stats.totalBeds}</Text>
              <Text style={styles.cardSub}>75% Total Capacity</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Pending Labs</Text>
              <Text style={[styles.cardVal, { color: COLORS.warning }]}>{stats.pendingLabs}</Text>
              <Text style={styles.cardSub}>Requisitions in Queue</Text>
            </View>
          </View>
        </View>
      )}

      {/* 6. PATIENT PORTAL VIEW */}
      {roleId === 'patient' && (
        <View style={styles.section}>
          <View style={[styles.banner, { backgroundColor: '#0284c7' }]}>
            <View style={styles.badgeRow}>
              <View style={styles.bannerBadge}>
                <Text style={styles.bannerBadgeText}>MY HEALTH WALLET</Text>
              </View>
            </View>
            <Text style={styles.bannerTitle}>Welcome, John Doe</Text>
            <Text style={styles.bannerSub}>MRN: MRN-2026-0001 &bull; Blood Group: O+</Text>
            
            <View style={styles.actionRow}>
              <TouchableOpacity style={styles.primaryBtn} onPress={() => onNavigate('patients')}>
                <Ionicons name="calendar-outline" size={16} color="#0284c7" style={{ marginRight: 6 }} />
                <Text style={[styles.primaryBtnText, { color: '#0284c7' }]}>Book Appointment</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.secondaryBtn} onPress={() => onNavigate('clinical')}>
                <Ionicons name="document-text-outline" size={16} color="#ffffff" style={{ marginRight: 6 }} />
                <Text style={styles.secondaryBtnText}>My Prescriptions</Text>
              </TouchableOpacity>
            </View>
          </View>

          <View style={styles.grid2}>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Active Prescriptions</Text>
              <Text style={[styles.cardVal, { color: COLORS.primary }]}>2</Text>
              <Text style={styles.cardSub}>Valid Refills</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Lab Reports</Text>
              <Text style={[styles.cardVal, { color: COLORS.accent }]}>1</Text>
              <Text style={styles.cardSub}>Ready to View</Text>
            </View>
          </View>
        </View>
      )}

      {/* Fast Live Queue Widget */}
      <View style={styles.feedCard}>
        <View style={styles.feedHeader}>
          <View style={{ flexDirection: 'row', alignItems: 'center' }}>
            <Ionicons name="time-outline" size={18} color={COLORS.primary} style={{ marginRight: 6 }} />
            <Text style={styles.feedTitle}>Live Patient Queue & Triage</Text>
          </View>
          <TouchableOpacity onPress={() => onNavigate('patients')}>
            <Text style={styles.feedLink}>View All &rarr;</Text>
          </TouchableOpacity>
        </View>

        {patients.slice(0, 3).map((p) => (
          <View key={p.id} style={styles.patientRow}>
            <View style={styles.tokenBox}>
              <Text style={styles.tokenText}>{p.token}</Text>
            </View>
            <View style={styles.patientInfo}>
              <Text style={styles.patientName}>{p.name}</Text>
              <Text style={styles.patientSub}>{p.mrn} &bull; {p.condition}</Text>
            </View>
            <View style={[
              styles.statusBadge,
              p.status === 'Completed' && { backgroundColor: COLORS.successLight },
              p.status === 'In-Consultation' && { backgroundColor: COLORS.secondaryLight },
              p.status === 'Waiting' && { backgroundColor: COLORS.warningLight },
            ]}>
              <Text style={[
                styles.statusBadgeText,
                p.status === 'Completed' && { color: COLORS.success },
                p.status === 'In-Consultation' && { color: COLORS.secondary },
                p.status === 'Waiting' && { color: COLORS.warning },
              ]}>{p.status}</Text>
            </View>
          </View>
        ))}
      </View>

      <View style={{ height: 30 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 16,
    backgroundColor: COLORS.background,
  },
  section: {
    marginBottom: 16,
  },
  banner: {
    borderRadius: 24,
    padding: 20,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 10,
    elevation: 4,
  },
  badgeRow: {
    flexDirection: 'row',
    marginBottom: 8,
  },
  bannerBadge: {
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 12,
  },
  bannerBadgeText: {
    fontSize: 9,
    fontWeight: '800',
    color: '#ffffff',
    letterSpacing: 0.5,
  },
  bannerTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: '#ffffff',
    marginBottom: 4,
  },
  bannerSub: {
    fontSize: 12,
    color: 'rgba(255, 255, 255, 0.85)',
    lineHeight: 16,
    marginBottom: 16,
  },
  actionRow: {
    flexDirection: 'row',
    gap: 8,
  },
  primaryBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 14,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
  },
  primaryBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.primary,
  },
  secondaryBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.15)',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.3)',
  },
  secondaryBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#ffffff',
  },
  grid2: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  card: {
    flex: 1,
    minWidth: '45%',
    backgroundColor: COLORS.card,
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  cardLabel: {
    fontSize: 11,
    fontWeight: '700',
    color: COLORS.textMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: 6,
  },
  cardVal: {
    fontSize: 22,
    fontWeight: '900',
    marginBottom: 2,
  },
  cardSub: {
    fontSize: 10,
    color: COLORS.textMuted,
    fontWeight: '600',
  },
  feedCard: {
    backgroundColor: COLORS.card,
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 16,
  },
  feedHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 14,
    paddingBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  feedTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.text,
  },
  feedLink: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.primary,
  },
  patientRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.background,
  },
  tokenBox: {
    width: 38,
    height: 38,
    borderRadius: 10,
    backgroundColor: COLORS.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
    borderWidth: 1,
    borderColor: COLORS.primaryLight,
  },
  tokenText: {
    fontSize: 10,
    fontWeight: '900',
    color: COLORS.primaryDark,
  },
  patientInfo: {
    flex: 1,
  },
  patientName: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 2,
  },
  patientSub: {
    fontSize: 11,
    color: COLORS.textMuted,
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  statusBadgeText: {
    fontSize: 10,
    fontWeight: '800',
  },
});
