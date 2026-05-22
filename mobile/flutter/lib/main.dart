import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/date_symbol_data_local.dart';

import 'app.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Locale Indonesia
  await initializeDateFormatting('id_ID', null);

  runApp(const ProviderScope(child: SimpelDinsosApp()));
}
