import '../../core/api_client.dart';

class PaymentMethodRepository {
  final ApiClient _client;

  PaymentMethodRepository({ApiClient? client}) : _client = client ?? ApiClient();

  /// Moyens de paiement actifs pour un pays (code ISO sur 2 lettres, ex. "CM").
  Future<List<Map<String, dynamic>>> listForCountry(String countryCode) async {
    final country = Uri.encodeQueryComponent(countryCode.toUpperCase());
    final response = await _client.get('/payment-methods?country=$country');
    final data = (response is Map && response['data'] is List) ? response['data'] as List : <dynamic>[];
    return data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }
}