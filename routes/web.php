<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PilihinAkuController;
use Illuminate\Support\Facades\Route;

// Buyer routes (Phase 2 & Phase 11d)
Route::get('/', [BuyerController::class, 'index'])->name('home');
Route::get('/cari', [BuyerController::class, 'search'])->name('search');
Route::get('/kantin/{id}', [BuyerController::class, 'show'])->name('canteen.show');
Route::get('/riwayat', [BuyerController::class, 'history'])->name('buyer.history');

// Decision helper "Pilihin Aku" (Phase 11a)
Route::get('/pilihin-aku', [PilihinAkuController::class, 'index'])->name('pilihin-aku.index');
Route::post('/pilihin-aku/pesan', [PilihinAkuController::class, 'order'])->name('pilihin-aku.order');

// Cart routes (Phase 3)
Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/increase/{id}', [CartController::class, 'increase'])->name('cart.increase');
Route::post('/cart/decrease/{id}', [CartController::class, 'decrease'])->name('cart.decrease');
Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/note/{id}', [CartController::class, 'setNote'])->name('cart.setNote');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Delivery & Order creation (Phase 4)
Route::get('/pengantaran', [OrderController::class, 'showDeliveryForm'])->name('order.delivery');
Route::post('/pengantaran', [OrderController::class, 'store'])->name('order.store');

// Buyer order status & tracking (Phase 5)
Route::get('/pesanan/{order_code}', [OrderController::class, 'showOrder'])->name('order.show');
Route::get('/pesanan/{order_code}/status', [OrderController::class, 'statusJson'])->name('order.status');
Route::post('/pesanan/{order_code}/simulasi-bayar', [OrderController::class, 'simulatePayment'])->name('order.simulate-payment');

// QRIS Payment & Webhook (Phase 8)
Route::get('/pembayaran/{order_code}', [PaymentController::class, 'show'])->name('payment.show');
Route::post('/pembayaran/{order_code}/retry', [PaymentController::class, 'retry'])->name('payment.retry');
Route::get('/pembayaran/{order_code}/status', [PaymentController::class, 'statusJson'])->name('payment.status');
Route::post('/pembayaran/{order_code}/simulasi', [PaymentController::class, 'simulate'])->name('payment.simulate');
Route::post('/midtrans/webhook', [PaymentController::class, 'webhook'])->name('midtrans.webhook');
Route::post('/api/midtrans/webhook', [PaymentController::class, 'webhook']);

// Admin panel routes (Phase 6 & Phase 7)
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware(['admin.auth'])->group(function () {
        // Orders management (Phase 6)
        Route::get('/pesanan', [AdminOrderController::class, 'index'])->name('admin.orders');
        Route::get('/pesanan/poll', [AdminOrderController::class, 'poll'])->name('admin.orders.poll');
        Route::post('/kantin/toggle-status', [AdminOrderController::class, 'toggleCanteenStatus'])->name('admin.canteen.toggle');
        Route::post('/pesanan/{id}/process', [AdminOrderController::class, 'process'])->name('admin.orders.process');
        Route::post('/pesanan/{id}/deliver', [AdminOrderController::class, 'deliver'])->name('admin.orders.deliver');
        Route::post('/pesanan/{id}/complete', [AdminOrderController::class, 'complete'])->name('admin.orders.complete');
        Route::get('/pesanan/{order}/foto', [AdminOrderController::class, 'photo'])->name('admin.orders.photo');

        // Menu management (Phase 7)
        Route::get('/menu', [AdminMenuController::class, 'index'])->name('admin.menu.index');
        Route::get('/menu/create', [AdminMenuController::class, 'create'])->name('admin.menu.create');
        Route::post('/menu', [AdminMenuController::class, 'store'])->name('admin.menu.store');
        Route::get('/menu/{id}/edit', [AdminMenuController::class, 'edit'])->name('admin.menu.edit');
        Route::put('/menu/{id}', [AdminMenuController::class, 'update'])->name('admin.menu.update');
        Route::delete('/menu/{id}', [AdminMenuController::class, 'destroy'])->name('admin.menu.destroy');
        Route::post('/menu/{id}/toggle-availability', [AdminMenuController::class, 'toggleAvailability'])->name('admin.menu.toggle');

        // Canteen Profile management (Phase 10e)
        Route::get('/profil', [AdminProfileController::class, 'edit'])->name('admin.profile');
        Route::put('/profil', [AdminProfileController::class, 'update'])->name('admin.profile.update');
    });
});
