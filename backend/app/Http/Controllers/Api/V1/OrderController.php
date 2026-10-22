<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $supplier = Supplier::findOrFail($data['supplier_id']);

        $order = Order::create([
            'reference' => 'ORD-'.strtoupper((string) Str::ulid()),
            'user_id' => $request->user()->id,
            'supplier_id' => $supplier->id,
            'description' => $data['description'],
            'amount_minor' => $data['amount_minor'],
            // La devise vient du fournisseur, jamais du client.
            'currency' => $supplier->settlement_currency,
            'status' => OrderStatus::Created->value,
        ]);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        // 404 plutôt que 403 : on ne révèle pas l'existence des commandes des autres.
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);

        return new OrderResource($order);
    }
}
