<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\SuggestionLog;
use App\Services\CartService;
use App\Services\SuggestionService;
use Illuminate\Http\Request;

class PilihinAkuController extends Controller
{
    /**
     * Show questionnaire or suggestion results.
     */
    public function index(Request $request, SuggestionService $suggestionService)
    {
        $hasSearched = $request->has('budget');
        $suggestions = [];
        $logId = null;
        $budget = (int) $request->input('budget', 20000);
        $mood = strtolower($request->input('mood', 'bebas'));

        if ($hasSearched) {
            $validated = $request->validate([
                'budget' => 'required|integer|min:5000',
                'mood' => 'nullable|string|in:segar,hangat,pedas,bebas,Segar,Hangat,Pedas,Bebas',
            ], [
                'budget.min' => 'Budget minimal Rp5.000.',
                'budget.required' => 'Masukkan budget kamu terlebih dahulu.',
                'budget.integer' => 'Budget harus berupa angka.',
            ]);

            $budget = (int) $validated['budget'];
            $mood = strtolower($validated['mood'] ?? 'bebas');

            $suggestions = $suggestionService->getSuggestions($budget, $mood);

            $log = SuggestionLog::create([
                'budget' => $budget,
                'mood' => $mood,
                'results_count' => count($suggestions),
                'chosen' => false,
            ]);

            $logId = $log->id;
        }

        return view('buyer.pilihin-aku', compact(
            'hasSearched',
            'suggestions',
            'budget',
            'mood',
            'logId'
        ));
    }

    /**
     * Add the chosen suggestion items to the cart and redirect to /keranjang.
     */
    public function order(Request $request, CartService $cartService)
    {
        $request->validate([
            'menu_ids' => 'required|array|min:1|max:2',
            'menu_ids.*' => 'required|integer|exists:menus,id',
            'log_id' => 'nullable|integer|exists:suggestion_logs,id',
            'force' => 'nullable|boolean',
        ]);

        $menuIds = $request->input('menu_ids');
        $menus = Menu::with('canteen')->whereIn('id', $menuIds)->get();

        if ($menus->isEmpty()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Menu tidak ditemukan.'], 404);
            }
            return redirect()->back()->with('error', 'Menu tidak ditemukan.');
        }

        $force = (bool) $request->input('force', false);

        // Add first item (handles canteen conflict if cart contains other canteen)
        $firstResult = $cartService->add($menus->first(), 1, null, $force);

        if (!$firstResult['success']) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($firstResult);
            }
            if ($firstResult['conflict'] ?? false) {
                return redirect()->back()->with('conflict', $firstResult);
            }
            return redirect()->back()->with('error', $firstResult['message'] ?? 'Gagal menambahkan menu.');
        }

        // Add second item if combo
        if ($menus->count() > 1) {
            $secondMenu = $menus->skip(1)->first();
            $cartService->add($secondMenu, 1, null, false);
        }

        // Mark research log as chosen
        if ($logId = $request->input('log_id')) {
            SuggestionLog::where('id', $logId)->update(['chosen' => true]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'redirect' => route('cart.index'),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Paket pilihan berhasil ditambahkan!');
    }
}

