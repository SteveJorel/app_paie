<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RefundTest extends TestCase
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

    private function paidPayment(): Payment
    {
        $supplier = Supplier::factory()->create(['status' => 'active']);
        $order = Order::factory()->create([
            'supplier_id' => $supplier->id,
            'amount_minor' => 10000,
            'currency' => $supplier->settlement_currency,
            'status' => 'paid',
        ]);

        return Payment::create([
            'reference' => 'PAY-F'.$order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_refund_'.$order->id,
            'amount_minor' => $order->amount_minor,
            'currency' => $order->currency,
            'payment_method' => 'mobile_money',
            'status' => 'paid',
            'idempotency_key' => 'cle-refund-paid-'.$order->id,
            'confirmed_at' => now(),
        ]);
    }

    public function test_un_admin_rembourse_un_paiement_confirme(): void
    {
        $admin = $this->makeUser('admin', 'admin.refund1@example.com');
        $token = $this->token($admin);
        $payment = $this->paidPayment();

        $this->withToken($token)->postJson('/api/v1/admin/refunds', [
            'payment_id' => $payment->id,
            'reason' => 'Produit non livré',
        ])->assertCreated();

        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertDatabaseHas('refunds', ['payment_id' => $payment->id, 'status' => 'completed']);
        $this->assertDatabaseHas('receipts', ['payment_id' => $payment->id, 'receipt_type' => 'refund']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'refund.completed', 'auditable_id' => $payment->id]);
        $this->assertDatabaseHas('transaction_history', [
            'payment_id' => $payment->id,
            'new_status' => 'refunded',
            'event_type' => 'refund.completed',
        ]);
    }

    public function test_un_paiement_deja_rembourse_ne_peut_pas_letre_deux_fois(): void
    {
        $admin = $this->makeUser('admin', 'admin.refund2@example.com');
        $token = $this->token($admin);
        $payment = $this->paidPayment();

        $this->withToken($token)->postJson('/api/v1/admin/refunds', [
            'payment_id' => $payment->id, 'reason' => 'Premier remboursement',
        ])->assertCreated();

        $this->withToken($token)->postJson('/api/v1/admin/refunds', [
            'payment_id' => $payment->id, 'reason' => 'Deuxième tentative',
        ])->assertStatus(422);
    }

    public function test_un_paiement_non_confirme_ne_peut_pas_etre_rembourse(): void
    {
        $admin = $this->makeUser('admin', 'admin.refund3@example.com');
        $token = $this->token($admin);

        $supplier = Supplier::factory()->create(['status' => 'active']);
        $order = Order::factory()->create(['supplier_id' => $supplier->id]);
        $payment = Payment::create([
            'reference' => 'PAY-P'.$order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_pending_'.$order->id,
            'amount_minor' => $order->amount_minor,
            'currency' => $order->currency,
            'payment_method' => 'mobile_money',
            'status' => 'pending',
            'idempotency_key' => 'cle-refund-pending-'.$order->id,
        ]);

        $this->withToken($token)->postJson('/api/v1/admin/refunds', [
            'payment_id' => $payment->id, 'reason' => 'Test',
        ])->assertStatus(422);
    }

    public function test_un_client_ne_peut_pas_initier_un_remboursement(): void
    {
        $client = $this->makeUser('client', 'client.refund@example.com');
        $token = $this->token($client);
        $payment = $this->paidPayment();

        $this->withToken($token)->postJson('/api/v1/admin/refunds', [
            'payment_id' => $payment->id, 'reason' => 'Test',
        ])->assertForbidden();
    }
}
