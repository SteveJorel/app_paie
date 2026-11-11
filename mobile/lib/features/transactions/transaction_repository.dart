import '../../core/api_client.dart';
import '../../core/paged_list.dart';

/// Critères de recherche de l'historique client (tous facultatifs).
class TransactionFilters {
  final String reference;
  final String? status;
  final int? supplierId;
  final String? supplierName;
  final DateTime? from;
  final DateTime? to;

  const TransactionFilters({
    this.reference = '',
    this.status,
    this.supplierId,
    this.supplierName,
    this.from,
    this.to,
  });

  bool get isEmpty =>
      reference.trim().isEmpty && status == null && supplierId == null && from == null && to == null;

  static String _date(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  String toQuery(int page) {
    final params = <String, String>{'page': '$page', 'per_page': '20'};

    if (reference.trim().isNotEmpty) params['reference'] = reference.trim();
    if (status != null) params['status'] = status!;
    if (supplierId != null) params['supplier_id'] = '$supplierId';
    if (from != null) params['from'] = _date(from!);
    if (to != null) params['to'] = _date(to!);

    return params.entries.map((e) => '${e.key}=${Uri.encodeQueryComponent(e.value)}').join('&');
  }
}

class TransactionRepository {
  final ApiClient _client;

  TransactionRepository({ApiClient? client}) : _client = client ?? ApiClient();

  /// Une page de l'historique, filtrée côté serveur.
  Future<PagedResult> page(int page, TransactionFilters filters) async {
    return PagedResult.fromJson(await _client.get('/me/transactions?${filters.toQuery(page)}'));
  }

  Future<List<Map<String, dynamic>>> list({String? status}) async {
    final query = (status != null && status.isNotEmpty) ? '?status=$status' : '';
    final response = await _client.get('/me/transactions$query');
    final data = (response is Map && response['data'] is List) ? response['data'] as List : <dynamic>[];
    return data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  Future<Map<String, dynamic>> show(int paymentId) async {
    final response = await _client.get('/me/transactions/$paymentId');

    if (response is Map && response['data'] is Map) {
      return Map<String, dynamic>.from(response['data'] as Map);
    }

    return Map<String, dynamic>.from(response as Map);
  }

  Future<Map<String, dynamic>> receiptFor(int paymentId) async {
    final response = await _client.get('/me/transactions/$paymentId/receipt');

    if (response is Map && response['data'] is Map) {
      return Map<String, dynamic>.from(response['data'] as Map);
    }

    return Map<String, dynamic>.from(response as Map);
  }

  Future<List<int>> downloadReceiptPdf(int receiptId) {
    return _client.getBytes('/me/receipts/$receiptId/download');
  }
}