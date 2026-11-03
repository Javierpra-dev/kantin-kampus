<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'cart';

    /**
     * Get the full cart data from session.
     */
    public function get(): array
    {
        return Session::get(self::SESSION_KEY, [
            'canteen_id' => null,
            'canteen_name' => null,
            'items' => [],
            'total' => 0,
            'count' => 0,
        ]);
    }

    /**
     * Add a menu item to the cart.
     * Enforces the rule: A cart may only contain items from ONE canteen.
     *
     * @return array ['success' => bool, 'conflict' => bool, 'current_canteen' => string|null, 'new_canteen' => string|null]
     */
    public function add(Menu $menu, int $quantity = 1, ?string $note = null, bool $forceClear = false): array
    {
        if (!$menu->is_available || $menu->isOutOfStock() || !$menu->canteen->is_open) {
            return [
                'success' => false,
                'conflict' => false,
                'message' => 'Menu atau kantin sedang tidak tersedia.',
            ];
        }

        $cart = $this->get();
        $menuCanteen = $menu->canteen;

        // If cart has items from another canteen
        if (!empty($cart['items']) && $cart['canteen_id'] !== null && $cart['canteen_id'] !== $menu->canteen_id) {
            if (!$forceClear) {
                return [
                    'success' => false,
                    'conflict' => true,
                    'current_canteen' => $cart['canteen_name'] ?? 'Kantin lain',
                    'new_canteen' => $menuCanteen->name,
                    'message' => 'Keranjang hanya boleh berisi menu dari satu kantin.',
                ];
            }
            // User confirmed clearing the cart
            $cart = [
                'canteen_id' => null,
                'canteen_name' => null,
                'items' => [],
                'total' => 0,
                'count' => 0,
            ];
        }

        $menuId = (int) $menu->id;
        $existingQty = isset($cart['items'][$menuId]) ? $cart['items'][$menuId]['quantity'] : 0;

        if ($menu->daily_stock !== null && ($existingQty + $quantity) > $menu->daily_stock) {
            return [
                'success' => false,
                'conflict' => false,
                'message' => "Sisa porsi {$menu->name} tinggal {$menu->daily_stock}.",
            ];
        }

        $cart['canteen_id'] = $menu->canteen_id;
        $cart['canteen_name'] = $menuCanteen->name;

        $effectivePrice = (int) $menu->effectivePrice();

        if (isset($cart['items'][$menuId])) {
            $cart['items'][$menuId]['quantity'] += $quantity;
            $cart['items'][$menuId]['price'] = $effectivePrice;
            $cart['items'][$menuId]['subtotal'] = $cart['items'][$menuId]['quantity'] * $effectivePrice;
            if ($note !== null) {
                $cart['items'][$menuId]['note'] = $note;
            }
        } else {
            $cart['items'][$menuId] = [
                'id' => $menuId,
                'canteen_id' => $menu->canteen_id,
                'name' => $menu->name,
                'price' => $effectivePrice,
                'photo' => $menu->photo,
                'quantity' => $quantity,
                'note' => $note ?? '',
                'subtotal' => $effectivePrice * $quantity,
            ];
        }

        $this->save($cart);

        return [
            'success' => true,
            'conflict' => false,
            'cart' => $this->get(),
        ];
    }

    /**
     * Increase quantity of an item.
     */
    public function increase(int $menuId): void
    {
        $cart = $this->get();
        if (isset($cart['items'][$menuId])) {
            $menu = Menu::find($menuId);
            if ($menu && $menu->daily_stock !== null && ($cart['items'][$menuId]['quantity'] + 1) > $menu->daily_stock) {
                return;
            }
            $cart['items'][$menuId]['quantity']++;
            if ($menu) {
                $cart['items'][$menuId]['price'] = (int) $menu->effectivePrice();
            }
            $cart['items'][$menuId]['subtotal'] = $cart['items'][$menuId]['quantity'] * $cart['items'][$menuId]['price'];
            $this->save($cart);
        }
    }

    /**
     * Decrease quantity of an item. Removes if reaches 0.
     */
    public function decrease(int $menuId): void
    {
        $cart = $this->get();
        if (isset($cart['items'][$menuId])) {
            $cart['items'][$menuId]['quantity']--;
            if ($cart['items'][$menuId]['quantity'] <= 0) {
                unset($cart['items'][$menuId]);
            } else {
                $cart['items'][$menuId]['subtotal'] = $cart['items'][$menuId]['quantity'] * $cart['items'][$menuId]['price'];
            }
            $this->save($cart);
        }
    }

    /**
     * Remove item completely.
     */
    public function remove(int $menuId): void
    {
        $cart = $this->get();
        if (isset($cart['items'][$menuId])) {
            unset($cart['items'][$menuId]);
            $this->save($cart);
        }
    }

    /**
     * Set special note for a cart item (e.g. "tanpa es").
     */
    public function setNote(int $menuId, string $note): void
    {
        $cart = $this->get();
        if (isset($cart['items'][$menuId])) {
            $cart['items'][$menuId]['note'] = $note;
            $this->save($cart);
        }
    }

    /**
     * Calculate total price of cart items.
     */
    public function total(): int
    {
        $cart = $this->get();
        return (int) ($cart['total'] ?? 0);
    }

    /**
     * Count total items in cart.
     */
    public function count(): int
    {
        $cart = $this->get();
        return (int) ($cart['count'] ?? 0);
    }

    /**
     * Clear all items in cart.
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Synchronize cart with latest database state:
     * - Adjust/remove items if stock dropped or menu became unavailable
     * - Adjust prices if promo started or ended
     *
     * @return array List of user-facing notification messages
     */
    public function syncWithDatabase(): array
    {
        $cart = $this->get();
        if (empty($cart['items'])) {
            return [];
        }

        $menuIds = array_keys($cart['items']);
        $menus = Menu::whereIn('id', $menuIds)->get()->keyBy('id');
        $messages = [];
        $hasChanges = false;

        foreach ($cart['items'] as $menuId => &$item) {
            $menu = $menus->get($menuId);

            // If menu no longer exists or is unavailable or stock is 0
            if (!$menu || !$menu->is_available || ($menu->daily_stock !== null && $menu->daily_stock <= 0)) {
                $messages[] = "Menu '{$item['name']}' sudah habis dan dihapus dari keranjang.";
                unset($cart['items'][$menuId]);
                $hasChanges = true;
                continue;
            }

            // Check stock limitation
            if ($menu->daily_stock !== null && $item['quantity'] > $menu->daily_stock) {
                $messages[] = "Sisa porsi {$menu->name} tinggal {$menu->daily_stock}, jumlah pesanan disesuaikan.";
                $item['quantity'] = $menu->daily_stock;
                $hasChanges = true;
            }

            // Check effective price change (promo started or ended)
            $currentPrice = (int) $menu->effectivePrice();
            if ((int) $item['price'] !== $currentPrice) {
                if ($currentPrice > (int) $item['price']) {
                    $messages[] = "Promo untuk {$menu->name} telah berakhir, harga kembali menjadi Rp " . number_format($currentPrice, 0, ',', '.') . ".";
                } else {
                    $messages[] = "Harga {$menu->name} kini promo menjadi Rp " . number_format($currentPrice, 0, ',', '.') . "!";
                }
                $item['price'] = $currentPrice;
                $hasChanges = true;
            }

            $item['subtotal'] = $item['quantity'] * $item['price'];
        }
        unset($item);

        if ($hasChanges) {
            $this->save($cart);
        }

        return $messages;
    }

    /**
     * Recalculate totals and write to session.
     */
    private function save(array $cart): void
    {
        if (empty($cart['items'])) {
            $this->clear();
            return;
        }

        $total = 0;
        $count = 0;

        foreach ($cart['items'] as $item) {
            $total += $item['subtotal'];
            $count += $item['quantity'];
        }

        $cart['total'] = $total;
        $cart['count'] = $count;

        Session::put(self::SESSION_KEY, $cart);
    }
}
