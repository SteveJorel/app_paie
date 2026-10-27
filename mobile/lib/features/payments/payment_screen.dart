import 'package:flutter/material.dart';
import '../../core/api_exception.dart';
import '../../core/format.dart';
import '../orders/order_repository.dart';
import 'idempotency_store.dart';
import 'payment_method_repository.dart';
import 'payment_repository.dart';

class PaymentScreen extends StatefulWidget {
  final Map<String, dynamic> supplier;

  const PaymentScreen({super.key, required this.supplier});

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  final _formKey = GlobalKey<FormState>();
  final _descriptionController = TextEditingController();
  final _amountController = TextEditingController();

  final _orderRepository = OrderRepository();
  final _paymentRepository = PaymentRepository();
  final _methodRepository = PaymentMethodRepository();
  final _idempotencyStore = IdempotencyStore();

  List<Map<String, dynamic>> _methods = [];
  String? _paymentMethod;
  bool _isLoadingMethods = true;
  String? _methodsError;

  bool _isSubmitting = false;
  Map<String, dynamic>? _result;

  // Commande déjà créée pour la saisie en cours : elle est réutilisée si l'utilisateur réessaie,
  // pour ne jamais créer deux commandes (donc deux paiements) pour la même saisie.
  Map<String, dynamic>? _order;
  String? _orderFingerprint;

  String get _currency =>
      (widget.supplier['settlement_currency'] ?? widget.supplier['currency'] ?? 'XAF').toString();

  String get _country => (widget.supplier['country_code'] ?? widget.supplier['country'] ?? '').toString();

  @override
  void initState() {
    super.initState();
    _loadMethods();
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    _amountController.dispose();
    super.dispose();
  }

  void _showMessage(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _loadMethods() async {
    setState(() {
      _isLoadingMethods = true;
      _methodsError = null;
    });

    try {
      final methods = await _methodRepository.listForCountry(_country);
      if (!mounted) return;

      setState(() {
        _methods = methods;
        final codes = methods.map((m) => m['code'].toString()).toList();
        if (_paymentMethod == null || !codes.contains(_paymentMethod)) {
          _paymentMethod = codes.isNotEmpty ? codes.first : null;
        }
      });
    } on ApiException catch (e) {
      if (mounted) setState(() => _methodsError = e.message);
    } catch (_) {
      if (mounted) setState(() => _methodsError = 'Impossible de charger les moyens de paiement.');
    } finally {
      if (mounted) setState(() => _isLoadingMethods = false);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final method = _paymentMethod;
    if (method == null) return;

    setState(() {
      _isSubmitting = true;
      _result = null;
    });

    try {
      final amountMinor = parseAmountToMinor(_amountController.text, _currency)!;
      final description = _descriptionController.text.trim();
      final fingerprint = '${widget.supplier['id']}|$description|$amountMinor';

      if (_order == null || _orderFingerprint != fingerprint) {
        _order = await _orderRepository.create(
          supplierId: widget.supplier['id'] as int,
          description: description,
          amountMinor: amountMinor,
        );
        _orderFingerprint = fingerprint;
      }

      final orderId = _order!['id'] as int;
      final idempotencyKey = await _idempotencyStore.keyForOrder(orderId);

      final payment = await _paymentRepository.createIntent(
        orderId: orderId,
        paymentMethod: method,
        idempotencyKey: idempotencyKey,
      );

      // Le paiement a atteint un état défini côté serveur : la clé peut être libérée.
      // En cas d'échec réseau avant cette ligne, elle reste stockée et sera réutilisée
      // automatiquement si l'utilisateur retente le paiement sur la même commande.
      await _idempotencyStore.clearForOrder(orderId);

      if (mounted) setState(() => _result = payment);
    } on ApiException catch (e) {
      _showMessage(e.message);
    } catch (_) {
      _showMessage('Connexion impossible. Vérifiez votre réseau et réessayez.');
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final name = (widget.supplier['business_name'] ?? widget.supplier['name'] ?? 'Fournisseur').toString();

    return Scaffold(
      appBar: AppBar(title: Text('Payer $name')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: _result != null ? _buildResult() : _buildForm(),
      ),
    );
  }

  Widget _buildMethodsSection() {
    if (_isLoadingMethods) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 12),
        child: Row(
          children: [
            SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)),
            SizedBox(width: 12),
            Text('Chargement des moyens de paiement…'),
          ],
        ),
      );
    }

    if (_methodsError != null) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(_methodsError!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
          TextButton(onPressed: _loadMethods, child: const Text('Réessayer')),
        ],
      );
    }

    if (_methods.isEmpty) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 12),
        child: Text('Aucun moyen de paiement disponible pour ce pays pour le moment.'),
      );
    }

    return DropdownButtonFormField<String>(
      key: ValueKey(_methods.map((m) => m['code']).join(',')),
      initialValue: _paymentMethod,
      decoration: const InputDecoration(labelText: 'Moyen de paiement'),
      items: _methods.map((m) {
        final code = m['code'].toString();
        return DropdownMenuItem<String>(
          value: code,
          child: Text((m['name'] ?? paymentMethodLabel(code)).toString()),
        );
      }).toList(),
      onChanged: (value) => setState(() => _paymentMethod = value ?? _paymentMethod),
    );
  }

  Widget _buildForm() {
    final canSubmit = !_isSubmitting && !_isLoadingMethods && _paymentMethod != null;

    return Form(
      key: _formKey,
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TextFormField(
              controller: _descriptionController,
              decoration: const InputDecoration(labelText: 'Motif du paiement'),
              validator: (value) => (value == null || value.trim().isEmpty) ? 'Champ requis' : null,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _amountController,
              decoration: InputDecoration(labelText: 'Montant ($_currency)'),
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              validator: (value) {
                if (value == null || value.trim().isEmpty) return 'Champ requis';
                if (parseAmountToMinor(value, _currency) == null) {
                  return _currency == 'NGN'
                      ? 'Montant invalide (2 décimales au maximum)'
                      : 'Montant invalide (nombre entier, sans décimales)';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            _buildMethodsSection(),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: canSubmit ? _submit : null,
              child: _isSubmitting
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Text('Payer'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildResult() {
    final status = _result?['status']?.toString() ?? 'inconnu';
    final reference = _result?['reference']?.toString() ?? '-';
    final amount = formatAmount(_result?['amount_minor'], _result?['currency']);

    final failed = status == 'failed' || status == 'expired';
    final paid = status == 'paid';
    final icon = failed ? Icons.error_outline : (paid ? Icons.check_circle_outline : Icons.hourglass_top);
    final color = failed ? Colors.red : (paid ? Colors.green : Colors.orange);

    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(icon, size: 64, color: color),
        const SizedBox(height: 16),
        Text('Paiement $reference', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 8),
        Text('Montant : $amount'),
        Text('Statut : ${statusLabel(status)}'),
        if (!failed && !paid) ...[
          const SizedBox(height: 8),
          const Text('Nous attendons la confirmation du paiement.'),
        ],
        const SizedBox(height: 24),
        OutlinedButton(
          onPressed: () => Navigator.of(context).popUntil((route) => route.isFirst),
          child: const Text("Retour à l'accueil"),
        ),
      ],
    );
  }
}