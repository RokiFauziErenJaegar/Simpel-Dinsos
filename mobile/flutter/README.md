# SIMPEL DINSOS Mobile (Flutter)

Aplikasi mobile native Flutter yang mengkonsumsi REST API `/api/v1/*` dari backend SIMPEL DINSOS.

## Status

🚧 **Starter project** — struktur lengkap, beberapa screen masih stub. Siap dikembangkan oleh tim mobile.

| Screen | Status |
|---|---|
| Splash | TODO |
| Login (OTP) | ✅ Implemented |
| OTP Verify | ✅ Implemented |
| Home (Service List) | ✅ Implemented |
| Service Detail | ✅ Implemented (form pengajuan TODO) |
| My Applications | ✅ Implemented |
| Application Detail + Timeline | ✅ Implemented |
| Profile | ✅ Implemented |
| Queue Live | TODO |
| Push Notification | TODO |
| WebView untuk halaman web (Data Rights, 2FA) | TODO |

## Setup

### Prerequisite
- Flutter SDK 3.22+
- Android Studio / Xcode untuk emulator
- Backend SIMPEL DINSOS running di `http://localhost:8000`

### Install
```bash
cd mobile/flutter
flutter pub get
```

### Run
```bash
# Android emulator (10.0.2.2 = host PC dari emulator)
flutter run

# iOS simulator (pakai localhost atau IP LAN)
flutter run --dart-define=API_BASE_URL=http://localhost:8000/api/v1

# Device fisik di WiFi yang sama (pakai IP LAN PC)
flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8000/api/v1
```

## Arsitektur

```
lib/
├── main.dart                     # Entry point
├── app.dart                      # MaterialApp.router
├── core/
│   ├── api_client.dart           # Dio + interceptor token
│   ├── router.dart               # GoRouter dengan auth guard
│   └── theme.dart                # Brand color #1E4D8C + Inter/Plus Jakarta Sans
├── features/
│   ├── auth/
│   │   ├── auth_state.dart       # AsyncNotifier untuk auth state
│   │   ├── login_screen.dart     # Input HP → kirim OTP
│   │   └── otp_screen.dart       # Verifikasi 6 digit
│   ├── home/
│   │   └── home_shell.dart       # Bottom navigation
│   ├── services/
│   │   ├── service_list_screen.dart
│   │   └── service_detail_screen.dart
│   ├── applications/
│   │   ├── my_applications_screen.dart
│   │   └── application_detail_screen.dart
│   └── profile/
│       └── profile_screen.dart
└── models/
    ├── application.dart
    └── service_type.dart
```

State management: **Riverpod 2** (AsyncNotifier + Provider.family)
HTTP: **Dio** dengan interceptor Bearer token
Routing: **GoRouter** dengan auth redirect
Storage: **flutter_secure_storage** untuk token

## API Endpoint yang Digunakan

```
GET  /services                  → List service catalog
GET  /services/{slug}           → Service detail
POST /auth/send-otp             → Kirim OTP ke nomor
POST /auth/verify-otp           → Verify OTP + dapatkan Bearer token
GET  /auth/me                   → Profile user
POST /auth/logout               → Revoke token
GET  /my/applications           → Pengajuan saya
GET  /applications/{code}       → Detail pengajuan + timeline
GET  /queue/status              → Antrian live (untuk dashboard)
```

## Build & Release

### Android APK
```bash
flutter build apk --release \
  --dart-define=API_BASE_URL=https://simpel-dinsos.pringsewu.go.id/api/v1
```

### Android App Bundle (Play Store)
```bash
flutter build appbundle --release \
  --dart-define=API_BASE_URL=https://simpel-dinsos.pringsewu.go.id/api/v1
```

### iOS (perlu Xcode + Mac)
```bash
flutter build ios --release \
  --dart-define=API_BASE_URL=https://simpel-dinsos.pringsewu.go.id/api/v1
```

## TODO Berikutnya

- [ ] Form pengajuan layanan dengan multipart upload
- [ ] WebView untuk halaman complex (Data Rights, 2FA setup)
- [ ] Push notification dengan FCM
- [ ] Offline support pakai isar/hive
- [ ] Biometric login (fingerprint/Face ID) sebagai pengganti OTP setiap kali
- [ ] Splash screen + onboarding
- [ ] Multi-language (id, en)
- [ ] Dark mode

## Lisensi

Properti Pemerintah Kabupaten Pringsewu — Dinas Sosial.
