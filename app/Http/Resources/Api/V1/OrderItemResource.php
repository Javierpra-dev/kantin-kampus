<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_id' => $this->menu_id,
            'name' => $this->name ?? ($this->item_name ?? ''),
            'unit_price' => (int) ($this->unit_price ?? $this->price),
            'price' => (int) ($this->price ?? $this->unit_price),
            'quantity' => (int) $this->quantity,
            'subtotal' => (int) ($this->subtotal ?? (($this->unit_price ?? $this->price) * $this->quantity)),
            'note' => $this->note ?? ($this->notes ?? null),
        ];
    }
}

