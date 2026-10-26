import '../../core/api_client.dart';
import '../../core/token_storage.dart';

class AuthRepository {
  final ApiClient _client;
  final TokenStorage _tokenStorage;

  AuthRepository({ApiClient? client, TokenStorage? tokenStorage})
      : _client = client ?? ApiClient(),
        _tokenStorage = tokenStorage ?? TokenStorage();

  Future<void> register({
    String? email,
    String? phone,
    required String name,
    required String password,
  }) async {
    final response = await _client.post('/auth/register', {
      if (email != null && email.isNotEmpty) 'email': email,
      if (phone != null && phone.isNotEmpty) 'phone': phone,
      'name': name,
      'password': password,
    }, withAuth: false);

    final token = response['token'] as String;
    await _tokenStorage.save(token);
  }

  Future<void> login({required String login, required String password}) async {
    final response = await _client.post('/auth/login', {
      'login': login,
      'password': password,
    }, withAuth: false);

    final token = response['token'] as String;
    await _tokenStorage.save(token);
  }

  Future<Map<String, dynamic>> me() async {
    final response = await _client.get('/auth/me');

    if (response is Map && response['data'] is Map) {
      return Map<String, dynamic>.from(response['data'] as Map);
    }

    return Map<String, dynamic>.from(response as Map);
  }

  Future<void> logout() async {
    try {
      await _client.post('/auth/logout', {});
    } finally {
      await _tokenStorage.clear();
    }
  }

  Future<bool> hasToken() async {
    final token = await _tokenStorage.read();
    return token != null;
  }
}