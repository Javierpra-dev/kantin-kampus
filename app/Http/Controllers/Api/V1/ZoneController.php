<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ZoneResource;
use App\Models\Zone;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ZoneController extends Controller
{
    /**
     * GET /api/v1/zones
     */
    public function index(): AnonymousResourceCollection
    {
        $zones = Zone::where('is_active', true)->get();

        return ZoneResource::collection($zones);
    }
}

