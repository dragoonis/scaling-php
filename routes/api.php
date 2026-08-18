<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/runtime', function () {
    static $requestsServedByThisWorker = 0;
    $requestsServedByThisWorker++;

    return [
        'sapi' => php_sapi_name(),
        'octane_worker_mode' => env('LARAVEL_OCTANE') === '1',
        'pid' => getmypid(),
        'requests_served_by_this_worker' => $requestsServedByThisWorker,
        'app_booted_at' => defined('LARAVEL_START') ? LARAVEL_START : null,
        'memory_mb' => round(memory_get_usage(true) / 1048576, 1),
    ];
});

Route::get('/products', function () {
    return ['products' => Product::query()->orderBy('id')->get()];
});

Route::get('/products/cached', function () {
    return Cache::remember('products.all', 60, fn () => ['products' => Product::query()->orderBy('id')->get()->toArray()]);
});

Route::get('/products/cached/{id}', function (int $id) {
    return Cache::remember("products.$id", 60, fn () => Product::query()->findOrFail($id)->toArray());
});

Route::get('/products/{product}', function (Product $product) {
    return $product;
});

Route::get('/customers', function () {
    return ['customers' => Customer::query()->orderBy('id')->get()];
});

Route::get('/customers/cached', function () {
    return Cache::remember('customers.all', 60, fn () => ['customers' => Customer::query()->orderBy('id')->get()->toArray()]);
});

Route::get('/customers/{customer}', function (Customer $customer) {
    return $customer;
});

Route::get('/orders', function () {
    return ['orders' => Order::query()->orderBy('id')->get()];
});

Route::get('/orders/cached', function () {
    return Cache::remember('orders.all', 60, fn () => ['orders' => Order::query()->orderBy('id')->get()->toArray()]);
});

Route::get('/orders/{order}', function (Order $order) {
    return $order->load('customer');
});
