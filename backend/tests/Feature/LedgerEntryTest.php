<?php

namespace Tests\Feature;

use App\Models\CommissionRule;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Ledger\LedgerRecorder;
use App\Services\Payments\WebhookSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LedgerEntryTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'secret-de-test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.providers.sandbox.webhook_secret' => self::SECRET]);
    }

    private function payAndConfirm(Payment $payment): void
    {
        $event = [
            'id' => 'evt_'.$payment->id,
            'type' => 'payment.succeeded',
            'data' => [
                'provider_reference' => $payment->provider_reference,
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
            ],
        ];
        $body = json_encode($event);
        $ts = (string) now()->getTimestamp();

        $this->call('POST', '/api/v1/webhooks/sandbox', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_TIMESTAMP' => $ts,
            'HTTP_X_WEBHOOK_SIGNATURE' => WebhookSignature::sign(self::SECRET, $ts, $body),
        ], $body)->assertOk();
    }

    public function test_un_paiement_confirme_produit_deux_ecritures_qui_couvrent_le_montant_total(): void
    {
        CommissionRule::factory()->create(['percentage_bps' => 500]);
        $supplier = Supplier::factory()->create(['status' => 'active']);
        $order = Order::factory()->create([
            'supplier_id' => $supplier->id,
            'amount_minor' => 10000,
            'currency' => $supplier->settlement_currency,
        ]);

        $user = new User();
        $user->forceFill([
            'name' => 'Client', 'email' => 'ledger.client@example.com',
            'password' => Hash::make('MotDePasse123'), 'role' => 'client', 'status' => 'active',
        ])->save();
        $order->update(['user_id' => $user->id]);

        $token = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email, 'password' => 'MotDePasse123',
        ])->assertOk()->json('token');

        $created = $this->withToken($token)
            ->withHeaders(['Idempotency-Key' => 'cle-ledger-0001'])
            ->postJson("/api/v1/orders/{$order->id}/payment-intent", ['payment_method' => 'mobile_money'])
            ->assertCreated();

        $payment = Payment::findOrFail($created->json('data.id'));
        $this->payAndConfirm($payment);

        $entries = LedgerEntry::where('payment_id', $payment->id)->get();

        $this->assertCount(2, $entries);
        $this->assertSame(10000, (int) $entries->sum('amount_minor'));
        $this->assertTrue($entries->contains('entry_type', 'commission'));
        $this->assertTrue($entries->contains('entry_type', 'settlement_due'));
    }

    public function test_appeler_deux_fois_lenregistrement_ne_duplique_pas_les_ecritures(): void
    {
        CommissionRule::factory()->create(['percentage_bps' => 500]);
        $supplier = Supplier::factory()->create(['status' => 'active']);
        $order = Order::factory()->create(['supplier_id' => $supplier->id]);

        $payment = Payment::create([
            'reference' => 'PAY-L'.$order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_ledger'.$order->id,
            'amount_minor' => $order->amount_minor,
            'currency' => $order->currency,
            'payment_method' => 'mobile_money',
            'status' => 'paid',
            'idempotency_key' => 'cle-l-'.$order->id,
            'confirmed_at' => now(),
        ]);

        \App\Models\Allocation::create([
            'payment_id' => $payment->id,
            'beneficiary_type' => 'supplier',
            'beneficiary_id' => $supplier->id,
            'gross_amount_minor' => $order->amount_minor,
            'fees_minor' => 0,
            'net_amount_minor' => $order->amount_minor,
            'currency' => $payment->currency,
            'status' => 'pending',
        ]);

        LedgerRecorder::recordForPayment($payment);
        LedgerRecorder::recordForPayment($payment);

        $this->assertSame(1, LedgerEntry::where('payment_id', $payment->id)->count());
    }

    public function test_une_ecriture_du_journal_ne_peut_etre_ni_modifiee_ni_supprimee(): void
    {
        $entry = LedgerEntry::create([
            'entry_type' => 'commission',
            'beneficiary_type' => 'company',
            'beneficiary_id' => 1,
            'amount_minor' => 500,
            'currency' => 'XAF',
        ]);

        $this->expectException(\LogicException::class);

        $entry->update(['amount_minor' => 1000]);
    }
}
