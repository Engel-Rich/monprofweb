<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class StorePayementServiceRequest extends FormRequest
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
            'payment_provider_id' => 'nullable|integer|exists:payment_providers,id',
            'title' => 'required|string',
            'img' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
            'description' => 'nullable|string',
            'status' => 'required|integer',
            'subtitle' => 'required|string',
            'is_active' => 'required|integer|in:0,1',
            'reg_exp' => 'nullable|string',
            'subscription_id' => 'nullable|integer',
            'sens' => 'nullable|in:IN,OUT',
        ];
    }
}
