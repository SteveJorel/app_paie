import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';

class IdempotencyStore {
  static const _prefix = 'idem_key_order_';
  final _uuid = const Uuid();

  // Générée une seule fois par commande ; renvoyée à l'identique en cas de nouvel essai,
  // exactement comme l'exige le cahier des charges pour les tentatives de paiement.
  Future<String> keyForOrder(int orderId) async {
    final prefs = await SharedPreferences.getInstance();
    final storageKey = '$_prefix$orderId';
    final existing = prefs.getString(storageKey);

    if (existing != null) {
      return existing;
    }

    final generated = _uuid.v4();
    await prefs.setString(storageKey, generated);
    return generated;
  }

  Future<void> clearForOrder(int orderId) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('$_prefix$orderId');
  }
}