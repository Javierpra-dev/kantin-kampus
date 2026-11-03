<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'canteen_id' => $this->canteen_id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'price' => (int) $this->price,
            'promo_price' => $this->promo_price !== null ? (int) $this->promo_price : null,
            'effective_price' => (int) $this->effectivePrice(),
            'is_promo_active' => (bool) $this->isPromoActive(),
            'daily_stock' => $this->daily_stock !== null ? (int) $this->daily_stock : null,
            'is_available' => (bool) $this->is_available,
            'is_out_of_stock' => (bool) $this->isOutOfStock(),
            'flavor_tags' => (array) ($this->flavor_tags ?? []),
            'photo_url' => $this->photo_url,
            'canteen' => $this->whenLoaded('canteen', function () {
                return [
                    'id' => $this->canteen->id,
                    'name' => $this->canteen->name,
                    'is_open' => (bool) $this->canteen->is_open,
                ];
            }),
        ];
    }
}

