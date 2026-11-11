import 'package:flutter/material.dart';
import '../../core/api_exception.dart';
import '../../core/date_format.dart';
import 'supplier_portal_repository.dart';

class SupplierProfileView extends StatefulWidget {
  const SupplierProfileView({super.key});

  @override
  State<SupplierProfileView> createState() => _SupplierProfileViewState();
}

class _SupplierProfileViewState extends State<SupplierProfileView> {
  final _repository = SupplierPortalRepository();
  late Future<Map<String, dynamic>> _future;

  static const _countries = {'CM': 'Cameroun (CM)', 'NG': 'Nigeria (NG)'};
  static const _statuses = {
    'pending': 'En attente de validation',
    'active': 'Actif',
    'suspended': 'Suspendu',
  };

  @override
  void initState() {
    super.initState();
    _future = _repository.profile();
  }

  Future<void> _reload() async {
    final next = _repository.profile();
    setState(() => _future = next);

    try {
      await next;
    } catch (_) {
      // L'erreur est affichée par le FutureBuilder.
    }
  }

  String _text(dynamic value) {
    final text = '${value ?? ''}'.trim();
    return text.isEmpty ? '-' : text;
  }

  Widget _row(String label, String value) {
    return ListTile(
      title: Text(label, style: Theme.of(context).textTheme.bodySmall),
      subtitle: Text(value, style: Theme.of(context).textTheme.bodyLarge),
    );
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, dynamic>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }

        if (snapshot.hasError) {
          final error = snapshot.error;
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    error is ApiException ? error.message : 'Impossible de charger votre profil.',
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton(onPressed: _reload, child: const Text('Réessayer')),
                ],
              ),
            ),
          );
        }

        final profile = snapshot.data ?? {};
        final status = '${profile['status'] ?? ''}';

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: [
              _row('Raison sociale', _text(profile['business_name'])),
              _row('Statut', _statuses[status] ?? _text(status)),
              _row('Pays', _countries['${profile['country_code']}'] ?? _text(profile['country_code'])),
              _row('Catégorie', _text(profile['category'])),
              _row('Devise de reversement', _text(profile['settlement_currency'])),
              _row('E-mail de contact', _text(profile['contact_email'])),
              _row('Téléphone de contact', _text(profile['contact_phone'])),
              _row('Inscrit le', formatDateTime(profile['created_at'])),
              const Padding(
                padding: EdgeInsets.all(16),
                child: Text(
                  'Pour modifier ces informations, contactez l\'administrateur de la plateforme.',
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}