<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * GET /api/v1/search?q=...&category=...&max_price=...
     */
    public function search(Request $request, SearchService $searchService): JsonResponse
    {
        $query = $request->query('q', '');
        $category = $request->query('category');
        $maxPrice = $request->query('max_price') ? (int) $request->query('max_price') : null;

        $results = $searchService->search(
            query: $query,
            filter: null,
            category: $category,
            maxPrice: $maxPrice
        );

        return response()->json([
            'data' => [
                'query' => $results['query'],
                'menus' => $results['menus'],
                'canteens' => $results['canteens'],
                'total' => $results['total'],
            ],
        ]);
    }
}

