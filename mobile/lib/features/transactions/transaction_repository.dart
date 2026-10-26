import '../../core/api_client.dart';

class TransactionRepository {
  final ApiClient _client;

  TransactionRepository({ApiClient? client}) : _client = client ?? ApiClient();

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