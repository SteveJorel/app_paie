import '../../core/api_client.dart';

class PaymentRepository {
  final ApiClient _client;

  PaymentRepository({ApiClient? client}) : _client = client ?? ApiClient();

  Future<Map<String, dynamic>> createIntent({
    required int orderId,
    required String paymentMethod,
    required String idempotencyKey,
  }) async {
    final response = await _client.post(
      '/orders/$orderId/payment-intent',
      {'payment_method': paymentMethod},
      extraHeaders: {'Idempotency-Key': idempotencyKey},
    );

    if (response is Map && response['data'] is Map) {
      return Map<String, dynamic>.from(response['data'] as Map);
    }

    return Map<String, dynamic>.from(response as Map);
  }
}