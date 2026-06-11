# Scaling PHP Systems - FrankenPHP @ IPC Conference Germany

A Symfony CQRS demo for exploring how modern PHP runtimes scale. The same app runs three ways - **PHP-FPM**,
**FrankenPHP classic**, and **FrankenPHP worker** - so you can load-test them side by side with k6 and watch
the difference live. Everything runs in Docker Compose and is driven by the Makefile.

## What this demo shows

- One Symfony app (Products / Customers / Orders) using **CQRS**: writes go to the database, reads come from
  fast **Redis projections**.
- The same app served by **three runtimes**, so you can compare them fairly:

  | Runtime            | Port | What it is                                    |
  |--------------------|------|-----------------------------------------------|
  | PHP-FPM            | 8088 | Traditional process-per-request               |
  | FrankenPHP classic | 8080 | Modern PHP server on Caddy                     |
  | FrankenPHP worker  | 8081 | Long-lived workers, app kept warm in memory   |

- Live metrics for all of them (FPM status, OPcache, Caddy/Prometheus) plus Grafana dashboards.
- **🔥 [Ember](ember.md)** - a one-command live demo that ramps traffic up and down while you watch the
  numbers move.

> Runtimes run **PHP 8.4** (FrankenPHP `1.12.4`). New here? **Start with [ember.md](ember.md).**

## Web Interfaces & Dashboards

| Service               | URL                           | Description                           |
|-----------------------|-------------------------------|---------------------------------------|
| FPM App               | http://localhost:8088         | Main Symfony app (FPM)                |
| Franken               | http://localhost:8080         | FrankenPHP (HTTP, regular mode)       |
| Franken Worker        | http://localhost:8081         | FrankenPHP Worker (HTTP, optimized)   |
| Grafana               | http://localhost:3000         | Metrics dashboard (symfony/symfony)   |
| Prometheus            | http://localhost:9090         | Prometheus metrics                    |
| Opcache Dashboard     | http://localhost:42042        | PHP Opcache dashboard                 |
| Opcache Metrics (FPM) | http://localhost:8088/metrics | PHP Opcache metrics via FPM app       |
| Franken Metrics       | http://localhost:8080/metrics | Caddy/FrankenPHP metrics (non-worker) |
| Worker Metrics        | http://localhost:8081/metrics | Caddy/FrankenPHP metrics (worker)     |

> The same metrics are also served by the Caddy admin API on ports 2019 (non-worker) and
> 2020 (worker) — used by Prometheus and fine with `curl` — but Caddy v2.11 rejects browser
> navigations to the admin API as CSRF protection (browsers send `Sec-Fetch-*` headers that
> trigger its origin check, producing `client is not allowed to access from origin ''`).

## Setup

### Required Dependencies

1. **Docker & Docker Compose**
   - [Docker Desktop](https://www.docker.com/products/docker-desktop/) (includes Docker Compose)
   - Or install separately: [Docker](https://docs.docker.com/get-docker/)
     and [Docker Compose](https://docs.docker.com/compose/install/)

2. **Make**
   - **macOS**: Usually pre-installed, or install via Homebrew: `brew install make`
   - **Linux**: Install via package manager:
      - Ubuntu/Debian: `sudo apt-get install make`
      - CentOS/RHEL: `sudo yum install make`
      - Fedora: `sudo dnf install make`

3. **K6 (Load Testing Tool)**
   - **macOS**: `brew install k6`
   - **Linux**:
      - Ubuntu/Debian: `sudo gpg -k`
     ```bash
     sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
     echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
     sudo apt-get update
     sudo apt-get install k6
     ```
      - CentOS/RHEL/Fedora: `sudo yum install k6` or `sudo dnf install k6`

### Verify Installation

Run the test script to verify all dependencies are installed:

```bash
./test-system.sh
```

This will check for Docker, Docker Compose, Make, and K6 and report their status.

### Docker Images Setup

Before starting the application, you need to pull the required Docker images.

```bash
make pull-docker
```

This ensures all required Docker images are available locally before starting the services.

## Quick Start

1. **Build and start all services:**

```bash
make up
```

2. **Set up the database and seed data:**

```bash
make setup
```

Then open the app in your browser:

```bash
make open          # opens http://localhost:8088 (works on macOS, Linux, WSL, Windows)
make open http://localhost:8081/en/products/db   # or open any URL directly
make urls          # or print every dashboard URL to Ctrl+click
```

## 🔥 Ember - live demo (start here)

The quickest way to see what this project is about. We use **[Ember](https://github.com/alexandre-daubois/ember)**,
a terminal dashboard for FrankenPHP, and watch it react to a wave of traffic.

```bash
# one-time setup: install Ember, start the stack, create + seed the database
make ember-install
make up && make up-worker && make setup
```

> `make setup` (re)seeds the database (10k products + projections) - it's a **one-time** step.
> On later runs just start the services: `make up && make up-franken`.

```bash
# terminal 1 - the live dashboard
make ember
```

```bash
# terminal 2 - send a spiky wave of traffic
make ember-load
```

Watch RPS and busy threads climb and fall as FrankenPHP handles the wave - no flags needed, both commands
default to the stable classic server.

Want the **side-by-side** FPM vs classic vs worker race? Use `make compare` + `make compare-load`.

Full beginner walkthrough (with screenshots) in **[ember.md](ember.md)**.

## PHP-FPM & OPcache Configuration

For detailed end-to-end guides on PHP-FPM and OPcache configuration, monitoring, and optimization:

**See [`fpm.md`](fpm.md) for complete PHP-FPM documentation including:**
- Booting and running PHP-FPM
- FPM settings and configuration
- FPM math and right-sizing calculations
- FPM calculator tool
- K6 load testing
- FPM status page and monitoring
- FPM exporter metrics
- Grafana dashboard integration

**See [`php.ini.md`](php.ini.md) for complete OPcache documentation including:**
- Booting and accessing the OPcache dashboard
- PHP OPcache .ini settings and configuration
- Metrics endpoint and export
- Prometheus integration for OPcache
- Grafana dashboard for OPcache monitoring

### Composer Autoload Optimization

For optimal performance, this project uses Composer autoload optimizations configured in `composer.json`:

```json
{
  "config": {
    "optimize-autoloader": true,
    "classmap-authoritative": true
  }
}
```

**Performance Impact:**

- `optimize-autoloader`: ~10-15% faster autoloading (converts PSR-0/PSR-4 to classmap)
- `apcu-autoloader`: ~50-70% faster (requires APCu extension)
- `classmap-authoritative`: Set to `false` for development, `true` for production only

**Reference:** See the
official [Symfony Performance Documentation](https://symfony.com/doc/current/performance.html#optimize-composer-autoloader)
for detailed autoloader optimization guidelines and best practices.


## FrankenPHP Configuration

This project uses FrankenPHP (a modern PHP runtime built on Caddy) with two different configurations for performance
comparison and monitoring.

**See [`frankenphp.md`](frankenphp.md) for complete FrankenPHP documentation including:**

- Service configuration and differences
- Auto-reload (file watching) setup
- Caddy configuration and environment variables
- Performance testing and monitoring
- Troubleshooting guide
- Resource optimization guidelines

## Grafana Dashboard

A detailed PHP-FPM and OPcache monitoring dashboard is available in Grafana. It includes:

- PHP-FPM health, queue, and process metrics
- Request rate, duration, and memory usage
- OPcache hit ratio, memory, and script cache stats
- JIT and interned strings monitoring
- Alerts and color-coded panels for quick health checks

**See [`grafana-dashboard.md`](grafana-dashboard.md) for a full description of all panels and dashboard features.**

## PHP Configuration Reference

For detailed PHP configuration documentation and settings explanation, see [`php.ini.md`](php.ini.md).

This covers all settings in `./docker/symfony.prod.ini` including:
- OPcache configuration
- Memory management
- Security settings
- Session management
- Performance optimization