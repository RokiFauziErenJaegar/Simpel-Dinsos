import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/applications/application_detail_screen.dart';
import '../features/applications/my_applications_screen.dart';
import '../features/auth/auth_state.dart';
import '../features/auth/login_screen.dart';
import '../features/auth/otp_screen.dart';
import '../features/home/home_shell.dart';
import '../features/profile/profile_screen.dart';
import '../features/services/service_detail_screen.dart';
import '../features/services/service_list_screen.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authNotifierProvider);

  return GoRouter(
    initialLocation: '/home',
    redirect: (context, state) {
      final isLogged = auth.value?.isLoggedIn ?? false;
      final isAuthRoute = state.uri.path.startsWith('/auth');
      // Profile & My Apps perlu auth
      final requiresAuth =
          state.uri.path == '/me' || state.uri.path == '/my-applications';
      if (requiresAuth && !isLogged) return '/auth/login';
      if (isAuthRoute && isLogged) return '/home';
      return null;
    },
    routes: [
      GoRoute(path: '/auth/login', builder: (_, __) => const LoginScreen()),
      GoRoute(
        path: '/auth/otp/:phone',
        builder: (ctx, st) => OtpScreen(phone: st.pathParameters['phone']!),
      ),
      ShellRoute(
        builder: (ctx, st, child) => HomeShell(child: child),
        routes: [
          GoRoute(
            path: '/home',
            builder: (_, __) => const ServiceListScreen(),
          ),
          GoRoute(
            path: '/my-applications',
            builder: (_, __) => const MyApplicationsScreen(),
          ),
          GoRoute(path: '/me', builder: (_, __) => const ProfileScreen()),
        ],
      ),
      GoRoute(
        path: '/services/:slug',
        builder: (ctx, st) => ServiceDetailScreen(slug: st.pathParameters['slug']!),
      ),
      GoRoute(
        path: '/applications/:code',
        builder: (ctx, st) =>
            ApplicationDetailScreen(code: st.pathParameters['code']!),
      ),
    ],
  );
});
