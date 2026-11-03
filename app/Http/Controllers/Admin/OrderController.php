<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Orders dashboard (/admin/pesanan)
     */
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $canteen = $admin->canteen;
        $currentTab = $request->input('tab', 'baru');

        // Only orders from admin's own canteen with payment_status = 'dibayar'
        $baseQuery = Order::with(['zone', 'items'])
            ->where('canteen_id', $canteen->id)
            ->where('payment_status', 'dibayar');

        // Calculate count badges
        $counts = [
            'baru' => (clone $baseQuery)->where('status', 'diproses')->whereNull('processed_at')->count(),
            'diproses' => (clone $baseQuery)->where('status', 'diproses')->whereNotNull('processed_at')->count(),
            'diantar' => (clone $baseQuery)->where('status', 'diantar')->count(),
            'selesai' => (clone $baseQuery)->where('status', 'selesai')->count(),
        ];

        // Filter orders by active tab
        $query = clone $baseQuery;
        if ($currentTab === 'baru') {
            $query->where('status', 'diproses')->whereNull('processed_at')->orderBy('id', 'asc');
        } elseif ($currentTab === 'diproses') {
            $query->where('status', 'diproses')->whereNotNull('processed_at')->orderBy('processed_at', 'desc');
        } elseif ($currentTab === 'diantar') {
            $query->where('status', 'diantar')->orderBy('delivered_at', 'desc');
        } elseif ($currentTab === 'selesai') {
            $query->where('status', 'selesai')->orderBy('completed_at', 'desc');
        }

        $orders = $query->get();

        return view('admin.orders', compact('admin', 'canteen', 'orders', 'counts', 'currentTab'));
    }

    /**
     * Polling JSON endpoint to check for updates & new orders.
     */
    public function poll(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $canteen = $admin->canteen;

        $baseQuery = Order::where('canteen_id', $canteen->id)
            ->where('payment_status', 'dibayar');

        $counts = [
            'baru' => (clone $baseQuery)->where('status', 'diproses')->whereNull('processed_at')->count(),
            'diproses' => (clone $baseQuery)->where('status', 'diproses')->whereNotNull('processed_at')->count(),
            'diantar' => (clone $baseQuery)->where('status', 'diantar')->count(),
            'selesai' => (clone $baseQuery)->where('status', 'selesai')->count(),
        ];

        $latestNewOrderIds = (clone $baseQuery)
            ->where('status', 'diproses')
            ->whereNull('processed_at')
            ->pluck('id')
            ->toArray();

        return response()->json([
            'canteen_is_open' => $canteen->is_open,
            'counts' => $counts,
            'new_order_ids' => $latestNewOrderIds,
        ]);
    }

    /**
     * Toggle canteen open/closed status.
     */
    public function toggleCanteenStatus(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $canteen = $admin->canteen;

        $canteen->is_open = !$canteen->is_open;
        $canteen->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_open' => $canteen->is_open,
            ]);
        }

        $statusText = $canteen->is_open ? 'dibuka' : 'ditutup';
        return back()->with('success', "Status kantin berhasil {$statusText}.");
    }

    /**
     * Move "Baru" order to "Diproses" (sets processed_at).
     */
    public function process($id)
    {
        $admin = Auth::guard('admin')->user();
        $order = Order::where('canteen_id', $admin->canteen_id)->findOrFail($id);

        $this->orderService->processOrder($order);

        return back()->with('success', "Pesanan {$order->order_code} mulai diproses.");
    }

    /**
     * Move order to "Diantar" (sets delivered_at).
     */
    public function deliver($id)
    {
        $admin = Auth::guard('admin')->user();
        $order = Order::where('canteen_id', $admin->canteen_id)->findOrFail($id);

        $this->orderService->deliverOrder($order);

        return back()->with('success', "Pesanan {$order->order_code} sedang diantar.");
    }

    /**
     * Move order to "Selesai" (sets completed_at).
     */
    public function complete($id)
    {
        $admin = Auth::guard('admin')->user();
        $order = Order::where('canteen_id', $admin->canteen_id)->findOrFail($id);

        $this->orderService->completeOrder($order);

        return back()->with('success', "Pesanan {$order->order_code} telah selesai.");
    }

    /**
     * Stream order seating location photo securely.
     * Accessible only if the logged-in admin belongs to that order's canteen; otherwise 403.
     */
    public function photo($id)
    {
        $admin = Auth::guard('admin')->user();
        $order = Order::findOrFail($id);

        if ((int) $order->canteen_id !== (int) $admin->canteen_id) {
            abort(403, 'Anda tidak memiliki akses ke foto pesanan ini.');
        }

        if (!$order->location_photo || !Storage::disk('local')->exists($order->location_photo)) {
            abort(404, 'Foto lokasi tidak ditemukan.');
        }

        $fullPath = Storage::disk('local')->path($order->location_photo);

        return response()->file($fullPath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }
}
