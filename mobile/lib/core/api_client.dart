import 'dart:convert';
import 'package:http/http.dart' as http;
import 'api_config.dart';
import 'api_exception.dart';
import 'token_storage.dart';

class ApiClient {
  final TokenStorage _tokenStorage;

  ApiClient({TokenStorage? tokenStorage}) : _tokenStorage = tokenStorage ?? TokenStorage();

  Future<Map<String, String>> _headers({bool withAuth = true, Map<String, String>? extra}) async {
    final headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...?extra,
    };

    if (withAuth) {
      final token = await _tokenStorage.read();
      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }
    }

    return headers;
  }

  Future<dynamic> get(String path, {bool withAuth = true}) async {
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}$path'),
      headers: await _headers(withAuth: withAuth),
    );
    return _handle(response);
  }

  Future<List<int>> getBytes(String path, {bool withAuth = true}) async {
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}$path'),
      headers: {
        'Accept': 'application/pdf',
        if (withAuth) ...await _authHeaderOnly(),
      },
    );

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return response.bodyBytes;
    }

    String message = 'Une erreur est survenue (${response.statusCode}).';
    try {
      final decoded = jsonDecode(response.body);
      if (decoded is Map && decoded['message'] != null) {
        message = decoded['message'] as String;
      }
    } catch (_) {
      // Le corps n'est pas du JSON (normal pour une réponse binaire d'erreur générique) ; on garde le message par défaut.
    }

    throw ApiException(response.statusCode, message);
  }

  Future<Map<String, String>> _authHeaderOnly() async {
    final token = await _tokenStorage.read();
    return token != null ? {'Authorization': 'Bearer $token'} : {};
  }

  Future<dynamic> post(
    String path,
    Map<String, dynamic> body, {
    bool withAuth = true,
    Map<String, String>? extraHeaders,
  }) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}$path'),
      headers: await _headers(withAuth: withAuth, extra: extraHeaders),
      body: jsonEncode(body),
    );
    return _handle(response);
  }

  Future<dynamic> patch(
    String path,
    Map<String, dynamic> body, {
    bool withAuth = true,
    Map<String, String>? extraHeaders,
  }) async {
    final response = await http.patch(
      Uri.parse('${ApiConfig.baseUrl}$path'),
      headers: await _headers(withAuth: withAuth, extra: extraHeaders),
      body: jsonEncode(body),
    );
    return _handle(response);
  }

  dynamic _handle(http.Response response) {
    final decoded = response.body.isNotEmpty ? jsonDecode(response.body) : null;

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return decoded;
    }

    final message = (decoded is Map && decoded['message'] != null)
        ? decoded['message'] as String
        : 'Une erreur est survenue (${response.statusCode}).';

    final errors = (decoded is Map && decoded['errors'] != null)
        ? Map<String, dynamic>.from(decoded['errors'] as Map)
        : null;

    throw ApiException(response.statusCode, message, errors: errors);
  }
}