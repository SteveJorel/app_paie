<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'reference' => $this->reference,
            'order_reference' => $this->order_reference,
            'created_at' => Carbon::parse($this->created_at)->toIso8601String(),
            'confirmed_at' => $this->confirmed_at ? Carbon::parse($this->confirmed_at)->toIso8601String() : null,
            'supplier' => [
                'id' => (int) $this->supplier_id,
                'name' => $this->supplier_name,
            ],
            'description' => $this->description,
            'amount_minor' => (int) $this->amount_minor,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'receipt' => $this->receipt_number
                ? ['id' => (int) $this->receipt_id, 'number' => $this->receipt_number]
                : null,
        ];
    }
}