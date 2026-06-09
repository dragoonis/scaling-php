K6_SERVICE := k6

# Environment variables for k6 testing
FRANKEN_URL := http://localhost:8080
FRANKEN_WORKER_URL := http://localhost:8081
FPM_URL := http://localhost:8088
PRODUCT_ID := 105069

.PHONY: k6 clean

pull-docker:
	@echo "Pulling Docker images..."
	docker pull redis:7-alpine
	docker pull php:8.4-fpm
	docker pull prom/prometheus:v2.53.4
	docker pull grafana/grafana:latest
	docker pull hipages/php-fpm_exporter:latest
	docker pull dunglas/frankenphp:1.12.4-php8.4-bookworm
	@echo "All Docker images pulled successfully!"

# Individual Docker image pull commands
docker-redis:
	@echo "Pulling Redis image..."
	docker pull redis:7-alpine
	@echo "Redis image pulled successfully!"

docker-php:
	@echo "Pulling PHP-FPM image..."
	docker pull php:8.4-fpm
	@echo "PHP-FPM image pulled successfully!"

docker-prometheus:
	@echo "Pulling Prometheus image..."
	docker pull prom/prometheus:v2.53.4
	@echo "Prometheus image pulled successfully!"

docker-grafana:
	@echo "Pulling Grafana image..."
	docker pull grafana/grafana:latest
	@echo "Grafana image pulled successfully!"

docker-exporter:
	@echo "Pulling PHP-FPM Exporter image..."
	docker pull hipages/php-fpm_exporter:latest
	@echo "PHP-FPM Exporter image pulled successfully!"

docker-frankenphp:
	@echo "Pulling FrankenPHP image..."
	docker pull dunglas/frankenphp:1.12.4-php8.4-bookworm
	@echo "FrankenPHP image pulled successfully!"

build:
	docker-compose build

restart:
	docker-compose stop app
	docker-compose up -d app

shell:
	docker-compose exec app bash

up:
	docker-compose up -d app
	docker-compose exec app composer install
	docker-compose up -d redis

down:
	docker-compose down


ps:
	docker-compose ps

up-redis:
	docker-compose up -d redis


up-franken:
	docker-compose up franken -d

down-franken:
	docker-compose stop franken

up-worker:
	docker-compose up franken-worker -d

down-worker:
	docker-compose stop franken-worker

up-prometheus:
	docker-compose up -d prometheus

down-prometheus:
	docker-compose down prometheus

up-grafana:
	docker-compose up prometheus grafana -d

down-grafana:
	docker-compose stop prometheus grafana

up-opcache-dashboard:
	docker-compose up -d opcache-dashboard

down-opcache-dashboard:
	docker-compose down opcache-dashboard

up-exporter:
	docker-compose up -d php-fpm-exporter

down-exporter:
	docker-compose down php-fpm-exporter

app-shell:
	docker-compose exec -it app bash

franken-shell:
	docker-compose exec -it franken bash

worker-shell:
	docker-compose exec -it franken-worker bash

migrate: ## Create/update the DB schema from entity metadata (DB-agnostic; the committed migrations are SQLite-only)
	docker-compose exec app php bin/console doctrine:schema:update --force --complete

seed: ## Seed the database with test data
	@echo "Seeding database with test data..."
	@echo "Seeder file: src/Command/SeedDatabaseCommand.php"
	docker-compose exec app php bin/console app:seed-database

setup: migrate seed ## Run migrations and seed database

test: ## Run the unit test suite (APP_ENV=test)
	docker-compose exec -e APP_ENV=test app php bin/phpunit

clean:
	docker-compose down -v --remove-orphans

# ──────────────────────────────────────────────────────────────
# 🔥 Ember - live FrankenPHP dashboard (see ember.md)
#   Ember is the tool from https://github.com/alexandre-daubois/ember
#   Install once:  brew install alexandre-daubois/tap/ember
# ──────────────────────────────────────────────────────────────
# Defaults target FrankenPHP CLASSIC (:8080 / admin :2019). Classic stays rock-solid
# under load; the worker mode can deadlock when hammered, so it's not the default for
# the live wave. `make ember` + `make ember-load` work as a matched pair out of the box.
EMBER_ADDR ?= http://localhost:2019
EMBER_TARGETS ?= franken
.PHONY: ember ember-install ember-load compare

ember-install: ## 🔥 Install the Ember CLI (auto-detects macOS / Linux / Windows)
	@bash bin/install-ember.sh

ember: ## 🔥 Open the Ember dashboard (classic by default; EMBER_ADDR=...:2020 for the worker)
	ember --addr $(EMBER_ADDR)

ember-load: ## 🔥 Run the up→down→up traffic wave (classic by default; EMBER_TARGETS=worker for the worker)
	k6 run -e EMBER_TARGETS=$(EMBER_TARGETS) k6/ember_ramp.js

compare: ## 🔥 Side-by-side bars for FPM vs FrankenPHP classic vs worker (run alongside 'make compare-load')
	docker-compose exec app php bin/scope

compare-load: ## 🔥 Drive ALL THREE runtimes at once (use this with 'make compare')
	k6 run -e EMBER_TARGETS=fpm,franken,worker k6/ember_ramp.js

# Franken Worker targets
.PHONY: k6-franken-worker-products-db
k6-franken-worker-products-db:
	@echo "Running products DB test against Franken Worker..."
	k6 run --env BASE_URL=$(FRANKEN_WORKER_URL) k6/list_products_db.js

.PHONY: k6-franken-worker-products-redis
k6-franken-worker-products-redis:
	@echo "Running products Redis test against Franken Worker..."
	k6 run --env BASE_URL=$(FRANKEN_WORKER_URL) k6/list_products_redis.js

.PHONY: k6-franken-worker-customers-db
k6-franken-worker-customers-db:
	@echo "Running customers DB test against Franken Worker..."
	k6 run --env BASE_URL=$(FRANKEN_WORKER_URL) k6/list_customers_db.js

.PHONY: k6-franken-worker-customers-redis
k6-franken-worker-customers-redis:
	@echo "Running customers Redis test against Franken Worker..."
	k6 run --env BASE_URL=$(FRANKEN_WORKER_URL) k6/list_customers_redis.js

.PHONY: k6-franken-worker-orders-db
k6-franken-worker-orders-db:
	@echo "Running orders DB test against Franken Worker..."
	k6 run --env BASE_URL=$(FRANKEN_WORKER_URL) k6/list_orders_db.js

.PHONY: k6-franken-worker-orders-redis
k6-franken-worker-orders-redis:
	@echo "Running orders Redis test against Franken Worker..."
	k6 run --env BASE_URL=$(FRANKEN_WORKER_URL) k6/list_orders_redis.js

# Franken targets
.PHONY: k6-franken-products-db
k6-franken-products-db:
	@echo "Running products DB test against Franken..."
	k6 run --env BASE_URL=$(FRANKEN_URL) k6/list_products_db.js

.PHONY: k6-franken-products-redis
k6-franken-products-redis:
	@echo "Running products Redis test against Franken..."
	k6 run --env BASE_URL=$(FRANKEN_URL) k6/list_products_redis.js

.PHONY: k6-franken-customers-db
k6-franken-customers-db:
	@echo "Running customers DB test against Franken..."
	k6 run --env BASE_URL=$(FRANKEN_URL) k6/list_customers_db.js

.PHONY: k6-franken-customers-redis
k6-franken-customers-redis:
	@echo "Running customers Redis test against Franken..."
	k6 run --env BASE_URL=$(FRANKEN_URL) k6/list_customers_redis.js

.PHONY: k6-franken-orders-db
k6-franken-orders-db:
	@echo "Running orders DB test against Franken..."
	k6 run --env BASE_URL=$(FRANKEN_URL) k6/list_orders_db.js

.PHONY: k6-franken-orders-redis
k6-franken-orders-redis:
	@echo "Running orders Redis test against Franken..."
	k6 run --env BASE_URL=$(FRANKEN_URL) k6/list_orders_redis.js

# mysql read
.PHONY: k6-fpm-products-db
k6-fpm-products-db:
	@echo "Running products DB test against FPM..."
	k6 run --env BASE_URL=$(FPM_URL) k6/list_products_db.js

# projection read
.PHONY: k6-fpm-products-redis
k6-fpm-products-redis:
	@echo "Running products Redis test against FPM..."
	k6 run --env BASE_URL=$(FPM_URL) k6/list_products_redis.js

.PHONY: k6-fpm-customers-db
k6-fpm-customers-db:
	@echo "Running customers DB test against FPM..."
	k6 run --env BASE_URL=$(FPM_URL) k6/list_customers_db.js

.PHONY: k6-fpm-customers-redis
k6-fpm-customers-redis:
	@echo "Running customers Redis test against FPM..."
	k6 run --env BASE_URL=$(FPM_URL) k6/list_customers_redis.js

.PHONY: k6-fpm-orders-db
k6-fpm-orders-db:
	@echo "Running orders DB test against FPM..."
	k6 run --env BASE_URL=$(FPM_URL) k6/list_orders_db.js

.PHONY: k6-fpm-orders-redis
k6-fpm-orders-redis:
	@echo "Running orders Redis test against FPM..."
	k6 run --env BASE_URL=$(FPM_URL) k6/list_orders_redis.js

# Batch testing targets
.PHONY: k6-all-franken-worker
k6-all-franken-worker:
	@echo "Running all tests against Franken Worker..."
	$(MAKE) k6-franken-worker-products-db
	$(MAKE) k6-franken-worker-products-redis
	$(MAKE) k6-franken-worker-customers-db
	$(MAKE) k6-franken-worker-customers-redis
	$(MAKE) k6-franken-worker-orders-db
	$(MAKE) k6-franken-worker-orders-redis

.PHONY: k6-all-franken
k6-all-franken:
	@echo "Running all tests against Franken..."
	$(MAKE) k6-franken-products-db
	$(MAKE) k6-franken-products-redis
	$(MAKE) k6-franken-customers-db
	$(MAKE) k6-franken-customers-redis
	$(MAKE) k6-franken-orders-db
	$(MAKE) k6-franken-orders-redis

.PHONY: k6-all-fpm
k6-all-fpm:
	@echo "Running all tests against FPM..."
	$(MAKE) k6-fpm-products-db
	$(MAKE) k6-fpm-products-redis
	$(MAKE) k6-fpm-customers-db
	$(MAKE) k6-fpm-customers-redis
	$(MAKE) k6-fpm-orders-db
	$(MAKE) k6-fpm-orders-redis

.PHONY: k6-all-environments
k6-all-environments:
	@echo "Running all tests against all environments..."
	$(MAKE) k6-all-franken-worker
	$(MAKE) k6-all-franken
	$(MAKE) k6-all-fpm

# Utility targets
.PHONY: k6-clean-reports
k6-clean-reports:
	@echo "Cleaning k6 report files..."
	rm -f k6/report-*.html

.PHONY: k6-install
k6-install:
	@echo "Installing k6..."
	@if command -v k6 >/dev/null 2>&1; then \
		echo "k6 is already installed"; \
	else \
		echo "Installing k6..."; \
		if command -v brew >/dev/null 2>&1; then \
			brew install k6; \
		elif command -v apt-get >/dev/null 2>&1; then \
			sudo apt-get update && sudo apt-get install -y k6; \
		else \
			echo "Please install k6 manually from https://k6.io/docs/getting-started/installation/"; \
		fi; \
	fi

# Product by ID benchmark targets (single ID)
.PHONY: benchmark-product-by-id-franken
benchmark-product-by-id-franken:
	@echo "Running product by ID benchmark against Franken (port 8080)..."
	BASE_URL=$(FRANKEN_URL) PRODUCT_ID=$(PRODUCT_ID) k6 run k6/get_product_by_id.js

.PHONY: benchmark-product-by-id-franken-worker
benchmark-product-by-id-franken-worker:
	@echo "Running product by ID benchmark against Franken Worker (port 8081)..."
	BASE_URL=$(FRANKEN_WORKER_URL) PRODUCT_ID=$(PRODUCT_ID) k6 run k6/get_product_by_id.js

.PHONY: benchmark-product-by-id-fpm
benchmark-product-by-id-fpm:
	@echo "Running product by ID benchmark against FPM (port 8088)..."
	BASE_URL=$(FPM_URL) PRODUCT_ID=$(PRODUCT_ID) k6 run k6/get_product_by_id.js

.PHONY: benchmark-product-by-id-all
benchmark-product-by-id-all:
	@echo "Running product by ID benchmarks against all environments..."
	@echo ""
	$(MAKE) benchmark-product-by-id-franken
	@echo ""
	@echo ""
	$(MAKE) benchmark-product-by-id-franken-worker
	@echo ""
	@echo ""
	$(MAKE) benchmark-product-by-id-fpm

# Product by ID benchmark targets (random IDs from projection)
.PHONY: benchmark-product-random-franken
benchmark-product-random-franken:
	@echo "Running random product ID benchmark against Franken (port 8080)..."
	BASE_URL=$(FRANKEN_URL) k6 run k6/get_product_by_id_random.js

.PHONY: benchmark-product-random-franken-worker
benchmark-product-random-franken-worker:
	@echo "Running random product ID benchmark against Franken Worker (port 8081)..."
	BASE_URL=$(FRANKEN_WORKER_URL) k6 run k6/get_product_by_id_random.js

.PHONY: benchmark-product-random-fpm
benchmark-product-random-fpm:
	@echo "Running random product ID benchmark against FPM (port 8088)..."
	BASE_URL=$(FPM_URL) k6 run k6/get_product_by_id_random.js

.PHONY: benchmark-product-random-all
benchmark-product-random-all:
	@echo "Running random product ID benchmarks against all environments..."
	@echo ""
	$(MAKE) benchmark-product-random-franken
	@echo ""
	@echo ""
	$(MAKE) benchmark-product-random-franken-worker
	@echo ""
	@echo ""
	$(MAKE) benchmark-product-random-fpm

# Product by ID benchmark targets (cycling through IDs from /projection)
.PHONY: benchmark-product-projection-franken
benchmark-product-projection-franken:
	@echo "Running cycling product ID benchmark (Projection) against Franken (port 8080)..."
	BASE_URL=$(FRANKEN_URL) k6 run k6/get_product_by_id_projection.js

.PHONY: benchmark-product-projection-franken-worker
benchmark-product-projection-franken-worker:
	@echo "Running cycling product ID benchmark (Projection) against Franken Worker (port 8081)..."
	BASE_URL=$(FRANKEN_WORKER_URL) k6 run k6/get_product_by_id_projection.js

.PHONY: benchmark-product-projection-fpm
benchmark-product-projection-fpm:
	@echo "Running cycling product ID benchmark (Projection) against FPM (port 8088)..."
	BASE_URL=$(FPM_URL) k6 run k6/get_product_by_id_projection.js

.PHONY: benchmark-product-projection-all
benchmark-product-projection-all:
	@echo "Running cycling product ID benchmarks (Projection) against all environments..."
	@echo ""
	$(MAKE) benchmark-product-projection-franken
	@echo ""
	@echo ""
	$(MAKE) benchmark-product-projection-franken-worker
	@echo ""
	@echo ""
	$(MAKE) benchmark-product-projection-fpm

# Projection rebuild targets
.PHONY: rebuild-projections
rebuild-projections:
	@echo "Rebuilding all projections..."
	@docker-compose exec app php bin/console app:rebuild-product-projections 2>&1 | grep -v "User Deprecated"
	@docker-compose exec app php bin/console app:rebuild-customer-projections 2>&1 | grep -v "User Deprecated"
	@docker-compose exec app php bin/console app:rebuild-order-projections 2>&1 | grep -v "User Deprecated"

.PHONY: rebuild-products
rebuild-products:
	@echo "Rebuilding product projections..."
	docker-compose exec app bin/console app:rebuild-product-projections

.PHONY: rebuild-customers
rebuild-customers:
	@echo "Rebuilding customer projections..."
	docker-compose exec app bin/console app:rebuild-customer-projections

.PHONY: rebuild-orders
rebuild-orders:
	@echo "Rebuilding order projections..."
	docker-compose exec app bin/console app:rebuild-order-projections

.PHONY: seed-db
seed-db:
	@echo "Seeding database with test data..."
	docker-compose exec app bin/console app:seed-database

.PHONY: reset-and-seed
reset-and-seed:
	@echo "Resetting database and seeding with test data..."
	docker-compose exec php bin/console doctrine:database:drop --force --if-exists
	docker-compose exec php bin/console doctrine:database:create
	docker-compose exec php bin/console doctrine:migrations:migrate --no-interaction
	docker-compose exec php bin/console app:seed-database



## help: show available targets
help:
	@echo "Scaling PHP - FrankenPHP demo (see README.md and ember.md)"
	@echo ""
	@echo "Available targets:"
	@echo "  help                    - Show this help message"
	@echo ""
	@echo "🔥 Ember live demo:"
	@echo "  ember-install           - Install the Ember CLI (macOS / Linux / Windows)"
	@echo "  ember                   - Open the Ember dashboard (classic by default)"
	@echo "  ember-load              - Run the up->down->up traffic wave (classic)"
	@echo "  compare                 - Side-by-side bars: FPM vs classic vs worker"
	@echo "  compare-load            - Drive ALL THREE runtimes (use with 'compare')"
	@echo "  docker                  - Pull all required Docker images"
	@echo "  docker-redis            - Pull Redis image only"
	@echo "  docker-php              - Pull PHP-FPM image only"
	@echo "  docker-prometheus       - Pull Prometheus image only"
	@echo "  docker-grafana          - Pull Grafana image only"
	@echo "  docker-exporter         - Pull PHP-FPM Exporter image only"
	@echo "  docker-frankenphp       - Pull FrankenPHP image only"
	@echo ""
	@echo "Franken Worker (http://localhost:8081):"
	@echo "  k6-franken-worker-products    - Test products endpoint"
	@echo "  k6-franken-worker-products-db - Test products DB endpoint"
	@echo "  k6-franken-worker-products-redis - Test products Redis endpoint"
	@echo "  k6-franken-worker-customers   - Test customers endpoint"
	@echo "  k6-franken-worker-customers-db - Test customers DB endpoint"
	@echo "  k6-franken-worker-customers-redis - Test customers Redis endpoint"
	@echo "  k6-franken-worker-orders      - Test orders endpoint"
	@echo "  k6-franken-worker-orders-db   - Test orders DB endpoint"
	@echo "  k6-franken-worker-orders-redis - Test orders Redis endpoint"
	@echo "  k6-franken-worker-blog        - Test blog endpoint"
	@echo ""
	@echo "Franken (http://localhost:8080):"
	@echo "  k6-franken-products           - Test products endpoint"
	@echo "  k6-franken-products-db        - Test products DB endpoint"
	@echo "  k6-franken-products-redis     - Test products Redis endpoint"
	@echo "  k6-franken-customers          - Test customers endpoint"
	@echo "  k6-franken-customers-db       - Test customers DB endpoint"
	@echo "  k6-franken-customers-redis    - Test customers Redis endpoint"
	@echo "  k6-franken-orders             - Test orders endpoint"
	@echo "  k6-franken-orders-db          - Test orders DB endpoint"
	@echo "  k6-franken-orders-redis       - Test orders Redis endpoint"
	@echo "  k6-franken-blog               - Test blog endpoint"
	@echo ""
	@echo "FPM (http://localhost:8088):"
	@echo "  k6-fpm-products               - Test products endpoint"
	@echo "  k6-fpm-products-db            - Test products DB endpoint"
	@echo "  k6-fpm-products-redis         - Test products Redis endpoint"
	@echo "  k6-fpm-customers              - Test customers endpoint"
	@echo "  k6-fpm-customers-db           - Test customers DB endpoint"
	@echo "  k6-fpm-customers-redis        - Test customers Redis endpoint"
	@echo "  k6-fpm-orders                 - Test orders endpoint"
	@echo "  k6-fpm-orders-db              - Test orders DB endpoint"
	@echo "  k6-fpm-orders-redis           - Test orders Redis endpoint"
	@echo "  k6-fpm-blog                   - Test blog endpoint"
	@echo ""
	@echo "Batch testing:"
	@echo "  k6-all-franken-worker         - Run all tests against Franken Worker"
	@echo "  k6-all-franken                - Run all tests against Franken"
	@echo "  k6-all-fpm                    - Run all tests against FPM"
	@echo "  k6-all-environments           - Run all tests against all environments"
	@echo ""
	@echo "Projection management:"
	@echo "  rebuild-projections            - Rebuild all projections (products, customers, orders)"
	@echo "  rebuild-products               - Rebuild product projections only"
	@echo "  rebuild-customers              - Rebuild customer projections only"
	@echo "  rebuild-orders                 - Rebuild order projections only"
	@echo "  seed-db                        - Seed database with test data"
	@echo "  reset-and-seed                 - Reset database and seed with test data"
