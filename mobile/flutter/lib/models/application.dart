class Application {
  final String code;
  final String serviceCode;
  final String serviceName;
  final String beneficiary;
  final String status;
  final String statusLabel;
  final DateTime? submittedAt;
  final DateTime? slaDueAt;
  final DateTime? completedAt;
  final String? queueTicket;
  final String? verifyUrl;

  Application({
    required this.code,
    required this.serviceCode,
    required this.serviceName,
    required this.beneficiary,
    required this.status,
    required this.statusLabel,
    this.submittedAt,
    this.slaDueAt,
    this.completedAt,
    this.queueTicket,
    this.verifyUrl,
  });

  factory Application.fromJson(Map<String, dynamic> json) => Application(
        code: json['code'] as String,
        serviceCode: json['service_code'] as String? ?? '',
        serviceName: json['service_name'] as String? ?? '',
        beneficiary: json['beneficiary'] as String? ?? '',
        status: json['status'] as String? ?? 'submitted',
        statusLabel: json['status_label'] as String? ?? 'Diajukan',
        submittedAt:
            json['submitted_at'] != null ? DateTime.parse(json['submitted_at']) : null,
        slaDueAt: json['sla_due_at'] != null ? DateTime.parse(json['sla_due_at']) : null,
        completedAt:
            json['completed_at'] != null ? DateTime.parse(json['completed_at']) : null,
        queueTicket: json['queue_ticket'] as String?,
        verifyUrl: json['verify_url'] as String?,
      );

  bool get isCompleted => status == 'completed';
  bool get isRejected => status == 'rejected';
  bool get isFinal => isCompleted || isRejected;
}
