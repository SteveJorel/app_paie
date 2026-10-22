<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'supplier_id' => $this->supplier_id,
            'description' => $this->description,
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
