<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserValidateRequest extends FormRequest
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
            // 'rule_id'=> 'integer|exists:rules,id',
            'rule_id'=> 'integer',
            'name'=> 'required|max:50',
            'last_name'=> 'nullable|max:30',
            'phone'=> 'required|max:14',                
            'email'=> 'required|email',                
            'password'=> 'required|min:4',                
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'rule_id'=>1
        ]);
    }
}
