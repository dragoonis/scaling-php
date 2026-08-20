# Scaling PHP - one Laravel app, three runtimes

The same Laravel 12 app served three ways, so you can load test them side by side and watch
what actually happens:

| Runtime | Port | What it is |
|---|---|---|
| PHP-FPM | 8088 | nginx + process pool, the classic setup |
| FrankenPHP classic | 8080 | modern threaded server, still boots the app per request |
| Octane worker | 8081 | FrankenPHP + Laravel Octane, app booted once and kept warm |

Everything runs in Docker Compose and is driven by the Makefile. Metrics flow into
Prometheus and Grafana out of the box.

## Requirements

Docker, make, and [k6](https://k6.io) (`brew install k6`). Check with `bash test-system.sh`.

## Quickstart

```bash
make up          # PHP-FPM app on :8088 (builds image, composer install)
make setup       # migrations + demo data (10k products, 2k customers, 5k orders)
make up-franken  # FrankenPHP classic on :8080
make up-worker   # Octane worker on :8081
```

Check it works: http://localhost:8088/products (same path on 8080 and 8081).

Prove which mode is serving: `curl localhost:8081/runtime` - watch
`requests_served_by_this_worker` climb across calls. On 8088 and 8080 it is always 1,
because those runtimes throw the app away after every request. That one number is the
whole worker-mode story.

## The demos

**🔥 The live dashboard** - start with **[ember.md](ember.md)**, a follow-along guide:
`make ember` in one terminal, `make ember-load` in another, watch a FrankenPHP server
handle a wave of traffic in real time.

**📊 All three at once** - `make compare` + `make compare-load`: one glowing bar per
runtime, same wave, watch the worker pull ahead.

**🔍 Watch FPM breathe** - `make fpm-htop` during load: children spawn as traffic climbs
and die back after. See [fpm.md](fpm.md) for the pool sizing math.

**🔁 Disposable workers** - [disposable-workers.md](disposable-workers.md): FPM
`pm.max_requests` and Octane `--max-requests` are the same idea. Includes the
zero-downtime `make octane-reload` mid-load stunt.

**📈 Benchmarks** - `make benchmark-product-random-fpm` (and `-franken`,
`-franken-worker`): wrk-style numbers against real data.

## Dashboards

```bash
make up-grafana   # prometheus :9090 + grafana :3000
make up-exporter  # php-fpm metrics exporter
```

- Grafana: http://localhost:3000 (view without login; admin is symfony/symfony)
- Prometheus: http://localhost:9090
- Raw metrics: :8080/metrics and :8081/metrics (FrankenPHP), :8088/fpm-status (FPM)

`make urls` prints every URL.

## Tuning

- Octane worker knobs and the sizing math: **[franken-worker.md](franken-worker.md)** -
  workers default to 16, override per machine with
  `OCTANE_WORKERS=28 docker compose up -d franken-worker`
- FrankenPHP classic: **[frankenphp.md](frankenphp.md)**
- PHP-FPM pool sizing: **[fpm.md](fpm.md)**
- Serializer choice for Redis and sessions: **[igbinary.md](igbinary.md)**
- SLA-driven queue worker autoscaling: **[queues.md](queues.md)** - try
  `make queue-autoscale`, `make queue-watch`, `make queue-burst`

## Five numbers to check monday morning

The closing-slide checklist ([docs/images/monday-checklist.png](docs/images/monday-checklist.png)),
each number is one command in this repo:

| # | number | healthy | check it here |
|---|---|---|---|
| 1 | max children reached | 0 | `curl localhost:8088/fpm-status` (or exporter :9114) |
| 2 | listen queue | 0 | `curl localhost:8088/fpm-status`, the queue PHP never sees |
| 3 | opcache hit rate + wasted % | >99%, <5% | `make opcache-status` or `curl :8088/metrics` |
| 4 | memory per worker (RSS) | stable | `make fpm-ps`, the unit behind max_children |
| 5 | oldest job age | inside your SLA | `make queue-debug`, age matters, depth lies |

Details: 1-2 and 4 in [fpm.md](fpm.md), 3 in [fpm.md](fpm.md) + [grafana-dashboard.md](grafana-dashboard.md), 5 in [queues.md](queues.md).

## Gotchas that will bite you

- **Switched branches?** `make rebuild` - the php.ini is baked into the images
- **Edited PHP code?** `docker compose restart app franken franken-worker` - prod opcache
  settings cache compiled code until restart
- **Load test everything.** Three real bugs in this repo (file sessions, cached Eloquent
  models, a 3x metrics double count) were invisible in a browser and only showed up
  under k6

## Slide assets

Dark-themed slide graphics in `docs/images/` (runtime benchmark, disposable workers,
metrics pipelines, FrankenPHP tuning, FPM sizing math, igbinary, queue sizing and
the failure fuse, the monday checklist), HTML sources in `docs/diagrams/`, and
ChatGPT prompts to regenerate or restyle any of them in
[docs/diagrams/PROMPTS.md](docs/diagrams/PROMPTS.md).
