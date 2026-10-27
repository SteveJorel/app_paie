import 'package:flutter/material.dart';
import '../../core/date_format.dart';
import '../../core/details_sheet.dart';
import '../../core/format.dart';
import '../../core/paged_list.dart';
import 'supplier_portal_repository.dart';

class SupplierTransactionsView extends StatelessWidget {
  const SupplierTransactionsView({super.key});

  static const _allocationLabels = {
    'pending': 'En attente',
    'settlement_pending': 'Reversement en cours',
    'settled': 'Reversé',
    'refunded': 'Remboursé',
    'reversed': 'Annulé',
  };

  String _allocationLabel(String status) => _allocationLabels[status] ?? status;

  Widget _item(BuildContext context, Map<String, dynamic> row) {
    final currency = row['currency'];
    final description = (row['description'] ?? row['payment_reference'] ?? '-').toString();
    final allocation = _allocationLabel('${row['allocation_status']}');

    return ListTile(
      title: Text(description, maxLines: 1, overflow: TextOverflow.ellipsis),
      subtitle: Text('${formatDateTime(row['created_at'])} · $allocation'),
      trailing: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Text(
            formatAmount(row['net_amount_minor'], currency),
            style: const TextStyle(fontWeight: FontWeight.bold),
          ),
          Text(statusLabel('${row['payment_status']}'), style: const TextStyle(fontSize: 12, color: Colors.grey)),
        ],
      ),
      onTap: () => showDetailsSheet(
        context,
        title: description,
        rows: [
          MapEntry('Référence du paiement', '${row['payment_reference']}'),
          MapEntry('Commande', '${row['order_reference']}'),
          MapEntry('Statut du paiement', statusLabel('${row['payment_status']}')),
          MapEntry('Statut de la part', allocation),
          MapEntry('Montant alloué', formatAmount(row['gross_amount_minor'], currency)),
          MapEntry('Frais', formatAmount(row['fees_minor'], currency)),
          MapEntry('Net à percevoir', formatAmount(row['net_amount_minor'], currency)),
          MapEntry('Confirmé le', formatDateTime(row['confirmed_at'])),
          MapEntry('Créé le', formatDateTime(row['created_at'])),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final repository = SupplierPortalRepository();

    return PagedList(
      loader: repository.transactions,
      itemBuilder: _item,
      emptyMessage: 'Aucune transaction pour le moment.',
      errorHint: "Si votre compte n'est pas rattaché à un fournisseur, contactez l'administrateur.",
    );
  }
}