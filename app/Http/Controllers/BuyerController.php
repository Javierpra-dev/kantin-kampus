<?php

namespace App\Http\Controllers;

use App\Models\Canteen;
use App\Models\Menu;
use App\Services\CartService;
use App\Services\SearchService;
use Illuminate\Http\Request;

class BuyerController extends Controller
{
    /**
     * Home page (/)
     */
    public function index(Request $request)
    {
        $search = trim($request->input('q', ''));
        $filter = $request->input('filter', 'Semua');

        $query = Canteen::query()->with(['menus']);

        // Search logic: by canteen name OR menu name
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('menus', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('description', 'like', "%{$search}%");
                  });
            });
        }

        // Filter chips logic:
        // 'Semua', 'Makanan berat', 'Minuman', 'Camilan', 'Di bawah Rp10.000'
        if ($filter !== 'Semua') {
            if ($filter === 'Di bawah Rp10.000') {
                $query->whereHas('menus', function ($mq) {
                    $mq->where('price', '<', 10000);
                });
            } else {
                $query->whereHas('menus', function ($mq) use ($filter) {
                    $mq->where('category', $filter);
                });
            }
        }

        $canteens = $query->get()->map(function ($canteen) use ($filter, $search) {
            // Pick two popular menu names
            $popularMenus = $canteen->menus->where('is_popular', true)->take(2);
            if ($popularMenus->count() < 2) {
                $popularMenus = $canteen->menus->take(2);
            }
            $canteen->popular_menu_names = $popularMenus->pluck('name')->implode(' • ');

            return $canteen;
        });

        $filterChips = [
            'Semua',
            'Makanan berat',
            'Minuman',
            'Camilan',
            'Di bawah Rp10.000',
        ];

        $isSearchingOrFiltering = ($search !== '' || $filter !== 'Semua');

        $moodRows = [];
        if (!$isSearchingOrFiltering) {
            $tagsConfig = [
                ['title' => 'Pengen yang seger-seger', 'tag' => 'segar'],
                ['title' => 'Pengen yang hangat-hangat', 'tag' => 'hangat'],
                ['title' => 'Pengen yang pedes-pedes', 'tag' => 'pedas'],
            ];

            foreach ($tagsConfig as $config) {
                $items = Menu::with('canteen')
                    ->where('is_available', true)
                    ->whereJsonContains('flavor_tags', $config['tag'])
                    ->latest('id')
                    ->take(8)
                    ->get();

                if ($items->isNotEmpty()) {
                    $moodRows[] = [
                        'title' => $config['title'],
                        'tag' => $config['tag'],
                        'menus' => $items,
                    ];
                }
            }
        }

        // Active afternoon promo menus from open canteens
        $promoMenus = collect();
        if (!$isSearchingOrFiltering) {
            $promoMenus = Menu::with('canteen')
                ->whereHas('canteen', function ($q) {
                    $q->where('is_open', true);
                })
                ->where('is_available', true)
                ->whereNotNull('promo_price')
                ->whereNotNull('promo_start')
                ->whereNotNull('promo_end')
                ->get()
                ->filter(function ($menu) {
                    return $menu->isPromoActive() && !$menu->isOutOfStock();
                })
                ->values();
        }

        $cart = app(CartService::class)->get();

        return view('buyer.home', compact('canteens', 'search', 'filter', 'filterChips', 'isSearchingOrFiltering', 'moodRows', 'promoMenus', 'cart'));
    }

    /**
     * JSON search endpoint for live buyer search (/cari?q=...&filter=...).
     */
    public function search(Request $request, SearchService $searchService)
    {
        $q = $request->input('q', '');
        $filter = $request->input('filter', 'Semua');

        $result = $searchService->search($q, $filter);

        return response()->json($result);
    }

    /**
     * Canteen menu page (/kantin/{id})
     */
    public function show($id, CartService $cartService)
    {
        $canteen = Canteen::with(['menus' => function ($q) {
            $q->orderBy('is_available', 'desc')->orderBy('category');
        }])->findOrFail($id);

        $cart = $cartService->get();
        $isCartFromThisCanteen = ($cart['canteen_id'] === (int) $id);

        return view('buyer.canteen-menu', compact('canteen', 'cart', 'isCartFromThisCanteen'));
    }

    /**
     * Riwayat pesanan page (/riwayat)
     */
    public function history()
    {
        $orderCodes = session('orders_history', []);
        $orders = !empty($orderCodes)
            ? \App\Models\Order::with(['canteen', 'zone', 'items'])
                ->whereIn('order_code', $orderCodes)
                ->orderBy('id', 'desc')
                ->get()
            : collect();

        return view('buyer.history', compact('orders'));
    }
}
