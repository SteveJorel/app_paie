import '../../core/api_client.dart';

class SupportRepository {
  final ApiClient _client;

  SupportRepository({ApiClient? client}) : _client = client ?? ApiClient();

  /// Coordonnées du support : { company_name, support_email, support_phone }.
  Future<Map<String, dynamic>> info() async {
    final response = await _client.get('/support', withAuth: false);

    if (response is Map && response['data'] is Map) {
      return Map<String, dynamic>.from(response['data'] as Map);
    }

    throw StateError('Réponse /support inattendue.');
  }
}