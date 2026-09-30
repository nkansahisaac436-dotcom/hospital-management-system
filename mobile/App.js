import React, { useState } from 'react';
import { StyleSheet, View, Text, TouchableOpacity, SafeAreaView, Platform, StatusBar } from 'react-native';
import { StatusBar as ExpoStatusBar } from 'expo-status-bar';
import { Ionicons } from '@expo/vector-icons';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { COLORS, ROLES } from './src/theme';
import { INITIAL_DATA } from './src/mockData';
import Header from './src/components/Header';
import RoleSwitcherModal from './src/components/RoleSwitcherModal';

import DashboardScreen from './src/screens/DashboardScreen';
import PatientsScreen from './src/screens/PatientsScreen';
import ClinicalScreen from './src/screens/ClinicalScreen';
import WebPortalScreen from './src/screens/WebPortalScreen';
import SettingsScreen from './src/screens/SettingsScreen';

export default function App() {
  const [activeTab, setActiveTab] = useState('dashboard'); // 'dashboard' | 'patients' | 'clinical' | 'web' | 'settings'
  const [activeRoleId, setActiveRoleId] = useState('reception');
  const [roleSwitcherVisible, setRoleSwitcherVisible] = useState(false);
  const [serverUrl, setServerUrl] = useState('http://172.20.10.3/hospital%20management%20system');
  const [appData, setAppData] = useState(INITIAL_DATA);

  const currentRole = ROLES.find((r) => r.id === activeRoleId) || ROLES[0];

  return (
    <SafeAreaProvider>
      <SafeAreaView style={styles.safeArea}>
        <ExpoStatusBar style="dark" />
        
        {/* Global Hospital Header */}
        <Header
          currentRole={currentRole}
          onOpenRoleSwitcher={() => setRoleSwitcherVisible(true)}
          serverConnected={true}
        />

        {/* Screen Routing */}
        <View style={styles.screenContainer}>
          {activeTab === 'dashboard' && (
            <DashboardScreen
              roleId={activeRoleId}
              data={appData}
              onNavigate={setActiveTab}
              onOpenRoleSwitcher={() => setRoleSwitcherVisible(true)}
            />
          )}

          {activeTab === 'patients' && (
            <PatientsScreen
              patients={appData.patients}
              onSelectPatient={() => {}}
            />
          )}

          {activeTab === 'clinical' && (
            <ClinicalScreen
              beds={appData.beds}
              medicines={appData.medicines}
              labRequests={appData.labRequests}
            />
          )}

          {activeTab === 'web' && (
            <WebPortalScreen
              serverUrl={serverUrl}
              onUpdateServerUrl={setServerUrl}
            />
          )}

          {activeTab === 'settings' && (
            <SettingsScreen
              serverUrl={serverUrl}
              onUpdateServerUrl={setServerUrl}
              onOpenRoleSwitcher={() => setRoleSwitcherVisible(true)}
              currentRole={currentRole}
            />
          )}
        </View>

        {/* Bottom Navigation Bar */}
        <View style={styles.bottomNav}>
          <TouchableOpacity
            style={styles.navItem}
            onPress={() => setActiveTab('dashboard')}
          >
            <Ionicons
              name={activeTab === 'dashboard' ? 'grid' : 'grid-outline'}
              size={22}
              color={activeTab === 'dashboard' ? COLORS.primary : COLORS.textMuted}
            />
            <Text style={[styles.navLabel, activeTab === 'dashboard' && styles.navLabelActive]}>
              Overview
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.navItem}
            onPress={() => setActiveTab('patients')}
          >
            <Ionicons
              name={activeTab === 'patients' ? 'people' : 'people-outline'}
              size={22}
              color={activeTab === 'patients' ? COLORS.primary : COLORS.textMuted}
            />
            <Text style={[styles.navLabel, activeTab === 'patients' && styles.navLabelActive]}>
              Patients
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.navItem}
            onPress={() => setActiveTab('clinical')}
          >
            <Ionicons
              name={activeTab === 'clinical' ? 'medkit' : 'medkit-outline'}
              size={22}
              color={activeTab === 'clinical' ? COLORS.primary : COLORS.textMuted}
            />
            <Text style={[styles.navLabel, activeTab === 'clinical' && styles.navLabelActive]}>
              Clinical
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.navItem}
            onPress={() => setActiveTab('web')}
          >
            <Ionicons
              name={activeTab === 'web' ? 'globe' : 'globe-outline'}
              size={22}
              color={activeTab === 'web' ? COLORS.primary : COLORS.textMuted}
            />
            <Text style={[styles.navLabel, activeTab === 'web' && styles.navLabelActive]}>
              Web Portal
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.navItem}
            onPress={() => setActiveTab('settings')}
          >
            <Ionicons
              name={activeTab === 'settings' ? 'settings' : 'settings-outline'}
              size={22}
              color={activeTab === 'settings' ? COLORS.primary : COLORS.textMuted}
            />
            <Text style={[styles.navLabel, activeTab === 'settings' && styles.navLabelActive]}>
              Settings
            </Text>
          </TouchableOpacity>
        </View>

        {/* Role Switcher Modal */}
        <RoleSwitcherModal
          visible={roleSwitcherVisible}
          onClose={() => setRoleSwitcherVisible(false)}
          activeRoleId={activeRoleId}
          onSelectRole={setActiveRoleId}
        />

      </SafeAreaView>
    </SafeAreaProvider>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#ffffff',
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
  },
  screenContainer: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  bottomNav: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-around',
    paddingVertical: 10,
    backgroundColor: COLORS.card,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 8,
  },
  navItem: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 8,
  },
  navLabel: {
    fontSize: 10,
    fontWeight: '600',
    color: COLORS.textMuted,
    marginTop: 3,
  },
  navLabelActive: {
    color: COLORS.primary,
    fontWeight: '800',
  },
});
