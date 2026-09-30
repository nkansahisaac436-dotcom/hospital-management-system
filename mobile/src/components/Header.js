import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../theme';

export default function Header({ currentRole, onOpenRoleSwitcher, serverConnected }) {
  return (
    <View style={styles.container}>
      <View style={styles.brandRow}>
        <View style={styles.logoBadge}>
          <Ionicons name="medical" size={20} color="#ffffff" />
        </View>
        <View style={styles.titleCol}>
          <Text style={styles.title}>CarePoint Pro HMS</Text>
          <View style={styles.statusRow}>
            <View style={[styles.statusDot, { backgroundColor: serverConnected ? '#10b981' : '#f59e0b' }]} />
            <Text style={styles.statusText}>{serverConnected ? 'Live Server Connected' : 'Local Standalone Mode'}</Text>
          </View>
        </View>
      </View>

      <TouchableOpacity
        style={[styles.roleButton, { borderColor: currentRole.color }]}
        onPress={onOpenRoleSwitcher}
        activeOpacity={0.7}
      >
        <Ionicons name={currentRole.icon || 'person-outline'} size={14} color={currentRole.color} style={{ marginRight: 4 }} />
        <Text style={[styles.roleText, { color: currentRole.color }]}>{currentRole.badge}</Text>
        <Ionicons name="chevron-down" size={12} color={currentRole.color} style={{ marginLeft: 2 }} />
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: COLORS.card,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  logoBadge: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: COLORS.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
  },
  titleCol: {
    justifyContent: 'center',
  },
  title: {
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
    letterSpacing: -0.3,
  },
  statusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 2,
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    marginRight: 4,
  },
  statusText: {
    fontSize: 10,
    color: COLORS.textMuted,
    fontWeight: '600',
  },
  roleButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 20,
    borderWidth: 1.5,
    backgroundColor: '#ffffff',
  },
  roleText: {
    fontSize: 11,
    fontWeight: '800',
  },
});
