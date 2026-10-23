<?php

namespace Tests\Feature;

use App\Models\Allocation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Settlement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Payments\WebhookSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReportTest extends TestCase
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

    private function token(User $user): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'login' => $user->email, 'password' => 'MotDePasse123',
        ])->assertOk()->json('token');
    }

    // Création directe en base (sans passer par /payment-intent) pour ne jamais changer
    // de jeton client à l'intérieur d'un même test.
    private function createPayment(Supplier $supplier, int $amount, string $currency, int $commissionBps = 500): Payment
    {
        $commission = (int) round($amount * $commissionBps / 10000);
        $net = $amount - $commission;

        $order = Order::factory()->create([
            'supplier_id' => $supplier->id,
            'amount_minor' => $amount,
            'currency' => $currency,
            'status' => 'pending',
        ]);

        $payment = Payment::create([
            'reference' => 'PAY-R'.$order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_report_'.$order->id,
            'amount_minor' => $amount,
            'currency' => $currency,
            'payment_method' => 'mobile_money',
            'status' => 'pending',
            'idempotency_key' => 'cle-report-'.$order->id,
        ]);

        Allocation::create([
            'payment_id' => $payment->id,
            'beneficiary_type' => 'company',
            'beneficiary_id' => null,
            'gross_amount_minor' => $amount,
            'fees_minor' => 0,
            'net_amount_minor' => $commission,
            'currency' => $currency,
            'status' => 'pending',
        ]);

        Allocation::create([
            'payment_id' => $payment->id,
            'beneficiary_type' => 'supplier',
            'beneficiary_id' => $supplier->id,
            'gross_amount_minor' => $amount,
            'fees_minor' => 0,
            'net_amount_minor' => $net,
            'currency' => $currency,
            'status' => 'pending',
        ]);

        return $payment;
    }

    private function send(Payment $payment, string $type, array $dataOverride = [])
    {
        $event = [
            'id' => 'evt_'.uniqid(),
            'type' => $type,
            'data' => array_merge([
                'provider_reference' => $payment->provider_reference,
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
            ], $dataOverride),
        ];
        $body = json_encode($event);
        $ts = (string) now()->getTimestamp();

        return $this->call('POST', '/api/v1/webhooks/sandbox', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_TIMESTAMP' => $ts,
            'HTTP_X_WEBHOOK_SIGNATURE' => WebhookSignature::sign(self::SECRET, $ts, $body),
        ], $body);
    }

    public function test_le_rapport_agrege_les_montants_par_devise_et_les_ecarts(): void
    {
        $supplierXaf = Supplier::factory()->create(['status' => 'active', 'settlement_currency' => 'XAF', 'country_code' => 'CM']);
        $supplierNgn = Supplier::factory()->create(['status' => 'active', 'settlement_currency' => 'NGN', 'country_code' => 'NG']);

        // Paiement confirmé en XAF : 10 000, commission 5 % = 500.
        $paymentXaf = $this->createPayment($supplierXaf, 10000, 'XAF');
        $this->send($paymentXaf, 'payment.succeeded')->assertOk()->assertJsonPath('outcome', 'processed');

        // Paiement confirmé en NGN : 20 000, commission 5 % = 1000.
        $paymentNgn = $this->createPayment($supplierNgn, 20000, 'NGN');
        $this->send($paymentNgn, 'payment.succeeded')->assertOk()->assertJsonPath('outcome', 'processed');

        // Paiement qui échoue.
        $paymentFailed = $this->createPayment($supplierXaf, 5000, 'XAF');
        $this->send($paymentFailed, 'payment.failed')->assertOk()->assertJsonPath('outcome', 'processed');

        // Paiement avec un écart de montant.
        $paymentMismatch = $this->createPayment($supplierXaf, 7000, 'XAF');
        $this->send($paymentMismatch, 'payment.succeeded', ['amount_minor' => 1])
            ->assertJsonPath('outcome', 'amount_mismatch');

        // Un reversement confirmé manuellement, pour tester le compartiment "settled".
        Settlement::where('supplier_id', $supplierXaf->id)->first()->update([
            'status' => 'settled',
            'settled_at' => now(),
        ]);

        $admin = $this->makeUser('admin', 'admin.report@example.com');
        $token = $this->token($admin);

        $response = $this->withToken($token)->getJson('/api/v1/admin/reports/settlements')->assertOk();

        $response->assertJsonPath('data.by_currency.XAF.payments_paid_count', 1);
        $response->assertJsonPath('data.by_currency.XAF.volume_paid_minor', 10000);
        $response->assertJsonPath('data.by_currency.XAF.commissions_minor', 500);
        $response->assertJsonPath('data.by_currency.XAF.settlements_settled_minor', 9500);
        $response->assertJsonPath('data.by_currency.NGN.volume_paid_minor', 20000);
        $response->assertJsonPath('data.by_currency.NGN.commissions_minor', 1000);
        $response->assertJsonPath('data.payments_failed_count', 1);
        $response->assertJsonPath('data.amount_mismatches_count', 1);
    }

    public function test_le_responsable_financier_peut_consulter_le_rapport(): void
    {
        $finance = $this->makeUser('finance', 'finance.report@example.com');
        $token = $this->token($finance);

        $this->withToken($token)->getJson('/api/v1/admin/reports/settlements')->assertOk();
    }

    public function test_un_client_ne_peut_pas_consulter_le_rapport(): void
    {
        $client = $this->makeUser('client', 'client.report@example.com');
        $token = $this->token($client);

        $this->withToken($token)->getJson('/api/v1/admin/reports/settlements')->assertForbidden();
    }
}

