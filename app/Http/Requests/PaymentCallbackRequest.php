<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentCallbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "pay_token" => "nullable|string",
            "status" => "required|string",
            "transaction_id" => "string|nullable",
            "raison_reject" => "nullable|string",
            "reference" => "nullable|string",
            "external_reference" => "nullable|string",
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->transaction_id) && empty($this->reference) && empty($this->external_reference)) {
                $validator->errors()->add('transaction_id', 'Au moins un identifiant de transaction est requis.');
            }
        });
    }
}
