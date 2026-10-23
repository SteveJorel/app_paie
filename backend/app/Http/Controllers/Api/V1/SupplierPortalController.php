<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPortalController extends Controller
{
    private function supplierFor(Request $request): Supplier
    {
        return Supplier::where('owner_user_id', $request->user()->id)->firstOrFail();
    }

    public function transactions(Request $request)
    {
        $supplier = $this->supplierFor($request);

        $rows = DB::table('allocations')
            ->join('payments', 'payments.id', '=', 'allocations.payment_id')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('allocations.beneficiary_type', 'supplier')
            ->where('allocations.beneficiary_id', $supplier->id)
            ->select([
                'payments.reference as payment_reference',
                'orders.reference as order_reference',
                'orders.description',
                'payments.status as payment_status',
                'payments.currency',
                'allocations.gross_amount_minor',
                'allocations.fees_minor',
                'allocations.net_amount_minor',
                'allocations.status as allocation_status',
                'payments.confirmed_at',
                'payments.created_at',
            ])
            ->orderByDesc('payments.created_at')
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($rows);
    }

    public function settlements(Request $request)
    {
        $supplier = $this->supplierFor($request);

        $rows = DB::table('settlements')
            ->where('supplier_id', $supplier->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($rows);
    }
}
