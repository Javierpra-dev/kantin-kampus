<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Show QRIS Payment Page (/pembayaran/{order_code})
     */
    public function show($order_code)
    {
        $order = Order::with(['canteen', 'zone', 'items'])
            ->where('order_code', $order_code)
            ->orWhere('order_number', $order_code)
            ->firstOrFail();

        // If order already paid, redirect straight to order tracking page
        if ($order->payment_status === 'dibayar') {
            return redirect()->route('order.show', $order->order_code)
                ->with('success', 'Pembayaran sudah lunas! Pesanan sedang diproses.');
        }

        // Check if expired
        $isExpired = $this->paymentService->checkExpiry($order);

        $qrData = null;
        $remainingSeconds = 0;

        if (!$isExpired) {
            $qrData = $this->paymentService->createQrisCharge($order);
            $expiresAt = $order->expires_at ?? Carbon::now()->addMinutes(15);
            $remainingSeconds = max(0, Carbon::now()->diffInSeconds($expiresAt, false));
            if ($remainingSeconds <= 0) {
                app(\App\Services\OrderService::class)->markPaymentExpired($order);
                $isExpired = true;
            }
        }

        $simulationEnabled = (bool) config('midtrans.payment_simulation', true);

        return view('buyer.payment', compact('order', 'qrData', 'isExpired', 'remainingSeconds', 'simulationEnabled'));
    }

    /**
     * Retry payment if expired (/pembayaran/{order_code}/retry)
     */
    public function retry($order_code)
    {
        $order = Order::where('order_code', $order_code)
            ->orWhere('order_number', $order_code)
            ->firstOrFail();

        if ($order->payment_status !== 'dibayar') {
            $this->paymentService->createQrisCharge($order, forceRefresh: true);
        }

        return redirect()->route('payment.show', $order->order_code)
            ->with('success', 'Kode QRIS baru berhasil dibuat. Silakan lakukan pembayaran.');
    }

    /**
     * JSON status polling endpoint for automatic redirect once paid.
     */
    public function statusJson($order_code)
    {
        $order = Order::where('order_code', $order_code)
            ->orWhere('order_number', $order_code)
            ->firstOrFail();

        $this->paymentService->checkExpiry($order);

        $isPaid = ($order->payment_status === 'dibayar');
        $isExpired = ($order->payment_status === 'kedaluwarsa');

        return response()->json([
            'order_code' => $order->order_code,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'is_paid' => $isPaid,
            'is_expired' => $isExpired,
            'redirect_url' => route('order.show', $order->order_code),
        ]);
    }

    /**
     * Simulated payment trigger (fallback demo mode).
     */
    public function simulate($order_code)
    {
        if (!config('midtrans.payment_simulation', true)) {
            abort(403, 'Payment simulation is disabled.');
        }

        $order = Order::where('order_code', $order_code)
            ->orWhere('order_number', $order_code)
            ->firstOrFail();

        $this->paymentService->simulatePayment($order);

        return redirect()->route('order.show', $order->order_code)
            ->with('success', '⚡ [Simulasi] Pembayaran berhasil diverifikasi. Pesanan diteruskan ke dapur.');
    }

    /**
     * Midtrans Notification Webhook endpoint.
     * Excluded from CSRF protection.
     * Doc page: https://docs.midtrans.com/docs/http-notification-webhooks
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();

        // Verify Midtrans SHA512 signature key
        if (!$this->paymentService->verifySignature($payload)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid signature key verification.',
            ], 401);
        }

        $order = $this->paymentService->handleNotification($payload);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'order_code' => $order->order_code,
            'payment_status' => $order->payment_status,
        ]);
    }
}

