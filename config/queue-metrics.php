<?php

declare(strict_types=1);

use Cbox\LaravelQueueMetrics\Actions\CalculateBaselinesAction;
use Cbox\LaravelQueueMetrics\Actions\CalculateJobMetricsAction;
use Cbox\LaravelQueueMetrics\Actions\RecordJobCompletionAction;
use Cbox\LaravelQueueMetrics\Actions\RecordJobFailureAction;
use Cbox\LaravelQueueMetrics\Actions\RecordJobStartAction;
use Cbox\LaravelQueueMetrics\Actions\RecordQueueDepthHistoryAction;
use Cbox\LaravelQueueMetrics\Actions\RecordThroughputHistoryAction;
use Cbox\LaravelQueueMetrics\Actions\RecordWorkerHeartbeatAction;
use Cbox\LaravelQueueMetrics\Actions\TransitionWorkerStateAction;
use Cbox\LaravelQueueMetrics\Http\Middleware\Authorize;
use Cbox\LaravelQueueMetrics\Repositories\Contracts\BaselineRepository;
use Cbox\LaravelQueueMetrics\Repositories\Contracts\JobMetricsRepository;
use Cbox\LaravelQueueMetrics\Repositories\Contracts\QueueMetricsRepository;
use Cbox\LaravelQueueMetrics\Repositories\Contracts\WorkerHeartbeatRepository;
use Cbox\LaravelQueueMetrics\Repositories\Contracts\WorkerRepository;

// config for Cbox/LaravelQueueMetrics
return [

    /*
    |--------------------------------------------------------------------------
    | 👉 BASIC
    |--------------------------------------------------------------------------
    */

    'enabled' => env('QUEUE_METRICS_ENABLED', true),

    'persistence' => [
        'enabled' => env('QUEUE_METRICS_PERSISTENCE', true),
    ],

    'storage' => [
        'driver' => env('QUEUE_METRICS_STORAGE', 'redis'),
        'connection' => env('QUEUE_METRICS_CONNECTION', 'default'),
        'prefix' => 'queue_metrics',

        // Maximum retained samples per metric key.
        // Recommended: 1000 for Redis, 500 for database driver.
        'max_samples_per_key' => env('QUEUE_METRICS_MAX_SAMPLES', 1000),
        'cleanup_chunk_size' => 1000,

        'ttl' => [
            'raw' => 3600,
            'aggregated' => 604800,
            'baseline' => 2592000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 🔒 SECURITY
    |--------------------------------------------------------------------------
    */

    'middleware' => ['api', Authorize::class],

    'allowed_ips' => env('QUEUE_METRICS_ALLOWED_IPS') ? explode(',', env('QUEUE_METRICS_ALLOWED_IPS')) : null,

    /*
    |--------------------------------------------------------------------------
    | 📊 PROMETHEUS
    |--------------------------------------------------------------------------
    */

    'prometheus' => [
        'enabled' => env('QUEUE_METRICS_PROMETHEUS_ENABLED', true),
        'namespace' => env('QUEUE_METRICS_PROMETHEUS_NAMESPACE', 'laravel_queue'),
        'cache_ttl' => env('QUEUE_METRICS_PROMETHEUS_CACHE_TTL', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | 📡 TELEMETRY (cboxdk/laravel-telemetry)
    |--------------------------------------------------------------------------
    |
    | When cboxdk/laravel-telemetry is installed, queue metrics automatically
    | publishes observable gauges (queue depth, worker state, baselines) and
    | pushes domain events (health changes, depth threshold breaches,
    | debounced jobs) to it. Batteries included: on by default, no-op when
    | the package is not installed.
    |
    | Tip: run `php artisan queue-metrics:doctor` — it warns when both this
    | integration and the built-in Prometheus exporter are active, which
    | would expose the same metrics twice.
    |
    */

    'telemetry' => [
        'enabled' => env('QUEUE_METRICS_TELEMETRY_ENABLED', true),

        // Snapshot cache in seconds, so concurrent scrapes don't all scan
        // the metrics store at once. 0 disables caching.
        'cache_ttl' => env('QUEUE_METRICS_TELEMETRY_CACHE_TTL', 10),

        // Observable gauge groups, evaluated at scrape time.
        'gauges' => [
            'queues' => true,
            'workers' => true,
            'baselines' => true,
        ],

        // Push counters/gauges/OTLP events from package domain events
        // (health score changes, depth threshold breaches, debounces,
        // worker efficiency, baseline recalculations).
        'events' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | ⚙️ ADVANCED
    |--------------------------------------------------------------------------
    */

    'worker_heartbeat' => [
        'stale_threshold' => env('QUEUE_METRICS_STALE_THRESHOLD', 60),
    ],

    'baseline' => [
        'sliding_window_days' => env('QUEUE_METRICS_BASELINE_WINDOW_DAYS', 7),
        'decay_factor' => env('QUEUE_METRICS_BASELINE_DECAY_FACTOR', 0.1),
        'target_sample_size' => env('QUEUE_METRICS_BASELINE_TARGET_SAMPLES', 200),

        'intervals' => [
            'no_baseline' => 1,
            'low_confidence' => 5,
            'medium_confidence' => 10,
            'high_confidence' => 30,
            'very_high_confidence' => 60,
        ],

        'deviation' => [
            'enabled' => env('QUEUE_METRICS_BASELINE_DEVIATION_ENABLED', true),
            'threshold' => env('QUEUE_METRICS_BASELINE_DEVIATION_THRESHOLD', 2.0),
            'trigger_interval' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 🛠️ EXTENSIBILITY
    |--------------------------------------------------------------------------
    */

    'repositories' => [
        JobMetricsRepository::class => null,
        QueueMetricsRepository::class => null,
        WorkerRepository::class => null,
        BaselineRepository::class => null,
        WorkerHeartbeatRepository::class => null,
    ],

    'actions' => [
        'record_job_start' => RecordJobStartAction::class,
        'record_job_completion' => RecordJobCompletionAction::class,
        'record_job_failure' => RecordJobFailureAction::class,
        'calculate_job_metrics' => CalculateJobMetricsAction::class,
        'record_worker_heartbeat' => RecordWorkerHeartbeatAction::class,
        'transition_worker_state' => TransitionWorkerStateAction::class,
        'record_queue_depth_history' => RecordQueueDepthHistoryAction::class,
        'record_throughput_history' => RecordThroughputHistoryAction::class,
        'calculate_baselines' => CalculateBaselinesAction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | ⏰ SCHEDULING
    |--------------------------------------------------------------------------
    |
    | The package can automatically schedule necessary maintenance and recording
    | tasks. You can disable these if you prefer to schedule them manually
    | in your application's console kernel.
    |
    */

    'scheduling' => [
        'enabled' => env('QUEUE_METRICS_SCHEDULING_ENABLED', true),
        'tasks' => [
            'cleanup_stale_workers' => true,
            'calculate_baselines' => true,
            'calculate_queue_metrics' => true,
            'record_trends' => true,
        ],
    ],

];
