<?php

namespace Tests\Feature;

use App\Models\Allocation;
use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Payments\GatewayResult;
use App\Services\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MotDePasse123';

    private function tokenFor(string $role): string
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Utilisateur '.$role,
            'email' => $role.'@example.com',
            'password' => Hash::make(self::PASSWORD),
            'role' => $role,
            'status' => 'active',
        ])->save();

        return $this->postJson('/api/v1/auth/login', [
            'login' => $role.'@example.com',
            'password' => self::PASSWORD,
        ])->assertOk()->json('token');
    }

    private function userOf(string $role): User
    {
        return User::where('email', $role.'@example.com')->firstOrFail();
    }

    private function orderFor(User $user, array $override = []): Order
    {
        return Order::factory()->create(array_merge(['user_id' => $user->id], $override));
    }

    private function intent(string $token, Order $order, string $key = 'cle-test-0001', string $method = 'mobile_money')
    {
        return $this->withToken($token)
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson("/api/v1/orders/{$order->id}/payment-intent", ['payment_method' => $method]);
    }

    private function makePayment(Order $order, string $status = 'pending'): Payment
    {
        return Payment::create([
            'reference' => 'PAY-TEST'.$order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_test'.$order->id,
            'amount_minor' => $order->amount_minor,
            'currency' => $order->currency,
            'payment_method' => 'mobile_money',
            'status' => $status,
            'idempotency_key' => 'cle-existante-'.$order->id,
        ]);
    }

    // ---------- Commandes ----------

    public function test_un_client_cree_une_commande_avec_une_reference_serveur(): void
    {
        $token = $this->tokenFor('client');
        $supplier = Supplier::factory()->create();

        $this->withToken($token)->postJson('/api/v1/orders', [
            'supplier_id' => $supplier->id,
            'description' => 'Facture 2026-001',
            'amount_minor' => 10000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'created')
            ->assertJsonPath('data.amount_minor', 10000)
            ->assertJsonPath('data.currency', 'XAF');

        $order = Order::firstOrFail();
        $this->assertMatchesRegularExpression('/^ORD-[0-9A-Z]{26}$/', $order->reference);
        $this->assertSame($this->userOf('client')->id, (int) $order->user_id);
    }

    public function test_la_devise_vient_du_fournisseur_et_non_du_client(): void
    {
        $token = $this->tokenFor('client');
        $supplier = Supplier::factory()->create(['country_code' => 'NG', 'settlement_currency' => 'NGN']);

        $this->withToken($token)->postJson('/api/v1/orders', [
            'supplier_id' => $supplier->id,
            'description' => 'Facture',
            'amount_minor' => 50000,
            'currency' => 'XAF',
        ])
            ->assertCreated()
            ->assertJsonPath('data.currency', 'NGN');
    }

    public function test_un_fournisseur_non_actif_est_refuse(): void
    {
        $token = $this->tokenFor('client');
        $supplier = Supplier::factory()->create(['status' => 'pending']);

        $this->withToken($token)->postJson('/api/v1/orders', [
            'supplier_id' => $supplier->id,
            'description' => 'Facture',
            'amount_minor' => 10000,
        ])->assertUnprocessable()->assertJsonValidationErrors('supplier_id');
    }

    public function test_un_montant_invalide_est_refuse(): void
    {
        $token = $this->tokenFor('client');
        $supplier = Supplier::factory()->create();

        foreach ([0, -5, 100.5, 'abc'] as $bad) {
            $this->withToken($token)->postJson('/api/v1/orders', [
                'supplier_id' => $supplier->id,
                'description' => 'Facture',
                'amount_minor' => $bad,
            ])->assertUnprocessable()->assertJsonValidationErrors('amount_minor');
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_un_fournisseur_ne_peut_pas_creer_de_commande(): void
    {
        $token = $this->tokenFor('supplier');
        $supplier = Supplier::factory()->create();

        $this->withToken($token)->postJson('/api/v1/orders', [
            'supplier_id' => $supplier->id,
            'description' => 'Facture',
            'amount_minor' => 10000,
        ])->assertForbidden();
    }

    public function test_un_client_voit_sa_commande_mais_pas_celle_dun_autre(): void
    {
        $token = $this->tokenFor('client');
        $mine = $this->orderFor($this->userOf('client'));
        $other = Order::factory()->create();

        $this->withToken($token)->getJson("/api/v1/orders/{$mine->id}")->assertOk();
        $this->withToken($token)->getJson("/api/v1/orders/{$other->id}")->assertNotFound();
    }

    // ---------- Initiation du paiement ----------

    public function test_une_demande_de_paiement_fige_la_repartition_et_cree_un_paiement_en_attente(): void
    {
        CommissionRule::factory()->create(['percentage_bps' => 500]);
        $token = $this->tokenFor('client');
        $order = $this->orderFor($this->userOf('client'));

        $this->intent($token, $order)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.amount_minor', 10000)
            ->assertJsonMissingPath('data.commission_minor');

        $payment = Payment::firstOrFail();
        $this->assertMatchesRegularExpression('/^PAY-[0-9A-Z]{26}$/', $payment->reference);
        $this->assertSame('sandbox', $payment->provider);
        $this->assertNotNull($payment->provider_reference);

        $company = Allocation::where('beneficiary_type', 'company')->firstOrFail();
        $supplier = Allocation::where('beneficiary_type', 'supplier')->firstOrFail();
        $this->assertSame(500, $company->net_amount_minor);
        $this->assertSame(9500, $supplier->net_amount_minor);
        $this->assertSame((int) $order->supplier_id, $supplier->beneficiary_id);
        $this->assertNotNull($company->commission_rule_id);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_la_meme_cle_renvoie_le_meme_paiement_sans_doublon(): void
    {
        CommissionRule::factory()->create();
        $token = $this->tokenFor('client');
        $order = $this->orderFor($this->userOf('client'));

        $first = $this->intent($token, $order)->assertCreated();

        $this->intent($token, $order)
            ->assertOk()
            ->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('allocations', 2);
    }

    public function test_la_meme_cle_avec_un_autre_moyen_de_paiement_est_refusee(): void
    {
        CommissionRule::factory()->create();
        $token = $this->tokenFor('client');
        $order = $this->orderFor($this->userOf('client'));

        $this->intent($token, $order)->assertCreated();
        $this->intent($token, $order, 'cle-test-0001', 'card')->assertStatus(409);

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_la_meme_cle_pour_une_autre_commande_est_refusee(): void
    {
        CommissionRule::factory()->create();
        $token = $this->tokenFor('client');
        $user = $this->userOf('client');
        $first = $this->orderFor($user);
        $second = $this->orderFor($user);

        $this->intent($token, $first)->assertCreated();
        $this->intent($token, $second)->assertStatus(409);

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_un_second_paiement_est_refuse_tant_que_le_premier_est_actif(): void
    {
        CommissionRule::factory()->create();
        $token = $this->tokenFor('client');
        $order = $this->orderFor($this->userOf('client'));

        $this->intent($token, $order)->assertCreated();
        $this->intent($token, $order, 'cle-test-0002')->assertStatus(409);

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_apres_un_echec_une_nouvelle_tentative_avec_une_nouvelle_cle_est_possible(): void
    {
        CommissionRule::factory()->create();
        $token = $this->tokenFor('client');
        $order = $this->orderFor($this->userOf('client'));

        $this->intent($token, $order)->assertCreated();
        Payment::query()->update(['status' => 'failed']);

        $this->intent($token, $order, 'cle-test-0002')->assertCreated();

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_la_cle_didempotence_est_obligatoire(): void
    {
        CommissionRule::factory()->create();
        $token = $this->tokenFor('client');
        $order = $this->orderFor($this->userOf('client'));

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/payment-intent", ['payment_method' => 'mobile_money'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_sans_regle_de_commission_le_paiement_est_refuse(): void
    {
        $token = $this->tokenFor('client');
        $order = $this->orderFor($this->userOf('client'));

        $this->intent($token, $order)->assertUnprocessable();

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('allocations', 0);
    }

    public function test_un_fournisseur_suspendu_ne_peut_plus_etre_paye(): void
    {
        CommissionRule::factory()->create();
        $token = $this->tokenFor('client');
        $supplier = Supplier::factory()->create(['status' => 'suspended']);
        $order = $this->orderFor($this->userOf('client'), ['supplier_id' => $supplier->id]);

        $this->intent($token, $order)->assertUnprocessable();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_un_client_ne_peut_pas_payer_la_commande_dun_autre(): void
    {
        CommissionRule::factory()->create();
        $token = $this->tokenFor('client');
        $other = Order::factory()->create();

        $this->intent($token, $other)->assertNotFound();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_si_le_prestataire_est_indisponible_le_paiement_passe_a_failed(): void
    {
        CommissionRule::factory()->create();
        $token = $this->tokenFor('client');
        $order = $this->orderFor($this->userOf('client'));

        $this->instance(PaymentGateway::class, new class implements PaymentGateway {
            public function name(): string
            {
                return 'sandbox';
            }

            public function initiate(Payment $payment): GatewayResult
            {
                throw new RuntimeException('Prestataire indisponible');
            }
        });

        $this->intent($token, $order)
            ->assertStatus(502)
            ->assertJsonPath('data.status', 'failed');

        $this->assertSame('created', $order->fresh()->status);
    }

    // ---------- Consultation d'un paiement ----------

    public function test_un_client_consulte_son_paiement_mais_pas_celui_dun_autre(): void
    {
        $token = $this->tokenFor('client');
        $mine = $this->makePayment($this->orderFor($this->userOf('client')));
        $other = $this->makePayment(Order::factory()->create());

        $this->withToken($token)->getJson("/api/v1/payments/{$mine->id}")
            ->assertOk()
            ->assertJsonPath('data.reference', $mine->reference);

        $this->withToken($token)->getJson("/api/v1/payments/{$other->id}")->assertNotFound();
    }
}
