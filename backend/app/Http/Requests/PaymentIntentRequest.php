<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    public function rules(): array
    {
        return [
            // La disponibilité réelle de chaque moyen par pays sera confirmée avec le prestataire (EF-04).
            'payment_method' => ['required', Rule::in(['mobile_money', 'card', 'bank_transfer'])],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:100', 'regex:/^[A-Za-z0-9_\-:.]+$/'],
        ];
    }
}
