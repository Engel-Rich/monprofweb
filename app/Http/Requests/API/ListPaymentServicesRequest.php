<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class ListPaymentServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('payment-services.*')) {
            if ($this->filled('sens')) {
                $this->merge([
                    'sens' => strtoupper((string) $this->input('sens')),
                ]);
            }

            return;
        }

        $this->merge([
            'sens' => 'IN',
        ]);
    }

    public function rules(): array
    {
        return [
            'sens' => ['nullable', 'in:IN,OUT'],
            'search' => ['nullable', 'string', 'max:100'],
            'provider_id' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
