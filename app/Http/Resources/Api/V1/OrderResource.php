<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'order_code' => $this->order_code,
            'order_token' => $this->access_token,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'customer_name' => $this->customer_name,
            'customer_whatsapp' => $this->customer_phone,
            'canteen' => [
                'id' => $this->canteen_id,
                'name' => $this->canteen ? $this->canteen->name : null,
            ],
            'zone' => [
                'id' => $this->zone_id,
                'name' => $this->zone ? $this->zone->name : null,
                'delivery_fee' => (int) $this->delivery_fee,
            ],
            'location_note' => $this->location_notes,
            'subtotal' => (int) $this->subtotal,
            'delivery_fee' => (int) $this->delivery_fee,
            'total' => (int) $this->total_amount,
            'estimate_text' => null,
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'processed_at' => $this->processed_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
                'completed_at' => $this->completed_at?->toIso8601String(),
                'payment_expired_at' => $this->expires_at?->toIso8601String(),
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items', $this->items ?? [])),
        ];
    }
}

