<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\StockConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Menu;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * POST /api/v1/orders
     */
    public function store(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('location_photo')) {
                $data['location_photo'] = $request->file('location_photo');
            }

            $order = $orderService->createOrder($data, $data['items']);
            $order->load(['canteen', 'zone', 'items']);

            return (new OrderResource($order))
                ->response()
                ->setStatusCode(201);
        } catch (StockConflictException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/v1/orders/{order_code}
     */
    public function show(string $order_code, Request $request): JsonResponse
    {
        $order = Order::with(['canteen', 'zone', 'items'])
            ->where('order_code', $order_code)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => "Pesanan dengan kode {$order_code} tidak ditemukan.",
            ], 404);
        }

        $token = $request->header('X-Order-Token');
        if (empty($token) || $token !== $order->access_token) {
            return response()->json([
                'message' => 'Akses pesanan tidak diizinkan. Token pesanan tidak valid atau tidak disertakan.',
            ], 403);
        }

        return (new OrderResource($order))->response();
    }

    /**
     * POST /api/v1/orders/{order_code}/payment
     */
    public function payment(string $order_code, Request $request, PaymentService $paymentService): JsonResponse
    {
        $order = Order::where('order_code', $order_code)->first();

        if (!$order) {
            return response()->json([
                'message' => "Pesanan dengan kode {$order_code} tidak ditemukan.",
            ], 404);
        }

        $token = $request->header('X-Order-Token');
        if (empty($token) || $token !== $order->access_token) {
            return response()->json([
                'message' => 'Akses pembayaran tidak diizinkan. Token pesanan tidak valid.',
            ], 403);
        }

        try {
            $qrData = $paymentService->createQrisCharge($order);

            return response()->json([
                'data' => [
                    'order_code' => $order->order_code,
                    'amount' => (int) $order->total_amount,
                    'qr_string' => $qrData['qr_string'],
                    'qr_image_url' => $qrData['qr_url'],
                    'expires_at' => $order->expires_at?->toIso8601String(),
                    'simulation' => (bool) $qrData['is_simulated'],
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat QRIS pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/orders/{order_code}/simulate-pay
     */
    public function simulatePay(string $order_code, Request $request, PaymentService $paymentService): JsonResponse
    {
        if (!config('midtrans.payment_simulation', true)) {
            return response()->json([
                'message' => 'Endpoint simulasi pembayaran tidak aktif di lingkungan ini.',
            ], 404);
        }

        $order = Order::with(['canteen', 'zone', 'items'])
            ->where('order_code', $order_code)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => "Pesanan dengan kode {$order_code} tidak ditemukan.",
            ], 404);
        }

        $token = $request->header('X-Order-Token');
        if (empty($token) || $token !== $order->access_token) {
            return response()->json([
                'message' => 'Akses pembayaran tidak diizinkan. Token pesanan tidak valid.',
            ], 403);
        }

        $updatedOrder = $paymentService->simulatePayment($order);
        $updatedOrder->load(['canteen', 'zone', 'items']);

        return (new OrderResource($updatedOrder))->response();
    }

    /**
     * POST /api/v1/orders/{order_code}/reorder-check
     */
    public function reorderCheck(string $order_code, Request $request): JsonResponse
    {
        $order = Order::with(['canteen', 'items'])
            ->where('order_code', $order_code)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => "Pesanan dengan kode {$order_code} tidak ditemukan.",
            ], 404);
        }

        $token = $request->header('X-Order-Token');
        if (empty($token) || $token !== $order->access_token) {
            return response()->json([
                'message' => 'Akses pesanan tidak diizinkan. Token pesanan tidak valid.',
            ], 403);
        }

        $canteen = $order->canteen;
        $canteenOpen = (bool) ($canteen?->is_open ?? false);

        $menuIds = $order->items->pluck('menu_id')->filter()->all();
        $menus = Menu::whereIn('id', $menuIds)->get()->keyBy('id');

        $checkedItems = [];
        foreach ($order->items as $item) {
            $menu = $item->menu_id ? $menus->get($item->menu_id) : null;
            $isAvailable = $menu && $menu->is_available && !$menu->isOutOfStock() && $canteenOpen;

            $checkedItems[] = [
                'menu_id' => $item->menu_id,
                'name' => $item->name,
                'requested_quantity' => (int) $item->quantity,
                'is_available' => (bool) $isAvailable,
                'current_stock' => $menu?->daily_stock,
                'current_effective_price' => $menu ? (int) $menu->effectivePrice() : (int) $item->price,
            ];
        }

        return response()->json([
            'data' => [
                'canteen_id' => $order->canteen_id,
                'canteen_name' => $canteen?->name,
                'canteen_is_open' => $canteenOpen,
                'items' => $checkedItems,
            ],
        ]);
    }
}

