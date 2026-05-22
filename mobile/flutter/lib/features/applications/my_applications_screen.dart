import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/api_client.dart';
import '../../models/application.dart';

final myApplicationsProvider = FutureProvider<List<Application>>((ref) async {
  final api = ref.read(apiClientProvider);
  final res = await api.dio.get('/my/applications');
  final list = res.data['data'] as List;
  return list.map((j) => Application.fromJson(j)).toList();
});

class MyApplicationsScreen extends ConsumerWidget {
  const MyApplicationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final appsAsync = ref.watch(myApplicationsProvider);
    final df = DateFormat('d MMM y · HH:mm', 'id_ID');

    return Scaffold(
      appBar: AppBar(title: const Text('Pengajuan Saya')),
      body: appsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Error: $e')),
        data: (apps) {
          if (apps.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(32),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.assignment_outlined, size: 64, color: Colors.grey),
                    SizedBox(height: 12),
                    Text('Belum ada pengajuan', style: TextStyle(color: Colors.grey)),
                    SizedBox(height: 4),
                    Text('Ajukan layanan dari tab Layanan', style: TextStyle(color: Colors.grey, fontSize: 12)),
                  ],
                ),
              ),
            );
          }
          return RefreshIndicator(
            onRefresh: () async => ref.refresh(myApplicationsProvider.future),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: apps.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (ctx, i) {
                final a = apps[i];
                final color = switch (a.status) {
                  'completed' => Colors.green,
                  'rejected' => Colors.red,
                  _ => Colors.orange,
                };
                return Card(
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    title: Text(a.serviceName, maxLines: 2, overflow: TextOverflow.ellipsis),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 6),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(a.code, style: const TextStyle(fontSize: 11, color: Colors.grey)),
                          if (a.submittedAt != null)
                            Text(df.format(a.submittedAt!), style: const TextStyle(fontSize: 12, color: Colors.grey)),
                        ],
                      ),
                    ),
                    trailing: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: color.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(a.statusLabel,
                          style: TextStyle(color: color, fontWeight: FontWeight.w600, fontSize: 12)),
                    ),
                    onTap: () => context.go('/applications/${a.code}'),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
