<?php

namespace App\Services;

use App\Exceptions\StockConflictException;
use App\Models\Canteen;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Create order inside a database transaction.
     * Supports web orders (via CartService) and API orders (via explicit items).
     */
    public function createOrder(array $validatedData, ?array $explicitItems = null): Order
    {
        $storedPhotoPath = null;
        if (isset($validatedData['location_photo']) && $validatedData['location_photo'] instanceof \Illuminate\Http\UploadedFile) {
            $storedPhotoPath = $this->processLocationPhoto($validatedData['location_photo']);
        }

        try {
            return DB::transaction(function () use ($validatedData, $explicitItems, $storedPhotoPath) {
                $isApiOrder = ($explicitItems !== null || !empty($validatedData['items']));
                $rawItems = $explicitItems ?? ($validatedData['items'] ?? []);

                if ($isApiOrder) {
                    if (empty($rawItems)) {
                        throw new RuntimeException('Daftar pesanan tidak boleh kosong.');
                    }
                    $canteenId = $validatedData['canteen_id'] ?? null;
                    if (!$canteenId) {
                        throw new RuntimeException('Kantin belum dipilih.');
                    }
                    $canteen = Canteen::lockForUpdate()->findOrFail($canteenId);

                    // Transform API items to indexed format
                    $cartItems = [];
                    foreach ($rawItems as $it) {
                        $mId = (int) $it['menu_id'];
                        $cartItems[$mId] = [
                            'quantity' => (int) ($it['quantity'] ?? 1),
                            'note' => $it['note'] ?? null,
                        ];
                    }
                } else {
                    $cart = $this->cartService->get();
                    if (empty($cart['items']) || empty($cart['canteen_id'])) {
                        throw new RuntimeException('Keranjang belanja Anda kosong.');
                    }
                    $canteen = Canteen::lockForUpdate()->findOrFail($cart['canteen_id']);
                    $cartItems = $cart['items'];
                }

                // 1. Validate that the canteen is open
                if (!$canteen->is_open) {
                    throw new RuntimeException("Kantin '{$canteen->name}' sedang tutup dan tidak dapat menerima pesanan.");
                }

                $zone = Zone::findOrFail($validatedData['zone_id']);

                // 2. Fetch fresh menu items from database with row lock
                $menuIds = array_keys($cartItems);
                $menus = Menu::whereIn('id', $menuIds)->lockForUpdate()->get()->keyBy('id');

                $orderItems = [];
                $subtotal = 0;

                foreach ($cartItems as $menuId => $cartItem) {
                    $menu = $menus->get($menuId);

                    if (!$menu) {
                        throw new RuntimeException("Menu dengan ID {$menuId} tidak ditemukan.");
                    }

                    // Ensure menu belongs to the canteen
                    if ($menu->canteen_id !== $canteen->id) {
                        throw new RuntimeException("Menu '{$menu->name}' bukan dari kantin {$canteen->name}.");
                    }

                    // 3. Validate that every menu item is still available
                    if (!$menu->is_available || ($menu->daily_stock !== null && $menu->daily_stock <= 0)) {
                        throw new StockConflictException("Menu '{$menu->name}' saat ini sedang habis dan tidak dapat dipesan.");
                    }

                    $quantity = max(1, (int) $cartItem['quantity']);

                    // 4. Validate daily stock
                    if ($menu->daily_stock !== null && $quantity > $menu->daily_stock) {
                        throw new StockConflictException("Sisa porsi {$menu->name} tinggal {$menu->daily_stock}.");
                    }

                    // Decrement stock if limited
                    if ($menu->daily_stock !== null) {
                        $menu->daily_stock -= $quantity;
                        $menu->save();
                    }

                    // Copy unit_price from effectivePrice() in database (never trust client/session prices)
                    $unitPrice = (int) $menu->effectivePrice();
                    $itemSubtotal = $unitPrice * $quantity;
                    $subtotal += $itemSubtotal;

                    $orderItems[] = [
                        'menu_id' => $menu->id,
                        'item_name' => $menu->name,
                        'unit_price' => $unitPrice,
                        'price' => $unitPrice,
                        'quantity' => $quantity,
                        'subtotal' => $itemSubtotal,
                        'note' => $cartItem['note'] ?? null,
                    ];
                }

                $deliveryFee = (int) ($zone->delivery_fee ?? 0);
                $totalAmount = $subtotal + $deliveryFee;

                // Generate unique order_code and random access_token
                $orderCode = $this->generateOrderCode();
                $accessToken = Str::random(40);

                // Create Order
                $customerPhone = $validatedData['customer_phone'] ?? ($validatedData['customer_whatsapp'] ?? null);
                $locationNotes = $validatedData['location_notes'] ?? ($validatedData['location_note'] ?? null);

                $order = Order::create([
                    'order_code' => $orderCode,
                    'access_token' => $accessToken,
                    'order_number' => $orderCode,
                    'canteen_id' => $canteen->id,
                    'zone_id' => $zone->id,
                    'customer_name' => $validatedData['customer_name'],
                    'customer_phone' => $customerPhone,
                    'location_notes' => $locationNotes,
                    'delivery_notes' => $locationNotes,
                    'location_photo' => $storedPhotoPath,
                    'had_location_photo' => ($storedPhotoPath !== null),
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'total_amount' => $totalAmount,
                    'status' => 'menunggu_bayar',
                    'payment_method' => 'qris',
                    'payment_status' => 'belum_bayar',
                ]);

                // Create OrderItems
                foreach ($orderItems as $item) {
                    $item['order_id'] = $order->id;
                    OrderItem::create($item);
                }

                if (!$isApiOrder) {
                    // Clear web cart
                    $this->cartService->clear();

                    // Save order to buyer session for Bottom Nav "Pesanan" & "Riwayat"
                    session(['latest_order_code' => $order->order_code]);
                    $history = session('orders_history', []);
                    if (!in_array($order->order_code, $history)) {
                        array_unshift($history, $order->order_code);
                        session(['orders_history' => $history]);
                    }
                }

                return $order;
            });
        } catch (\Throwable $e) {
            if ($storedPhotoPath && Storage::disk('local')->exists($storedPhotoPath)) {
                Storage::disk('local')->delete($storedPhotoPath);
            }
            throw $e;
        }
    }

    /**
     * Simulate payment (Phase 5 requirement).
     * Sets payment_status to dibayar and status to diproses.
     */
    public function simulatePayment(Order $order): Order
    {
        $order->update([
            'payment_status' => 'dibayar',
            'status' => 'diproses',
        ]);

        return $order->fresh();
    }

    /**
     * Move "Baru" order to "Diproses", setting processed_at (Phase 6 requirement).
     */
    public function processOrder(Order $order): Order
    {
        $order->update([
            'status' => 'diproses',
            'processed_at' => now(),
        ]);

        return $order->fresh();
    }

    /**
     * Move order to "Diantar", setting delivered_at (Phase 6 requirement).
     */
    public function deliverOrder(Order $order): Order
    {
        $order->update([
            'status' => 'diantar',
            'delivered_at' => now(),
        ]);

        return $order->fresh();
    }

    /**
     * Move order to "Selesai", setting completed_at (Phase 6 requirement).
     * Privacy cleanup: delete stored photo and null the column.
     */
    public function completeOrder(Order $order): Order
    {
        if ($order->location_photo && Storage::disk('local')->exists($order->location_photo)) {
            Storage::disk('local')->delete($order->location_photo);
        }

        $order->update([
            'status' => 'selesai',
            'location_photo' => null,
            'completed_at' => now(),
        ]);

        return $order->fresh();
    }

    /**
     * Process uploaded location photo:
     * - Re-encode with PHP GD to JPEG
     * - Longest side max 1200px
     * - Strips EXIF/GPS metadata
     * - Store on private local disk with random UUID
     */
    protected function processLocationPhoto($file): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $filePath = $file->getRealPath();
        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            return null;
        }

        $mime = $imageInfo['mime'];
        $srcImg = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($filePath),
            'image/png' => @imagecreatefrompng($filePath),
            'image/webp' => @imagecreatefromwebp($filePath),
            default => null,
        };

        if (!$srcImg) {
            return null;
        }

        $origWidth = imagesx($srcImg);
        $origHeight = imagesy($srcImg);
        $maxDim = 1200;

        if ($origWidth > $maxDim || $origHeight > $maxDim) {
            if ($origWidth >= $origHeight) {
                $newWidth = $maxDim;
                $newHeight = (int) round(($origHeight * $maxDim) / $origWidth);
            } else {
                $newHeight = $maxDim;
                $newWidth = (int) round(($origWidth * $maxDim) / $origHeight);
            }
        } else {
            $newWidth = $origWidth;
            $newHeight = $origHeight;
        }

        $dstImg = imagecreatetruecolor($newWidth, $newHeight);

        // Fill background with white in case of transparent PNG/WebP
        $white = imagecolorallocate($dstImg, 255, 255, 255);
        imagefilledrectangle($dstImg, 0, 0, $newWidth, $newHeight, $white);

        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        ob_start();
        imagejpeg($dstImg, null, 85);
        $jpegData = ob_get_clean();

        imagedestroy($srcImg);
        imagedestroy($dstImg);

        // Private disk, random UUID filename
        $filename = 'location_photos/' . Str::uuid() . '.jpg';
        Storage::disk('local')->put($filename, $jpegData);

        return $filename;
    }

    /**
     * Mark order payment as expired and restore reserved stock.
     */
    public function markPaymentExpired(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();
            if ($lockedOrder && $lockedOrder->payment_status !== 'kedaluwarsa') {
                $lockedOrder->update(['payment_status' => 'kedaluwarsa']);
                $this->restoreStock($lockedOrder);
            }
            return $lockedOrder ?? $order;
        });
    }

    /**
     * Restore stock for items in the given order.
     */
    public function restoreStock(Order $order): void
    {
        $order->loadMissing('items');
        foreach ($order->items as $item) {
            if ($item->menu_id) {
                $menu = Menu::where('id', $item->menu_id)->lockForUpdate()->first();
                if ($menu && $menu->daily_stock !== null) {
                    $menu->daily_stock += $item->quantity;
                    $menu->save();
                }
            }
        }
    }

    /**
     * Generate unique order code (e.g., ORD-20261025-ABCD).
     */
    protected function generateOrderCode(): string
    {
        do {
            $code = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }
}
