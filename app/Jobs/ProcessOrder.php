<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Redis;

class ProcessOrder implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId, public int $workMs = 100, public bool $shouldFail = false) {}

    public function handle(): void
    {
        usleep($this->workMs * 1000);

        if ($this->shouldFail) {
            throw new \RuntimeException("Order {$this->orderId}: upstream is down");
        }

        Redis::incr('demo:orders:processed');
    }
}
