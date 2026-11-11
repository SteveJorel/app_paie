import 'package:flutter/material.dart';
import '../../core/format.dart';
import '../../core/paged_list.dart';
import '../suppliers/supplier_repository.dart';
import 'transaction_detail_screen.dart';
import 'transaction_repository.dart';

String _fmtDate(DateTime d) =>
    '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}/${d.year}';

class TransactionsScreen extends StatefulWidget {
  const TransactionsScreen({super.key});

  @override
  State<TransactionsScreen> createState() => _TransactionsScreenState();
}

class _TransactionsScreenState extends State<TransactionsScreen> {
  final _repository = TransactionRepository();
  TransactionFilters _filters = const TransactionFilters();
  int _version = 0; // change à chaque nouvelle recherche : recrée la liste et repart de la page 1

  void _apply(TransactionFilters filters) {
    setState(() {
      _filters = filters;
      _version++;
    });
  }

  Future<void> _openFilters() async {
    final result = await showModalBottomSheet<TransactionFilters>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _FiltersSheet(initial: _filters),
    );

    if (result != null) _apply(result);
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
      case 'partially_refunded':
      case 'disputed':
        return Colors.orange;
      default:
        return Colors.blueGrey;
    }
  }

  Widget _item(BuildContext context, Map<String, dynamic> row) {
    final reference = _field(row, ['reference', 'payment_reference']);
    final description = _field(row, ['description']);
    final supplier = row['supplier'] is Map ? (row['supplier'] as Map)['name'] : null;
    final status = _field(row, ['status']);
    final id = row['id'];

    return ListTile(
      title: Text(reference),
      subtitle: Text(supplier != null ? '$supplier · $description' : description),
      trailing: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Text(formatAmount(row['amount_minor'], row['currency'])),
          Text(
            statusLabel(status),
            style: TextStyle(color: _statusColor(status), fontWeight: FontWeight.bold),
          ),
        ],
      ),
      onTap: id is int
          ? () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => TransactionDetailScreen(paymentId: id)),
              )
          : null,
    );
  }

  Widget _activeFilters() {
    final chips = <String>[
      if (_filters.reference.trim().isNotEmpty) 'Réf. ${_filters.reference.trim()}',
      if (_filters.status != null) statusLabel(_filters.status!),
      if (_filters.supplierName != null) _filters.supplierName!,
      if (_filters.from != null) 'Du ${_fmtDate(_filters.from!)}',
      if (_filters.to != null) 'Au ${_fmtDate(_filters.to!)}',
    ];

    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 0),
      child: Row(
        children: [
          Expanded(
            child: Wrap(
              spacing: 6,
              runSpacing: 0,
              children: [for (final label in chips) Chip(label: Text(label))],
            ),
          ),
          TextButton(
            onPressed: () => _apply(const TransactionFilters()),
            child: const Text('Effacer'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes transactions'),
        actions: [
          IconButton(
            tooltip: 'Rechercher',
            icon: Badge(isLabelVisible: !_filters.isEmpty, child: const Icon(Icons.search)),
            onPressed: _openFilters,
          ),
        ],
      ),
      body: Column(
        children: [
          if (!_filters.isEmpty) _activeFilters(),
          Expanded(
            child: PagedList(
              key: ValueKey(_version),
              loader: (page) => _repository.page(page, _filters),
              itemBuilder: _item,
              emptyMessage: _filters.isEmpty
                  ? 'Aucune transaction pour le moment.'
                  : 'Aucune transaction ne correspond à votre recherche.',
            ),
          ),
        ],
      ),
    );
  }
}

class _FiltersSheet extends StatefulWidget {
  final TransactionFilters initial;

  const _FiltersSheet({required this.initial});

  @override
  State<_FiltersSheet> createState() => _FiltersSheetState();
}

class _FiltersSheetState extends State<_FiltersSheet> {
  static const _statuses = ['paid', 'pending', 'requires_action', 'failed', 'expired', 'refunded', 'disputed'];

  late final TextEditingController _reference;
  late final Future<List<Map<String, dynamic>>> _suppliers;
  String? _status;
  int? _supplierId;
  String? _supplierName;
  DateTime? _from;
  DateTime? _to;
  String? _error;

  @override
  void initState() {
    super.initState();
    _reference = TextEditingController(text: widget.initial.reference);
    _status = widget.initial.status;
    _supplierId = widget.initial.supplierId;
    _supplierName = widget.initial.supplierName;
    _from = widget.initial.from;
    _to = widget.initial.to;
    _suppliers = SupplierRepository().listActive();
  }

  @override
  void dispose() {
    _reference.dispose();
    super.dispose();
  }

  Future<void> _pickDate({required bool isFrom}) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: (isFrom ? _from : _to) ?? now,
      firstDate: DateTime(2024),
      lastDate: DateTime(now.year + 1),
    );

    if (!mounted || picked == null) return;

    setState(() {
      if (isFrom) {
        _from = picked;
      } else {
        _to = picked;
      }
      _error = null;
    });
  }

  Widget _dateRow({required String label, required DateTime? value, required bool isFrom}) {
    return Row(
      children: [
        Expanded(
          child: OutlinedButton.icon(
            icon: const Icon(Icons.calendar_today, size: 16),
            label: Text(value == null ? label : '$label ${_fmtDate(value)}'),
            onPressed: () => _pickDate(isFrom: isFrom),
          ),
        ),
        if (value != null)
          IconButton(
            tooltip: 'Retirer la date',
            icon: const Icon(Icons.close),
            onPressed: () => setState(() {
              if (isFrom) {
                _from = null;
              } else {
                _to = null;
              }
            }),
          ),
      ],
    );
  }

  void _submit() {
    if (_from != null && _to != null && _from!.isAfter(_to!)) {
      setState(() => _error = 'La date de début doit précéder la date de fin.');
      return;
    }

    Navigator.of(context).pop(
      TransactionFilters(
        reference: _reference.text,
        status: _status,
        supplierId: _supplierId,
        supplierName: _supplierName,
        from: _from,
        to: _to,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('Rechercher une transaction', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 12),
            TextField(
              controller: _reference,
              decoration: const InputDecoration(
                labelText: 'Référence exacte',
                helperText: 'Paiement, commande ou numéro de reçu',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String?>(
              initialValue: _status,
              decoration: const InputDecoration(labelText: 'Statut', border: OutlineInputBorder()),
              items: [
                const DropdownMenuItem<String?>(value: null, child: Text('Tous')),
                for (final s in _statuses) DropdownMenuItem<String?>(value: s, child: Text(statusLabel(s))),
              ],
              onChanged: (value) => setState(() => _status = value),
            ),
            const SizedBox(height: 12),
            FutureBuilder<List<Map<String, dynamic>>>(
              future: _suppliers,
              builder: (context, snapshot) {
                if (snapshot.connectionState != ConnectionState.done) {
                  return const LinearProgressIndicator();
                }

                if (snapshot.hasError) {
                  return const Text('Liste des fournisseurs indisponible.');
                }

                final suppliers = snapshot.data ?? [];
                final ids = suppliers.map((s) => (s['id'] as num).toInt()).toList();

                return DropdownButtonFormField<int?>(
                  initialValue: ids.contains(_supplierId) ? _supplierId : null,
                  decoration: const InputDecoration(labelText: 'Fournisseur', border: OutlineInputBorder()),
                  items: [
                    const DropdownMenuItem<int?>(value: null, child: Text('Tous')),
                    for (final s in suppliers)
                      DropdownMenuItem<int?>(
                        value: (s['id'] as num).toInt(),
                        child: Text('${s['business_name'] ?? s['name'] ?? '-'}'),
                      ),
                  ],
                  onChanged: (value) {
                    final match = suppliers.where((s) => (s['id'] as num).toInt() == value);
                    setState(() {
                      _supplierId = value;
                      _supplierName = match.isEmpty ? null : '${match.first['business_name'] ?? match.first['name']}';
                    });
                  },
                );
              },
            ),
            const SizedBox(height: 12),
            _dateRow(label: 'Du', value: _from, isFrom: true),
            _dateRow(label: 'Au', value: _to, isFrom: false),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
            ],
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.of(context).pop(const TransactionFilters()),
                    child: const Text('Réinitialiser'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton(onPressed: _submit, child: const Text('Appliquer')),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}