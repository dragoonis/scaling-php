# FrankenPHP Classic

The middle runtime: a modern threaded PHP server (Caddy with PHP built in), but the app
still **boots on every request** like FPM. Comparing it against the
[Octane worker](franken-worker.md) is how you isolate what "keeping the app warm" is
actually worth.

## Bring it up

> First time here? Bootstrap the app once: `make up && make setup` (see [fpm.md](fpm.md)).
> All three runtimes share the same code, vendor/ and database.

```bash
make up-franken
```

Check it: http://localhost:8080/products

Prove it boots per request:

```bash
curl -s localhost:8080/runtime | jq
```

`requests_served_by_this_worker` is **always 1** - every request gets a fresh app.
Compare with the same call on :8081 where the counter climbs.

## How it works

- One process, many **threads** (no per-request fork like FPM)
- Thread count defaults to **2x CPU cores** (`docker/Caddyfile.regular` sets no
  `num_threads`, so on an 8-core machine you get 16, on 14 cores 28)
- Fast despite booting per request because **OPcache** serves all compiled code from
  shared memory (`validate_timestamps=0` = zero file stats per request)

## Metrics

- App + Caddy metrics for browsers: http://localhost:8080/metrics
- Admin API (Prometheus scrapes this): http://localhost:2019/metrics
- Watch it live: `make ember EMBER_ADDR=http://localhost:2019 EMBER_SERVICE=franken`

> Counting caveat: Caddy increments its request counter once per middleware handler and
> this Caddyfile has three, so raw `caddy_http_requests_total` sums read 3x reality.
> `make compare` already corrects for this.

## Benchmark it

```bash
make benchmark-product-by-id-franken
make benchmark-product-random-franken
```

Measured on this repo (50 VUs, 30s, same endpoint): classic ~618 rps @ 81ms avg vs the
worker's ~2,516 rps @ 20ms. Full chart: `docs/images/runtime-benchmark.png`.

## Dev mode

Uncomment `watch` in the Caddyfile for file watching during development. Never in prod,
and remember: with prod opcache settings, code changes need
`docker compose restart franken`.
