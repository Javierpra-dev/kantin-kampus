<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $photoUrl = null;
        if ($this->photo) {
            $photoUrl = str_starts_with($this->photo, 'http') ? $this->photo : asset('storage/' . ltrim($this->photo, '/'));
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'delivery_fee' => (int) $this->delivery_fee,
            'description' => $this->description,
            'photo_url' => $photoUrl,
        ];
    }
}

