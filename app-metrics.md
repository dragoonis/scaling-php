# App metrics with beberlei/metrics

The exporters tell you how the **infrastructure** is doing (FPM workers, OPcache, Caddy).
They cannot tell you how the **business** is doing: orders processed, orders failed, how
long a job took. For that the app has to count things itself.

[kevariable/laravel-metrics](https://github.com/kevariable/laravel-metrics) gives you one
API for that, whatever backend stores the numbers. It is a Laravel bridge for
[beberlei/metrics](https://github.com/beberlei/metrics). Think `Log::info()` or
`Cache::put()`, but for metrics:

```php
use Kevariable\Metrics\Facades\Metrics;

Metrics::increment('orders.processed_total');
Metrics::timing('orders.job_duration_ms', 412.5);
Metrics::gauge('cart.items', 3);
```

The backend is picked in config, your code never changes:

| `METRICS_COLLECTOR` | Where the numbers go |
|---|---|
| `prometheus` (default) | Redis, exposed on `/metrics` for Prometheus to scrape |
| `statsd` | a StatsD agent over UDP (`STATSD_HOST`, `STATSD_PORT`) |
| `log` | your Laravel log |
| `null` | nowhere, handy in tests |

The library also ships DogStatsD, OpenTelemetry, InfluxDB, Graphite, CloudWatch,
Telegraf, Doctrine DBAL and Chain (send to several at once) collectors.

## How it flows

```
ProcessOrder job ──Metrics::increment()──► buffer in memory
                                              │ flush after the job / after the response
                                              ▼
                                           Redis db 2  (promphp storage)
                                              │
Prometheus ──every 1s, GET app:80/metrics──►  rendered as Prometheus text
                                              │
Grafana ◄──────────── PromQL ─────────────────┘
```

- Calls are **buffered** and only written on `flush()`. The provider flushes when the
  request terminates (after FPM already sent the response, so users do not wait on it)
  and after every queue job, because a queue worker never terminates.
- The Prometheus backend needs **shared storage**. FPM is shared nothing: every request
  starts with empty memory, so a counter kept in PHP memory would reset every request.
  We use Redis (`METRICS_REDIS_DB`, default `2`) through
  [promphp/prometheus_client_php](https://github.com/PromPHP/prometheus_client_php).
- `/metrics` is served by its own FPM pool, so scrapes keep working while the main pool
  is saturated. See [fpm.md](fpm.md#a-dedicated-pool-for-metrics).

## What the demo exposes

| Metric | Type | Where |
|---|---|---|
| `app_orders_processed_total` | counter | `ProcessOrder` success |
| `app_orders_failed_total` | counter | `ProcessOrder` failure |
| `app_orders_job_duration_ms` | histogram (`_bucket`, `_sum`, `_count`) | `ProcessOrder` success |

Grafana: **PHP-FPM Performance Dashboard**, row **App Metrics (beberlei/metrics)**.

Try it:

```bash
make up && make up-redis && make up-grafana
curl "localhost:8088/orders/dispatch?count=500&work_ms=50"
docker compose exec app php artisan queue:work redis --queue=orders --stop-when-empty
curl -s localhost:8088/metrics | grep ^app_
```

Add `&fail=1` to the dispatch URL to watch the failed line climb.

## Where the code lives

| File | What |
|---|---|
| `vendor/kevariable/laravel-metrics` | the package: facade, collectors, flushing, fake |
| `.env` | `METRICS_COLLECTOR=prometheus`, `METRICS_NAMESPACE=app`, `METRICS_REDIS_DB=2` |
| `app/Jobs/ProcessOrder.php` | the instrumentation |
| `routes/api.php` | `/metrics` appends `Metrics::renderPrometheus()` to the hand written OPcache and queue gauges |
| `tests/Feature/MetricsTest.php` | runs against in memory storage, no Redis needed |

Everything else (drivers, `Metrics::fake()`, the optional built in scrape route) is in the
package README.

## Gotchas

- **PHP 8.4+.** beberlei/metrics v3 requires it, so `composer.json` now says `^8.4`. The
  containers already run 8.4; a host still on 8.3 cannot `composer install` any more.
- **Same tag keys every time.** Prometheus needs a metric to always carry the same tag
  keys. The package reports a mismatch through the exception handler instead of mixing
  them up.
- **Timings are histograms**, so Grafana can show averages and p95s:
  `histogram_quantile(0.95, sum by (le) (rate(app_orders_job_duration_ms_bucket[1m])))`
- **Edited PHP code?** Prod OPcache does not revalidate, run `make fpm-reload` or the
  `/metrics` pool keeps serving the old route.
