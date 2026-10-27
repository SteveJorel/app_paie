import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../auth/auth_state.dart';
import '../suppliers/suppliers_screen.dart';
import '../supplier_portal/supplier_home_screen.dart';
import '../transactions/transactions_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    final role = auth.user?['role'] as String?;
    final name = auth.user?['name'] as String?;

    // Un fournisseur arrive directement dans son espace.
    if (role == 'supplier') return const SupplierHomeScreen();

    final isStaff = role == 'admin' || role == 'finance';

    return Scaffold(
      appBar: AppBar(
        title: const Text('App Paie'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Se déconnecter',
            onPressed: () => context.read<AuthState>().logout(),
          ),
        ],
      ),
      body: Center(
        child: isStaff
            ? const Padding(
                padding: EdgeInsets.all(24),
                child: Text(
                  "Cette application est destinée aux clients et aux fournisseurs. "
                  "L'administration se fait depuis le portail web.",
                  textAlign: TextAlign.center,
                ),
              )
            : Column(
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