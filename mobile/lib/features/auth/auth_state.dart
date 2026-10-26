import 'package:flutter/foundation.dart';
import 'auth_repository.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthState extends ChangeNotifier {
  final AuthRepository _repository;

  AuthState({AuthRepository? repository}) : _repository = repository ?? AuthRepository();

  AuthStatus status = AuthStatus.unknown;
  Map<String, dynamic>? user;
  String? errorMessage;
  bool isLoading = false;

  Future<void> bootstrap() async {
    final hasToken = await _repository.hasToken();

    if (!hasToken) {
      status = AuthStatus.unauthenticated;
      notifyListeners();
      return;
    }

    try {
      user = await _repository.me();
      status = AuthStatus.authenticated;
    } catch (_) {
      status = AuthStatus.unauthenticated;
    }

    notifyListeners();
  }

  Future<bool> login(String login, String password) async {
    isLoading = true;
    errorMessage = null;
    notifyListeners();

    try {
      await _repository.login(login: login, password: password);
      try {
        user = await _repository.me();
      } catch (_) {
        user = null;
      }
      status = AuthStatus.authenticated;
      return true;
    } catch (e) {
      errorMessage = e.toString();
      return false;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> register({String? email, String? phone, required String name, required String password}) async {
    isLoading = true;
    errorMessage = null;
    notifyListeners();

    try {
      await _repository.register(email: email, phone: phone, name: name, password: password);
      try {
        user = await _repository.me();
      } catch (_) {
        user = null;
      }
      status = AuthStatus.authenticated;
      return true;
    } catch (e) {
      errorMessage = e.toString();
      return false;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> logout() async {
    await _repository.logout();
    user = null;
    status = AuthStatus.unauthenticated;
    notifyListeners();
  }
}