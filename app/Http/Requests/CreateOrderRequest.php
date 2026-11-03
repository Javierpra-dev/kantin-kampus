<?php

namespace App\Http\Requests;

use App\Models\Zone;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation (clean phone number).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('customer_phone') && $this->customer_phone !== null) {
            $cleaned = preg_replace('/[\s\-]/', '', $this->customer_phone);
            $this->merge(['customer_phone' => $cleaned !== '' ? $cleaned : null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => [
                'nullable',
                'string',
                'regex:/^(\+62|62|0)8[1-9][0-9]{6,11}$/',
            ],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'location_notes' => ['nullable', 'string', 'max:255'],
            'location_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }

    /**
     * Configure the validator instance.
     * Enforces: EITHER "Keterangan lokasi" OR "location_photo" is REQUIRED if zone is "Tidak yakin / lainnya".
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $zoneId = $this->input('zone_id');
            if ($zoneId) {
                $zone = Zone::find($zoneId);
                if ($zone && (
                    str_contains(strtolower($zone->name), 'tidak yakin') ||
                    str_contains(strtolower($zone->name), 'lainnya')
                )) {
                    if (blank($this->input('location_notes')) && !$this->hasFile('location_photo')) {
                        $validator->errors()->add(
                            'location_notes',
                            'Isi keterangan lokasi atau tambah foto supaya penjual bisa menemukanmu.'
                        );
                    }
                }
            }
        });
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Nama pemesan wajib diisi.',
            'customer_phone.regex' => 'Format nomor WhatsApp tidak valid. Gunakan format nomor Indonesia (contoh: 081234567890).',
            'zone_id.required' => 'Silakan pilih lokasi duduk Anda.',
            'zone_id.exists' => 'Zona lokasi duduk yang dipilih tidak valid.',
            'location_notes.required' => 'Keterangan lokasi wajib diisi.',
            'location_photo.image' => 'File harus berupa foto/gambar.',
            'location_photo.mimes' => 'Format foto harus berupa JPG, JPEG, PNG, atau WEBP.',
            'location_photo.max' => 'Ukuran foto maksimal 3 MB.',
        ];
    }
}
