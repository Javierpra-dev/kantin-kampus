<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    /**
     * Menu list page (/admin/menu).
     * Title: "Menu Kantin X"
     */
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        $canteen = $admin->canteen;

        $menus = Menu::where('canteen_id', $canteen->id)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('admin.menu.index', compact('admin', 'canteen', 'menus'));
    }

    /**
     * Form to create a new menu item (/admin/menu/create).
     */
    public function create()
    {
        $admin = Auth::guard('admin')->user();
        $canteen = $admin->canteen;

        $categories = ['Makanan berat', 'Minuman', 'Camilan'];

        return view('admin.menu.create', compact('admin', 'canteen', 'categories'));
    }

    /**
     * Store new menu item in database.
     */
    public function store(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'integer', 'min:0'],
            'category' => ['required', 'string', 'max:50'],
            'photo' => ['nullable', 'image', 'max:2048'], // max 2 MB
            'flavor_tags' => ['nullable', 'array'],
            'flavor_tags.*' => ['string', 'in:segar,hangat,pedas'],
            'daily_stock' => ['nullable', 'integer', 'min:0'],
            'promo_price' => ['nullable', 'integer', 'min:0'],
            'promo_start' => ['nullable', 'date_format:H:i'],
            'promo_end' => ['nullable', 'date_format:H:i'],
        ], [
            'name.required' => 'Nama menu wajib diisi.',
            'price.required' => 'Harga menu wajib diisi.',
            'price.integer' => 'Harga harus berupa angka bulat.',
            'category.required' => 'Kategori menu wajib dipilih.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'flavor_tags.array' => 'Tag rasa tidak valid.',
            'flavor_tags.*.in' => 'Tag rasa yang dipilih tidak valid.',
            'daily_stock.integer' => 'Sisa porsi harus berupa angka bulat.',
            'daily_stock.min' => 'Sisa porsi minimal 0.',
            'promo_price.integer' => 'Harga promo harus berupa angka bulat.',
            'promo_price.min' => 'Harga promo minimal 0.',
        ]);

        $validator->after(function ($v) use ($request) {
            $promoPrice = $request->input('promo_price');
            $promoStart = $request->input('promo_start');
            $promoEnd = $request->input('promo_end');
            $price = $request->input('price');

            $hasPromoPrice = !is_null($promoPrice) && $promoPrice !== '';
            $hasPromoStart = !empty($promoStart);
            $hasPromoEnd = !empty($promoEnd);

            if ($hasPromoPrice || $hasPromoStart || $hasPromoEnd) {
                if (!$hasPromoPrice || !$hasPromoStart || !$hasPromoEnd) {
                    $v->errors()->add('promo_price', 'Field promo (harga, jam mulai, jam selesai) harus diisi bersamaan.');
                } else {
                    if (is_numeric($promoPrice) && is_numeric($price) && (int) $promoPrice >= (int) $price) {
                        $v->errors()->add('promo_price', 'Harga promo harus lebih murah dari harga normal.');
                    }
                    if ($promoStart && $promoEnd && strtotime($promoEnd) <= strtotime($promoStart)) {
                        $v->errors()->add('promo_end', 'Jam promo selesai harus lebih lambat dari jam mulai.');
                    }
                }
            }
        });

        $validated = $validator->validate();

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('menus', 'public');
        }

        $dailyStock = (!is_null($request->input('daily_stock')) && $request->input('daily_stock') !== '') ? (int) $request->input('daily_stock') : null;
        $promoPrice = (!is_null($request->input('promo_price')) && $request->input('promo_price') !== '') ? (int) $request->input('promo_price') : null;
        $promoStart = !empty($request->input('promo_start')) ? $request->input('promo_start') : null;
        $promoEnd = !empty($request->input('promo_end')) ? $request->input('promo_end') : null;

        Menu::create([
            'canteen_id' => $admin->canteen_id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => (int) $validated['price'],
            'category' => $validated['category'],
            'photo' => $photoPath,
            'is_available' => true,
            'is_popular' => false,
            'flavor_tags' => $request->input('flavor_tags', null),
            'daily_stock' => $dailyStock,
            'promo_price' => $promoPrice,
            'promo_start' => $promoStart,
            'promo_end' => $promoEnd,
        ]);

        return redirect()->route('admin.menu.index')
            ->with('success', "Menu '{$validated['name']}' berhasil ditambahkan.");
    }

    /**
     * Edit menu item form (/admin/menu/{id}/edit).
     */
    public function edit($id)
    {
        $admin = Auth::guard('admin')->user();
        $canteen = $admin->canteen;

        // Security: admin can only edit their own canteen's menu
        $menu = Menu::where('canteen_id', $admin->canteen_id)->findOrFail($id);

        $categories = ['Makanan berat', 'Minuman', 'Camilan'];

        return view('admin.menu.edit', compact('admin', 'canteen', 'menu', 'categories'));
    }

    /**
     * Update menu item.
     */
    public function update(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        // Security: admin can only edit their own canteen's menu
        $menu = Menu::where('canteen_id', $admin->canteen_id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'integer', 'min:0'],
            'category' => ['required', 'string', 'max:50'],
            'photo' => ['nullable', 'image', 'max:2048'], // max 2 MB
            'flavor_tags' => ['nullable', 'array'],
            'flavor_tags.*' => ['string', 'in:segar,hangat,pedas'],
            'daily_stock' => ['nullable', 'integer', 'min:0'],
            'promo_price' => ['nullable', 'integer', 'min:0'],
            'promo_start' => ['nullable', 'date_format:H:i'],
            'promo_end' => ['nullable', 'date_format:H:i'],
        ], [
            'name.required' => 'Nama menu wajib diisi.',
            'price.required' => 'Harga menu wajib diisi.',
            'price.integer' => 'Harga harus berupa angka bulat.',
            'category.required' => 'Kategori menu wajib dipilih.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'flavor_tags.array' => 'Tag rasa tidak valid.',
            'flavor_tags.*.in' => 'Tag rasa yang dipilih tidak valid.',
            'daily_stock.integer' => 'Sisa porsi harus berupa angka bulat.',
            'daily_stock.min' => 'Sisa porsi minimal 0.',
            'promo_price.integer' => 'Harga promo harus berupa angka bulat.',
            'promo_price.min' => 'Harga promo minimal 0.',
        ]);

        $validator->after(function ($v) use ($request) {
            $promoPrice = $request->input('promo_price');
            $promoStart = $request->input('promo_start');
            $promoEnd = $request->input('promo_end');
            $price = $request->input('price');

            $hasPromoPrice = !is_null($promoPrice) && $promoPrice !== '';
            $hasPromoStart = !empty($promoStart);
            $hasPromoEnd = !empty($promoEnd);

            if ($hasPromoPrice || $hasPromoStart || $hasPromoEnd) {
                if (!$hasPromoPrice || !$hasPromoStart || !$hasPromoEnd) {
                    $v->errors()->add('promo_price', 'Field promo (harga, jam mulai, jam selesai) harus diisi bersamaan.');
                } else {
                    if (is_numeric($promoPrice) && is_numeric($price) && (int) $promoPrice >= (int) $price) {
                        $v->errors()->add('promo_price', 'Harga promo harus lebih murah dari harga normal.');
                    }
                    if ($promoStart && $promoEnd && strtotime($promoEnd) <= strtotime($promoStart)) {
                        $v->errors()->add('promo_end', 'Jam promo selesai harus lebih lambat dari jam mulai.');
                    }
                }
            }
        });

        $validated = $validator->validate();

        if ($request->hasFile('photo')) {
            // Delete old photo from public disk if not a remote URL
            if ($menu->photo && !str_starts_with($menu->photo, 'http')) {
                Storage::disk('public')->delete($menu->photo);
            }
            $menu->photo = $request->file('photo')->store('menus', 'public');
        }

        $dailyStock = (!is_null($request->input('daily_stock')) && $request->input('daily_stock') !== '') ? (int) $request->input('daily_stock') : null;
        $promoPrice = (!is_null($request->input('promo_price')) && $request->input('promo_price') !== '') ? (int) $request->input('promo_price') : null;
        $promoStart = !empty($request->input('promo_start')) ? $request->input('promo_start') : null;
        $promoEnd = !empty($request->input('promo_end')) ? $request->input('promo_end') : null;

        $menu->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => (int) $validated['price'],
            'category' => $validated['category'],
            'photo' => $menu->photo,
            'flavor_tags' => $request->input('flavor_tags', null),
            'daily_stock' => $dailyStock,
            'promo_price' => $promoPrice,
            'promo_start' => $promoStart,
            'promo_end' => $promoEnd,
        ]);

        return redirect()->route('admin.menu.index')
            ->with('success', "Menu '{$menu->name}' berhasil diperbarui.");
    }

    /**
     * Delete menu item (with confirmation).
     */
    public function destroy($id)
    {
        $admin = Auth::guard('admin')->user();

        // Security: admin can only delete their own canteen's menu
        $menu = Menu::where('canteen_id', $admin->canteen_id)->findOrFail($id);

        $menuName = $menu->name;

        if ($menu->photo && !str_starts_with($menu->photo, 'http')) {
            Storage::disk('public')->delete($menu->photo);
        }

        $menu->delete();

        return redirect()->route('admin.menu.index')
            ->with('success', "Menu '{$menuName}' berhasil dihapus.");
    }

    /**
     * Instant toggle availability (Alpine.js + fetch).
     */
    public function toggleAvailability($id, Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $menu = Menu::where('canteen_id', $admin->canteen_id)->findOrFail($id);
        $menu->is_available = !$menu->is_available;
        $menu->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_available' => $menu->is_available,
                'status_label' => $menu->is_available ? 'Tersedia' : 'Habis',
            ]);
        }

        return back()->with('success', "Status ketersediaan menu diperbarui.");
    }
}
