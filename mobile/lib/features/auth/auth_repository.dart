import '../../core/api_client.dart';
import '../../core/token_storage.dart';

class AuthRepository {
  final ApiClient _client;
  final TokenStorage _tokenStorage;

  AuthRepository({ApiClient? client, TokenStorage? tokenStorage})
      : _client = client ?? ApiClient(),
        _tokenStorage = tokenStorage ?? TokenStorage();

  // Le backend renvoie { "user": {...} } (login, register et /auth/me) ; on accepte aussi { "data": {...} }.
  Map<String, dynamic>? _extractUser(dynamic response) {
    if (response is Map) {
      if (response['user'] is Map) return Map<String, dynamic>.from(response['user'] as Map);
      if (response['data'] is Map) return Map<String, dynamic>.from(response['data'] as Map);
    }
    return null;
  }

  Future<Map<String, dynamic>?> register({
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

    await _tokenStorage.save(response['token'] as String);
    return _extractUser(response);
  }

  Future<Map<String, dynamic>?> login({required String login, required String password}) async {
    final response = await _client.post('/auth/login', {
      'login': login,
      'password': password,
    }, withAuth: false);

    await _tokenStorage.save(response['token'] as String);
    return _extractUser(response);
  }

  Future<Map<String, dynamic>> me() async {
    final user = _extractUser(await _client.get('/auth/me'));

    if (user == null) {
      throw StateError('Réponse /auth/me inattendue.');
    }

    return user;
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