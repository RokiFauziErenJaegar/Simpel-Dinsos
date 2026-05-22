class ServiceType {
  final int id;
  final String code;
  final String slug;
  final String name;
  final String description;
  final String bidang;
  final String slaDisplay;
  final String? icon;
  final bool isFeatured;

  ServiceType({
    required this.id,
    required this.code,
    required this.slug,
    required this.name,
    required this.description,
    required this.bidang,
    required this.slaDisplay,
    this.icon,
    this.isFeatured = false,
  });

  factory ServiceType.fromJson(Map<String, dynamic> json) => ServiceType(
        id: json['id'] as int,
        code: json['code'] as String,
        slug: json['slug'] as String,
        name: json['name'] as String,
        description: json['description'] as String? ?? '',
        bidang: json['bidang'] as String? ?? 'Umum',
        slaDisplay: json['sla_display'] as String? ?? 'Kondisional',
        icon: json['icon'] as String?,
        isFeatured: json['is_featured'] as bool? ?? false,
      );

  String get emoji {
    return switch (icon) {
      'identification' => '🪪',
      'shield-check' => '🏥',
      'heart' => '❤️',
      'user-group' => '♿',
      'megaphone' => '📢',
      'gift' => '🎁',
      'banknotes' => '💰',
      'building-office' => '🏛',
      'academic-cap' => '🎓',
      'truck' => '🚚',
      'user' => '👤',
      'users' => '👨‍👩‍👧',
      'sparkles' => '✨',
      'speaker-wave' => '📣',
      'home-modern' => '🏠',
      'chat-bubble-left-right' => '💬',
      _ => '📋',
    };
  }
}
