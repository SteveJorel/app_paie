// Fonctions de présentation partagées par les écrans (montants, statuts, moyens de paiement).
// Règle : les montants circulent toujours en entiers, en unités mineures
// (1 XAF = 1, 1 NGN = 100 kobo), comme sur le serveur.

const int _maxAmountMinor = 100000000000000; // plafond du serveur (10^14)

int currencyDecimals(String currency) => currency == 'NGN' ? 2 : 0;

/// Affiche un montant en unités mineures : 10000 XAF -> "10 000 XAF", 1000000 NGN -> "10 000,00 NGN".
String formatAmount(dynamic minor, dynamic currency) {
  final value = minor is int ? minor : int.tryParse('$minor');
  if (value == null) return '-';

  final code = '$currency';
  final decimals = currencyDecimals(code);
  final factor = decimals == 0 ? 1 : 100;

  final whole = (value ~/ factor).toString();
  final grouped = whole.replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (_) => ' ');
  final fraction = decimals == 0 ? '' : ',${(value % factor).toString().padLeft(2, '0')}';

  return '$grouped$fraction $code';
}

/// Convertit une saisie ("1 500,50") en unités mineures, sans nombre à virgule.
/// Retourne null si la saisie est invalide (format, trop de décimales, nul ou trop grand).
int? parseAmountToMinor(String input, String currency) {
  final text = input.replaceAll(RegExp(r'\s'), '').replaceAll(',', '.');
  final match = RegExp(r'^(\d+)(?:\.(\d+))?$').firstMatch(text);
  if (match == null) return null;

  final decimals = currencyDecimals(currency);
  final whole = match.group(1)!;
  final fraction = match.group(2) ?? '';

  if (fraction.length > decimals) return null;

  final value = int.tryParse('$whole${fraction.padRight(decimals, '0')}');
  if (value == null || value <= 0 || value > _maxAmountMinor) return null;

  return value;
}

const _statusLabels = {
  'created': 'En attente',
  'pending': 'En attente',
  'requires_action': 'Action requise',
  'paid': 'Payé',
  'failed': 'Échec',
  'expired': 'Expiré',
  'partially_refunded': 'Partiellement remboursé',
  'refunded': 'Remboursé',
  'disputed': 'Contesté',
};

String statusLabel(String status) => _statusLabels[status] ?? status;

const _methodLabels = {
  'mobile_money': 'Mobile Money',
  'card': 'Carte bancaire',
  'bank_transfer': 'Virement bancaire',
};

String paymentMethodLabel(String code) => _methodLabels[code] ?? code;