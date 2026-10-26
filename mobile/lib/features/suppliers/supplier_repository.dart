import '../../core/api_client.dart';

class SupplierRepository {
  final ApiClient _client;

  SupplierRepository({ApiClient? client}) : _client = client ?? ApiClient();

  Future<List<Map<String, dynamic>>> listActive() async {
    final response = await _client.get('/suppliers');
    final data = (response is Map && response['data'] is List) ? response['data'] as List : <dynamic>[];
    return data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }
}