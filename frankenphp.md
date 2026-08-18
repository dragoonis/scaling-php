> ⚠️ **Legacy doc:** written for the Symfony version of this demo. The commands and endpoints may not match the Laravel branch yet.

# FrankenPHP Configuration & Performance Testing

## Overview

This project uses FrankenPHP (a modern PHP runtime built on Caddy) with two different configurations for performance
comparison and monitoring.

## Bring it up

> Prerequisite: bootstrap the app once first (`make up && make setup` - see [fpm.md](fpm.md)).
> All three runtimes share the same code, vendor/ and database, so this only needs doing once.

### up normal franken

```
make up-franken
make ps | grep franken
```

Go to - http://localhost:8080/products

After pulling code changes, restart the runtime (`docker compose restart franken franken-worker`) -
opcache caches compiled PHP in production mode, so edits are invisible until a restart.

### Show target prom sources

Check franken metric is up

go to http://localhost:8080/metrics (or `curl http://localhost:2019/metrics` — the admin port works with curl but not in a browser)

Find CTRL / CMD + F `frankenphp_total_threads`

![img_1.png](docs/images/franken-metric-classic.png)

we notice that we have total threads 16 by default, this is means Franken doing monitoring for us

now check Prometheus go to http://localhost:9090/targets?search=

![img.png](docs/images/prometheus-franken.png)

we notice that franken:2019 is up

### show grafana franken/opcache dashboard

let's make performance testing on it

```bash
make benchmark-product-random-franken
```

![img.png](docs/images/benchmark-product-random-franken.png)

see file inside k6/report-UTC-xxxxxxx.html
> i.e: k6/report-product-by-id-random-8088-2025-11-25T10-45-06.548Z.html

open it in browser

![img_3.png](docs/images/k6-report-franken-1.png)
![img_4.png](docs/images/k6-report-franken-2.png)

Go to Grafana Dashboard http://localhost:3000/
or http://localhost:3000/d/9cf6e57a-6300-46ca-8cc7-345c8ab2b665/frankenphp-2b-caddy-symfony-optimized?var-interval=30s&orgId=1&from=now-5m&to=now&timezone=browser&var-datasource=PBFA97CFB590B2093&var-job=caddy&var-instance=franken:2019&refresh=1m

![img.png](docs/images/grafana-franken-dashboard.png)

## up worker mode franken

```
make up-worker
make ps | grep franken-worker
```

Go to - http://localhost:8081/

### Show target prom sources

Check franken metrics is up

Go to http://localhost:8081/metrics (or `curl http://localhost:2020/metrics` — the admin port works with curl but not in a browser)

Find CTRL / CMD + F `frankenphp_total_threads`

![img.png](docs/images/franken-metrics.png)

we notice that our `frankenphp_total_threads` is 20

now let's check Prometheus go to http://localhost:9090/targets?search=

we notice that our http://franken-worker:2019 is Up

![img.png](docs/images/prometheus-franken.png)

### show grafana franken/opcache dashboard

```bash
make benchmark-product-random-franken-worker
```

![FrankenPHP Worker Benchmark](docs/images/benchmark-product-random-franken-worker.png)

see file inside k6/report-UTC-xxxxxxx.html
> i.e: k6/report-product-by-id-random-8081-2025-11-25T13-09-59.512Z.html

![img_7.png](docs/images/k6-report-franken-1.png)
![img_8.png](docs/images/k6-report-franken-2.png)

Check grafana output go to http://localhost:3000 or http://localhost:3000/d/9cf6e57a-6300-46ca-8cc7-345c8ab2b665/frankenphp-2b-caddy-symfony-optimized?var-interval=30s&orgId=1&from=now-5m&to=now&timezone=browser&var-datasource=PBFA97CFB590B2093&var-job=caddy&var-instance=franken-worker:2019&refresh=1m

![img_6.png](docs/images/grafana-franken-worker-dashboard.png)

you can scroll down to see more graph

---

## Performance Comparison: PHP-FPM vs FrankenPHP Classic vs FrankenPHP Worker

![Same Laravel app on three runtimes](docs/images/runtime-benchmark.png)

Measured on this repo with k6 (50 VUs, 30s per runtime, same GET /products/{id} endpoint,
zero failed requests). Interactive slide: [docs/diagrams/runtime-benchmark.html](docs/diagrams/runtime-benchmark.html).

So far we are testing FPM, Franken Classic and Franken worker mode and we know where Franken worker shine

### Performance Summary Table

| Metric                | PHP-FPM | FrankenPHP Classic | FrankenPHP Worker | Winner          |
|-----------------------|---------|--------------------|-------------------|-----------------|
| **Requests/sec**      | ~850    | ~750               | ~8,000            | 🏆 Worker (10x) |
| **Avg Latency**       | ~115ms  | ~125ms             | ~13ms             | 🏆 Worker (8x)  |
| **P95 Latency**       | ~145ms  | ~155ms             | ~22ms             | 🏆 Worker (7x)  |
| **Memory Efficiency** | Medium  | Good               | Excellent         | 🏆 Worker       |
| **CPU Efficiency**    | Medium  | Good               | Excellent         | 🏆 Worker       |

---

## Tuning cheat sheet

![FrankenPHP worker tuning](docs/images/franken-tuning.png)

Interactive version: [docs/diagrams/franken-tuning.html](docs/diagrams/franken-tuning.html).

## Service Configuration

### FrankenPHP Services

| Service               | Port | Mode    | Caddyfile           | Purpose                      |
|-----------------------|------|---------|---------------------|------------------------------|
| **FrankenPHP**        | 8080 | Regular | `Caddyfile.regular` | Traditional PHP server mode  |
| **FrankenPHP Worker** | 8081 | Worker  | `Caddyfile`         | High-performance worker mode |

### Configuration Differences

**Regular Mode (`Caddyfile.regular`):**

- Uses traditional PHP server mode
- Good for development and simple applications
- Auto-reloads on file changes if needed

**Worker Mode (`Caddyfile`):**

- Uses FrankenPHP worker mode for better performance
- Handles multiple requests concurrently
- Auto-reloads on file changes if needed
- Better suited for production workloads

### Auto-Reload (File Watching) When you are in Development Mode

Both FrankenPHP services can automatically reload PHP workers when your code changes, thanks to the `watch` directive in
the Caddyfile:

open ./docker/Caddyfile file


uncomment

```bash
# watch

# php_ini {
#     opcache.preload ""
#     opcache.revalidate_freq 0
#     opcache.validate_timestamps 1
# }
```

so it will looks like this

```caddyfile
frankenphp {
    num_threads 20 # Optimal: 16 workers + 4 handling threads
    max_threads auto # Keep stable, no dynamic scaling

    worker {
        watch
    
        file ./public/index.php
        num 16 # 2 workers per CPU core (8 cores * 2) - optimal balance
    }
    
    php_ini opcache.preload ""
    php_ini opcache.revalidate_freq 0
    php_ini opcache.validate_timestamps 1
}
```

why ?

```
watch → FrankenPHP auto-reloads when files change
preload → Load files once; changes NOT detected
revalidate_freq → How often to check files for changes
validate_timestamps → Whether to check changes at all
```

go to terminal

```bash
make worker-shell
```

run this command

```bash
frankenphp reload --config=/etc/frankenphp/Caddyfile
```

it will show something like this

```bash
root@c187ea8857b8:/var/www/html# frankenphp reload --config=/etc/frankenphp/Caddyfile
2025/11/25 13:44:47.086	INFO	using config from file	{"file": "/etc/frankenphp/Caddyfile"}
2025/11/25 13:44:47.087	INFO	adapted config to JSON	{"adapter": "caddyfile"}
2025/11/25 13:44:47.087	WARN	Caddyfile input is not formatted; run 'caddy fmt --overwrite' to fix inconsistencies	{"adapter": "caddyfile", "file": "/etc/frankenphp/Caddyfile", "line": 11}
```
if you notice that we have format issue at the file we can run

```bash
frankenphp fmt --overwrite --config=/etc/frankenphp/Caddyfile
```

if you don't faced that issue then just continue command below

```bash
frankenphp reload --config=/etc/frankenphp/Caddyfile
```

Go to http://localhost:8081/en/blog/, if you reload it will hard reload the browser

if you found it stuck at loading run command below in terminal to restart container in docker

```bash
make down-worker && make up-worker
```

now let's open file ./src/Controller/BlogController.php

and uncomment `// phpinfo();`

```php
public function index(Request $request, int $page, string $_format, PostRepository $posts, TagRepository $tags): Response
{
    phpinfo();
    
    ... another code
}
```

then Go to http://localhost:8081/en/blog/ again, we notice that phpinfo page is render

![img.png](docs/images/franken-worker-controller-watch.png)

so now everytime you make changes on the php, twig files it will reflect on the browser

