<?php

namespace Tests\Feature;

use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TransactionHistory;
use App\Models\User;
use App\Services\Payments\WebhookSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'secret-de-test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['payments.providers.sandbox.webhook_secret' => self::SECRET]);
    }

    private function makePayment(array $override = []): Payment
    {
        $order = Order::factory()->create(['status' => 'pending']);

        return Payment::create(array_merge([
            'reference' => 'PAY-T'.$order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_ref'.$order->id,
            'amount_minor' => 10000,
            'currency' => 'XAF',
            'payment_method' => 'mobile_money',
            'status' => 'pending',
            'idempotency_key' => 'cle-'.$order->id,
        ], $override));
    }

    private function event(Payment $payment, string $type = 'payment.succeeded', ?string $id = null, array $data = []): array
    {
        return [
            'id' => $id ?? 'evt_'.uniqid(),
            'type' => $type,
            'data' => array_merge([
                'provider_reference' => $payment->provider_reference,
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
            ], $data),
        ];
    }

    private function sendRaw(string $body, string $signature, string $timestamp, string $provider = 'sandbox')
    {
        return $this->call('POST', "/api/v1/webhooks/{$provider}", [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], $body);
    }

    private function send(array $event, ?string $secret = null, ?int $timestamp = null)
    {
        $body = json_encode($event);
        $ts = (string) ($timestamp ?? now()->getTimestamp());

        return $this->sendRaw($body, WebhookSignature::sign($secret ?? self::SECRET, $ts, $body), $ts);
    }

    // ---------- Authenticité ----------

    public function test_un_succes_signe_confirme_le_paiement_la_commande_et_lhistorique(): void
    {
        $payment = $this->makePayment();

        $this->send($this->event($payment, 'payment.succeeded', 'evt_1'))
            ->assertOk()
            ->assertJsonPath('outcome', 'processed');

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->confirmed_at);
        $this->assertSame('paid', Order::find($payment->order_id)->status);

        $history = TransactionHistory::firstOrFail();
        $this->assertSame('pending', $history->previous_status);
        $this->assertSame('paid', $history->new_status);
        $this->assertSame('evt_1', $history->event_reference);
        $this->assertSame('provider', $history->actor_type);
    }

    public function test_une_signature_invalide_est_refusee(): void
    {
        $payment = $this->makePayment();
        $body = json_encode($this->event($payment));

        $this->sendRaw($body, 'signature-bidon', (string) now()->getTimestamp())->assertUnauthorized();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_une_signature_faite_avec_un_autre_secret_est_refusee(): void
    {
        $payment = $this->makePayment();

        $this->send($this->event($payment), 'autre-secret')->assertUnauthorized();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_un_horodatage_trop_ancien_est_refuse(): void
    {
        $payment = $this->makePayment();

        $this->send($this->event($payment), null, now()->subHour()->getTimestamp())->assertUnauthorized();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_un_corps_modifie_apres_signature_est_refuse(): void
    {
        $payment = $this->makePayment();
        $ts = (string) now()->getTimestamp();
        $signature = WebhookSignature::sign(self::SECRET, $ts, json_encode($this->event($payment, 'payment.succeeded', 'evt_x')));
        $tampered = json_encode($this->event($payment, 'payment.succeeded', 'evt_x', ['amount_minor' => 1]));

        $this->sendRaw($tampered, $signature, $ts)->assertUnauthorized();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_sans_secret_configure_aucun_evenement_nest_accepte(): void
    {
        config(['payments.providers.sandbox.webhook_secret' => '']);
        $payment = $this->makePayment();

        $this->send($this->event($payment), '')->assertUnauthorized();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_un_prestataire_inconnu_est_refuse(): void
    {
        $this->sendRaw('{}', 'x', (string) now()->getTimestamp(), 'inconnu')->assertNotFound();
    }

    public function test_un_evenement_mal_forme_est_refuse(): void
    {
        $this->send(['type' => 'payment.succeeded'])->assertUnprocessable();
    }

    // ---------- Idempotence et ordre des événements ----------

    public function test_un_evenement_rejoue_na_aucun_effet(): void
    {
        $payment = $this->makePayment();
        $event = $this->event($payment, 'payment.succeeded', 'evt_rejoue');

        $this->send($event)->assertOk()->assertJsonPath('outcome', 'processed');
        $this->send($event)->assertOk()->assertJsonPath('outcome', 'duplicate');

        $this->assertDatabaseCount('payment_events', 1);
        $this->assertDatabaseCount('transaction_history', 1);
    }

    public function test_un_second_succes_avec_un_autre_identifiant_est_ignore(): void
    {
        $payment = $this->makePayment();

        $this->send($this->event($payment, 'payment.succeeded', 'evt_a'))->assertJsonPath('outcome', 'processed');
        $this->send($this->event($payment, 'payment.succeeded', 'evt_b'))->assertJsonPath('outcome', 'ignored');

        $this->assertDatabaseCount('transaction_history', 1);
    }

    public function test_un_echec_tardif_ne_defait_pas_un_paiement_confirme(): void
    {
        $payment = $this->makePayment();

        $this->send($this->event($payment, 'payment.succeeded', 'evt_a'))->assertOk();
        $this->send($this->event($payment, 'payment.failed', 'evt_b'))->assertJsonPath('outcome', 'ignored');

        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_une_action_requise_puis_un_succes(): void
    {
        $payment = $this->makePayment();

        $this->send($this->event($payment, 'payment.requires_action', 'evt_a'))->assertJsonPath('outcome', 'processed');
        $this->assertSame('requires_action', $payment->fresh()->status);

        $this->send($this->event($payment, 'payment.succeeded', 'evt_b'))->assertJsonPath('outcome', 'processed');
        $this->assertSame('paid', $payment->fresh()->status);

        $this->assertDatabaseCount('transaction_history', 2);
    }

    public function test_un_echec_remet_la_commande_en_etat_de_nouvelle_tentative(): void
    {
        $payment = $this->makePayment();

        $this->send($this->event($payment, 'payment.failed', 'evt_a'))->assertJsonPath('outcome', 'processed');

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('created', Order::find($payment->order_id)->status);
    }

    public function test_un_type_devenement_inconnu_est_enregistre_mais_ignore(): void
    {
        $payment = $this->makePayment();

        $this->send($this->event($payment, 'payment.autre', 'evt_a'))->assertJsonPath('outcome', 'ignored');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertDatabaseCount('transaction_history', 0);
    }

    public function test_un_paiement_inconnu_renvoie_404_et_ne_stocke_rien(): void
    {
        $payment = $this->makePayment();
        $event = $this->event($payment, 'payment.succeeded', 'evt_a', ['provider_reference' => 'sbx_inexistant']);

        $this->send($event)->assertNotFound();

        $this->assertDatabaseCount('payment_events', 0);
    }

    // ---------- Contrôles de cohérence financière ----------

    public function test_un_montant_different_ne_confirme_pas_le_paiement(): void
    {
        $payment = $this->makePayment();

        $this->send($this->event($payment, 'payment.succeeded', 'evt_a', ['amount_minor' => 9999]))
            ->assertOk()
            ->assertJsonPath('outcome', 'amount_mismatch');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', Order::find($payment->order_id)->status);
        $this->assertDatabaseCount('transaction_history', 0);
        $this->assertDatabaseHas('payment_events', ['event_id' => 'evt_a', 'status' => 'amount_mismatch']);
    }

    public function test_une_devise_differente_ne_confirme_pas_le_paiement(): void
    {
        $payment = $this->makePayment();

        $this->send($this->event($payment, 'payment.succeeded', 'evt_a', ['currency' => 'NGN']))
            ->assertJsonPath('outcome', 'amount_mismatch');

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_un_succes_sans_montant_ne_confirme_pas_le_paiement(): void
    {
        $payment = $this->makePayment();
        $event = $this->event($payment, 'payment.succeeded', 'evt_a');
        unset($event['data']['amount_minor']);

        $this->send($event)->assertJsonPath('outcome', 'amount_mismatch');

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_un_succes_apres_un_echec_est_signale_comme_anomalie(): void
    {
        $payment = $this->makePayment(['status' => 'failed']);

        $this->send($this->event($payment, 'payment.succeeded', 'evt_a'))
            ->assertOk()
            ->assertJsonPath('outcome', 'anomaly');

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertDatabaseHas('payment_events', ['event_id' => 'evt_a', 'status' => 'anomaly']);
    }

    public function test_lhistorique_ne_peut_etre_ni_modifie_ni_supprime(): void
    {
        $payment = $this->makePayment();
        $row = TransactionHistory::create([
            'payment_id' => $payment->id,
            'previous_status' => 'pending',
            'new_status' => 'paid',
            'event_type' => 'payment.succeeded',
            'actor_type' => 'provider',
        ]);

        $this->expectException(LogicException::class);

        $row->update(['new_status' => 'failed']);
    }

    // ---------- Parcours complet ----------

    public function test_parcours_complet_commande_paiement_confirmation(): void
    {
        CommissionRule::factory()->create(['percentage_bps' => 500]);

        $user = new User();
        $user->forceFill([
            'name' => 'Client',
            'email' => 'client@example.com',
            'password' => Hash::make('MotDePasse123'),
            'role' => 'client',
            'status' => 'active',
        ])->save();

        $token = $this->postJson('/api/v1/auth/login', [
            'login' => 'client@example.com',
            'password' => 'MotDePasse123',
        ])->assertOk()->json('token');

        $order = Order::factory()->create(['user_id' => $user->id]);

        $created = $this->withToken($token)
            ->withHeaders(['Idempotency-Key' => 'cle-parcours-0001'])
            ->postJson("/api/v1/orders/{$order->id}/payment-intent", ['payment_method' => 'mobile_money'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $payment = Payment::findOrFail($created->json('data.id'));

        $this->send($this->event($payment))->assertOk()->assertJsonPath('outcome', 'processed');

        $this->withToken($token)->getJson("/api/v1/payments/{$payment->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertSame('paid', Order::find($order->id)->status);
    }
}
