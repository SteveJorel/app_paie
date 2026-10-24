<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'country' => ['required', 'string', 'size:2', 'exists:countries,code'],
        ]);

        $rows = DB::table('country_payment_methods')
            ->join('payment_methods', 'payment_methods.code', '=', 'country_payment_methods.payment_method_code')
            ->where('country_payment_methods.country_code', strtoupper($data['country']))
            ->where('country_payment_methods.status', 'active')
            ->select(['payment_methods.code', 'payment_methods.name'])
            ->get();

        return response()->json(['data' => $rows]);
    }
}
