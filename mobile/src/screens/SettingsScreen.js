import React from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, TextInput, Alert, Linking } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../theme';

export default function SettingsScreen({ serverUrl, onUpdateServerUrl, onOpenRoleSwitcher, currentRole }) {
  const [tempUrl, setTempUrl] = React.useState(serverUrl);

  const saveUrl = () => {
    onUpdateServerUrl(tempUrl);
    Alert.alert('Settings Saved', `Backend server endpoint set to:\n${tempUrl}`);
  };

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      
      {/* 1. Server Configuration */}
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <Ionicons name="server-outline" size={18} color={COLORS.primary} style={{ marginRight: 8 }} />
          <Text style={styles.cardTitle}>Backend Server Connection</Text>
        </View>
        <Text style={styles.cardSub}>
          Connect the mobile client to your local XAMPP host or cloud hospital domain:
        </Text>

        <TextInput
          style={styles.input}
          value={tempUrl}
          onChangeText={setTempUrl}
          autoCapitalize="none"
          autoCorrect={false}
          placeholder="http://172.20.10.3/hospital%20management%20system"
        />

        <View style={styles.btnRow}>
          <TouchableOpacity style={styles.saveBtn} onPress={saveUrl}>
            <Ionicons name="save-outline" size={15} color="#ffffff" style={{ marginRight: 6 }} />
            <Text style={styles.saveBtnText}>Save Server Endpoint</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.tipBox}>
          <Ionicons name="information-circle" size={16} color={COLORS.secondary} style={{ marginRight: 6 }} />
          <Text style={styles.tipText}>
            Local Wi-Fi IP detected: <Text style={{ fontWeight: '800' }}>172.20.10.3</Text> (Apache port 80, MySQL port 3307).
          </Text>
        </View>
      </View>

      {/* 2. Active Role Workspace */}
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <Ionicons name="people-outline" size={18} color={COLORS.accent} style={{ marginRight: 8 }} />
          <Text style={styles.cardTitle}>Current Workspace</Text>
        </View>
        <Text style={styles.cardSub}>
          Currently operating as <Text style={{ fontWeight: '800', color: currentRole.color }}>{currentRole.name}</Text>.
        </Text>

        <TouchableOpacity style={styles.switchRoleBtn} onPress={onOpenRoleSwitcher}>
          <Ionicons name="swap-horizontal" size={16} color={COLORS.primary} style={{ marginRight: 6 }} />
          <Text style={styles.switchRoleText}>Change Workspace Role</Text>
        </TouchableOpacity>
      </View>

      {/* 3. System & Runtime Information */}
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <Ionicons name="code-slash-outline" size={18} color={COLORS.text} style={{ marginRight: 8 }} />
          <Text style={styles.cardTitle}>System Information</Text>
        </View>

        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>App Version</Text>
          <Text style={styles.infoVal}>CarePoint Pro HMS v2.5.0</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Framework</Text>
          <Text style={styles.infoVal}>React Native 0.86.3</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Expo SDK Runtime</Text>
          <Text style={[styles.infoVal, { color: COLORS.primary, fontWeight: '800' }]}>Expo SDK 57.0.26</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Backend Stack</Text>
          <Text style={styles.infoVal}>PHP 8.2 / MariaDB (Port 3307)</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>GitHub Repository</Text>
          <TouchableOpacity onPress={() => Linking.openURL('https://github.com/nkansahisaac436-dotcom/hospital-management-system')}>
            <Text style={[styles.infoVal, { color: COLORS.secondary, textDecorationLine: 'underline' }]}>View on GitHub</Text>
          </TouchableOpacity>
        </View>
      </View>

      <View style={{ height: 40 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
    padding: 16,
  },
  card: {
    backgroundColor: COLORS.card,
    borderRadius: 20,
    padding: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 16,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 6,
  },
  cardTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
  },
  cardSub: {
    fontSize: 12,
    color: COLORS.textMuted,
    lineHeight: 16,
    marginBottom: 12,
  },
  input: {
    backgroundColor: COLORS.background,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
    fontSize: 12,
    fontFamily: 'monospace',
    color: COLORS.text,
    marginBottom: 12,
  },
  btnRow: {
    flexDirection: 'row',
  },
  saveBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.primary,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 12,
  },
  saveBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#ffffff',
  },
  tipBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.secondaryLight,
    padding: 10,
    borderRadius: 12,
    marginTop: 12,
  },
  tipText: {
    fontSize: 11,
    color: COLORS.secondaryDark,
    flex: 1,
  },
  switchRoleBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.primarySoft,
    paddingVertical: 10,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.primaryLight,
  },
  switchRoleText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.primaryDark,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.background,
  },
  infoLabel: {
    fontSize: 12,
    color: COLORS.textMuted,
  },
  infoVal: {
    fontSize: 12,
    color: COLORS.text,
    fontWeight: '600',
  },
});
