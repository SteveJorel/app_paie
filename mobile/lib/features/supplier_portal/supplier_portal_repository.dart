import '../../core/api_client.dart';
import '../../core/paged_list.dart';

class SupplierPortalRepository {
  final ApiClient _client;

  SupplierPortalRepository({ApiClient? client}) : _client = client ?? ApiClient();

  Future<PagedResult> transactions(int page) async {
    return PagedResult.fromJson(await _client.get('/supplier/transactions?page=$page&per_page=20'));
  }

  Future<PagedResult> settlements(int page) async {
    return PagedResult.fromJson(await _client.get('/supplier/settlements?page=$page&per_page=20'));
  }
}