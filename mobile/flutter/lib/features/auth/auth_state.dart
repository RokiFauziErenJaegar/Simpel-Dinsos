import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_client.dart';

class AuthState {
  final bool isLoggedIn;
  final String? name;
  final String? phone;
  final String? role;

  AuthState({this.isLoggedIn = false, this.name, this.phone, this.role});

  AuthState copyWith({bool? isLoggedIn, String? name, String? phone, String? role}) =>
      AuthState(
        isLoggedIn: isLoggedIn ?? this.isLoggedIn,
        name: name ?? this.name,
        phone: phone ?? this.phone,
        role: role ?? this.role,
      );
}

class AuthNotifier extends AsyncNotifier<AuthState> {
  late ApiClient _api;

  @override
  Future<AuthState> build() async {
    _api = ref.read(apiClientProvider);
    final token = await _api.getToken();
    if (token == null) return AuthState();

    try {
      final res = await _api.dio.get('/auth/me');
      return AuthState(
        isLoggedIn: true,
        name: res.data['name'],
        phone: res.data['phone'],
        role: res.data['role'],
      );
    } on DioException {
      await _api.clearToken();
      return AuthState();
    }
  }

  Future<void> sendOtp(String phone) async {
    await _api.dio.post('/auth/send-otp', data: {'phone': phone});
  }

  Future<void> verifyOtp(String phone, String code) async {
    final res = await _api.dio.post('/auth/verify-otp', data: {
      'phone': phone,
      'code': code,
      'device_name': 'SIMPEL DINSOS Mobile',
    });
    await _api.saveToken(res.data['token']);
    final user = res.data['user'];
    state = AsyncData(AuthState(
      isLoggedIn: true,
      name: user['name'],
      phone: user['phone'],
      role: user['role'],
    ));
  }

  Future<void> logout() async {
    try {
      await _api.dio.post('/auth/logout');
    } catch (_) {}
    await _api.clearToken();
    state = AsyncData(AuthState());
  }
}

final authNotifierProvider =
    AsyncNotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);
