import React from 'react';
import { Modal, View, Text, StyleSheet, TouchableOpacity, ScrollView } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS, ROLES } from '../theme';

export default function RoleSwitcherModal({ visible, onClose, activeRoleId, onSelectRole }) {
  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <TouchableOpacity style={styles.overlay} activeOpacity={1} onPress={onClose}>
        <View style={styles.modalContent} onStartShouldSetResponder={() => true}>
          
          <View style={styles.modalHeader}>
            <View style={{ flexDirection: 'row', alignItems: 'center' }}>
              <Ionicons name="sparkles" size={18} color={COLORS.primary} style={{ marginRight: 6 }} />
              <Text style={styles.modalTitle}>Select Workspace Role</Text>
            </View>
            <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
              <Ionicons name="close" size={20} color={COLORS.textMuted} />
            </TouchableOpacity>
          </View>
          <Text style={styles.modalSub}>
            Switch instantaneously between isolated clinical, administrative, and patient workspaces:
          </Text>

          <ScrollView style={styles.rolesList} showsVerticalScrollIndicator={false}>
            {ROLES.map((r) => {
              const isSelected = r.id === activeRoleId;
              return (
                <TouchableOpacity
                  key={r.id}
                  style={[
                    styles.roleItem,
                    isSelected && { backgroundColor: `${r.color}15`, borderColor: r.color },
                  ]}
                  onPress={() => {
                    onSelectRole(r.id);
                    onClose();
                  }}
                  activeOpacity={0.7}
                >
                  <View style={[styles.iconBox, { backgroundColor: `${r.color}20` }]}>
                    <Ionicons name={r.icon} size={20} color={r.color} />
                  </View>
                  <View style={styles.roleInfo}>
                    <Text style={[styles.roleName, isSelected && { color: r.color, fontWeight: '800' }]}>
                      {r.name}
                    </Text>
                    <Text style={styles.roleDesc}>
                      {r.id === 'reception' && 'Patient registration & lobby triage'}
                      {r.id === 'doctor' && 'OPD consultations, EMR & e-prescriptions'}
                      {r.id === 'nurse' && 'Bed matrix & vital signs telemetry'}
                      {r.id === 'pharmacist' && 'POS dispensing & inventory control'}
                      {r.id === 'labtech' && 'Diagnostic investigations & pathology'}
                      {r.id === 'accountant' && 'Invoicing, receipts & cashier collections'}
                      {r.id === 'admin' && 'Hospital command center & full oversight'}
                      {r.id === 'patient' && 'Self-service portal & health history'}
                    </Text>
                  </View>
                  {isSelected && (
                    <Ionicons name="checkmark-circle" size={22} color={r.color} />
                  )}
                </TouchableOpacity>
              );
            })}
          </ScrollView>

        </View>
      </TouchableOpacity>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalContent: {
    width: '100%',
    maxHeight: '80%',
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.25,
    shadowRadius: 20,
    elevation: 10,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  modalTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: COLORS.text,
  },
  closeBtn: {
    padding: 4,
  },
  modalSub: {
    fontSize: 12,
    color: COLORS.textMuted,
    marginBottom: 16,
    lineHeight: 16,
  },
  rolesList: {
    marginBottom: 8,
  },
  roleItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: COLORS.border,
    marginBottom: 10,
    backgroundColor: COLORS.background,
  },
  iconBox: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  roleInfo: {
    flex: 1,
  },
  roleName: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 2,
  },
  roleDesc: {
    fontSize: 11,
    color: COLORS.textMuted,
  },
});
