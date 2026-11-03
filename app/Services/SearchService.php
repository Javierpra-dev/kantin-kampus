<?php

namespace App\Services;

use App\Models\Canteen;
use App\Models\Menu;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * Search menus and canteens based on query string and optional filter chip.
     *
     * @param string|null $query
     * @param string|null $filter
     * @param string|null $category
     * @param int|null $maxPrice
     * @return array
     */
    public function search(?string $query = '', ?string $filter = null, ?string $category = null, ?int $maxPrice = null): array
    {
        $normalizedQuery = $this->normalizeQuery($query ?? '');
        $hasValidQuery = mb_strlen($normalizedQuery) >= 2;
        $hasFilter = !empty($filter) && $filter !== 'Semua';
        $hasCategory = !empty($category);
        $hasMaxPrice = !is_null($maxPrice) && $maxPrice > 0;

        // Short query with no filter/category/price returns empty results
        if (!$hasValidQuery && !$hasFilter && !$hasCategory && !$hasMaxPrice) {
            return [
                'query' => $normalizedQuery,
                'filter' => $filter ?? 'Semua',
                'menus' => [],
                'canteens' => [],
                'total' => 0,
            ];
        }

        $synonyms = config('search.synonyms', []);
        $words = $hasValidQuery ? explode(' ', $normalizedQuery) : [];

        // Eager load canteen to prevent N+1
        $menus = Menu::with('canteen')->get();

        // Filter menus
        $filteredMenus = $menus->filter(function (Menu $menu) use ($words, $hasValidQuery, $filter, $hasFilter, $category, $hasCategory, $maxPrice, $hasMaxPrice, $synonyms) {
            // Apply category / price chip filter if active
            if ($hasFilter) {
                if ($filter === 'Di bawah Rp10.000') {
                    if ($menu->effectivePrice() >= 10000) {
                        return false;
                    }
                } else {
                    if (strcasecmp($menu->category, $filter) !== 0) {
                        return false;
                    }
                }
            }

            // Apply direct category filter if passed
            if ($hasCategory) {
                if (strcasecmp($menu->category, $category) !== 0) {
                    return false;
                }
            }

            // Apply direct max price filter if passed
            if ($hasMaxPrice) {
                if ($menu->effectivePrice() > $maxPrice) {
                    return false;
                }
            }

            // If no search words, match by filters
            if (!$hasValidQuery) {
                return true;
            }

            // Split into words; every word must match (AND)
            foreach ($words as $word) {
                if (!$this->matchesWord($menu, $word, $synonyms)) {
                    return false;
                }
            }

            return true;
        });

        // Rank results
        $rankedMenus = $this->rankMenus($filteredMenus, $normalizedQuery)->take(30);

        // Format menus for JSON output
        $formattedMenus = $rankedMenus->map(function (Menu $menu) {
            $isAvailable = $menu->is_available && !$menu->isOutOfStock();
            $canteenOpen = (bool) ($menu->canteen->is_open ?? false);
            $isOrderable = $isAvailable && $canteenOpen;

            return [
                'id' => $menu->id,
                'name' => $menu->name,
                'description' => $menu->description,
                'category' => $menu->category,
                'price' => (int) $menu->price,
                'promo_price' => $menu->promo_price !== null ? (int) $menu->promo_price : null,
                'effective_price' => (int) $menu->effectivePrice(),
                'is_promo_active' => (bool) $menu->isPromoActive(),
                'daily_stock' => $menu->daily_stock !== null ? (int) $menu->daily_stock : null,
                'is_available' => (bool) $menu->is_available,
                'is_out_of_stock' => (bool) $menu->isOutOfStock(),
                'is_orderable' => $isOrderable,
                'photo_url' => $menu->photo_url,
                'canteen' => [
                    'id' => $menu->canteen->id,
                    'name' => $menu->canteen->name,
                    'is_open' => $canteenOpen,
                ],
            ];
        })->values()->all();

        // Search matching canteens (max 2)
        $matchingCanteens = [];
        if ($hasValidQuery) {
            $matchingCanteens = Canteen::where('name', 'like', "%{$normalizedQuery}%")
                ->take(2)
                ->get()
                ->map(function (Canteen $canteen) {
                    return [
                        'id' => $canteen->id,
                        'name' => $canteen->name,
                        'is_open' => (bool) $canteen->is_open,
                        'photo_url' => $canteen->photo_url,
                        'description' => $canteen->description,
                    ];
                })
                ->values()
                ->all();
        }

        return [
            'query' => $normalizedQuery,
            'filter' => $filter ?? 'Semua',
            'menus' => $formattedMenus,
            'canteens' => $matchingCanteens,
            'total' => count($formattedMenus),
        ];
    }

    /**
     * Check if a menu item matches a single query word directly or via synonyms.
     */
    protected function matchesWord(Menu $menu, string $word, array $synonyms): bool
    {
        $name = mb_strtolower($menu->name);
        $desc = mb_strtolower($menu->description ?? '');
        $cat = mb_strtolower($menu->category);
        $canteenName = mb_strtolower($menu->canteen->name ?? '');
        $tags = array_map('mb_strtolower', (array) ($menu->flavor_tags ?? []));

        // 1. Direct match on menu name, description, category, canteen name, or tags
        if (
            str_contains($name, $word) ||
            str_contains($desc, $word) ||
            str_contains($cat, $word) ||
            str_contains($canteenName, $word) ||
            in_array($word, $tags, true)
        ) {
            return true;
        }

        // 2. Synonym match
        if (isset($synonyms[$word])) {
            $rule = $synonyms[$word];

            // Match flavor tags
            if (!empty($rule['flavor_tags'])) {
                foreach ($rule['flavor_tags'] as $tag) {
                    if (in_array(mb_strtolower($tag), $tags, true)) {
                        return true;
                    }
                }
            }

            // Match categories
            if (!empty($rule['categories'])) {
                foreach ($rule['categories'] as $ruleCat) {
                    if (strcasecmp($cat, mb_strtolower($ruleCat)) === 0) {
                        return true;
                    }
                }
            }

            // Match name terms
            if (!empty($rule['name_terms'])) {
                foreach ($rule['name_terms'] as $term) {
                    if (str_contains($name, mb_strtolower($term))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Rank menus:
     * 1. Available in open canteen first (score 0 vs 1)
     * 2. Name starts with full query (0), name contains full query (1), others (2)
     * 3. Lowest effective price
     */
    protected function rankMenus(Collection $menus, string $query): Collection
    {
        return $menus->sort(function (Menu $a, Menu $b) use ($query) {
            $aAvailable = $a->is_available && !$a->isOutOfStock() && ($a->canteen->is_open ?? false);
            $bAvailable = $b->is_available && !$b->isOutOfStock() && ($b->canteen->is_open ?? false);

            // Available in open canteen first
            if ($aAvailable !== $bAvailable) {
                return $aAvailable ? -1 : 1;
            }

            // Query relevance ranking
            if ($query !== '') {
                $aName = mb_strtolower($a->name);
                $bName = mb_strtolower($b->name);

                $aRank = str_starts_with($aName, $query) ? 0 : (str_contains($aName, $query) ? 1 : 2);
                $bRank = str_starts_with($bName, $query) ? 0 : (str_contains($bName, $query) ? 1 : 2);

                if ($aRank !== $bRank) {
                    return $aRank <=> $bRank;
                }
            }

            // Tie-break by lowest effective price
            return $a->effectivePrice() <=> $b->effectivePrice();
        })->values();
    }

    /**
     * Clean and normalize search query.
     */
    protected function normalizeQuery(string $query): string
    {
        $query = mb_strtolower(trim($query));
        return preg_replace('/\s+/', ' ', $query) ?? $query;
    }
}

