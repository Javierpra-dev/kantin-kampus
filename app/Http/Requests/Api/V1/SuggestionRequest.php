<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SuggestionRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'budget' => ['required', 'integer', 'min:5000'],
            'mood' => ['nullable', 'string', 'in:segar,hangat,pedas,bebas'],
        ];
    }

    /**
     * Custom Indonesian validation error messages.
     */
    public function messages(): array
    {
        return [
            'budget.required' => 'Budget wajib diisi.',
            'budget.integer' => 'Budget harus berupa angka.',
            'budget.min' => 'Budget minimal Rp5.000.',
            'mood.in' => 'Mood yang dipilih harus salah satu dari: segar, hangat, pedas, bebas.',
        ];
    }

    /**
     * Return consistent JSON format on validation error.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Data input tidak valid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}

