<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DisputeTest extends TestCase
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

    private function paidPayment(string $slug): Payment
    {
        $supplier = Supplier::factory()->create(['status' => 'active']);
        $order = Order::factory()->create([
            'supplier_id' => $supplier->id,
            'amount_minor' => 10000,
            'currency' => $supplier->settlement_currency,
            'status' => 'paid',
        ]);

        return Payment::create([
            'reference' => 'PAY-D'.$order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_dispute_'.$slug.'_'.$order->id,
            'amount_minor' => $order->amount_minor,
            'currency' => $order->currency,
            'payment_method' => 'mobile_money',
            'status' => 'paid',
            'idempotency_key' => 'cle-dispute-'.$slug.'-'.$order->id,
            'confirmed_at' => now(),
        ]);
    }

    public function test_un_admin_ouvre_une_contestation_sur_un_paiement_confirme(): void
    {
        $admin = $this->makeUser('admin', 'admin.dispute1@example.com');
        $token = $this->token($admin);
        $payment = $this->paidPayment('a');

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$payment->id}/dispute", [
            'reason' => 'Produit non conforme',
        ])->assertOk()->assertJsonPath('data.status', 'disputed');

        $this->assertSame('disputed', $payment->fresh()->status);
        $this->assertDatabaseHas('transaction_history', ['payment_id' => $payment->id, 'event_type' => 'dispute.opened']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'dispute.opened', 'auditable_id' => $payment->id]);
        $this->assertDatabaseHas('notification_logs', ['payment_id' => $payment->id, 'type' => 'dispute.opened']);
    }

    public function test_un_paiement_non_confirme_ne_peut_pas_etre_conteste(): void
    {
        $admin = $this->makeUser('admin', 'admin.dispute2@example.com');
        $token = $this->token($admin);

        $supplier = Supplier::factory()->create(['status' => 'active']);
        $order = Order::factory()->create(['supplier_id' => $supplier->id]);
        $payment = Payment::create([
            'reference' => 'PAY-DP'.$order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_dispute_pending_'.$order->id,
            'amount_minor' => $order->amount_minor,
            'currency' => $order->currency,
            'payment_method' => 'mobile_money',
            'status' => 'pending',
            'idempotency_key' => 'cle-dispute-pending-'.$order->id,
        ]);

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$payment->id}/dispute", [
            'reason' => 'Test',
        ])->assertStatus(422);
    }

    public function test_un_client_ne_peut_pas_ouvrir_une_contestation(): void
    {
        $client = $this->makeUser('client', 'client.dispute@example.com');
        $token = $this->token($client);
        $payment = $this->paidPayment('c');

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$payment->id}/dispute", [
            'reason' => 'Test',
        ])->assertForbidden();
    }

    public function test_une_contestation_rejetee_retourne_au_statut_paye(): void
    {
        $admin = $this->makeUser('admin', 'admin.dispute3@example.com');
        $token = $this->token($admin);
        $payment = $this->paidPayment('r');

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$payment->id}/dispute", ['reason' => 'Test'])->assertOk();

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$payment->id}/resolve-dispute", [
            'resolution' => 'rejected',
        ])->assertOk()->assertJsonPath('data.status', 'paid');

        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_une_contestation_retenue_reste_en_attente_puis_peut_etre_remboursee(): void
    {
        $admin = $this->makeUser('admin', 'admin.dispute4@example.com');
        $token = $this->token($admin);
        $payment = $this->paidPayment('u');

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$payment->id}/dispute", ['reason' => 'Test'])->assertOk();

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$payment->id}/resolve-dispute", [
            'resolution' => 'upheld',
        ])->assertOk()->assertJsonPath('data.status', 'disputed');

        $this->withToken($token)->postJson('/api/v1/admin/refunds', [
            'payment_id' => $payment->id,
            'reason' => 'Contestation retenue',
        ])->assertCreated();

        $this->assertSame('refunded', $payment->fresh()->status);
    }

    public function test_resoudre_un_paiement_qui_nest_pas_conteste_echoue(): void
    {
        $admin = $this->makeUser('admin', 'admin.dispute5@example.com');
        $token = $this->token($admin);
        $payment = $this->paidPayment('n');

        $this->withToken($token)->postJson("/api/v1/admin/payments/{$payment->id}/resolve-dispute", [
            'resolution' => 'rejected',
        ])->assertStatus(422);
    }
}
