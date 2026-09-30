import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../theme';

export default function ClinicalScreen({ beds, medicines, labRequests }) {
  const [activeTab, setActiveTab] = useState('beds'); // 'beds' | 'pharmacy' | 'labs'

  return (
    <View style={styles.container}>
      
      {/* Top Section Tabs */}
      <View style={styles.tabsRow}>
        <TouchableOpacity
          style={[styles.tabBtn, activeTab === 'beds' && styles.tabBtnActive]}
          onPress={() => setActiveTab('beds')}
        >
          <Ionicons name="bed" size={15} color={activeTab === 'beds' ? '#ffffff' : COLORS.textMuted} style={{ marginRight: 6 }} />
          <Text style={[styles.tabText, activeTab === 'beds' && styles.tabTextActive]}>Ward Bed Matrix</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.tabBtn, activeTab === 'pharmacy' && styles.tabBtnActive]}
          onPress={() => setActiveTab('pharmacy')}
        >
          <Ionicons name="flask" size={15} color={activeTab === 'pharmacy' ? '#ffffff' : COLORS.textMuted} style={{ marginRight: 6 }} />
          <Text style={[styles.tabText, activeTab === 'pharmacy' && styles.tabTextActive]}>Pharmacy Stock</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.tabBtn, activeTab === 'labs' && styles.tabBtnActive]}
          onPress={() => setActiveTab('labs')}
        >
          <Ionicons name="pulse" size={15} color={activeTab === 'labs' ? '#ffffff' : COLORS.textMuted} style={{ marginRight: 6 }} />
          <Text style={[styles.tabText, activeTab === 'labs' && styles.tabTextActive]}>Lab Diagnostic</Text>
        </TouchableOpacity>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 40 }}>
        
        {/* 1. BED MATRIX TAB */}
        {activeTab === 'beds' && (
          <View>
            <View style={styles.legendRow}>
              <View style={styles.legendItem}>
                <View style={[styles.legendDot, { backgroundColor: COLORS.danger }]} />
                <Text style={styles.legendLabel}>Occupied</Text>
              </View>
              <View style={styles.legendItem}>
                <View style={[styles.legendDot, { backgroundColor: COLORS.success }]} />
                <Text style={styles.legendLabel}>Available</Text>
              </View>
            </View>

            <View style={styles.bedGrid}>
              {beds.map((b) => {
                const isOccupied = b.status === 'Occupied';
                return (
                  <TouchableOpacity
                    key={b.id}
                    style={[
                      styles.bedCard,
                      isOccupied ? styles.bedCardOccupied : styles.bedCardAvailable,
                    ]}
                    onPress={() => {
                      if (isOccupied) {
                        Alert.alert(
                          `Bed ${b.number} - Occupied`,
                          `Patient: ${b.patient}\nWard: ${b.ward}\nTelemetry: ${b.vitals || 'Stable'}`
                        );
                      } else {
                        Alert.alert(`Bed ${b.number} - Free`, `Ready for admission in ${b.ward}.`);
                      }
                    }}
                  >
                    <View style={styles.bedHeader}>
                      <Text style={[styles.bedNum, isOccupied ? { color: COLORS.danger } : { color: COLORS.success }]}>
                        {b.number}
                      </Text>
                      <Ionicons
                        name={isOccupied ? 'person' : 'checkmark-circle'}
                        size={16}
                        color={isOccupied ? COLORS.danger : COLORS.success}
                      />
                    </View>
                    <Text style={styles.bedWard} numberOfLines={1}>{b.ward}</Text>
                    {isOccupied ? (
                      <View style={styles.patientPill}>
                        <Text style={styles.patientPillText} numberOfLines={1}>{b.patient}</Text>
                      </View>
                    ) : (
                      <View style={[styles.patientPill, { backgroundColor: COLORS.successLight }]}>
                        <Text style={[styles.patientPillText, { color: COLORS.success }]}>Available</Text>
                      </View>
                    )}
                  </TouchableOpacity>
                );
              })}
            </View>
          </View>
        )}

        {/* 2. PHARMACY TAB */}
        {activeTab === 'pharmacy' && (
          <View>
            {medicines.map((m) => {
              const isLow = m.stock <= m.minAlert;
              return (
                <View key={m.id} style={styles.medCard}>
                  <View style={styles.medHeader}>
                    <View style={styles.medIconBox}>
                      <Ionicons name="medical" size={18} color={COLORS.primary} />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.medName}>{m.name}</Text>
                      <Text style={styles.medGen}>{m.generic} &bull; {m.category}</Text>
                    </View>
                    <Text style={styles.medPrice}>${m.price.toFixed(2)}</Text>
                  </View>

                  <View style={styles.medFooter}>
                    <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                      <Text style={styles.stockLabel}>Stock Level: </Text>
                      <Text style={[styles.stockVal, isLow && { color: COLORS.danger, fontWeight: '800' }]}>
                        {m.stock} units {isLow && '(LOW STOCK)'}
                      </Text>
                    </View>
                    <TouchableOpacity
                      style={styles.dispenseBtn}
                      onPress={() => Alert.alert('Dispense Medication', `Ready to dispense ${m.name} ($${m.price.toFixed(2)}) via POS`)}
                    >
                      <Text style={styles.dispenseBtnText}>Dispense</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              );
            })}
          </View>
        )}

        {/* 3. LABS TAB */}
        {activeTab === 'labs' && (
          <View>
            {labRequests.map((l) => (
              <View key={l.id} style={styles.labCard}>
                <View style={styles.labHeader}>
                  <View>
                    <Text style={styles.labReq}>{l.reqNo}</Text>
                    <Text style={styles.labPatient}>{l.patient}</Text>
                  </View>
                  <View style={[
                    styles.prioBadge,
                    l.priority === 'Urgent' ? { backgroundColor: COLORS.dangerLight } : { backgroundColor: COLORS.primaryLight },
                  ]}>
                    <Text style={[
                      styles.prioText,
                      l.priority === 'Urgent' ? { color: COLORS.danger } : { color: COLORS.primaryDark },
                    ]}>{l.priority}</Text>
                  </View>
                </View>
                <Text style={styles.labTest}>{l.test}</Text>
                <View style={styles.labFooter}>
                  <Text style={styles.labStatus}>Status: <Text style={{ fontWeight: '700', color: COLORS.text }}>{l.status}</Text></Text>
                  <TouchableOpacity
                    style={styles.labAction}
                    onPress={() => Alert.alert('Lab Diagnostics', `Requisition: ${l.reqNo}\nTest: ${l.test}\nPatient: ${l.patient}\nStatus: ${l.status}`)}
                  >
                    <Text style={styles.labActionText}>View Findings</Text>
                  </TouchableOpacity>
                </View>
              </View>
            ))}
          </View>
        )}

      </ScrollView>

    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
    padding: 16,
  },
  tabsRow: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 16,
  },
  tabBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    backgroundColor: COLORS.card,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  tabBtnActive: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  tabText: {
    fontSize: 11,
    fontWeight: '700',
    color: COLORS.textMuted,
  },
  tabTextActive: {
    color: '#ffffff',
  },
  legendRow: {
    flexDirection: 'row',
    gap: 16,
    marginBottom: 12,
    justifyContent: 'flex-end',
  },
  legendItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  legendDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginRight: 6,
  },
  legendLabel: {
    fontSize: 11,
    color: COLORS.textMuted,
    fontWeight: '600',
  },
  bedGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  bedCard: {
    width: '47.5%',
    backgroundColor: COLORS.card,
    borderRadius: 18,
    padding: 14,
    borderWidth: 1.5,
  },
  bedCardOccupied: {
    borderColor: '#fca5a5',
    backgroundColor: '#fff5f5',
  },
  bedCardAvailable: {
    borderColor: '#86efac',
    backgroundColor: '#f0fdf4',
  },
  bedHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 4,
  },
  bedNum: {
    fontSize: 14,
    fontWeight: '900',
  },
  bedWard: {
    fontSize: 10,
    color: COLORS.textMuted,
    marginBottom: 10,
  },
  patientPill: {
    backgroundColor: '#fee2e2',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  patientPillText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#991b1b',
  },
  medCard: {
    backgroundColor: COLORS.card,
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 12,
  },
  medHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  medIconBox: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: COLORS.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
  },
  medName: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.text,
  },
  medGen: {
    fontSize: 11,
    color: COLORS.textMuted,
  },
  medPrice: {
    fontSize: 14,
    fontWeight: '900',
    color: COLORS.primary,
  },
  medFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: COLORS.background,
    paddingTop: 10,
  },
  stockLabel: {
    fontSize: 11,
    color: COLORS.textMuted,
  },
  stockVal: {
    fontSize: 11,
    color: COLORS.text,
    fontWeight: '600',
  },
  dispenseBtn: {
    backgroundColor: COLORS.primary,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
  },
  dispenseBtnText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#ffffff',
  },
  labCard: {
    backgroundColor: COLORS.card,
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 12,
  },
  labHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  labReq: {
    fontSize: 11,
    fontWeight: '900',
    color: COLORS.primary,
    fontFamily: 'monospace',
  },
  labPatient: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.text,
  },
  prioBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  prioText: {
    fontSize: 10,
    fontWeight: '800',
  },
  labTest: {
    fontSize: 12,
    color: COLORS.textMuted,
    marginBottom: 12,
  },
  labFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: COLORS.background,
    paddingTop: 10,
  },
  labStatus: {
    fontSize: 11,
    color: COLORS.textMuted,
  },
  labAction: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 8,
    backgroundColor: COLORS.primarySoft,
  },
  labActionText: {
    fontSize: 11,
    fontWeight: '700',
    color: COLORS.primaryDark,
  },
});
