<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Payments\ReceiptIssuer;
use App\Services\Payments\WebhookSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MotDePasse123';
    private const SECRET = 'secret-de-test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['payments.providers.sandbox.webhook_secret' => self::SECRET]);
    }

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

    private function makePayment(User $user, array $payment = [], array $order = []): Payment
    {
        $order = Order::factory()->create(array_merge(['user_id' => $user->id], $order));

        return Payment::create(array_merge([
            'reference' => 'PAY-T'.$order->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_ref'.$order->id,
            'amount_minor' => $order->amount_minor,
            'currency' => $order->currency,
            'payment_method' => 'mobile_money',
            'status' => 'pending',
            'idempotency_key' => 'cle-'.$order->id,
        ], $payment));
    }

    private function makePaid(User $user, array $order = []): Payment
    {
        $payment = $this->makePayment($user, ['status' => 'paid', 'confirmed_at' => now()], $order);
        ReceiptIssuer::issueFor($payment);

        return $payment;
    }

    private function confirm(Payment $payment, string $type = 'payment.succeeded', ?string $id = null)
    {
        $body = json_encode([
            'id' => $id ?? 'evt_'.uniqid(),
            'type' => $type,
            'data' => [
                'provider_reference' => $payment->provider_reference,
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
            ],
        ]);
        $ts = (string) now()->getTimestamp();

        return $this->call('POST', '/api/v1/webhooks/sandbox', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_TIMESTAMP' => $ts,
            'HTTP_X_WEBHOOK_SIGNATURE' => WebhookSignature::sign(self::SECRET, $ts, $body),
        ], $body);
    }

    // ---------- Émission des reçus ----------

    public function test_la_confirmation_dun_paiement_emet_un_recu_unique(): void
    {
        $user = User::factory()->create();
        $payment = $this->makePayment($user);

        $this->confirm($payment)->assertOk()->assertJsonPath('outcome', 'processed');

        $receipt = Receipt::firstOrFail();
        $this->assertSame(sprintf('RC-%s-%08d', now()->format('Y'), $payment->id), $receipt->receipt_number);
        $this->assertSame($user->id, (int) $receipt->user_id);
        $this->assertSame('issued', $receipt->status);
        $this->assertSame(10000, $receipt->snapshot['amount_minor']);
        $this->assertSame('paid', $receipt->snapshot['status']);
    }

    public function test_un_paiement_echoue_nemet_pas_de_recu(): void
    {
        $payment = $this->makePayment(User::factory()->create());

        $this->confirm($payment, 'payment.failed')->assertOk();

        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_un_evenement_rejoue_nemet_pas_un_second_recu(): void
    {
        $payment = $this->makePayment(User::factory()->create());

        $this->confirm($payment, 'payment.succeeded', 'evt_fixe')->assertJsonPath('outcome', 'processed');
        $this->confirm($payment, 'payment.succeeded', 'evt_fixe')->assertJsonPath('outcome', 'duplicate');

        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_lemission_est_idempotente(): void
    {
        $payment = $this->makePayment(User::factory()->create(), ['status' => 'paid', 'confirmed_at' => now()]);

        $first = ReceiptIssuer::issueFor($payment);
        $second = ReceiptIssuer::issueFor($payment);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_un_recu_ne_peut_pas_etre_modifie(): void
    {
        $this->makePaid(User::factory()->create());
        $receipt = Receipt::firstOrFail();

        $this->expectException(LogicException::class);

        $receipt->update(['status' => 'void']);
    }

    public function test_un_recu_ne_peut_pas_etre_supprime(): void
    {
        $this->makePaid(User::factory()->create());
        $receipt = Receipt::firstOrFail();

        $this->expectException(LogicException::class);

        $receipt->delete();
    }

    public function test_le_recu_reste_identique_si_le_fournisseur_change_de_nom(): void
    {
        $token = $this->tokenFor('client');
        $supplier = Supplier::factory()->create(['business_name' => 'Ancien Nom SARL']);
        $payment = $this->makePaid($this->userOf('client'), ['supplier_id' => $supplier->id]);

        $supplier->update(['business_name' => 'Nouveau Nom SARL']);

        $this->withToken($token)->getJson("/api/v1/me/transactions/{$payment->id}/receipt")
            ->assertOk()
            ->assertJsonPath('data.document.supplier_name', 'Ancien Nom SARL');
    }

    // ---------- Historique ----------

    public function test_lhistorique_ne_contient_que_les_transactions_du_client(): void
    {
        $token = $this->tokenFor('client');
        $user = $this->userOf('client');

        $this->makePayment($user, ['status' => 'pending']);
        $this->makePaid($user);
        $this->makePayment($user, ['status' => 'failed']);
        $this->makePayment(User::factory()->create());

        $response = $this->withToken($token)->getJson('/api/v1/me/transactions')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissingPath('data.0.commission_minor');

        $statuses = collect($response->json('data'))->pluck('status')->sort()->values()->all();
        $this->assertSame(['failed', 'paid', 'pending'], $statuses);
    }

    public function test_filtre_par_statut(): void
    {
        $token = $this->tokenFor('client');
        $user = $this->userOf('client');
        $this->makePayment($user, ['status' => 'pending']);
        $this->makePaid($user);

        $this->withToken($token)->getJson('/api/v1/me/transactions?status=paid')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'paid');
    }

    public function test_filtre_par_fournisseur(): void
    {
        $token = $this->tokenFor('client');
        $user = $this->userOf('client');
        $supplier = Supplier::factory()->create();
        $this->makePayment($user, [], ['supplier_id' => $supplier->id]);
        $this->makePayment($user);

        $this->withToken($token)->getJson('/api/v1/me/transactions?supplier_id='.$supplier->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.supplier.id', $supplier->id);
    }

    public function test_filtre_par_periode(): void
    {
        $token = $this->tokenFor('client');
        $user = $this->userOf('client');
        $old = $this->makePayment($user);
        Payment::whereKey($old->id)->update(['created_at' => '2026-01-15 10:00:00']);
        $this->makePayment($user);

        $this->withToken($token)->getJson('/api/v1/me/transactions?from=2026-01-01&to=2026-01-31')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', $old->reference);
    }

    public function test_recherche_par_reference_de_transaction_ou_numero_de_recu(): void
    {
        $token = $this->tokenFor('client');
        $user = $this->userOf('client');
        $this->makePayment($user);
        $paid = $this->makePaid($user);
        $receipt = Receipt::firstOrFail();

        $this->withToken($token)->getJson('/api/v1/me/transactions?reference='.$paid->reference)
            ->assertJsonCount(1, 'data');

        $this->withToken($token)->getJson('/api/v1/me/transactions?reference='.$receipt->receipt_number)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.receipt.number', $receipt->receipt_number);

        $this->withToken($token)->getJson('/api/v1/me/transactions?reference=INEXISTANT')
            ->assertJsonCount(0, 'data');
    }

    public function test_detail_dune_transaction_propre_et_dune_transaction_etrangere(): void
    {
        $token = $this->tokenFor('client');
        $mine = $this->makePayment($this->userOf('client'));
        $other = $this->makePayment(User::factory()->create());

        $this->withToken($token)->getJson("/api/v1/me/transactions/{$mine->id}")
            ->assertOk()
            ->assertJsonPath('data.reference', $mine->reference)
            ->assertJsonPath('data.receipt', null);

        $this->withToken($token)->getJson("/api/v1/me/transactions/{$other->id}")->assertNotFound();
    }

    // ---------- Consultation du reçu ----------

    public function test_un_paiement_en_attente_donne_un_document_provisoire(): void
    {
        $token = $this->tokenFor('client');
        $payment = $this->makePayment($this->userOf('client'), ['status' => 'pending']);

        $this->withToken($token)->getJson("/api/v1/me/transactions/{$payment->id}/receipt")
            ->assertOk()
            ->assertJsonPath('data.type', 'provisional')
            ->assertJsonPath('data.is_proof_of_payment', false)
            ->assertJsonPath('data.status_label', 'En attente')
            ->assertJsonPath('data.receipt_number', null)
            ->assertJsonPath('data.download_url', null);

        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_un_paiement_echoue_affiche_echec(): void
    {
        $token = $this->tokenFor('client');
        $payment = $this->makePayment($this->userOf('client'), ['status' => 'failed']);

        $this->withToken($token)->getJson("/api/v1/me/transactions/{$payment->id}/receipt")
            ->assertOk()
            ->assertJsonPath('data.is_proof_of_payment', false)
            ->assertJsonPath('data.status_label', 'Échec');
    }

    public function test_un_paiement_confirme_donne_le_recu_officiel(): void
    {
        $token = $this->tokenFor('client');
        $payment = $this->makePaid($this->userOf('client'));
        $receipt = Receipt::firstOrFail();

        $this->withToken($token)->getJson("/api/v1/me/transactions/{$payment->id}/receipt")
            ->assertOk()
            ->assertJsonPath('data.type', 'receipt')
            ->assertJsonPath('data.is_proof_of_payment', true)
            ->assertJsonPath('data.receipt_number', $receipt->receipt_number)
            ->assertJsonPath('data.status_label', 'Payé')
            ->assertJsonPath('data.document.amount_minor', 10000);
    }

    public function test_un_paiement_confirme_sans_recu_recoit_son_recu_a_la_consultation(): void
    {
        $token = $this->tokenFor('client');
        $payment = $this->makePayment($this->userOf('client'), ['status' => 'paid', 'confirmed_at' => now()]);

        $this->withToken($token)->getJson("/api/v1/me/transactions/{$payment->id}/receipt")
            ->assertOk()
            ->assertJsonPath('data.type', 'receipt');

        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_le_recu_dun_autre_client_est_introuvable(): void
    {
        $token = $this->tokenFor('client');
        $other = $this->makePaid(User::factory()->create());

        $this->withToken($token)->getJson("/api/v1/me/transactions/{$other->id}/receipt")->assertNotFound();
    }

    // ---------- Téléchargement PDF ----------

    public function test_le_client_telecharge_son_recu_en_pdf(): void
    {
        $token = $this->tokenFor('client');
        $this->makePaid($this->userOf('client'));
        $receipt = Receipt::firstOrFail();

        $response = $this->withToken($token)->get("/api/v1/me/receipts/{$receipt->id}/download");

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_le_pdf_dun_autre_client_est_introuvable(): void
    {
        $token = $this->tokenFor('client');
        $this->makePaid(User::factory()->create());
        $receipt = Receipt::firstOrFail();

        $this->withToken($token)->get("/api/v1/me/receipts/{$receipt->id}/download")->assertNotFound();
    }

    // ---------- Accès ----------

    public function test_un_fournisseur_naccede_pas_a_lhistorique_des_clients(): void
    {
        $this->withToken($this->tokenFor('supplier'))->getJson('/api/v1/me/transactions')->assertForbidden();
    }

    public function test_lhistorique_exige_un_jeton(): void
    {
        $this->getJson('/api/v1/me/transactions')->assertUnauthorized();
    }
}