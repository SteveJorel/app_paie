<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->join('suppliers', 'suppliers.id', '=', 'orders.supplier_id')
            ->select([
                'payments.id',
                'payments.reference as payment_reference',
                'orders.reference as order_reference',
                'orders.description',
                'payments.status',
                'payments.amount_minor',
                'payments.currency',
                'payments.payment_method',
                'payments.confirmed_at',
                'payments.created_at',
                'suppliers.id as supplier_id',
                'suppliers.business_name as supplier_name',
                'suppliers.country_code',
            ]);

        if ($request->filled('country_code')) {
            $query->where('suppliers.country_code', $request->string('country_code'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('suppliers.id', (int) $request->input('supplier_id'));
        }

        if ($request->filled('currency')) {
            $query->where('payments.currency', $request->string('currency'));
        }

        if ($request->filled('status')) {
            $query->where('payments.status', $request->string('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('payments.created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('payments.created_at', '<=', $request->date('to'));
        }

        if ($request->filled('reference')) {
            $reference = (string) $request->string('reference');
            $query->where(function ($q) use ($reference) {
                $q->where('payments.reference', $reference)
                    ->orWhere('orders.reference', $reference);
            });
        }

        $rows = $query->orderByDesc('payments.created_at')
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($rows);
    }
}
