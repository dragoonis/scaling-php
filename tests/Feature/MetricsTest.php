<?php

namespace Tests\Feature;

use App\Jobs\ProcessOrder;
use Kevariable\Metrics\Facades\Metrics;
use Beberlei\Metrics\Collector\NullCollector;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    public function test_processed_order_is_exposed_on_the_metrics_endpoint(): void
    {
        Redis::shouldReceive('incr')->once()->with('demo:orders:processed');

        (new ProcessOrder(1, 0))->handle();
        Metrics::flush();

        $this->get('/metrics')
            ->assertOk()
            ->assertSee('app_orders_processed_total 1', false)
            ->assertSee('# TYPE app_orders_job_duration_ms histogram', false);
    }

    public function test_failed_order_is_counted_separately(): void
    {
        try {
            (new ProcessOrder(1, 0, true))->handle();
        } catch (\RuntimeException) {
        }

        Metrics::flush();

        $this->get('/metrics')
            ->assertSee('app_orders_failed_total 1', false)
            ->assertDontSee('app_orders_processed_total', false);
    }

    public function test_collector_is_chosen_from_config(): void
    {
        config(['metrics.default' => 'null']);

        $this->assertInstanceOf(NullCollector::class, Metrics::collector()->getCollector());
    }
}
