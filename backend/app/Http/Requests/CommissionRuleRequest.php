<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CommissionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'currency' => ['required', Rule::in(['XAF', 'NGN'])],
            'type' => ['required', Rule::in(['percentage', 'fixed', 'combined'])],
            'percentage_bps' => ['required_if:type,percentage,combined', 'nullable', 'integer', 'min:0', 'max:10000'],
            'fixed_minor' => ['required_if:type,fixed,combined', 'nullable', 'integer', 'min:0', 'max:1000000000000'],
            // Une règle ne peut pas prendre effet dans le passé.
            'valid_from' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $bps = (int) $this->input('percentage_bps', 0);
                $fixed = (int) $this->input('fixed_minor', 0);

                $consistent = match ($this->input('type')) {
                    'percentage' => $bps > 0 && $fixed === 0,
                    'fixed' => $fixed > 0 && $bps === 0,
                    'combined' => $bps > 0 && $fixed > 0,
                    default => true,
                };

                if (! $consistent) {
                    $validator->errors()->add('type', 'Les valeurs ne correspondent pas au type de commission choisi.');
                }
            },
        ];
    }
}
