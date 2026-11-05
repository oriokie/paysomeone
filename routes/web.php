<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DeskController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderWorkController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/office.php';

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/how-it-works', [PageController::class, 'how'])->name('how');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:8,1')->name('contact.store');
Route::get('/reviews', [PageController::class, 'reviews'])->name('reviews');
Route::get('/legal/{page}', [PageController::class, 'legal'])->name('legal');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/apply', [\App\Http\Controllers\ApplyController::class, 'create'])->name('apply');
Route::post('/apply', [\App\Http\Controllers\ApplyController::class, 'store'])->middleware('throttle:6,1')->name('apply.store');

Route::get('/order', [OrderController::class, 'create'])->name('order.create');
Route::post('/order', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('order.store');
Route::post('/order/quote', [OrderController::class, 'quote'])->middleware('throttle:60,1')->name('quote');

Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:8,1');
    Route::get('/forgot-password', [PasswordController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'send'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:8,1')->name('password.update');
});

Route::middleware('auth:customer')->group(function () {
    Route::get('/account', [DeskController::class, 'home'])->name('account');
    Route::get('/account/orders', [DeskController::class, 'orders'])->name('desk.orders');
    Route::get('/account/messages', [DeskController::class, 'messages'])->name('desk.messages');
    Route::get('/account/orders/{order}', [DeskController::class, 'show'])->name('desk.order');
    Route::post('/account/orders/{order}/files', [OrderWorkController::class, 'upload'])->name('desk.files.store');
    Route::get('/account/orders/{order}/files/{file}', [OrderWorkController::class, 'download'])->name('desk.files.download');
    Route::post('/account/orders/{order}/messages', [OrderWorkController::class, 'message'])->name('desk.messages.store');
    Route::post('/account/orders/{order}/revisions', [OrderWorkController::class, 'revision'])->name('desk.revisions.store');
    Route::post('/account/orders/{order}/accept', [OrderWorkController::class, 'accept'])->name('desk.orders.accept');
    Route::post('/account/orders/{order}/rating', [OrderWorkController::class, 'rate'])->name('desk.rating.store');
    Route::post('/account/orders/{order}/tip', [OrderWorkController::class, 'tip'])->name('desk.tip.store');
    Route::post('/account/orders/{order}/pay', [PaymentController::class, 'pay'])->name('desk.pay');
    Route::get('/account/wallet', [DeskController::class, 'wallet'])->name('desk.wallet');
    Route::get('/account/referrals', [DeskController::class, 'referrals'])->name('desk.referrals');
    Route::get('/account/notifications', [DeskController::class, 'notifications'])->name('desk.notifications');
    Route::get('/account/profile', [DeskController::class, 'profile'])->name('desk.profile');
    Route::post('/account/profile', [DeskController::class, 'updateProfile'])->name('desk.profile.update');
    Route::post('/account/password', [DeskController::class, 'updatePassword'])->name('desk.password.update');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::post('/payments/mpesa/callback', [PaymentController::class, 'mpesa'])->name('payments.mpesa');
Route::post('/payments/mpesa/b2c', function (\Illuminate\Http\Request $request) {
    $ok = app(\App\Services\Payments\MpesaB2c::class)->reconcile($request->all());

    return response()->json(['ResultCode' => $ok ? 0 : 1, 'ResultDesc' => $ok ? 'Accepted' : 'Ignored']);
})->name('payments.mpesa.b2c');
Route::post('/payments/stripe/webhook', [PaymentController::class, 'stripe'])->name('payments.stripe');
Route::post('/payments/paypal/webhook', [PaymentController::class, 'paypal'])->name('payments.paypal');
Route::post('/payments/pesapal/ipn', [PaymentController::class, 'pesapal'])->name('payments.pesapal');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
