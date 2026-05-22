import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api_client.dart';

final applicationDetailProvider =
    FutureProvider.family<Map<String, dynamic>, String>((ref, code) async {
  final api = ref.read(apiClientProvider);
  final res = await api.dio.get('/applications/$code');
  return Map<String, dynamic>.from(res.data);
});

class ApplicationDetailScreen extends ConsumerWidget {
  final String code;
  const ApplicationDetailScreen({super.key, required this.code});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dataAsync = ref.watch(applicationDetailProvider(code));
    final df = DateFormat('d MMM y · HH:mm', 'id_ID');

    return Scaffold(
      appBar: AppBar(title: Text(code)),
      body: dataAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Error: $e')),
        data: (a) {
          final logs = (a['logs'] as List?) ?? [];
          final output = a['output'] as Map?;

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(a['service']?['name'] ?? '',
                  style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 4),
              Text('Penerima: ${a['beneficiary'] ?? '-'}'),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.orange.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(a['status_label'] ?? '',
                    style: const TextStyle(color: Colors.orange, fontWeight: FontWeight.w700)),
              ),
              const SizedBox(height: 20),
              if (output != null) ...[
                Card(
                  color: Colors.green.shade50,
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('📄 Surat Tersedia', style: TextStyle(fontWeight: FontWeight.w700)),
                        const SizedBox(height: 4),
                        Text('Nomor: ${output['number']}'),
                        const SizedBox(height: 12),
                        ElevatedButton.icon(
                          icon: const Icon(Icons.download),
                          label: const Text('Unduh / Verifikasi'),
                          onPressed: () => launchUrl(Uri.parse(output['verify_url'])),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 20),
              ],
              Text('Timeline', style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 8),
              ...logs.map((l) {
                final time = l['time'] != null ? df.format(DateTime.parse(l['time'])) : '-';
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: 12,
                        height: 12,
                        margin: const EdgeInsets.only(top: 4),
                        decoration: const BoxDecoration(color: Colors.blue, shape: BoxShape.circle),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(time, style: const TextStyle(fontSize: 11, color: Colors.grey)),
                            Text((l['action'] ?? '').toString().toUpperCase(),
                                style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12)),
                            if (l['notes'] != null) Text(l['notes']),
                          ],
                        ),
                      ),
                    ],
                  ),
                );
              }),
            ],
          );
        },
      ),
    );
  }
}
