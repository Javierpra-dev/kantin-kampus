<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
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
            'canteen_id' => ['required', 'integer', 'exists:canteens,id'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_whatsapp' => ['nullable', 'string', 'regex:/^(\+62|62|0)8[1-9][0-9]{6,10}$/'],
            'customer_phone' => ['nullable', 'string'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'location_note' => ['nullable', 'string', 'max:255'],
            'location_notes' => ['nullable', 'string', 'max:255'],
            'location_photo' => ['nullable', 'image', 'max:2048'],
            'scheduled_for' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom Indonesian validation error messages.
     */
    public function messages(): array
    {
        return [
            'canteen_id.required' => 'Kantin wajib dipilih.',
            'canteen_id.exists' => 'Kantin yang dipilih tidak valid.',
            'customer_name.required' => 'Nama pemesan wajib diisi.',
            'customer_whatsapp.regex' => 'Format nomor WhatsApp tidak valid. Gunakan format Indonesia (misal: 08123456789).',
            'zone_id.required' => 'Zona pengantaran wajib dipilih.',
            'zone_id.exists' => 'Zona pengantaran tidak valid.',
            'location_photo.image' => 'Foto lokasi harus berupa file gambar.',
            'location_photo.max' => 'Ukuran foto lokasi maksimal 2 MB.',
            'items.required' => 'Daftar menu pesanan wajib diisi.',
            'items.array' => 'Daftar menu pesanan harus berupa array.',
            'items.min' => 'Pesanan harus berisi minimal 1 menu.',
            'items.*.menu_id.required' => 'Menu ID wajib diisi.',
            'items.*.menu_id.exists' => 'Menu yang dipilih tidak ditemukan.',
            'items.*.quantity.required' => 'Jumlah porsi wajib diisi.',
            'items.*.quantity.min' => 'Jumlah porsi minimal 1.',
        ];
    }

    /**
     * Return consistent JSON format on validation error.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Data pesanan yang diberikan tidak valid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}

