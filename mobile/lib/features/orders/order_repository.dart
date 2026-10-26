import '../../core/api_client.dart';

class OrderRepository {
  final ApiClient _client;

  OrderRepository({ApiClient? client}) : _client = client ?? ApiClient();

  Future<Map<String, dynamic>> create({
    required int supplierId,
    required String description,
    required int amountMinor,
  }) async {
    final response = await _client.post('/orders', {
      'supplier_id': supplierId,
      'description': description,
      'amount_minor': amountMinor,
    });

    if (response is Map && response['data'] is Map) {
      return Map<String, dynamic>.from(response['data'] as Map);
    }

    return Map<String, dynamic>.from(response as Map);
  }
}