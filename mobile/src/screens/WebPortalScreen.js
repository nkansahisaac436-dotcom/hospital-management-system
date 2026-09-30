import React, { useState, useRef } from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { WebView } from 'react-native-webview';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../theme';

export default function WebPortalScreen({ serverUrl, onUpdateServerUrl }) {
  const [urlInput, setUrlInput] = useState(serverUrl);
  const [currentUrl, setCurrentUrl] = useState(serverUrl);
  const [isLoading, setIsLoading] = useState(true);
  const [canGoBack, setCanGoBack] = useState(false);
  const [canGoForward, setCanGoForward] = useState(false);
  const webViewRef = useRef(null);

  const handleNavigate = () => {
    let clean = urlInput.trim();
    if (!clean.startsWith('http://') && !clean.startsWith('https://')) {
      clean = 'http://' + clean;
    }
    setCurrentUrl(clean);
    onUpdateServerUrl(clean);
  };

  return (
    <View style={styles.container}>
      
      {/* URL Control Bar */}
      <View style={styles.navBar}>
        <View style={styles.navControls}>
          <TouchableOpacity
            style={[styles.navBtn, !canGoBack && styles.navBtnDisabled]}
            disabled={!canGoBack}
            onPress={() => webViewRef.current?.goBack()}
          >
            <Ionicons name="chevron-back" size={18} color={canGoBack ? COLORS.text : COLORS.textLight} />
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.navBtn, !canGoForward && styles.navBtnDisabled]}
            disabled={!canGoForward}
            onPress={() => webViewRef.current?.goForward()}
          >
            <Ionicons name="chevron-forward" size={18} color={canGoForward ? COLORS.text : COLORS.textLight} />
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.navBtn}
            onPress={() => webViewRef.current?.reload()}
          >
            <Ionicons name="reload" size={16} color={COLORS.text} />
          </TouchableOpacity>
        </View>

        <View style={styles.inputBox}>
          <Ionicons name="lock-closed" size={12} color={COLORS.primary} style={{ marginRight: 6 }} />
          <TextInput
            style={styles.urlInput}
            value={urlInput}
            onChangeText={setUrlInput}
            autoCapitalize="none"
            autoCorrect={false}
            onSubmitEditing={handleNavigate}
          />
        </View>

        <TouchableOpacity style={styles.goBtn} onPress={handleNavigate}>
          <Text style={styles.goBtnText}>Go</Text>
        </TouchableOpacity>
      </View>

      {/* Loading Bar */}
      {isLoading && (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="small" color={COLORS.primary} />
          <Text style={styles.loadingText}>Connecting to CarePoint Pro backend...</Text>
        </View>
      )}

      {/* Embedded Live WebView */}
      <WebView
        ref={webViewRef}
        source={{ uri: currentUrl }}
        style={styles.webview}
        onLoadStart={() => setIsLoading(true)}
        onLoadEnd={() => setIsLoading(false)}
        onNavigationStateChange={(navState) => {
          setCanGoBack(navState.canGoBack);
          setCanGoForward(navState.canGoForward);
          setUrlInput(navState.url);
        }}
        onError={(syntheticEvent) => {
          const { nativeEvent } = syntheticEvent;
          setIsLoading(false);
          Alert.alert(
            'Connection Notice',
            `Could not connect to ${currentUrl}.\n\nMake sure your PC/phone are on the same Wi-Fi network and Apache/MySQL is running on XAMPP.`
          );
        }}
        startInLoadingState
        scalesPageToFit
      />

    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  navBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: COLORS.card,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
    gap: 8,
  },
  navControls: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  navBtn: {
    width: 32,
    height: 32,
    borderRadius: 8,
    backgroundColor: COLORS.background,
    alignItems: 'center',
    justifyContent: 'center',
  },
  navBtnDisabled: {
    opacity: 0.4,
  },
  inputBox: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.background,
    borderRadius: 12,
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  urlInput: {
    flex: 1,
    fontSize: 11,
    color: COLORS.text,
    fontFamily: 'monospace',
  },
  goBtn: {
    backgroundColor: COLORS.primary,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 10,
  },
  goBtnText: {
    fontSize: 12,
    fontWeight: '800',
    color: '#ffffff',
  },
  loadingContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 6,
    backgroundColor: COLORS.primarySoft,
  },
  loadingText: {
    fontSize: 11,
    color: COLORS.primaryDark,
    marginLeft: 8,
    fontWeight: '600',
  },
  webview: {
    flex: 1,
  },
});
