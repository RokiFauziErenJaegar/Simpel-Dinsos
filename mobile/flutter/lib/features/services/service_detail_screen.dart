import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_client.dart';

final serviceDetailProvider =
    FutureProvider.family<Map<String, dynamic>, String>((ref, slug) async {
  final api = ref.read(apiClientProvider);
  final res = await api.dio.get('/services/$slug');
  return Map<String, dynamic>.from(res.data);
});

class ServiceDetailScreen extends ConsumerWidget {
  final String slug;
  const ServiceDetailScreen({super.key, required this.slug});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dataAsync = ref.watch(serviceDetailProvider(slug));

    return Scaffold(
      appBar: AppBar(title: const Text('Detail Layanan')),
      body: dataAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Error: $e')),
        data: (s) => SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(s['code'] ?? '', style: const TextStyle(color: Colors.grey)),
              Text(s['name'] ?? '',
                  style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 12),
              Text(s['description'] ?? ''),
              const SizedBox(height: 20),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      const Icon(Icons.schedule, color: Colors.grey),
                      const SizedBox(width: 8),
                      Text('Estimasi: ${s['sla_display'] ?? '-'}'),
                      const Spacer(),
                      const Text('💰 Gratis',
                          style: TextStyle(color: Colors.green, fontWeight: FontWeight.w700)),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
              Text('Persyaratan',
                  style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 8),
              ...((s['requirements'] as List?) ?? []).map((r) => Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('✓ ', style: TextStyle(color: Colors.green)),
                        Expanded(child: Text(r.toString())),
                      ],
                    ),
                  )),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () {
                    // TODO: Form pengajuan — multipart upload ke /api/v1/applications
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Form pengajuan — implementasi multipart upload')),
                    );
                  },
                  child: const Text('Ajukan Layanan →'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
