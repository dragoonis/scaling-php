<?php

namespace App\Metrics;

use Beberlei\Metrics\Collector\CollectorInterface;
use Beberlei\Metrics\Factory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CollectorRegistry::class, fn (Application $app) => new CollectorRegistry(
            match ($app['config']['metrics.prometheus.storage']) {
                'in_memory' => new InMemory,
                default => new Redis($app['config']['metrics.prometheus.redis']),
            },
            false,
        ));

        $this->app->singleton(CollectorInterface::class, fn (Application $app) => $this->collector($app, $app['config']['metrics.default']));
    }

    public function boot(): void
    {
        $this->app->terminating(fn (Application $app) => $this->flush($app));

        Event::listen([JobProcessed::class, JobExceptionOccurred::class], fn () => $this->flush($this->app));
    }

    private function flush(Application $app): void
    {
        if ($app->resolved(CollectorInterface::class)) {
            $app->make(CollectorInterface::class)->flush();
        }
    }

    private function collector(Application $app, string $name): CollectorInterface
    {
        $config = $app['config']["metrics.collectors.$name"];

        return Factory::create($config['type'], match ($config['type']) {
            'prometheus' => [
                'collector_registry' => $app->make(CollectorRegistry::class),
                'namespace' => $config['namespace'] ?? '',
            ],
            'logger' => ['logger' => $app->make('log')->channel($config['channel'] ?? null)],
            'chain' => ['collectors' => array_map(fn (string $collector) => $this->collector($app, $collector), $config['collectors'])],
            default => array_diff_key($config, ['type' => true]),
        });
    }
}
