import 'package:flutter/material.dart';
import 'transaction_repository.dart';
import 'transaction_detail_screen.dart';

class TransactionsScreen extends StatefulWidget {
  const TransactionsScreen({super.key});

  @override
  State<TransactionsScreen> createState() => _TransactionsScreenState();
}

class _TransactionsScreenState extends State<TransactionsScreen> {
  final _repository = TransactionRepository();
  late Future<List<Map<String, dynamic>>> _future;
  String? _statusFilter;

  @override
  void initState() {
    super.initState();
    _load();
  }

  void _load() {
    setState(() => _future = _repository.list(status: _statusFilter));
  }

  String _field(Map<String, dynamic> row, List<String> keys, [String fallback = '-']) {
    for (final key in keys) {
      if (row[key] != null) return row[key].toString();
    }
    return fallback;
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'paid':
        return Colors.green;
      case 'failed':
      case 'expired':
        return Colors.red;
      case 'refunded':
      case 'disputed':
        return Colors.orange;
      default:
        return Colors.blueGrey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes transactions'),
        actions: [
          PopupMenuButton<String?>(
            icon: const Icon(Icons.filter_list),
            onSelected: (value) {
              setState(() => _statusFilter = value);
              _load();
            },
            itemBuilder: (context) => const [
              PopupMenuItem(value: null, child: Text('Toutes')),
              PopupMenuItem(value: 'paid', child: Text('Payées')),
              PopupMenuItem(value: 'pending', child: Text('En attente')),
              PopupMenuItem(value: 'failed', child: Text('Échouées')),
              PopupMenuItem(value: 'refunded', child: Text('Remboursées')),
            ],
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => _load(),
        child: FutureBuilder<List<Map<String, dynamic>>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }

            if (snapshot.hasError) {
              return Center(child: Text('Erreur : ${snapshot.error}'));
            }

            final rows = snapshot.data ?? [];

            if (rows.isEmpty) {
              return ListView(
                children: const [
                  SizedBox(height: 120),
                  Center(child: Text('Aucune transaction pour le moment.')),
                ],
              );
            }

            return ListView.separated(
              itemCount: rows.length,
              separatorBuilder: (_, _) => const Divider(height: 1),
              itemBuilder: (context, index) {
                final row = rows[index];
                final reference = _field(row, ['reference', 'payment_reference']);
                final description = _field(row, ['description']);
                final amount = _field(row, ['amount_minor']);
                final currency = _field(row, ['currency']);
                final status = _field(row, ['status']);
                final id = row['id'];

                return ListTile(
                  title: Text(reference),
                  subtitle: Text(description),
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text('$amount $currency'),
                      Text(status, style: TextStyle(color: _statusColor(status), fontWeight: FontWeight.bold)),
                    ],
                  ),
                  onTap: id is int
                      ? () => Navigator.of(context).push(
                            MaterialPageRoute(builder: (_) => TransactionDetailScreen(paymentId: id)),
                          )
                      : null,
                );
              },
            );
          },
        ),
      ),
    );
  }
}