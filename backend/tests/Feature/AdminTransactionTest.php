<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTransactionTest extends TestCase
{
    use RefreshDatabase;

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

    private function makePayment(Supplier $supplier, int $amount, string $currency, string $status): Payment
    {
        $order = Order::factory()->create([
            'supplier_id' => $supplier->id,
            'amount_minor' => $amount,
            'currency' => $currency,
            'status' => $status === 'paid' ? 'paid' : 'pending',
        ]);

        return Payment::create([
            'reference' => 'PAY-H'.$order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_hist_'.$order->id,
            'amount_minor' => $amount,
            'currency' => $currency,
            'payment_method' => 'mobile_money',
            'status' => $status,
            'idempotency_key' => 'cle-hist-'.$order->id,
            'confirmed_at' => $status === 'paid' ? now() : null,
        ]);
    }

    public function test_un_admin_filtre_lhistorique_par_pays_devise_et_statut(): void
    {
        $supplierCm = Supplier::factory()->create(['status' => 'active', 'country_code' => 'CM', 'settlement_currency' => 'XAF']);
        $supplierNg = Supplier::factory()->create(['status' => 'active', 'country_code' => 'NG', 'settlement_currency' => 'NGN']);

        $this->makePayment($supplierCm, 10000, 'XAF', 'paid');
        $paymentNg = $this->makePayment($supplierNg, 20000, 'NGN', 'paid');
        $this->makePayment($supplierCm, 5000, 'XAF', 'failed');

        $admin = $this->makeUser('admin', 'admin.hist@example.com');
        $token = $this->token($admin);

        $this->withToken($token)->getJson('/api/v1/admin/transactions?country_code=NG')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.payment_reference', $paymentNg->reference);

        $this->withToken($token)->getJson('/api/v1/admin/transactions?currency=XAF&status=paid')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($token)->getJson('/api/v1/admin/transactions?reference='.$paymentNg->reference)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_le_responsable_financier_peut_consulter_lhistorique(): void
    {
        $finance = $this->makeUser('finance', 'finance.hist@example.com');
        $token = $this->token($finance);

        $this->withToken($token)->getJson('/api/v1/admin/transactions')->assertOk();
    }

    public function test_un_client_ne_peut_pas_consulter_lhistorique_centralise(): void
    {
        $client = $this->makeUser('client', 'client.hist@example.com');
        $token = $this->token($client);

        $this->withToken($token)->getJson('/api/v1/admin/transactions')->assertForbidden();
    }
}
