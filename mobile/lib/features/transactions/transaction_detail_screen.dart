import 'package:flutter/material.dart';
import 'transaction_repository.dart';

class TransactionDetailScreen extends StatefulWidget {
  final int paymentId;

  const TransactionDetailScreen({super.key, required this.paymentId});

  @override
  State<TransactionDetailScreen> createState() => _TransactionDetailScreenState();
}

class _TransactionDetailScreenState extends State<TransactionDetailScreen> {
  final _repository = TransactionRepository();
  late Future<Map<String, dynamic>> _future;

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

          return Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _row('Référence', _field(row, ['reference', 'payment_reference'])),
                _row('Commande', _field(row, ['order_reference'])),
                _row('Description', _field(row, ['description'])),
                _row('Montant', '${_field(row, [
                      'amount_minor'
                    ])} ${_field(row, [
                      'currency'
                    ])}'),
                _row('Moyen de paiement', _field(row, ['payment_method'])),
                _row('Statut', _field(row, ['status'])),
                _row('Confirmé le', _field(row, ['confirmed_at'])),
                _row('Créé le', _field(row, ['created_at'])),
              ],
            ),
          );
        },
      ),
    );
  }
}