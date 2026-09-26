<?php

namespace App\Jobs;

use Kevariable\Metrics\Facades\Metrics;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Redis;

class ProcessOrder implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId, public int $workMs = 100, public bool $shouldFail = false) {}

    public function handle(): void
    {
        $startedAt = hrtime(true);

        usleep($this->workMs * 1000);

        if ($this->shouldFail) {
            Metrics::increment('orders.failed_total');

            throw new \RuntimeException("Order {$this->orderId}: upstream is down");
        }

        Redis::incr('demo:orders:processed');

        Metrics::increment('orders.processed_total');
        Metrics::timing('orders.job_duration_ms', (hrtime(true) - $startedAt) / 1e6);
    }
}
