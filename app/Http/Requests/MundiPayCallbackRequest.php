<?php

namespace App\Http\Requests;

use App\DTO\MundiPayWebhookPayload;
use Illuminate\Foundation\Http\FormRequest;

class MundiPayCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable'],
            'provider_reference' => ['nullable'],
            'transaction_id' => ['nullable'],
            'pay_token' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'result' => ['nullable', 'array'],
            'transaction' => ['nullable', 'array'],
            'data' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $payload = MundiPayWebhookPayload::normalize($this->all());

            if (blank($payload->providerReference)
                && blank($payload->paymentToken)
                && blank($payload->localReference)) {
                $validator->errors()->add('provider_reference', 'Le webhook MundiPay ne contient aucun identifiant exploitable.');
            }
        });
    }
}
