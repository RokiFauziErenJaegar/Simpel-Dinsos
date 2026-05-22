# Panduan Aplikasi Mobile — SIMPEL DINSOS

Dua opsi pendekatan mobile sudah disiapkan oleh sistem SIMPEL DINSOS. Pilih sesuai kebutuhan & sumber daya tim:

## Opsi A: PWA (sudah live) ✅

**Progressive Web App** — paling cepat sampai ke warga, tidak perlu store submission.

### Fitur sudah aktif
- ✅ Installable via Chrome "Add to Home screen" / Safari "Add to Home Screen"
- ✅ Standalone mode (tanpa address bar setelah dipasang)
- ✅ Offline shell + halaman fallback
- ✅ Service worker dengan cache versioning
- ✅ Push notification API (perlu VAPID key di prod)
- ✅ App shortcuts: Ajukan / Status / Lapor
- ✅ Banner install prompt dengan dismiss 7 hari
- ✅ Halaman `/pwa-test` untuk verifikasi device

### Cara warga pasang
**Android (Chrome):** akses `simpel-dinsos.pringsewu.go.id` → muncul banner "Pasang aplikasi" atau menu titik tiga → "Install app"
**iOS (Safari):** Share → Add to Home Screen

### Kelebihan
- Tidak perlu Apple Developer ($99/tahun) atau Play Console ($25 sekali)
- Update instan (no review)
- Satu codebase
- Akses semua fitur web

### Keterbatasan
- iOS push notification baru support penuh dari iOS 16.4+ (Maret 2023)
- Tidak bisa akses Bluetooth, NFC, kamera depan-otomatis seperti native

---

## Opsi B: Flutter Native App (REST API ready)

REST API v1 sudah lengkap di `/api/v1/*` untuk dikonsumsi mobile native.

### Endpoint yang Tersedia

```
PUBLIC:
GET  /api/v1/services                List 16 layanan
GET  /api/v1/services/{slug}         Detail layanan
GET  /api/v1/queue/status            Antrian live (now serving + waiting)
GET  /api/v1/applications/{code}     Status pengajuan + timeline

AUTH:
POST /api/v1/auth/send-otp           Body: {phone}
POST /api/v1/auth/verify-otp         Body: {phone, code, device_name}
                                     Response: {token, user}

AUTHENTICATED (Bearer token):
GET  /api/v1/auth/me                 Profil user
POST /api/v1/auth/logout             Revoke token
GET  /api/v1/my/applications         Pengajuan saya
```

### Flutter Project Sketch

Struktur folder yang direkomendasikan:

```
simpel_dinsos_mobile/
├── lib/
│   ├── main.dart
│   ├── app.dart                     # MaterialApp + theme Dinsos blue
│   ├── core/
│   │   ├── api_client.dart          # Dio + interceptor token + retry
│   │   ├── secure_storage.dart      # flutter_secure_storage untuk token
│   │   ├── theme.dart               # Brand color #1E4D8C
│   │   └── router.dart              # go_router
│   ├── features/
│   │   ├── auth/
│   │   │   ├── login_screen.dart    # Input nomor HP → OTP
│   │   │   ├── otp_screen.dart      # 6-digit input
│   │   │   └── auth_repository.dart
│   │   ├── services/
│   │   │   ├── service_list_screen.dart
│   │   │   ├── service_detail_screen.dart
│   │   │   └── services_repository.dart
│   │   ├── applications/
│   │   │   ├── my_applications_screen.dart
│   │   │   ├── application_detail_screen.dart  # timeline
│   │   │   └── applications_repository.dart
│   │   ├── queue/
│   │   │   └── queue_screen.dart    # antrian live (polling 5s)
│   │   └── home/
│   │       └── home_screen.dart     # bottom nav: Home / Status / Profile
│   └── models/
│       ├── service.dart
│       ├── application.dart
│       └── user.dart
├── pubspec.yaml
└── android/, ios/
```

### Dependencies (pubspec.yaml)

```yaml
dependencies:
  flutter:
    sdk: flutter
  dio: ^5.4.0                    # HTTP client
  flutter_secure_storage: ^9.0.0 # Token storage
  go_router: ^13.0.0             # Routing
  riverpod: ^2.4.0               # State management
  flutter_riverpod: ^2.4.0
  intl: ^0.19.0                  # I18n (id_ID)
  url_launcher: ^6.2.0           # WA call center
  flutter_local_notifications: ^16.0.0  # Push
  cached_network_image: ^3.3.0
  qr_flutter: ^4.1.0             # Display QR tiket antrian
```

### Sample API Client (Dio)

```dart
// lib/core/api_client.dart
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiClient {
  static const baseUrl = 'https://simpel-dinsos.pringsewu.go.id/api/v1';
  static final _storage = const FlutterSecureStorage();
  static final dio = Dio(BaseOptions(
    baseUrl: baseUrl,
    connectTimeout: const Duration(seconds: 15),
    receiveTimeout: const Duration(seconds: 30),
    headers: {'Accept': 'application/json'},
  ))..interceptors.add(_AuthInterceptor());
}

class _AuthInterceptor extends Interceptor {
  @override
  void onRequest(options, handler) async {
    final token = await ApiClient._storage.read(key: 'token');
    if (token != null) options.headers['Authorization'] = 'Bearer $token';
    handler.next(options);
  }

  @override
  void onError(err, handler) async {
    if (err.response?.statusCode == 401) {
      await ApiClient._storage.delete(key: 'token');
      // Redirect ke login screen via app router
    }
    handler.next(err);
  }
}
```

### Sample Auth Flow

```dart
// lib/features/auth/auth_repository.dart
class AuthRepository {
  Future<void> sendOtp(String phone) async {
    await ApiClient.dio.post('/auth/send-otp', data: {'phone': phone});
  }

  Future<User> verifyOtp(String phone, String code) async {
    final res = await ApiClient.dio.post('/auth/verify-otp', data: {
      'phone': phone,
      'code': code,
      'device_name': 'Flutter App',
    });
    await ApiClient._storage.write(key: 'token', value: res.data['token']);
    return User.fromJson(res.data['user']);
  }

  Future<void> logout() async {
    await ApiClient.dio.post('/auth/logout');
    await ApiClient._storage.delete(key: 'token');
  }
}
```

### Setup awal (estimasi 2 hari)

1. `flutter create simpel_dinsos_mobile --org id.go.pringsewu.dinsos`
2. Tambah dependencies di `pubspec.yaml`, `flutter pub get`
3. Setup theme dengan brand color `#1E4D8C`
4. Build auth flow (login + OTP screen)
5. Build home screen dengan bottom nav 3 tab
6. Build service list + detail
7. Build my applications screen
8. Test ke staging API
9. Submit ke Play Console + App Store

### Estimasi MVP Flutter

| Tahap | Durasi |
|---|---|
| Setup + theme + routing | 2 hari |
| Auth flow (OTP) | 2 hari |
| Service catalog + detail | 2 hari |
| My applications + timeline | 3 hari |
| Queue live screen | 1 hari |
| Push notification setup | 2 hari |
| Polish + bug fix | 3 hari |
| **Total MVP** | **15 hari** |

Submit ke store: tambahan 1-2 minggu review.

---

## Opsi C: React Native (alternatif Flutter)

Untuk tim yang sudah familier React.js, gunakan **Expo SDK** untuk cepat ke production:

```bash
npx create-expo-app simpel-dinsos-rn --template tabs
cd simpel-dinsos-rn
npx expo install @react-native-async-storage/async-storage axios expo-router expo-secure-store expo-notifications
```

Struktur & API sama dengan Flutter. Gunakan `axios` sebagai pengganti `dio`.

---

## Rekomendasi

| Skenario | Pilihan |
|---|---|
| Cepat ke production, anggaran tipis | **Opsi A: PWA** (sudah live) |
| Butuh fitur native (Bluetooth, kamera advanced) | **Opsi B: Flutter** |
| Tim sudah React | **Opsi C: React Native** |

**Saran untuk Dinsos Pringsewu:** mulai dengan **PWA** (sudah selesai), pantau adopsi 6 bulan, baru putuskan native app jika user retention dan engagement tinggi.

## Spesifikasi Brand & Asset

- Primary color: `#1E4D8C` (biru Pringsewu)
- Accent color: `#2DB67C` (hijau CARE)
- Font: Plus Jakarta Sans (heading) + Inter (body)
- Logo: lihat `/icons/icon-512.svg`
- Splash screen: gradient biru → hijau dengan logo "D"

## Kontak Tim API

- Repo backend: `C:\xampp\htdocs\simpel-dinsos`
- API base URL: `https://simpel-dinsos.pringsewu.go.id/api/v1` (production)
- Sandbox: `https://staging.simpel-dinsos.pringsewu.go.id/api/v1`
- Postman collection: dapat di-export via `php artisan route:list --path=api`
