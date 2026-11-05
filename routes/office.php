<?php

use App\Http\Controllers\Office\BoardController;
use App\Http\Controllers\Office\HomeController;
use App\Http\Controllers\Office\MoneyController;
use App\Http\Controllers\Office\PeopleController;
use App\Http\Controllers\Office\PoolController;
use App\Http\Controllers\Office\SessionController;
use App\Http\Controllers\Office\SystemController;
use App\Http\Controllers\Office\WorkController;
use Illuminate\Support\Facades\Route;

Route::prefix('office')->name('office.')->group(function () {
    Route::middleware('guest:staff')->group(function () {
        Route::get('login', [SessionController::class, 'create'])->name('login');
        Route::post('login', [SessionController::class, 'store'])->middleware('throttle:5,1');
    });

    Route::middleware('auth:staff')->group(function () {
        Route::get('security', [SessionController::class, 'setup'])->name('setup');
        Route::post('security', [SessionController::class, 'confirm'])->middleware('throttle:6,1')->name('setup.confirm');
        Route::get('challenge', [SessionController::class, 'challenge'])->name('challenge');
        Route::post('challenge', [SessionController::class, 'verify'])->middleware('throttle:6,1')->name('challenge.verify');
        Route::post('logout', [SessionController::class, 'destroy'])->name('logout');

        Route::middleware(['staff.2fa', 'staff.touch'])->group(function () {
            Route::get('/', HomeController::class)->name('home');
            Route::get('orders', [BoardController::class, 'index'])->name('orders');
            Route::post('orders/views', [BoardController::class, 'saveView'])->name('orders.views');
            Route::post('orders/bulk', [BoardController::class, 'bulk'])->name('orders.bulk');
            Route::get('orders/{order}', [WorkController::class, 'show'])->name('orders.show');
            Route::post('orders/{order}/assign', [WorkController::class, 'assign'])->name('orders.assign');
            Route::post('orders/{order}/bids/{bid}/approve', [WorkController::class, 'approveBid'])->name('orders.bids.approve');
            Route::post('orders/{order}/deadline', [WorkController::class, 'deadline'])->name('orders.deadline');
            Route::post('orders/{order}/cancel', [WorkController::class, 'cancel'])->name('orders.cancel');
            Route::post('orders/{order}/notes', [WorkController::class, 'note'])->name('orders.notes');
            Route::post('orders/{order}/messages', [WorkController::class, 'message'])->name('orders.messages');
            Route::post('orders/{order}/files', [WorkController::class, 'upload'])->name('orders.files');
            Route::get('orders/{order}/files/{file}', [WorkController::class, 'download'])->name('orders.files.download');
            Route::post('orders/{order}/submit', [WorkController::class, 'submitReview'])->name('orders.submit');
            Route::post('orders/{order}/approve', [WorkController::class, 'approve'])->name('orders.approve');
            Route::post('orders/{order}/return', [WorkController::class, 'returnToWriter'])->name('orders.return');
            Route::get('pool', [PoolController::class, 'index'])->name('pool');
            Route::post('pool/{order}/bid', [PoolController::class, 'bid'])->name('pool.bid');
            Route::get('earnings', [MoneyController::class, 'earnings'])->name('earnings');
            Route::post('earnings', [MoneyController::class, 'requestPayout'])->name('earnings.request');
            Route::post('payouts/{payout}/approve', [MoneyController::class, 'approve'])->name('payouts.approve');
            Route::post('fines', [MoneyController::class, 'fine'])->name('fines.store');
            Route::get('staff', [PeopleController::class, 'index'])->name('staff');
            Route::post('staff/{employee}', [PeopleController::class, 'update'])->name('staff.update');
            Route::get('customers', [PeopleController::class, 'customers'])->name('customers');
            Route::get('roles', [SystemController::class, 'matrix'])->name('roles');
            Route::post('roles', [SystemController::class, 'saveMatrix'])->name('roles.save');
            Route::get('settings', [SystemController::class, 'settings'])->name('settings');
            Route::post('settings', [SystemController::class, 'saveSettings'])->name('settings.save');
            Route::get('reports', [SystemController::class, 'reports'])->name('reports');
            Route::get('reports/orders.csv', [SystemController::class, 'export'])->name('reports.export');
            Route::get('news', [SystemController::class, 'news'])->name('news');
            Route::post('news', [SystemController::class, 'storeNews'])->name('news.store');
            Route::get('activity', [SystemController::class, 'activity'])->name('activity');
        });
    });
});
