<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\CoreApi as MidtransCoreApi;

class PaymentService
{
    public function __construct()
    {
        $this->initMidtrans();
    }

    /**
     * Initialize Midtrans Configuration from config/midtrans.php.
     * Doc page: https://docs.midtrans.com/reference/core-api-qris
     */
    protected function initMidtrans(): void
    {
        MidtransConfig::$serverKey = config('midtrans.server_key');
        MidtransConfig::$clientKey = config('midtrans.client_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production', false);
        MidtransConfig::$isSanitized = (bool) config('midtrans.is_sanitized', true);
        MidtransConfig::$is3ds = (bool) config('midtrans.is_3ds', true);
    }

    /**
     * Create QRIS charge for an order.
     * Uses order total and order_code as transaction id.
     * Doc: https://docs.midtrans.com/reference/core-api-qris
     *
     * @return array [
     *     'qr_string' => string,
     *     'qr_url' => string,
     *     'expires_at' => Carbon,
     *     'is_simulated' => bool
     * ]
     */
    public function createQrisCharge(Order $order, bool $forceRefresh = false): array
    {
        // If order already has unexpired QR data and we don't force refresh
        if (!$forceRefresh && $order->qr_url && $order->expires_at && Carbon::now()->lt($order->expires_at)) {
            return [
                'qr_string' => $order->qr_string,
                'qr_url' => $order->qr_url,
                'expires_at' => $order->expires_at,
                'is_simulated' => (bool) config('midtrans.payment_simulation'),
            ];
        }

        $params = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $order->order_code,
                'gross_amount' => (int) $order->total_amount,
            ],
            'qris' => [
                'acquirer' => 'gopay',
            ],
        ];

        try {
            $chargeResponse = MidtransCoreApi::charge($params);

            $qrString = $chargeResponse->qr_string ?? null;
            $qrUrl = null;

            if (isset($chargeResponse->actions) && is_array($chargeResponse->actions)) {
                foreach ($chargeResponse->actions as $action) {
                    if (($action->name ?? '') === 'generate-qr-code') {
                        $qrUrl = $action->url ?? null;
                        break;
                    }
                }
            }

            if (!$qrUrl && $qrString) {
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=' . urlencode($qrString);
            }

            $expiryTime = isset($chargeResponse->expiry_time)
                ? Carbon::parse($chargeResponse->expiry_time)
                : Carbon::now()->addMinutes(15);

            $order->update([
                'qr_string' => $qrString,
                'qr_url' => $qrUrl,
                'expires_at' => $expiryTime,
                'midtrans_transaction_id' => $chargeResponse->transaction_id ?? null,
                'payment_status' => 'belum_bayar',
            ]);

            return [
                'qr_string' => $qrString,
                'qr_url' => $qrUrl,
                'expires_at' => $expiryTime,
                'is_simulated' => false,
            ];
        } catch (Exception $e) {
            Log::warning('Midtrans QRIS Charge failed, fallback to simulation if enabled: ' . $e->getMessage());

            if (!config('midtrans.payment_simulation', true)) {
                throw $e;
            }

            return $this->createSimulatedQris($order);
        }
    }

    /**
     * Fallback simulated QRIS generation for test/demo environments.
     */
    public function createSimulatedQris(Order $order): array
    {
        $expiryTime = Carbon::now()->addMinutes(15);
        $simulatedQrData = "00020101021226500016ID.CO.KANTIN.WWW0118936009999" . $order->order_code . "52045812530336054" . sprintf("%02d", strlen((string)$order->total_amount)) . $order->total_amount . "5802ID5913KANTIN KAMPUS6007SEMARANG6304ABCD";
        $simulatedQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=' . urlencode($simulatedQrData);

        $order->update([
            'qr_string' => $simulatedQrData,
            'qr_url' => $simulatedQrUrl,
            'expires_at' => $expiryTime,
            'midtrans_transaction_id' => 'sim_' . uniqid(),
            'payment_status' => 'belum_bayar',
        ]);

        return [
            'qr_string' => $simulatedQrData,
            'qr_url' => $simulatedQrUrl,
            'expires_at' => $expiryTime,
            'is_simulated' => true,
        ];
    }

    /**
     * Verify signature of Midtrans webhook notification.
     * Formula: SHA512(order_id + status_code + gross_amount + ServerKey)
     * Doc page: https://docs.midtrans.com/reference/signature-key-validation
     */
    public function verifySignature(array $payload): bool
    {
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $incomingSignature = $payload['signature_key'] ?? '';
        $serverKey = config('midtrans.server_key');

        if (empty($orderId) || empty($statusCode) || empty($grossAmount) || empty($incomingSignature)) {
            return false;
        }

        $calculatedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($calculatedSignature, $incomingSignature);
    }

    /**
     * Handle webhook notification and transition order status.
     * Doc page: https://docs.midtrans.com/docs/http-notification-webhooks
     */
    public function handleNotification(array $payload): ?Order
    {
        $orderId = $payload['order_id'] ?? null;
        if (!$orderId) {
            return null;
        }

        $order = Order::where('order_code', $orderId)
            ->orWhere('order_number', $orderId)
            ->first();

        if (!$order) {
            return null;
        }

        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus = $payload['fraud_status'] ?? '';

        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'challenge') {
                $order->update(['payment_status' => 'menunggu_verifikasi']);
            } else if ($fraudStatus === 'accept') {
                $order->update([
                    'payment_status' => 'dibayar',
                    'status' => 'diproses',
                    'processed_at' => $order->processed_at ?? Carbon::now(),
                ]);
            }
        } elseif ($transactionStatus === 'settlement') {
            $order->update([
                'payment_status' => 'dibayar',
                'status' => 'diproses',
                'processed_at' => $order->processed_at ?? Carbon::now(),
            ]);
        } elseif ($transactionStatus === 'pending') {
            $order->update(['payment_status' => 'belum_bayar']);
        } elseif (in_array($transactionStatus, ['deny', 'cancel'])) {
            $order->update(['payment_status' => 'batal']);
        } elseif ($transactionStatus === 'expire') {
            app(OrderService::class)->markPaymentExpired($order);
        }

        return $order->fresh();
    }

    /**
     * Check if payment has expired.
     */
    public function checkExpiry(Order $order): bool
    {
        if ($order->payment_status === 'belum_bayar' && $order->expires_at && Carbon::now()->gt($order->expires_at)) {
            app(OrderService::class)->markPaymentExpired($order);
            return true;
        }

        return $order->payment_status === 'kedaluwarsa';
    }

    /**
     * Simulate successful payment (available when PAYMENT_SIMULATION=true).
     */
    public function simulatePayment(Order $order): Order
    {
        $order->update([
            'payment_status' => 'dibayar',
            'status' => 'diproses',
            'processed_at' => $order->processed_at ?? Carbon::now(),
        ]);

        return $order->fresh();
    }
}

