import 'package:flutter/material.dart';
import 'supplier_repository.dart';
import '../payments/payment_screen.dart';

class SuppliersScreen extends StatefulWidget {
  const SuppliersScreen({super.key});

  @override
  State<SuppliersScreen> createState() => _SuppliersScreenState();
}

class _SuppliersScreenState extends State<SuppliersScreen> {
  final _repository = SupplierRepository();
  late Future<List<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = _repository.listActive();
  }

  String _field(Map<String, dynamic> supplier, List<String> keys, [String fallback = '-']) {
    for (final key in keys) {
      if (supplier[key] != null) return supplier[key].toString();
    }
    return fallback;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Fournisseurs')),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(child: Text('Erreur : ${snapshot.error}'));
          }

          final suppliers = snapshot.data ?? [];

          if (suppliers.isEmpty) {
            return const Center(child: Text('Aucun fournisseur actif pour le moment.'));
          }

          return ListView.separated(
            itemCount: suppliers.length,
            separatorBuilder: (_, _) => const Divider(height: 1),
            itemBuilder: (context, index) {
              final supplier = suppliers[index];
              final name = _field(supplier, ['business_name', 'name']);
              final country = _field(supplier, ['country_code', 'country']);
              final currency = _field(supplier, ['settlement_currency', 'currency']);

              return ListTile(
                title: Text(name),
                subtitle: Text('$country · $currency'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => PaymentScreen(supplier: supplier)),
                ),
              );
            },
          );
        },
      ),
    );
  }
}