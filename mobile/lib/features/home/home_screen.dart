import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../auth/auth_state.dart';
import '../suppliers/suppliers_screen.dart';
import '../transactions/transactions_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    final name = auth.user?['name'] as String?;

    return Scaffold(
      appBar: AppBar(
        title: const Text('App Paie'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => context.read<AuthState>().logout(),
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(name != null ? 'Bonjour, $name !' : 'Connecté.'),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const SuppliersScreen()),
              ),
              child: const Text('Voir les fournisseurs'),
            ),
            const SizedBox(height: 12),
            OutlinedButton(
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const TransactionsScreen()),
              ),
              child: const Text('Mes transactions'),
            ),
          ],
        ),
      ),
    );
  }
}