<?php

namespace Tests\Feature;

use App\Models\Allocation;
use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Payments\SettlementIssuer;
use App\Services\Payments\WebhookSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupplierPortalTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'secret-de-test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.providers.sandbox.webhook_secret' => self::SECRET]);
    }

    private function makeUser(string $role, string $email): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Test '.$role,
            'email' => $email,
            'password' => Hash::make('MotDePasse123'),
            'role' => $role,
            'status' => 'active',
        ])->save();

        return $user;
    }

    private function login(User $user): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'MotDePasse123',
        ])->assertOk()->json('token');
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

    // Un seul jeton (client) dans ce test : on vérifie uniquement l'effet en base.
    public function test_un_paiement_confirme_cree_un_reversement_en_attente_pour_le_fournisseur(): void
    {
        CommissionRule::factory()->create(['percentage_bps' => 500]);
        $supplier = Supplier::factory()->create(['status' => 'active']);
        $order = Order::factory()->create([
            'supplier_id' => $supplier->id,
            'amount_minor' => 10000,
            'currency' => $supplier->settlement_currency,
        ]);

        $client = $this->makeUser('client', 'client@example.com');
        $order->update(['user_id' => $client->id]);
        $token = $this->login($client);

        $created = $this->withToken($token)
            ->withHeaders(['Idempotency-Key' => 'cle-0001'])
            ->postJson("/api/v1/orders/{$order->id}/payment-intent", ['payment_method' => 'mobile_money'])
            ->assertCreated();

        $payment = Payment::findOrFail($created->json('data.id'));
        $this->payAndConfirm($payment);

        $this->assertDatabaseHas('settlements', [
            'supplier_id' => $supplier->id,
            'status' => 'pending',
        ]);
    }

    // Données créées directement (pas de jeton client) : un seul jeton (fournisseur) dans ce test.
    public function test_un_fournisseur_consulte_ses_reversements_et_transactions(): void
    {
        $supplier = Supplier::factory()->create(['status' => 'active']);
        $order = Order::factory()->create([
            'supplier_id' => $supplier->id,
            'amount_minor' => 10000,
            'currency' => $supplier->settlement_currency,
        ]);

        $payment = Payment::create([
            'reference' => 'PAY-T'.$order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_ref'.$order->id,
            'amount_minor' => 10000,
            'currency' => $order->currency,
            'payment_method' => 'mobile_money',
            'status' => 'paid',
            'idempotency_key' => 'cle-'.$order->id,
            'confirmed_at' => now(),
        ]);

        Allocation::create([
            'payment_id' => $payment->id,
            'beneficiary_type' => 'supplier',
            'beneficiary_id' => $supplier->id,
            'gross_amount_minor' => 10000,
            'fees_minor' => 0,
            'net_amount_minor' => 9500,
            'currency' => $payment->currency,
            'status' => 'pending',
        ]);

        SettlementIssuer::issueFor($payment);

        $supplierUser = $this->makeUser('supplier', 'fournisseur@example.com');
        $supplier->update(['owner_user_id' => $supplierUser->id]);
        $token = $this->login($supplierUser);

        $this->withToken($token)->getJson('/api/v1/supplier/settlements')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($token)->getJson('/api/v1/supplier/transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_un_fournisseur_ne_voit_pas_les_transactions_dun_autre_fournisseur(): void
    {
        $supplierA = Supplier::factory()->create(['status' => 'active']);
        $supplierB = Supplier::factory()->create(['status' => 'active']);

        $userA = $this->makeUser('supplier', 'fournisseur.a@example.com');
        $supplierA->update(['owner_user_id' => $userA->id]);

        $userB = $this->makeUser('supplier', 'fournisseur.b@example.com');
        $supplierB->update(['owner_user_id' => $userB->id]);

        $tokenA = $this->login($userA);

        $this->withToken($tokenA)->getJson('/api/v1/supplier/transactions')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_un_utilisateur_sans_fournisseur_lie_recoit_404(): void
    {
        $user = $this->makeUser('supplier', 'orphelin@example.com');
        $token = $this->login($user);

        $this->withToken($token)->getJson('/api/v1/supplier/transactions')->assertNotFound();
    }

    public function test_un_client_ne_peut_pas_acceder_aux_routes_fournisseur(): void
    {
        $client = $this->makeUser('client', 'client2@example.com');
        $token = $this->login($client);

        $this->withToken($token)->getJson('/api/v1/supplier/transactions')->assertForbidden();
    }
}
