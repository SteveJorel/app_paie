<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CountryPaymentMethod;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;

class PaymentMethodAvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $query = CountryPaymentMethod::query();

        if ($request->filled('country')) {
            $query->where('country_code', strtoupper((string) $request->string('country')));
        }

        return response()->json([
            'data' => $query->orderBy('country_code')->orderBy('payment_method_code')->get(),
        ]);
    }

    public function update(Request $request, string $country, string $method)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending,active,inactive'],
        ]);

        $row = CountryPaymentMethod::where('country_code', strtoupper($country))
            ->where('payment_method_code', $method)
            ->firstOrFail();

        $previousStatus = $row->status;
        $row->update(['status' => $data['status']]);

        AuditLogger::record(
            actorId: $request->user()->id,
            actorRole: $request->user()->role,
            action: 'payment_method.availability_updated',
            auditableType: CountryPaymentMethod::class,
            auditableId: $row->id,
            changes: ['status' => [$previousStatus, $data['status']]],
        );

        return response()->json(['data' => $row->fresh()]);
    }
}
