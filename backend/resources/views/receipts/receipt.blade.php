<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu {{ $receipt->receipt_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: #555; }
        .badge { display: inline-block; padding: 2px 8px; border: 1px solid #2e7d32; color: #2e7d32; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 6px 4px; border-bottom: 1px solid #ddd; vertical-align: top; }
        td.label { width: 40%; color: #555; }
        .total td { font-size: 15px; font-weight: bold; border-bottom: 2px solid #111; }
        .footer { margin-top: 28px; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    @php
        $methods = ['mobile_money' => 'Mobile Money', 'card' => 'Carte bancaire', 'bank_transfer' => 'Virement bancaire'];
    @endphp

    <h1>{{ $data['company_name'] }}</h1>
    <p class="muted">Reçu de paiement</p>
    <p>N° <strong>{{ $receipt->receipt_number }}</strong> &nbsp; <span class="badge">PAYÉ</span></p>

    <table>
        <tr><td class="label">Référence de la transaction</td><td>{{ $data['payment_reference'] }}</td></tr>
        <tr><td class="label">Référence de la commande</td><td>{{ $data['order_reference'] }}</td></tr>
        <tr><td class="label">Créée le</td><td>{{ \Carbon\Carbon::parse($data['created_at'])->format('d/m/Y H:i') }} (UTC)</td></tr>
        <tr><td class="label">Payée le</td><td>{{ \Carbon\Carbon::parse($data['paid_at'])->format('d/m/Y H:i') }} (UTC)</td></tr>
        <tr><td class="label">Fournisseur</td><td>{{ $data['supplier_name'] }}</td></tr>
        <tr><td class="label">Description</td><td>{{ $data['description'] }}</td></tr>
        <tr><td class="label">Moyen de paiement</td><td>{{ $methods[$data['payment_method']] ?? $data['payment_method'] }}</td></tr>
        <tr><td class="label">Statut</td><td>Payé</td></tr>
        <tr class="total"><td class="label">Montant payé</td><td>{{ \App\Support\Money::format($data['amount_minor'], $data['currency']) }}</td></tr>
    </table>

    <p class="footer">
        Support : {{ $data['support_email'] }} @if(!empty($data['support_phone'])) · {{ $data['support_phone'] }} @endif<br>
        Ce reçu atteste d'un paiement confirmé par le prestataire de paiement. Il est conservé sans modification.
    </p>
</body>
</html>