<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function settlements(Request $request)
    {
        $paidVolume = DB::table('payments')
            ->selectRaw('currency, count(*) as payments_paid_count, sum(amount_minor) as volume_paid_minor')
            ->where('status', 'paid')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        $commissions = DB::table('ledger_entries')
            ->selectRaw('currency, sum(amount_minor) as commissions_minor')
            ->where('entry_type', 'commission')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        $settlementsPending = DB::table('settlements')
            ->selectRaw('currency, sum(amount_minor) as settlements_pending_minor')
            ->where('status', 'pending')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        $settlementsSettled = DB::table('settlements')
            ->selectRaw('currency, sum(amount_minor) as settlements_settled_minor')
            ->where('status', 'settled')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        $currencies = collect()
            ->merge($paidVolume->keys())
            ->merge($commissions->keys())
            ->merge($settlementsPending->keys())
            ->merge($settlementsSettled->keys())
            ->unique()
            ->values();

        $byCurrency = [];

        foreach ($currencies as $currency) {
            $byCurrency[$currency] = [
                'payments_paid_count' => (int) ($paidVolume[$currency]->payments_paid_count ?? 0),
                'volume_paid_minor' => (int) ($paidVolume[$currency]->volume_paid_minor ?? 0),
                'commissions_minor' => (int) ($commissions[$currency]->commissions_minor ?? 0),
                'settlements_pending_minor' => (int) ($settlementsPending[$currency]->settlements_pending_minor ?? 0),
                'settlements_settled_minor' => (int) ($settlementsSettled[$currency]->settlements_settled_minor ?? 0),
            ];
        }

        return response()->json([
            'data' => [
                'by_currency' => $byCurrency,
                'payments_failed_count' => (int) DB::table('payments')->where('status', 'failed')->count(),
                'payments_expired_count' => (int) DB::table('payments')->where('status', 'expired')->count(),
                'anomalies_count' => (int) DB::table('payment_events')->where('status', 'anomaly')->count(),
                'amount_mismatches_count' => (int) DB::table('payment_events')->where('status', 'amount_mismatch')->count(),
            ],
        ]);
    }
}
