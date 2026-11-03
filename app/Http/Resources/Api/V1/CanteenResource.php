<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CanteenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $popularMenuNames = $this->menus ? $this->menus->where('is_popular', true)->pluck('name')->values()->all() : [];
        if (empty($popularMenuNames) && $this->menus) {
            $popularMenuNames = $this->menus->take(2)->pluck('name')->values()->all();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'photo_url' => $this->photo_url,
            'is_open' => (bool) $this->is_open,
            'open_time' => $this->open_time,
            'close_time' => $this->close_time,
            'popular_menus' => $popularMenuNames,
            'crowd_level' => null,
            'active_orders_count' => null,
            'estimate_minutes' => null,
        ];
    }
}

