import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiClient {
  static const baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1', // 10.0.2.2 = host PC dari Android emulator
  );

  final Dio dio;
  final FlutterSecureStorage storage;

  ApiClient({Dio? dio, FlutterSecureStorage? storage})
      : dio = dio ??
            Dio(BaseOptions(
              baseUrl: baseUrl,
              connectTimeout: const Duration(seconds: 15),
              receiveTimeout: const Duration(seconds: 30),
              headers: {'Accept': 'application/json'},
            )),
        storage = storage ?? const FlutterSecureStorage() {
    this.dio.interceptors.add(_AuthInterceptor(this.storage));
    this.dio.interceptors.add(LogInterceptor(
          requestBody: false,
          responseBody: false,
          requestHeader: false,
          responseHeader: false,
          error: true,
        ));
  }

  Future<String?> getToken() => storage.read(key: 'auth_token');
  Future<void> saveToken(String token) =>
      storage.write(key: 'auth_token', value: token);
  Future<void> clearToken() => storage.delete(key: 'auth_token');
}

class _AuthInterceptor extends Interceptor {
  final FlutterSecureStorage storage;
  _AuthInterceptor(this.storage);

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await storage.read(key: 'auth_token');
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    if (err.response?.statusCode == 401) {
      await storage.delete(key: 'auth_token');
    }
    handler.next(err);
  }
}

final apiClientProvider = Provider<ApiClient>((ref) => ApiClient());
