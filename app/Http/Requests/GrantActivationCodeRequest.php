<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class GrantActivationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->user()?->rule_id === 1;
    }

    public function rules(): array
    {
        return [
            'eleve_id' => ['required', 'integer', 'exists:eleves,id'],
            'categorie_id' => ['required', 'integer', 'exists:categories,id'],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (filled($value) && ! PhoneNumber::isValidCameroonMobile($value)) {
                        $fail('Le numéro doit être un mobile camerounais valide (ex. 690000000).');
                    }
                },
            ],
            'reason' => ['nullable', 'string', 'max:500'],
            'send_sms' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'eleve_id' => 'élève',
            'categorie_id' => 'catégorie',
            'phone' => 'numéro SMS',
            'reason' => 'motif',
        ];
    }
}
