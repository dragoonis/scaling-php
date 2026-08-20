<?php

use App\Jobs\ProcessOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Cbox\LaravelQueueMetrics\Facades\QueueMetrics;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/metrics', function () {
    $status = function_exists('opcache_get_status') ? opcache_get_status(false) : false;
    if ($status === false) {
        return response("opcache_enabled 0\n", 200)->header('Content-Type', 'text/plain; version=0.0.4');
    }

    $stats = $status['opcache_statistics'] ?? [];
    $memory = $status['memory_usage'] ?? [];
    $interned = $status['interned_strings_usage'] ?? [];
    $jit = $status['jit'] ?? [];

    $metrics = [
        ['opcache_enabled', 'gauge', ! empty($status['opcache_enabled']) ? 1 : 0],
        ['opcache_cache_full', 'gauge', ! empty($status['cache_full']) ? 1 : 0],
        ['opcache_restart_pending', 'gauge', ! empty($status['restart_pending']) ? 1 : 0],
        ['opcache_restart_in_progress', 'gauge', ! empty($status['restart_in_progress']) ? 1 : 0],
        ['opcache_memory_used_bytes', 'gauge', $memory['used_memory'] ?? 0],
        ['opcache_memory_free_bytes', 'gauge', $memory['free_memory'] ?? 0],
        ['opcache_memory_wasted_bytes', 'gauge', $memory['wasted_memory'] ?? 0],
        ['opcache_interned_strings_used_memory_bytes', 'gauge', $interned['used_memory'] ?? 0],
        ['opcache_interned_strings_count', 'gauge', $interned['number_of_strings'] ?? 0],
        ['opcache_hit_ratio', 'gauge', ($stats['opcache_hit_rate'] ?? 0) / 100],
        ['opcache_hits_total', 'counter', $stats['hits'] ?? 0],
        ['opcache_misses_total', 'counter', $stats['misses'] ?? 0],
        ['opcache_num_cached_scripts', 'gauge', $stats['num_cached_scripts'] ?? 0],
        ['opcache_num_cached_keys', 'gauge', $stats['num_cached_keys'] ?? 0],
        ['opcache_max_cached_keys', 'gauge', $stats['max_cached_keys'] ?? 0],
        ['opcache_oom_restarts_total', 'counter', $stats['oom_restarts'] ?? 0],
        ['opcache_hash_restarts_total', 'counter', $stats['hash_restarts'] ?? 0],
        ['opcache_manual_restarts_total', 'counter', $stats['manual_restarts'] ?? 0],
        ['opcache_jit_enabled', 'gauge', ! empty($jit['enabled']) ? 1 : 0],
        ['opcache_jit_on', 'gauge', ! empty($jit['on']) ? 1 : 0],
        ['opcache_jit_buffer_size_bytes', 'gauge', $jit['buffer_size'] ?? 0],
        ['opcache_jit_opt_level', 'gauge', $jit['opt_level'] ?? 0],
    ];

    try {
        $depth = QueueMetrics::getQueueDepth('redis', 'orders');
        $oldestAge = $depth->oldestPendingJobAge ? (int) abs(now()->diffInSeconds($depth->oldestPendingJobAge)) : 0;
        $metrics[] = ['laravel_queue_pending_jobs', 'gauge', $depth->pendingJobs];
        $metrics[] = ['laravel_queue_reserved_jobs', 'gauge', $depth->reservedJobs];
        $metrics[] = ['laravel_queue_oldest_job_age_seconds', 'gauge', $oldestAge];
        $metrics[] = ['laravel_queue_processed_total', 'counter', (int) Redis::get('demo:orders:processed')];
    } catch (Throwable) {
    }

    $out = '';
    foreach ($metrics as [$name, $type, $value]) {
        $out .= "# TYPE $name $type\n$name $value\n";
    }

    return response($out, 200)->header('Content-Type', 'text/plain; version=0.0.4');
});

Route::get('/opcache-stats', function () {
    return opcache_get_status(false) ?: ['error' => 'opcache disabled'];
});

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

Route::get('/orders/dispatch', function () {
    $count = min((int) request('count', 100), 20000);
    $workMs = min((int) request('work_ms', 100), 2000);
    $fail = request()->boolean('fail');

    for ($i = 1; $i <= $count; $i++) {
        ProcessOrder::dispatch($i, $workMs, $fail)->onQueue('orders');
    }

    return ['dispatched' => $count, 'queue' => 'orders', 'work_ms' => $workMs, 'fail' => $fail];
});

Route::get('/orders/queue-status', function () {
    return [
        'pending' => Queue::size('orders'),
        'processed' => (int) Redis::get('demo:orders:processed'),
    ];
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
