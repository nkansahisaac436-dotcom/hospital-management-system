import React, { useState } from 'react';
import { View, Text, StyleSheet, FlatList, TextInput, TouchableOpacity, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../theme';

export default function PatientsScreen({ patients, onSelectPatient }) {
  const [search, setSearch] = useState('');
  const [selectedFilter, setSelectedFilter] = useState('All');

  const filtered = patients.filter((p) => {
    const matchesSearch =
      p.name.toLowerCase().includes(search.toLowerCase()) ||
      p.mrn.toLowerCase().includes(search.toLowerCase()) ||
      p.phone.includes(search);
    if (selectedFilter === 'All') return matchesSearch;
    return matchesSearch && p.status === selectedFilter;
  });

  return (
    <View style={styles.container}>
      
      {/* Search Bar */}
      <View style={styles.searchBar}>
        <Ionicons name="search" size={18} color={COLORS.textMuted} style={{ marginRight: 8 }} />
        <TextInput
          placeholder="Search by Patient Name, MRN, or Phone..."
          placeholderTextColor={COLORS.textLight}
          style={styles.searchInput}
          value={search}
          onChangeText={setSearch}
        />
        {search.length > 0 && (
          <TouchableOpacity onPress={() => setSearch('')}>
            <Ionicons name="close-circle" size={18} color={COLORS.textMuted} />
          </TouchableOpacity>
        )}
      </View>

      {/* Filter Tabs */}
      <View style={styles.filterRow}>
        {['All', 'Waiting', 'In-Consultation', 'Completed'].map((f) => (
          <TouchableOpacity
            key={f}
            style={[styles.filterChip, selectedFilter === f && styles.filterChipActive]}
            onPress={() => setSelectedFilter(f)}
          >
            <Text style={[styles.filterText, selectedFilter === f && styles.filterTextActive]}>
              {f}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* Patients List */}
      <FlatList
        data={filtered}
        keyExtractor={(item) => item.id.toString()}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: 40 }}
        renderItem={({ item }) => (
          <View style={styles.patientCard}>
            <View style={styles.cardHeader}>
              <View style={styles.avatar}>
                <Text style={styles.avatarText}>{item.name[0]}</Text>
              </View>
              <View style={styles.headerInfo}>
                <Text style={styles.name}>{item.name}</Text>
                <Text style={styles.mrn}>{item.mrn} &bull; {item.gender}, {item.age} yrs</Text>
              </View>
              <View style={styles.tokenBadge}>
                <Text style={styles.tokenBadgeText}>{item.token}</Text>
              </View>
            </View>

            <View style={styles.detailGrid}>
              <View style={styles.detailItem}>
                <Ionicons name="call-outline" size={13} color={COLORS.textMuted} style={{ marginRight: 4 }} />
                <Text style={styles.detailVal}>{item.phone}</Text>
              </View>
              <View style={styles.detailItem}>
                <Ionicons name="water-outline" size={13} color="#ef4444" style={{ marginRight: 4 }} />
                <Text style={[styles.detailVal, { color: '#ef4444', fontWeight: '700' }]}>Blood: {item.bloodGroup}</Text>
              </View>
            </View>

            {item.allergies !== 'None' && (
              <View style={styles.allergyWarning}>
                <Ionicons name="warning" size={13} color="#b91c1c" style={{ marginRight: 4 }} />
                <Text style={styles.allergyText}>Allergies: {item.allergies}</Text>
              </View>
            )}

            <View style={styles.cardFooter}>
              <View style={styles.doctorInfo}>
                <Text style={styles.doctorLabel}>Doctor:</Text>
                <Text style={styles.doctorVal}>{item.doctor}</Text>
              </View>
              <TouchableOpacity
                style={styles.actionBtn}
                onPress={() => Alert.alert('Patient EMR Record', `Full clinical record for ${item.name} (${item.mrn})\n\nCondition: ${item.condition}\nAssigned: ${item.doctor}`)}
              >
                <Text style={styles.actionBtnText}>View EMR &rarr;</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}
      />

    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
    padding: 16,
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.card,
    borderRadius: 16,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 12,
  },
  searchInput: {
    flex: 1,
    fontSize: 13,
    color: COLORS.text,
  },
  filterRow: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 16,
  },
  filterChip: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  filterChipActive: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  filterText: {
    fontSize: 11,
    fontWeight: '700',
    color: COLORS.textMuted,
  },
  filterTextActive: {
    color: '#ffffff',
  },
  patientCard: {
    backgroundColor: COLORS.card,
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 6,
    elevation: 2,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  avatar: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: COLORS.primaryLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  avatarText: {
    fontSize: 18,
    fontWeight: '900',
    color: COLORS.primaryDark,
  },
  headerInfo: {
    flex: 1,
  },
  name: {
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
    marginBottom: 2,
  },
  mrn: {
    fontSize: 11,
    color: COLORS.textMuted,
    fontFamily: 'monospace',
  },
  tokenBadge: {
    backgroundColor: COLORS.primarySoft,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: COLORS.primaryLight,
  },
  tokenBadgeText: {
    fontSize: 11,
    fontWeight: '900',
    color: COLORS.primaryDark,
  },
  detailGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
    borderTopWidth: 1,
    borderBottomWidth: 1,
    borderColor: COLORS.background,
    marginBottom: 8,
  },
  detailItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  detailVal: {
    fontSize: 11,
    color: COLORS.textMuted,
  },
  allergyWarning: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fef2f2',
    padding: 8,
    borderRadius: 10,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: '#fecaca',
  },
  allergyText: {
    fontSize: 10,
    color: '#991b1b',
    fontWeight: '700',
  },
  cardFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 4,
  },
  doctorInfo: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  doctorLabel: {
    fontSize: 11,
    color: COLORS.textMuted,
    marginRight: 4,
  },
  doctorVal: {
    fontSize: 11,
    fontWeight: '700',
    color: COLORS.text,
  },
  actionBtn: {
    backgroundColor: COLORS.primarySoft,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: COLORS.primaryLight,
  },
  actionBtnText: {
    fontSize: 11,
    fontWeight: '800',
    color: COLORS.primaryDark,
  },
});
