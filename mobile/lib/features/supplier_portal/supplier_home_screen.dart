import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../auth/auth_state.dart';
import 'supplier_settlements_view.dart';
import 'supplier_transactions_view.dart';

class SupplierHomeScreen extends StatelessWidget {
  const SupplierHomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Espace fournisseur'),
          actions: [
            IconButton(
              icon: const Icon(Icons.logout),
              tooltip: 'Se déconnecter',
              onPressed: () => context.read<AuthState>().logout(),
            ),
          ],
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Transactions'),
              Tab(text: 'Reversements'),
            ],
          ),
        ),
        body: const TabBarView(
          children: [
            SupplierTransactionsView(),
            SupplierSettlementsView(),
          ],
        ),
      ),
    );
  }
}