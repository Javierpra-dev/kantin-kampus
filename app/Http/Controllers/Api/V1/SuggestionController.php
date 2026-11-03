<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SuggestionRequest;
use App\Http\Resources\Api\V1\MenuResource;
use App\Models\SuggestionLog;
use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;

class SuggestionController extends Controller
{
    /**
     * POST /api/v1/suggestions
     */
    public function suggestions(SuggestionRequest $request, SuggestionService $suggestionService): JsonResponse
    {
        $budget = (int) $request->input('budget');
        $mood = $request->input('mood', 'bebas');

        $suggestions = $suggestionService->getSuggestions($budget, $mood);

        // Record suggestion log
        SuggestionLog::create([
            'budget' => $budget,
            'mood' => $mood ?? 'bebas',
            'results_count' => count($suggestions),
            'chosen' => false,
        ]);

        $formatted = array_map(function ($item) {
            return [
                'canteen' => [
                    'id' => $item['canteen']->id,
                    'name' => $item['canteen']->name,
                    'photo_url' => $item['canteen']->photo_url,
                    'is_open' => (bool) $item['canteen']->is_open,
                ],
                'is_combo' => (bool) $item['is_combo'],
                'total' => (int) $item['total_price'],
                'main' => new MenuResource($item['main']),
                'drink' => !empty($item['drink']) ? new MenuResource($item['drink']) : null,
            ];
        }, $suggestions);

        return response()->json([
            'data' => $formatted,
        ]);
    }
}

