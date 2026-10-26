import 'package:flutter/material.dart';
import '../../core/api_exception.dart';
import '../orders/order_repository.dart';
import 'idempotency_store.dart';
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
  String _paymentMethod = 'mobile_money';
  bool _isSubmitting = false;
  Map<String, dynamic>? _result;

  final _orderRepository = OrderRepository();
  final _paymentRepository = PaymentRepository();
  final _idempotencyStore = IdempotencyStore();

  String get _currency =>
      (widget.supplier['settlement_currency'] ?? widget.supplier['currency'] ?? 'XAF').toString();

  int _toMinorUnits(double amount) {
    // Hardcodé en attendant un endpoint public exposant le facteur des devises au mobile.
    final factor = _currency == 'NGN' ? 100 : 1;
    return (amount * factor).round();
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    _amountController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isSubmitting = true;
      _result = null;
    });

    try {
      final amountMinor = _toMinorUnits(double.parse(_amountController.text.replaceAll(',', '.')));

      final order = await _orderRepository.create(
        supplierId: widget.supplier['id'] as int,
        description: _descriptionController.text.trim(),
        amountMinor: amountMinor,
      );

      final orderId = order['id'] as int;
      final idempotencyKey = await _idempotencyStore.keyForOrder(orderId);

      final payment = await _paymentRepository.createIntent(
        orderId: orderId,
        paymentMethod: _paymentMethod,
        idempotencyKey: idempotencyKey,
      );

      // Le paiement a atteint un état défini côté serveur : la clé peut être libérée.
      // En cas d'échec réseau avant cette ligne, elle reste stockée et sera réutilisée
      // automatiquement si l'utilisateur retente le paiement sur la même commande.
      await _idempotencyStore.clearForOrder(orderId);

      setState(() => _result = payment);
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
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

  Widget _buildForm() {
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TextFormField(
            controller: _descriptionController,
            decoration: const InputDecoration(labelText: 'Motif du paiement'),
            validator: (value) => (value == null || value.isEmpty) ? 'Champ requis' : null,
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _amountController,
            decoration: InputDecoration(labelText: 'Montant ($_currency)'),
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            validator: (value) {
              if (value == null || value.isEmpty) return 'Champ requis';
              if (double.tryParse(value.replaceAll(',', '.')) == null) return 'Montant invalide';
              return null;
            },
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<String>(
            initialValue: _paymentMethod,
            decoration: const InputDecoration(labelText: 'Moyen de paiement'),
            items: const [
              DropdownMenuItem(value: 'mobile_money', child: Text('Mobile Money')),
              DropdownMenuItem(value: 'card', child: Text('Carte bancaire')),
              DropdownMenuItem(value: 'bank_transfer', child: Text('Virement bancaire')),
            ],
            onChanged: (value) => setState(() => _paymentMethod = value ?? _paymentMethod),
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: _isSubmitting ? null : _submit,
            child: _isSubmitting
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                : const Text('Payer'),
          ),
        ],
      ),
    );
  }

  Widget _buildResult() {
    final status = _result?['status']?.toString() ?? 'inconnu';
    final reference = _result?['reference']?.toString() ?? '-';

    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Icon(Icons.check_circle_outline, size: 64, color: Colors.green),
        const SizedBox(height: 16),
        Text('Paiement $reference', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 8),
        Text('Statut : $status'),
        const SizedBox(height: 24),
        OutlinedButton(
          onPressed: () => Navigator.of(context).popUntil((route) => route.isFirst),
          child: const Text("Retour à l'accueil"),
        ),
      ],
    );
  }
}