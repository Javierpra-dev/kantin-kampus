<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Canteen extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'photo',
        'is_open',
        'opening_hours',
        'open_time',
        'close_time',
        'phone',
        'whatsapp',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    /**
     * Get menu items for the canteen.
     */
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    /**
     * Alias for menus.
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    /**
     * Canteen admin user(s).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Orders placed at this canteen.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Helper to get popular menu items.
     */
    public function popularMenus()
    {
        return $this->menus()->where('is_popular', true)->limit(2);
    }

    /**
     * Get photo URL handling both external URLs and uploaded local storage files.
     */
    public function getPhotoUrlAttribute(): string
    {
        if (!$this->photo) {
            return asset('images/placeholder.svg');
        }

        if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
            return $this->photo;
        }

        return asset('storage/' . ltrim($this->photo, '/'));
    }
}
