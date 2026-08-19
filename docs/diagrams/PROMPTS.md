# Regenerating or resizing these graphics

## The easy way (no AI needed)

Every PNG in `docs/images/` is rendered from a self-contained HTML file in this folder.
For bigger versions just re-render at a higher scale factor - lossless, exact same design:

```bash
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new \
  --force-device-scale-factor=2 \
  --screenshot=out.png --window-size=1300,1250 --hide-scrollbars \
  "file:///path/to/docs/diagrams/fpm-metrics-pipeline.html"
```

Use `--force-device-scale-factor=3` or `4` for even larger. Or open the HTML in a
browser, zoom, and screenshot. The committed PNGs are already rendered at 2x
(2,400+ px wide).

## The ChatGPT way

Paste the BASE STYLE PROMPT below, then under it paste one of the per-graphic content
blocks. ChatGPT returns one HTML file - save it, open in a browser, screenshot.

### BASE STYLE PROMPT

Generate a professional dark-themed technical diagram as ONE self-contained HTML file
with inline SVG. Output only the complete HTML in a single code block. No JavaScript,
no external libraries; only external resource allowed is the JetBrains Mono font from
Google Fonts. Design system, follow exactly: page background slate-950 (#020617), all
text JetBrains Mono, content max-width 1100px centered; the SVG (viewBox about
1000x600) sits in a rounded card (background rgba(15,23,42,0.5), 1px border #1e293b);
inside the SVG a subtle 40px grid pattern (stroke #1e293b, width 0.5). Components are
rounded rects (rx=6, 1.5px stroke) drawn twice: an opaque #0f172a rect first, then the
tinted rect on top. Color semantics: frontend/UI fill rgba(8,51,68,0.4) stroke #22d3ee;
backend/runtime fill rgba(6,78,59,0.4) stroke #34d399; database/storage fill
rgba(76,29,149,0.4) stroke #a78bfa; infra/exporter fill rgba(120,53,15,0.3) stroke
#fbbf24; alerts/security fill rgba(136,19,55,0.4) stroke #fb7185; external fill
rgba(30,41,59,0.5) stroke #94a3b8. Component names 11-12px bold white, sublabels 9px
#94a3b8, annotations 8px. Draw arrows right after the grid so they render behind boxes;
solid lines for request flow, dashed (5,5) amber for polling/stats flows, arrowhead
marker fill #64748b. Boundary boxes: dashed (8,4) amber rounded rect (rx=12, fill
rgba(251,191,36,0.05)) with a 10px amber label top-left. Keep 40px between components,
no overlapping text, add a small legend in empty space. Page structure: header (title
with pulsing dot, grey one-line subtitle), the SVG card, three summary cards with
bullet points, tiny grey footer. After outputting, wait; if I report overlapping
labels, fix coordinates and output the full file again.

### Content blocks (paste ONE under the base prompt)

**fpm-metrics-pipeline** - "Draw this system: browser/k6 sends HTTP to nginx :80
(published :8088); nginx forwards over FastCGI :9000 to a PHP-FPM pool (master +
children, pm=dynamic, max_children=300, pm.status_path=/fpm-status) running Laravel;
the app reads SQLite. A php-fpm-exporter pulls /fpm-status over FastCGI (dashed amber)
and republishes Prometheus metrics on :9253; Prometheus scrapes it every 1s; Grafana
:3000 queries Prometheus with PromQL. Boundary 'app container' around nginx+FPM,
boundary 'monitoring stack' around exporter+Prometheus+Grafana."

**opcache-metrics-pipeline** - "Draw this system: three PHP runtime boxes stacked in
one amber boundary labeled 'each has its OWN OPcache per master process': app PHP-FPM
:8088, franken classic :8080, franken-worker Octane :8081. Each contains a violet
'OPcache SHM' chip (192MB, 16087 files max, validate_timestamps=0) and a cyan
'public/agent-pull.php' chip (dumps opcache_get_status() as JSON). A GoMetric
opcache-dashboard collector (amber box) polls each runtime's :80/agent-pull.php every
5s (dashed amber arrows); a browser views the dashboard UI on :42042."

**runtime-benchmark** - "Draw a two-row bar chart, no boundaries: row one 'Requests per
second': PHP-FPM 723 rps (cyan), FrankenPHP classic 618 rps (amber), Octane worker
2,516 rps (emerald, tallest, label '3.5x throughput'); row two 'Average latency':
69ms, 81ms, 20ms (worker shortest). Subtitle: GET /products/{id}, 50 VUs for 30s per
runtime, zero failed requests. Sublabels: FPM 'boots app per request', classic
'threads, still boots per request', worker 'app booted once, kept warm'."

**disposable-workers** - "Draw two config cards side by side flowing into one shared
principle box: left cyan card 'PHP-FPM' with code chip 'pm.max_requests = 1000'
(child serves 1000 requests then master forks a fresh one); right emerald card
'Laravel Octane' with code chip 'octane:frankenphp --max-requests=N' (worker restarts
with a fresh app). Both arrow down into an orange box: 'Same principle: a leak cannot
grow forever if the worker does not live forever' with note 'recycling is graceful,
in-flight request finishes first'. Footer notes: watch it live with make fpm-htop, or
press r in ember."

**franken-tuning** - "Draw an annotated config on the left (one tall emerald box
showing a Caddyfile: frankenphp { num_threads 20, max_threads auto, worker { file
frankenphp-worker.php, num 16 } }, then octane flags --workers=16 --max-requests=250,
then php.ini: APP_ENV=prod, opcache.validate_timestamps=0) with arrows to four
explanation boxes on the right: 'the math (8 cores): workers = 2x cores = 16, threads
= workers + spare = 20, max_threads auto = burst headroom'; 'recycling: workers restart
after 250 requests, zero dropped, octane:reload = zero downtime deploy' (amber);
'php must be prod mode: dev rebuilds caches per request and deadlocks' (cyan); 'watch
the queue not the cpu: frankenphp_queue_depth > 0 sustained = out of workers' (rose)."

**igbinary-benchmark** - "Draw a stats slide, three sections of horizontal paired bars
(cyan = PHP serialize, violet = igbinary): section 'Redis memory per session key':
3,128 B vs 1,592 B (-49%), note '1,000 sessions: 3,047 KiB vs 1,547 KiB, SET +5%
GET +6%'; section 'Serialized size': session 2,774 vs 1,462 (-47%), 100 products
35,508 vs 16,112 (-55%), nested array 125,184 vs 39,557 (-68%); section 'Unserialize
ops/s': session 287k vs 257k (about even), 100 products 33.3k vs 43.0k (+29%), nested
3.2k vs 6.2k (+94%). Footer: json was bigger and slower to decode in every test."

**igbinary-overview** - "Draw one violet 'ext-igbinary' box on top (compact binary
format replacing serialize(), same semantics) with an arrow down to three cards in a
row: rose 'phpredis' (Redis::OPT_SERIALIZER = SERIALIZER_IGBINARY, stacks with
OPT_COMPRESSION LZ4/ZSTD), emerald 'Laravel' (config/database.php redis.options
'serializer' => Redis::SERIALIZER_IGBINARY, one line), cyan 'php.ini'
(session.serialize_handler = igbinary, apc.serializer = igbinary). Below, two wide
boxes: emerald 'why (our measurements)': -49% redis memory per session, payloads
47-68% smaller, unserialize up to 2x faster, serialize rarely / unserialize often;
rose 'tradeoffs': binary PHP-only format, every reader needs the ext, flush when
switching serializers, tiny payloads serialize slightly slower."
