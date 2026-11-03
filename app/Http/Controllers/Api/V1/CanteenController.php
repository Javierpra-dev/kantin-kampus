<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CanteenResource;
use App\Http\Resources\Api\V1\MenuResource;
use App\Models\Canteen;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CanteenController extends Controller
{
    /**
     * GET /api/v1/canteens
     */
    public function index(): AnonymousResourceCollection
    {
        $canteens = Canteen::with(['menus' => function ($q) {
            $q->where('is_available', true);
        }])->get();

        return CanteenResource::collection($canteens);
    }

    /**
     * GET /api/v1/canteens/{id}/menus
     */
    public function menus(int $id): AnonymousResourceCollection
    {
        $canteen = Canteen::findOrFail($id);

        $menus = $canteen->menus()
            ->with('canteen')
            ->orderBy('is_available', 'desc')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return MenuResource::collection($menus);
    }
}

