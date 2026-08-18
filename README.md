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

- FrankenPHP knobs and the sizing math: **[frankenphp.md](frankenphp.md)** (see the
  tuning cheat sheet) - workers default to 16, override per machine with
  `OCTANE_WORKERS=28 docker compose up -d franken-worker`
- PHP-FPM pool sizing: **[fpm.md](fpm.md)**

## Gotchas that will bite you

- **Switched branches?** `make rebuild` - the php.ini is baked into the images
- **Edited PHP code?** `docker compose restart app franken franken-worker` - prod opcache
  settings cache compiled code until restart
- **Load test everything.** Three real bugs in this repo (file sessions, cached Eloquent
  models, a 3x metrics double count) were invisible in a browser and only showed up
  under k6

## Slide assets

Dark-themed diagrams in `docs/images/` (benchmark chart, disposable workers, FPM and
OPcache metrics pipelines, FrankenPHP tuning), interactive HTML versions in
`docs/diagrams/`.
