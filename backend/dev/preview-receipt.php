<?php

use App\Models\Receipt;
use App\Services\Payments\ReceiptPdf;

// Reçu fictif, jamais enregistré en base : sert uniquement à prévisualiser la mise en page.
$receipt = new Receipt([
    'receipt_number' => 'RC-2026-00000042',
    'issued_at' => now(),
    'snapshot' => [
        'company_name' => config('receipts.company_name'),
        'support_email' => config('receipts.support_email'),
        'support_phone' => config('receipts.support_phone'),
        'receipt_number' => 'RC-2026-00000042',
        'payment_reference' => 'PAY-01KTESTEXEMPLE0000000001',
        'order_reference' => 'ORD-01KTESTEXEMPLE0000000001',
        'supplier_name' => 'Boulangerie Sahel',
        'description' => 'Achat de baguettes et de viennoiseries pour la semaine',
        'amount_minor' => 10000,
        'currency' => 'XAF',
        'payment_method' => 'mobile_money',
        'status' => 'paid',
        'created_at' => now()->subMinutes(5)->toIso8601String(),
        'paid_at' => now()->toIso8601String(),
    ],
]);

file_put_contents(getenv('USERPROFILE').'\\Desktop\\recu_test.pdf', ReceiptPdf::render($receipt));

echo 'OK';

