<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'access_token',
        'order_number',
        'canteen_id',
        'zone_id',
        'customer_name',
        'customer_phone',
        'location_notes',
        'location_photo',
        'had_location_photo',
        'delivery_notes',
        'subtotal',
        'delivery_fee',
        'total_amount',
        'status',
        'payment_method',
        'payment_status',
        'qr_string',
        'qr_url',
        'expires_at',
        'midtrans_transaction_id',
        'processed_at',
        'delivered_at',
        'completed_at',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'delivery_fee' => 'integer',
        'total_amount' => 'integer',
        'had_location_photo' => 'boolean',
        'expires_at' => 'datetime',
        'processed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
