<?php

namespace App\Http\Requests;

use App\Services\Commission\CommissionCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('status', 'active')],
            'description' => ['required', 'string', 'max:255'],
            'amount_minor' => ['required', 'integer', 'min:1', 'max:'.CommissionCalculator::MAX_AMOUNT_MINOR],
        ];
    }
}
