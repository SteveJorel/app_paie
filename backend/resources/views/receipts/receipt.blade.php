@php
    $methods = ['mobile_money' => 'Mobile Money', 'card' => 'Carte bancaire', 'bank_transfer' => 'Virement bancaire'];

    // Logo optionnel, intégré au PDF en base64 : aucun fichier externe n'est nécessaire.
    $logoFile = public_path('images/logo.png');
    $logo = is_file($logoFile) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoFile)) : null;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu {{ $receipt->receipt_number }}</title>
    <style>
        /* Marge basse réservée au pied de page fixe */
        @page { margin: 40px 40px 120px 40px; }

        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #111; }
        p { margin: 0; }

        .center { text-align: center; }
        .brand { font-size: 28px; font-weight: bold; letter-spacing: 5px; }
        .network { font-size: 13px; font-weight: bold; letter-spacing: 1px; margin-top: 4px; }
        .tagline { font-size: 10px; font-style: italic; color: #555; margin-top: 3px; }
        .rule { border-top: 2px solid #1b5e20; margin: 14px 0; }
        .doc-title { font-size: 17px; font-weight: bold; color: #1b5e20; letter-spacing: 1px; }
        .qualified { font-size: 10px; color: #333; margin-top: 5px; }

        .ref { margin-top: 18px; }
        .badge { display: inline-block; padding: 2px 8px; border: 1px solid #2e7d32; color: #2e7d32; font-weight: bold; }

        table.lines { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.lines td { padding: 6px 4px; border-bottom: 1px solid #ddd; vertical-align: top; }
        td.label { width: 40%; color: #555; }
        tr.total td { font-size: 15px; font-weight: bold; border-bottom: 2px solid #111; }

        footer { position: fixed; bottom: -95px; left: 0; right: 0; height: 85px;
                 border-top: 1px solid #bbb; padding-top: 6px;
                 font-size: 8.5px; color: #555; text-align: center; }
        footer p { margin: 2px 0; }
        footer .notice { font-weight: bold; color: #111; }
    </style>
</head>
<body>
    <footer>
        <p class="notice">It must be furnished to HINCO the essential identification(s) of the article duly paid for forty-eight (48) hours before entry into the import country.</p>
        <p>Support : {{ $data['support_email'] }}@if(!empty($data['support_phone'])) &middot; {{ $data['support_phone'] }}@endif</p>
        <p>Ce reçu atteste d'un paiement confirmé par le prestataire de paiement. Il est conservé sans modification.</p>
    </footer>

    <div class="center">
        @if($logo)<img src="{{ $logo }}" style="height: 50px; margin-bottom: 6px;" alt=""><br>@endif
        <p class="brand">HINCO</p>
        <p class="network">GLOBAL FACDTTE NETWORK</p>
        <p class="tagline">Fight Against Custom Duty, Tariffs and Taxes Evasion</p>
    </div>

    <div class="rule"></div>

    <div class="center">
        <p class="doc-title">QUACTTACD RECEIPT</p>
        <p class="qualified">Qualified for Clearance of Tariffs, Taxes &amp; Custom Duty by HINCO on oath of fidelity</p>
    </div>

    <p class="ref">N° <strong>{{ $receipt->receipt_number }}</strong> &nbsp; <span class="badge">PAYÉ</span></p>

    <table class="lines">
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
</body>
</html>
