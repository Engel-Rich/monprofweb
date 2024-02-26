<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoursValidateRequest extends FormRequest
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
            "libelle" => 'string|required',
            "description" => 'string|required',
            'video' => 'required|file',
            'classe_id'=>'required|exists:classes,id',
            'matieres_id'=>'required|exists:matieres,id',
            'categorie_id'=>'required|exists:categories,id'
        ];
    }
}
