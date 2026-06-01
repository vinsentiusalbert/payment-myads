<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PaymentController::class, 'form'])->name('checkout.form');
Route::post('/checkout', [PaymentController::class, 'initiate'])->name('checkout.store');
Route::get('/payment', [PaymentController::class, 'show'])->name('payment.show');
Route::get('/payment/{transactionId}/qris.jpg', [PaymentController::class, 'qris'])->name('payment.qris');

Route::prefix('api/payment')->name('payment.api.')->group(function () {
    Route::post('/initiate', [PaymentController::class, 'initiate'])->name('initiate');
    Route::post('/callback', [PaymentController::class, 'callback'])->name('callback');
    Route::get('/transactions', [PaymentController::class, 'transactions'])->name('transactions');
    Route::get('/{transactionId}', [PaymentController::class, 'detail'])->name('detail');
});
