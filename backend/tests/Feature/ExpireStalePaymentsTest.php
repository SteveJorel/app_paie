<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpireStalePaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function makePayment(Supplier $supplier, string $status, \Carbon\Carbon $createdAt): Payment
    {
        $order = Order::factory()->create([
            'supplier_id' => $supplier->id,
            'currency' => $supplier->settlement_currency,
            'status' => 'pending',
        ]);

        $payment = Payment::create([
            'reference' => 'PAY-X'.$order->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'sandbox',
            'provider_reference' => 'sbx_expire_'.$order->id,
            'amount_minor' => $order->amount_minor,
            'currency' => $order->currency,
            'payment_method' => 'mobile_money',
            'status' => $status,
            'idempotency_key' => 'cle-expire-'.$order->id,
        ]);

        DB::table('payments')->where('id', $payment->id)->update(['created_at' => $createdAt]);

        return $payment->fresh();
    }

    public function test_un_paiement_trop_ancien_expire_et_libere_la_commande(): void
    {
        config(['payments.expiration_minutes' => 30]);
        $supplier = Supplier::factory()->create(['status' => 'active']);
        $payment = $this->makePayment($supplier, 'pending', now()->subHours(2));

        Artisan::call('payments:expire-stale');

        $this->assertSame('expired', $payment->fresh()->status);
        $this->assertSame('created', Order::find($payment->order_id)->status);
        $this->assertDatabaseHas('transaction_history', [
            'payment_id' => $payment->id,
            'new_status' => 'expired',
            'event_type' => 'payment.expired_auto',
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'payment_id' => $payment->id,
            'type' => 'payment.expired',
        ]);
    }

    public function test_un_paiement_recent_nest_pas_touche(): void
    {
        config(['payments.expiration_minutes' => 30]);
        $supplier = Supplier::factory()->create(['status' => 'active']);
        $payment = $this->makePayment($supplier, 'pending', now()->subMinutes(5));

        Artisan::call('payments:expire-stale');

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_un_paiement_deja_termine_nest_pas_touche(): void
    {
        config(['payments.expiration_minutes' => 30]);
        $supplier = Supplier::factory()->create(['status' => 'active']);
        $payment = $this->makePayment($supplier, 'paid', now()->subHours(5));

        Artisan::call('payments:expire-stale');

        $this->assertSame('paid', $payment->fresh()->status);
    }
}
