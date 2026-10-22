<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'order_id' => $this->order_id,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
            'confirmed_at' => $this->confirmed_at,
            'created_at' => $this->created_at,
        ];
    }
}
