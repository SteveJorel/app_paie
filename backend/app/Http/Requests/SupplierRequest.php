<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // POST = création (champs obligatoires) ; PATCH = modification partielle.
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'business_name' => [$required, 'string', 'max:150'],
            'country_code' => [$required, Rule::in(['CM', 'NG'])],
            'category' => [$required, 'string', 'max:80'],
            'settlement_currency' => [$required, Rule::in(['XAF', 'NGN'])],
            'contact_email' => ['nullable', 'email:rfc', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'owner_user_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'supplier')],
        ];
    }
}