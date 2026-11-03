<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MenuResource;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MenuController extends Controller
{
    /**
     * GET /api/v1/menus/popular
     * Return up to 8 popular and available menus from open canteens.
     */
    public function popular(): AnonymousResourceCollection
    {
        $menus = Menu::with('canteen')
            ->whereHas('canteen', function ($q) {
                $q->where('is_open', true);
            })
            ->where('is_available', true)
            ->where('is_popular', true)
            ->get()
            ->filter(fn (Menu $m) => !$m->isOutOfStock())
            ->take(8);

        // If less than 8, backfill with available items
        if ($menus->count() < 8) {
            $existingIds = $menus->pluck('id')->all();
            $more = Menu::with('canteen')
                ->whereHas('canteen', function ($q) {
                    $q->where('is_open', true);
                })
                ->where('is_available', true)
                ->whereNotIn('id', $existingIds)
                ->take(8 - $menus->count())
                ->get()
                ->filter(fn (Menu $m) => !$m->isOutOfStock());

            $menus = $menus->concat($more);
        }

        return MenuResource::collection($menus->values());
    }

    /**
     * GET /api/v1/menus/mood?tag=segar|hangat|pedas
     * Return up to 8 available menus matching the flavor tag.
     */
    public function mood(Request $request): AnonymousResourceCollection
    {
        $tag = strtolower(trim((string) $request->query('tag', '')));

        if (!in_array($tag, ['segar', 'hangat', 'pedas'], true)) {
            abort(422, 'Parameter tag harus salah satu dari: segar, hangat, pedas.');
        }

        $menus = Menu::with('canteen')
            ->whereHas('canteen', function ($q) {
                $q->where('is_open', true);
            })
            ->where('is_available', true)
            ->whereJsonContains('flavor_tags', $tag)
            ->latest('id')
            ->get()
            ->filter(fn (Menu $m) => !$m->isOutOfStock())
            ->take(8);

        return MenuResource::collection($menus->values());
    }
}

