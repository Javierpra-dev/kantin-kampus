<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menus';

    protected $fillable = [
        'canteen_id',
        'name',
        'description',
        'price',
        'category',
        'photo',
        'is_available',
        'is_popular',
        'flavor_tags',
        'daily_stock',
        'promo_price',
        'promo_start',
        'promo_end',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_available' => 'boolean',
        'is_popular' => 'boolean',
        'flavor_tags' => 'array',
        'daily_stock' => 'integer',
        'promo_price' => 'integer',
    ];

    /**
     * Check if the promotional price is currently active.
     */
    public function isPromoActive(): bool
    {
        if ($this->promo_price === null || empty($this->promo_start) || empty($this->promo_end)) {
            return false;
        }

        $now = now('Asia/Jakarta')->format('H:i:s');
        $start = strlen($this->promo_start) === 5 ? $this->promo_start . ':00' : $this->promo_start;
        $end = strlen($this->promo_end) === 5 ? $this->promo_end . ':00' : $this->promo_end;

        if ($start <= $end) {
            return $now >= $start && $now <= $end;
        }

        // In case promo spans across midnight
        return $now >= $start || $now <= $end;
    }

    /**
     * Get the effective price of the menu (promo price if active, otherwise standard price).
     */
    public function effectivePrice(): int
    {
        return $this->isPromoActive() ? (int) $this->promo_price : (int) $this->price;
    }

    /**
     * Check if menu is out of daily stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->daily_stock !== null && $this->daily_stock <= 0;
    }

    public function getEffectivePriceAttribute(): int
    {
        return $this->effectivePrice();
    }

    public function getIsPromoActiveAttribute(): bool
    {
        return $this->isPromoActive();
    }

    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get image URL handling both external URLs and locally uploaded storage files.
     */
    public function getPhotoUrlAttribute(): string
    {
        if (!$this->photo) {
            return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400';
        }

        if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
            return $this->photo;
        }

        return asset('storage/' . ltrim($this->photo, '/'));
    }
}
