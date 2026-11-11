import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../core/api_exception.dart';
import 'support_repository.dart';

class HelpScreen extends StatefulWidget {
  const HelpScreen({super.key});

  @override
  State<HelpScreen> createState() => _HelpScreenState();
}

class _HelpScreenState extends State<HelpScreen> {
  final _repository = SupportRepository();
  late Future<Map<String, dynamic>> _future;

  static const _faq = <(String, String)>[
    (
      'Mon paiement est « En attente »',
      'Votre paiement est en cours de confirmation par le prestataire. Ouvrez « Mes transactions » '
          'et tirez la liste vers le bas pour l\'actualiser. Un paiement qui n\'est jamais confirmé '
          'finit par expirer automatiquement. Si le statut vous semble anormal, contactez le support '
          'en indiquant la référence du paiement.',
    ),
    (
      'Où trouver mon reçu ?',
      'Ouvrez la transaction dans « Mes transactions », puis le reçu : vous pouvez le consulter, '
          'le télécharger ou le partager.',
    ),
    (
      'Mon paiement est « Remboursé » ou « Contesté »',
      'Ces statuts sont décidés par l\'administration de la plateforme. Un remboursement est total '
          'et donne lieu à un second reçu. Pour toute question, contactez le support avec la '
          'référence du paiement.',
    ),
    (
      'Comment retrouver une ancienne transaction ?',
      'Dans « Mes transactions », touchez la loupe : vous pouvez chercher par référence exacte, '
          'statut, fournisseur ou période.',
    ),
  ];

  @override
  void initState() {
    super.initState();
    _future = _repository.info();
  }

  void _reload() {
    setState(() => _future = _repository.info());
  }

  Future<void> _copy(String label, String value) async {
    await Clipboard.setData(ClipboardData(text: value));

    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$label copié.')));
  }

  Widget _contact(Map<String, dynamic> info) {
    final email = '${info['support_email'] ?? ''}'.trim();
    final phone = '${info['support_phone'] ?? ''}'.trim();

    if (email.isEmpty && phone.isEmpty) {
      return const ListTile(
        leading: Icon(Icons.info_outline),
        title: Text('Les coordonnées du support ne sont pas encore renseignées.'),
      );
    }

    return Column(
      children: [
        if (email.isNotEmpty)
          ListTile(
            leading: const Icon(Icons.email_outlined),
            title: Text(email),
            subtitle: const Text('E-mail'),
            trailing: IconButton(
              tooltip: 'Copier l\'e-mail',
              icon: const Icon(Icons.copy),
              onPressed: () => _copy('E-mail', email),
            ),
          ),
        if (phone.isNotEmpty)
          ListTile(
            leading: const Icon(Icons.phone_outlined),
            title: Text(phone),
            subtitle: const Text('Téléphone'),
            trailing: IconButton(
              tooltip: 'Copier le numéro',
              icon: const Icon(Icons.copy),
              onPressed: () => _copy('Numéro', phone),
            ),
          ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Aide')),
      body: ListView(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 4),
            child: Text('Contacter le support', style: Theme.of(context).textTheme.titleMedium),
          ),
          FutureBuilder<Map<String, dynamic>>(
            future: _future,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Padding(padding: EdgeInsets.all(16), child: LinearProgressIndicator());
              }

              if (snapshot.hasError) {
                final error = snapshot.error;
                return Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(error is ApiException ? error.message : 'Impossible de charger les coordonnées du support.'),
                      const SizedBox(height: 8),
                      OutlinedButton(onPressed: _reload, child: const Text('Réessayer')),
                    ],
                  ),
                );
              }

              return _contact(snapshot.data ?? {});
            },
          ),
          const Divider(),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
            child: Text('Questions fréquentes', style: Theme.of(context).textTheme.titleMedium),
          ),
          for (final (question, answer) in _faq)
            ExpansionTile(
              title: Text(question),
              childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
              expandedCrossAxisAlignment: CrossAxisAlignment.start,
              children: [Text(answer)],
            ),
        ],
      ),
    );
  }
}