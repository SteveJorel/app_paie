import 'package:flutter/material.dart';
import '../../core/date_format.dart';
import '../../core/details_sheet.dart';
import '../../core/format.dart';
import '../../core/paged_list.dart';
import 'supplier_portal_repository.dart';

class SupplierSettlementsView extends StatelessWidget {
  const SupplierSettlementsView({super.key});

  // Les colonnes de la table des reversements sont lues de façon tolérante.
  static const _amountKeys = ['net_amount_minor', 'amount_minor', 'total_net_minor', 'total_amount_minor'];
  static const _hiddenKeys = {'supplier_id', 'updated_at'};

  static const _statusLabels = {
    'pending': 'En attente',
    'processing': 'En cours',
    'settled': 'Reversé',
    'paid': 'Versé',
    'failed': 'Échec',
  };

  static const _knownLabels = {
    'id': 'Numéro',
    'reference': 'Référence',
    'status': 'Statut',
    'currency': 'Devise',
    'created_at': 'Créé le',
    'settled_at': 'Reversé le',
    'paid_at': 'Versé le',
  };

  String? _amountKey(Map<String, dynamic> row) {
    for (final key in _amountKeys) {
      if (row[key] != null) return key;
    }
    return null;
  }

  String _label(String key) => _knownLabels[key] ?? key.replaceAll('_', ' ');

  String _value(String key, dynamic value, dynamic currency) {
    if (key.endsWith('_minor')) return formatAmount(value, currency);
    if (key.endsWith('_at') || key.endsWith('_date')) return formatDateTime(value);
    if (key == 'status') return _statusLabels['$value'] ?? '$value';
    return '$value';
  }

  Widget _item(BuildContext context, Map<String, dynamic> row) {
    final currency = row['currency'] ?? '';
    final amountKey = _amountKey(row);
    final title = (row['reference'] ?? row['settlement_reference'] ?? 'Reversement #${row['id']}').toString();
    final status = row['status']?.toString();
    final date = formatDateTime(row['settled_at'] ?? row['paid_at'] ?? row['created_at']);

    return ListTile(
      title: Text(title),
      subtitle: Text(status != null ? '$date · ${_statusLabels[status] ?? status}' : date),
      trailing: amountKey != null
          ? Text(formatAmount(row[amountKey], currency), style: const TextStyle(fontWeight: FontWeight.bold))
          : null,
      onTap: () => showDetailsSheet(
        context,
        title: title,
        rows: [
          for (final entry in row.entries)
            if (entry.value != null && !_hiddenKeys.contains(entry.key))
              MapEntry(_label(entry.key), _value(entry.key, entry.value, currency)),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final repository = SupplierPortalRepository();

    return PagedList(
      loader: repository.settlements,
      itemBuilder: _item,
      emptyMessage: 'Aucun reversement pour le moment.',
      errorHint: "Si votre compte n'est pas rattaché à un fournisseur, contactez l'administrateur.",
    );
  }
}