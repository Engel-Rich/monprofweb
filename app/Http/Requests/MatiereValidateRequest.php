<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatiereValidateRequest extends FormRequest
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
            'libelle' => "required|unique:matieres,libelle",    
            'app_name'=> 'string|unique:matieres,app_name',
            'description' => "",            
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'app_name'=> $this->input('app_name')?:$this->input('libelle')
        ]);
    }
}
