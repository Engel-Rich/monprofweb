<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayementServiceRequest extends FormRequest
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
            'payment_provider_id' => 'sometimes|nullable|integer|exists:payment_providers,id',
            'title' => 'sometimes|required|string',
            'img' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|integer',
            'subtitle' => 'sometimes|required|string',
            'is_active' => 'sometimes|required|integer|in:0,1',
            'reg_exp' => 'nullable|string',
            'subscription_id' => 'nullable|integer',
            'sens' => 'nullable|in:IN,OUT',
        ];  //
    }
}
