import 'dart:io';
import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import '../../core/api_exception.dart';
import 'transaction_repository.dart';

class TransactionDetailScreen extends StatefulWidget {
  final int paymentId;

  const TransactionDetailScreen({super.key, required this.paymentId});

  @override
  State<TransactionDetailScreen> createState() => _TransactionDetailScreenState();
}

class _TransactionDetailScreenState extends State<TransactionDetailScreen> {
  static const _statusLabels = {
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

  final _repository = TransactionRepository();
  late Future<Map<String, dynamic>> _future;
  bool _isLoadingReceipt = false;

  @override
  void initState() {
    super.initState();
    _future = _repository.show(widget.paymentId);
  }

  String _field(Map<String, dynamic> row, List<String> keys, [String fallback = '-']) {
    for (final key in keys) {
      if (row[key] != null) return row[key].toString();
    }
    return fallback;
  }

  // Montants en unités mineures : 1 XAF = 1, 1 NGN = 100 kobo (même règle que le reçu PDF).
  String _formatAmount(dynamic minor, dynamic currency) {
    final value = minor is int ? minor : int.tryParse('$minor');
    if (value == null) return '-';

    final code = '$currency';
    final decimals = code == 'NGN' ? 2 : 0;
    final factor = decimals == 0 ? 1 : 100;

    final whole = (value ~/ factor).toString();
    final grouped = whole.replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ' ');
    final fraction = decimals == 0 ? '' : ',${(value % factor).toString().padLeft(2, '0')}';

    return '$grouped$fraction $code';
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey)),
          Flexible(child: Text(value, textAlign: TextAlign.right)),
        ],
      ),
    );
  }

  void _showMessage(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  // L'identifiant du reçu officiel vient de l'URL de téléchargement ("/me/receipts/{id}/download"),
  // à défaut d'une clé "receipt_id", à défaut du détail de la transaction (clé "receipt").
  Future<int?> _officialReceiptId(Map<String, dynamic> receipt) async {
    final url = receipt['download_url'];
    if (url is String) {
      final match = RegExp(r'/me/receipts/(\d+)/download').firstMatch(url);
      if (match != null) return int.parse(match.group(1)!);
    }

    final direct = receipt['receipt_id'];
    if (direct is int) return direct;

    final detail = await _repository.show(widget.paymentId);
    final nested = detail['receipt'];
    if (nested is Map && nested['id'] is int) return nested['id'] as int;

    return null;
  }

  Future<void> _viewReceipt() async {
    setState(() => _isLoadingReceipt = true);

    try {
      // Pour un paiement confirmé, cet appel garantit aussi l'existence du reçu officiel.
      final receipt = await _repository.receiptFor(widget.paymentId);

      if (receipt['is_proof_of_payment'] != true) {
        final label = _field(receipt, ['status_label', 'status'], 'en attente');
        _showMessage('Reçu non définitif pour le moment ($label).');
        return;
      }

      final receiptId = await _officialReceiptId(receipt);

      if (receiptId == null) {
        _showMessage('Le reçu est introuvable. Réessayez dans un instant.');
        return;
      }

      final bytes = await _repository.downloadReceiptPdf(receiptId);
      final dir = await getTemporaryDirectory();
      final file = File('${dir.path}/recu_${widget.paymentId}.pdf');
      await file.writeAsBytes(bytes);

      await SharePlus.instance.share(
        ShareParams(files: [XFile(file.path)], text: 'Reçu de paiement'),
      );
    } on ApiException catch (e) {
      _showMessage(e.message);
    } on SocketException {
      _showMessage('Connexion impossible. Vérifiez votre réseau.');
    } finally {
      if (mounted) setState(() => _isLoadingReceipt = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Détail de la transaction')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(child: Text('Erreur : ${snapshot.error}'));
          }

          final row = snapshot.data ?? {};
          final status = _field(row, ['status']);

          return Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _row('Référence', _field(row, ['reference', 'payment_reference'])),
                _row('Commande', _field(row, ['order_reference'])),
                _row('Description', _field(row, ['description'])),
                _row('Montant', _formatAmount(row['amount_minor'], row['currency'])),
                _row('Moyen de paiement', _field(row, ['payment_method'])),
                _row('Statut', _statusLabels[status] ?? status),
                _row('Confirmé le', _field(row, ['confirmed_at'])),
                _row('Créé le', _field(row, ['created_at'])),
                const SizedBox(height: 24),
                ElevatedButton.icon(
                  onPressed: _isLoadingReceipt ? null : _viewReceipt,
                  icon: _isLoadingReceipt
                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                      : const Icon(Icons.receipt_long),
                  label: const Text('Voir / partager le reçu'),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}