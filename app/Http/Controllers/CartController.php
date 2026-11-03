<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Cart page (/keranjang)
     */
    public function index()
    {
        $syncMessages = $this->cartService->syncWithDatabase();
        $cart = $this->cartService->get();
        return view('buyer.cart', compact('cart', 'syncMessages'));
    }

    /**
     * Add item to cart.
     */
    public function add(Request $request)
    {
        $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'quantity' => 'nullable|integer|min:1',
            'note' => 'nullable|string|max:255',
            'force' => 'nullable|boolean',
        ]);

        $menu = Menu::with('canteen')->findOrFail($request->input('menu_id'));
        $qty = (int) $request->input('quantity', 1);
        $note = $request->input('note');
        $force = (bool) $request->input('force', false);

        $result = $this->cartService->add($menu, $qty, $note, $force);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        if (!$result['success']) {
            if (!empty($result['conflict'])) {
                return back()->with('conflict', [
                    'current_canteen' => $result['current_canteen'],
                    'new_canteen' => $result['new_canteen'],
                    'menu_id' => $menu->id,
                ])->with('error', 'Keranjang hanya boleh berisi menu dari satu kantin.');
            }
            return back()->with('error', $result['message'] ?? 'Gagal menambahkan menu.');
        }

        return back()->with('success', "{$menu->name} berhasil ditambahkan ke keranjang.");
    }

    /**
     * Increase quantity.
     */
    public function increase($id, Request $request)
    {
        $this->cartService->increase((int) $id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart' => $this->cartService->get(),
            ]);
        }

        return back();
    }

    /**
     * Decrease quantity.
     */
    public function decrease($id, Request $request)
    {
        $this->cartService->decrease((int) $id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart' => $this->cartService->get(),
            ]);
        }

        return back();
    }

    /**
     * Remove item.
     */
    public function remove($id, Request $request)
    {
        $this->cartService->remove((int) $id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart' => $this->cartService->get(),
            ]);
        }

        return back()->with('success', 'Item berhasil dihapus dari keranjang.');
    }

    /**
     * Update item note.
     */
    public function setNote($id, Request $request)
    {
        $request->validate([
            'note' => 'nullable|string|max:255',
        ]);

        $this->cartService->setNote((int) $id, $request->input('note', ''));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart' => $this->cartService->get(),
            ]);
        }

        return back()->with('success', 'Catatan pesanan diperbarui.');
    }

    /**
     * Clear entire cart.
     */
    public function clear(Request $request)
    {
        $this->cartService->clear();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart' => $this->cartService->get(),
            ]);
        }

        return back()->with('success', 'Keranjang berhasil dikosongkan.');
    }
}
