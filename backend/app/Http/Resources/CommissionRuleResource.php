<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'currency' => $this->currency,
            'type' => $this->type,
            'percentage_bps' => $this->percentage_bps,
            'fixed_minor' => $this->fixed_minor,
            'valid_from' => $this->valid_from,
            'valid_until' => $this->valid_until,
            'version' => $this->version,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
