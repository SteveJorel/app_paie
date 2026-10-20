<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = $request->user()?->role === 'admin';

        return [
            'id' => $this->id,
            'business_name' => $this->business_name,
            'country_code' => $this->country_code,
            'category' => $this->category,
            'settlement_currency' => $this->settlement_currency,
            // Informations réservées à l'administrateur.
            $this->mergeWhen($isAdmin, [
                'status' => $this->status,
                'contact_email' => $this->contact_email,
                'contact_phone' => $this->contact_phone,
                'owner_user_id' => $this->owner_user_id,
                'created_at' => $this->created_at,
            ]),
        ];
    }
}