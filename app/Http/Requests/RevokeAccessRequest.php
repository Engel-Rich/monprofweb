<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevokeAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->user()?->rule_id === 1;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Le motif de la révocation est obligatoire.',
        ];
    }
}
