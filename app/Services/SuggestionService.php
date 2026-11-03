<?php

namespace App\Services;

use App\Models\Canteen;
use App\Models\Menu;

class SuggestionService
{
    /**
     * Generate combos or fallback single main items that fit the buyer's budget and mood.
     *
     * @param int $budget
     * @param string|null $mood ("segar", "hangat", "pedas", "bebas" / null)
     * @return array Array of suggestion items (max 3)
     */
    public function getSuggestions(int $budget, ?string $mood = null): array
    {
        $normalizedMood = $mood ? strtolower(trim($mood)) : 'bebas';
        if (!in_array($normalizedMood, ['segar', 'hangat', 'pedas'])) {
            $normalizedMood = 'bebas';
        }

        // Only open canteens and available menus
        $canteens = Canteen::where('is_open', true)
            ->with(['menus' => function ($q) {
                $q->where('is_available', true);
            }])
            ->get();

        $allCombos = [];

        foreach ($canteens as $canteen) {
            $mains = $canteen->menus->filter(function ($menu) {
                return $this->isMainCourse($menu->category) && !$menu->isOutOfStock();
            });

            $drinks = $canteen->menus->filter(function ($menu) {
                return $this->isDrink($menu->category) && !$menu->isOutOfStock();
            });

            foreach ($mains as $main) {
                foreach ($drinks as $drink) {
                    $totalPrice = (int) $main->effectivePrice() + (int) $drink->effectivePrice();
                    if ($totalPrice > $budget) {
                        continue;
                    }

                    if ($normalizedMood !== 'bebas') {
                        $mainHasMood = $this->hasFlavorTag($main, $normalizedMood);
                        $drinkHasMood = $this->hasFlavorTag($drink, $normalizedMood);
                        if (!$mainHasMood && !$drinkHasMood) {
                            continue;
                        }
                    }

                    // Score: budget ratio (0..100) + random jitter (0..25) to prefer higher budget usage with variety
                    $budgetRatio = $budget > 0 ? ($totalPrice / $budget) : 0;
                    $score = ($budgetRatio * 100) + mt_rand(0, 25);

                    $allCombos[] = [
                        'canteen' => $canteen,
                        'canteen_id' => $canteen->id,
                        'main' => $main,
                        'drink' => $drink,
                        'is_combo' => true,
                        'total_price' => $totalPrice,
                        'score' => $score,
                    ];
                }
            }
        }

        if (!empty($allCombos)) {
            return $this->selectTopSuggestions($allCombos, 3);
        }

        // Fallback: single main item that fits the budget
        $fallbackMains = [];

        foreach ($canteens as $canteen) {
            $mains = $canteen->menus->filter(function ($menu) {
                return $this->isMainCourse($menu->category) && !$menu->isOutOfStock();
            });

            foreach ($mains as $main) {
                $price = (int) $main->effectivePrice();
                if ($price > $budget) {
                    continue;
                }

                if ($normalizedMood !== 'bebas' && !$this->hasFlavorTag($main, $normalizedMood)) {
                    continue;
                }

                $budgetRatio = $budget > 0 ? ($price / $budget) : 0;
                $score = ($budgetRatio * 100) + mt_rand(0, 25);

                $fallbackMains[] = [
                    'canteen' => $canteen,
                    'canteen_id' => $canteen->id,
                    'main' => $main,
                    'drink' => null,
                    'is_combo' => false,
                    'total_price' => $price,
                    'score' => $score,
                ];
            }
        }

        if (!empty($fallbackMains)) {
            return $this->selectTopSuggestions($fallbackMains, 3);
        }

        return [];
    }

    /**
     * Pick up to $limit suggestions, preferring different canteens.
     *
     * @param array $items
     * @param int $limit
     * @return array
     */
    protected function selectTopSuggestions(array $items, int $limit = 3): array
    {
        // Shuffle first to ensure random tie-breaking
        shuffle($items);

        // Sort items by score descending
        usort($items, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Group by canteen
        $byCanteen = [];
        foreach ($items as $item) {
            $byCanteen[$item['canteen_id']][] = $item;
        }

        $selected = [];
        $canteenIds = array_keys($byCanteen);
        // Shuffle canteen order among those with top scores to add variety
        shuffle($canteenIds);
        // But re-sort canteens by their best item's score
        usort($canteenIds, function ($a, $b) use ($byCanteen) {
            return $byCanteen[$b][0]['score'] <=> $byCanteen[$a][0]['score'];
        });

        // First pass: 1 item per canteen (different canteens when possible)
        foreach ($canteenIds as $cid) {
            if (count($selected) >= $limit) {
                break;
            }
            if (!empty($byCanteen[$cid])) {
                $selected[] = array_shift($byCanteen[$cid]);
            }
        }

        // Second pass: fill remaining slots if fewer than $limit distinct canteens were found
        if (count($selected) < $limit) {
            $remaining = [];
            foreach ($byCanteen as $itemsInCanteen) {
                foreach ($itemsInCanteen as $remItem) {
                    $remaining[] = $remItem;
                }
            }
            usort($remaining, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            foreach ($remaining as $remItem) {
                if (count($selected) >= $limit) {
                    break;
                }
                $selected[] = $remItem;
            }
        }

        // Final sort of returned cards: highest price / score first
        usort($selected, function ($a, $b) {
            return $b['total_price'] <=> $a['total_price'];
        });

        return $selected;
    }

    /**
     * Check if a category represents a main course.
     */
    protected function isMainCourse(?string $category): bool
    {
        if (!$category) {
            return false;
        }
        $normalized = strtolower(str_replace(' ', '_', trim($category)));
        return in_array($normalized, ['makanan_berat', 'makanan', 'makanan_utama', 'main']);
    }

    /**
     * Check if a category represents a drink.
     */
    protected function isDrink(?string $category): bool
    {
        if (!$category) {
            return false;
        }
        $normalized = strtolower(trim($category));
        return in_array($normalized, ['minuman', 'drink', 'beverage']);
    }

    /**
     * Check if a menu item has the given flavor tag.
     */
    protected function hasFlavorTag(Menu $menu, string $tag): bool
    {
        $tags = $menu->flavor_tags;
        if (!is_array($tags)) {
            return false;
        }
        $normalizedTags = array_map(function ($t) {
            return strtolower(trim($t));
        }, $tags);

        return in_array(strtolower($tag), $normalizedTags);
    }
}

