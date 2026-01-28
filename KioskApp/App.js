import React, { useState, useEffect, useRef } from 'react';
import { StyleSheet, Text, View, TouchableOpacity, Image, Modal, Dimensions } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import * as FaceDetector from 'expo-face-detector';
import * as Location from 'expo-location';
import { ScanFace, X, CheckCircle, AlertCircle } from 'lucide-react-native';
import { Audio } from 'expo-av';

const { width, height } = Dimensions.get('window');

// API Configuration
const API_URL = 'https://wishluvbuildcon.com/hrms/ajax/kiosk_verify.php';

export default function App() {
  const [permission, requestPermission] = useCameraPermissions();
  const [locationPermission, requestLocationPermission] = useState(null);
  const [isKioskActive, setIsKioskActive] = useState(false);
  const [faceDetected, setFaceDetected] = useState(false);
  const [countdown, setCountdown] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);
  const [result, setResult] = useState(null);
  const [status, setStatus] = useState('System Ready');
  
  const cameraRef = useRef(null);
  const countdownInterval = useRef(null);
  const lastFaceTime = useRef(0);

  useEffect(() => {
    (async () => {
      const { status } = await Location.requestForegroundPermissionsAsync();
      requestLocationPermission(status === 'granted');
    })();
  }, []);

  if (!permission) return <View />;
  if (!permission.granted) {
    return (
      <View style={styles.container}>
        <Text style={styles.text}>Camera permission is required</Text>
        <TouchableOpacity style={styles.button} onPress={requestPermission}>
          <Text style={styles.buttonText}>Grant Permission</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const onFacesDetected = ({ faces }) => {
    if (isProcessing || result || !isKioskActive) return;

    if (faces.length > 0) {
      const face = faces[0];
      // Check if face is large enough and centered roughly
      const isLarge = face.bounds.size.width > 150;
      
      if (isLarge) {
        setFaceDetected(true);
        lastFaceTime.current = Date.now();
        if (countdown === null && !countdownInterval.current) {
          startCountdown();
        }
      } else {
        resetCountdown('Move Closer');
      }
    } else {
      if (Date.now() - lastFaceTime.current > 500) {
        resetCountdown('Scanning for face...');
      }
    }
  };

  const startCountdown = () => {
    setCountdown(3);
    setStatus('Ready in...');
    
    let current = 3;
    countdownInterval.current = setInterval(() => {
      current -= 1;
      if (current > 0) {
        setCountdown(current);
      } else {
        clearInterval(countdownInterval.current);
        countdownInterval.current = null;
        setCountdown(0);
        captureAndVerify();
      }
    }, 1000);
  };

  const resetCountdown = (msg) => {
    if (countdownInterval.current) {
      clearInterval(countdownInterval.current);
      countdownInterval.current = null;
    }
    setCountdown(null);
    setFaceDetected(false);
    setStatus(msg);
  };

  const captureAndVerify = async () => {
    setIsProcessing(true);
    setStatus('Analyzing...');
    setCountdown(null);

    try {
      const photo = await cameraRef.current.takePictureAsync({ quality: 0.7, base64: true });
      const location = await Location.getCurrentPositionAsync({});
      
      const formData = new FormData();
      formData.append('image_data', `data:image/jpeg;base64,${photo.base64}`);
      formData.append('latitude', location.coords.latitude);
      formData.append('longitude', location.coords.longitude);

      const response = await fetch(API_URL, {
        method: 'POST',
        body: formData,
      });

      const data = await response.json();
      if (data.success) {
        setResult(data);
        setTimeout(() => {
          setResult(null);
          setStatus('Next User Ready');
        }, 4000);
      } else {
        setStatus(data.message || 'Face Not Recognized');
        setTimeout(() => setStatus('Scanning...'), 2000);
      }
    } catch (error) {
      console.error(error);
      setStatus('Connection Error');
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <View style={styles.container}>
      {!isKioskActive ? (
        <View style={styles.landing}>
          <Image source={require('./assets/icon.png')} style={styles.logo} />
          <Text style={styles.title}>Wishluv HRMS</Text>
          <Text style={styles.subtitle}>Kiosk Attendance System</Text>
          <TouchableOpacity 
            style={styles.mainButton} 
            onPress={() => setIsKioskActive(true)}
          >
            <ScanFace color="white" size={32} />
            <Text style={styles.mainButtonText}>Start Attendance</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <View style={styles.cameraContainer}>
          <CameraView
            ref={cameraRef}
            style={styles.camera}
            facing="front"
            onFacesDetected={onFacesDetected}
            faceDetectorSettings={{
              mode: FaceDetector.FaceDetectorMode.fast,
              detectLandmarks: FaceDetector.FaceDetectorLandmarks.none,
              runClassifications: FaceDetector.FaceDetectorClassifications.none,
              minDetectionInterval: 100,
              tracking: true,
            }}
          >
            {/* HUD Overlay */}
            <View style={styles.overlay}>
              <TouchableOpacity style={styles.closeBtn} onPress={() => setIsKioskActive(false)}>
                <X color="white" size={24} />
              </TouchableOpacity>

              <View style={styles.statusBadge}>
                <Text style={styles.statusText}>{status}</Text>
              </View>

              <View style={styles.faceGuide} />

              {countdown !== null && (
                <View style={styles.countdownContainer}>
                  <Text style={styles.countdownText}>{countdown === 0 ? '0' : countdown}</Text>
                </View>
              )}
            </View>
          </CameraView>

          {/* Success Modal */}
          {result && (
            <View style={styles.resultContainer}>
              <View style={styles.resultCard}>
                <Image source={{ uri: result.avatar || 'https://via.placeholder.com/150' }} style={styles.avatar} />
                <Text style={styles.resultName}>{result.employee_name}</Text>
                <View style={styles.resultStatus}>
                   <CheckCircle color="#10b981" size={20} />
                   <Text style={styles.resultMsg}>{result.type === 'in' ? 'Punch-In Successful' : 'Punch-Out Successful'}</Text>
                </View>
                <Text style={styles.resultTime}>{result.message}</Text>
              </View>
            </View>
          )}
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0f172a',
  },
  landing: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 20,
  },
  logo: {
    width: 120,
    height: 120,
    marginBottom: 40,
    borderRadius: 20,
  },
  title: {
    fontSize: 28,
    fontWeight: '800',
    color: 'white',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: '#94a3b8',
    marginBottom: 40,
  },
  mainButton: {
    backgroundColor: '#6366f1',
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 18,
    paddingHorizontal: 32,
    borderRadius: 16,
    gap: 12,
  },
  mainButtonText: {
    color: 'white',
    fontSize: 20,
    fontWeight: '700',
  },
  cameraContainer: {
    flex: 1,
  },
  camera: {
    flex: 1,
  },
  overlay: {
    flex: 1,
    backgroundColor: 'transparent',
    alignItems: 'center',
    justifyContent: 'center',
  },
  closeBtn: {
    position: 'absolute',
    top: 50,
    right: 30,
    backgroundColor: 'rgba(0,0,0,0.5)',
    padding: 10,
    borderRadius: 25,
  },
  statusBadge: {
    position: 'absolute',
    top: 60,
    backgroundColor: 'rgba(0,0,0,0.7)',
    paddingHorizontal: 20,
    paddingVertical: 8,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.2)',
  },
  statusText: {
    color: 'white',
    fontSize: 14,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 2,
  },
  faceGuide: {
    width: 280,
    height: 350,
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.3)',
    borderStyle: 'dashed',
    borderRadius: 140,
  },
  countdownContainer: {
    position: 'absolute',
    alignItems: 'center',
    justifyContent: 'center',
  },
  countdownText: {
    fontSize: 120,
    fontWeight: '900',
    color: 'white',
    textShadowColor: 'rgba(99, 102, 241, 0.8)',
    textShadowOffset: { width: 0, height: 0 },
    textShadowRadius: 20,
  },
  resultContainer: {
    position: 'absolute',
    top: 100,
    left: 20,
    right: 20,
    alignItems: 'center',
    zIndex: 3000,
  },
  resultCard: {
    backgroundColor: 'white',
    width: '100%',
    padding: 24,
    borderRadius: 24,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.3,
    shadowRadius: 20,
    elevation: 10,
  },
  avatar: {
    width: 100,
    height: 100,
    borderRadius: 50,
    marginBottom: 16,
    borderWidth: 4,
    borderColor: '#10b981',
  },
  resultName: {
    fontSize: 24,
    fontWeight: '800',
    color: '#0f172a',
    marginBottom: 8,
  },
  resultStatus: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 4,
  },
  resultMsg: {
    fontSize: 18,
    fontWeight: '700',
    color: '#10b981',
  },
  resultTime: {
    fontSize: 14,
    color: '#64748b',
  },
});
