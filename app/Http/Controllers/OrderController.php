<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;
use App\Models\Zone;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use RuntimeException;

class OrderController extends Controller
{
    /**
     * Delivery details page (/pengantaran)
     * Title: "Kamu duduk di mana?"
     */
    public function showDeliveryForm(CartService $cartService)
    {
        $cart = $cartService->get();

        if (empty($cart['items'])) {
            return redirect()->route('cart.index')->with('error', 'Keranjangmu masih kosong.');
        }

        $zones = Zone::where('is_active', true)->orderBy('id')->get();

        return view('buyer.delivery', compact('cart', 'zones'));
    }

    /**
     * Submit order creation.
     */
    public function store(CreateOrderRequest $request, OrderService $orderService)
    {
        try {
            $data = $request->validated();
            if ($request->hasFile('location_photo')) {
                $data['location_photo'] = $request->file('location_photo');
            }

            $order = $orderService->createOrder($data);

            return redirect()->route('order.show', ['order_code' => $order->order_code])
                ->with('success', 'Pesanan berhasil dibuat! Silakan selesaikan pembayaran.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Order status / details page (/pesanan/{order_code})
     * Title: "Pesananmu"
     */
    public function showOrder($order_code)
    {
        $order = Order::with(['canteen', 'zone', 'items.menu'])
            ->where('order_code', $order_code)
            ->orWhere('order_number', $order_code)
            ->firstOrFail();

        $stepData = $this->calculateStepData($order);

        return view('buyer.order-status', compact('order', 'stepData'));
    }

    /**
     * JSON status endpoint for Alpine auto-refresh every 5s.
     */
    public function statusJson($order_code)
    {
        $order = Order::where('order_code', $order_code)
            ->orWhere('order_number', $order_code)
            ->firstOrFail();

        $stepData = $this->calculateStepData($order);

        return response()->json([
            'order_code' => $order->order_code,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'step' => $stepData['step'],
            'friendly_line' => $stepData['friendly_line'],
            'is_completed' => ($order->status === 'selesai'),
        ]);
    }

    /**
     * Temporary simulate payment (Phase 5 requirement).
     * Sets payment_status to dibayar and status to diproses.
     */
    public function simulatePayment($order_code, OrderService $orderService, Request $request)
    {
        $order = Order::where('order_code', $order_code)
            ->orWhere('order_number', $order_code)
            ->firstOrFail();

        $orderService->simulatePayment($order);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran simulasi berhasil.',
                'order' => $order->fresh(),
            ]);
        }

        return back()->with('success', 'Pembayaran berhasil disimulasikan! Pesanan sekarang diproses.');
    }

    /**
     * Helper to compute progress step number (1 to 4) and friendly line.
     */
    protected function calculateStepData(Order $order): array
    {
        // 4 steps:
        // 1: Menunggu pembayaran
        // 2: Diproses
        // 3: Diantar
        // 4: Selesai

        if ($order->status === 'selesai') {
            $step = 4;
            $line = 'Pesananmu telah selesai diantar. Selamat menikmati makananmu!';
        } elseif ($order->status === 'diantar') {
            $step = 3;
            $line = 'Pesananmu sedang diantar ke lokasi dudukmu!';
        } elseif ($order->status === 'diproses' || $order->payment_status === 'dibayar') {
            $step = 2;
            $line = 'Pesananmu sedang disiapkan di dapur kantin.';
        } else {
            $step = 1;
            $line = 'Menunggu pembayaranmu diselesaikan.';
        }

        return [
            'step' => $step,
            'friendly_line' => $line,
        ];
    }
}
