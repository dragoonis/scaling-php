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

**queue-littles-law** - "Draw a big-formula slide, no SVG diagram needed: cyan label
'QUEUE WORKER SIZING (LITTLE'S LAW)', grey subtitle 'declare the SLA: a job waits at
most 60s before pickup, and derive the workers' (SLA quote in amber), centered giant
formula 'workers = pending jobs / SLA seconds / jobs/s per worker' (operands emerald,
operators grey), small grey note '(needed throughput to drain the backlog in time,
divided by what one worker can do)'. Two example cards side by side: emerald 'STEADY
STATE: 100 pending, 60s SLA, 10 jobs/s each, (100/60)/10 = 1 worker'; rose 'SPIKE,
5s OF SLA LEFT: 500 pending, oldest job 55s old, 8 jobs/s each, (500/5)/8 = 13 x
safety margin = 16 workers'. Footer: supervisor numprocs=10 is a guess, this
recomputes every 5s, capped by host RAM/CPU."

**capacity-math** - "Draw a two-column unifier slide, no SVG diagram needed: big white
title 'capacity is math, not guessing', grey subtitle 'the two pools every Laravel app
runs, sized by the same idea: measure one unit, derive the count'. Left card (cyan top
border) 'WEB REQUESTS / PHP-FPM': formula max_children = RAM for PHP / avg process
size, example 16 GB box, 12 GB for PHP x 0.9 / 255 MB = ~42 children, unit to measure:
MB per worker under load. Right card (emerald top border) 'QUEUE JOBS / WORKERS':
formula workers = (pending / SLA seconds) / jobs/s per worker, example 100 pending /
60s SLA / 10 jobs/s = 1 worker, unit to measure: jobs/s one worker sustains. Below
both, one amber pill: 'too low = queues while hardware idles, too high = swap, OOM,
thrash'. Footer: both formulas re-run when reality changes, alert when observed
drifts from derived."

**scaling-toolbox** - "Draw a three-card closing slide, no SVG diagram needed: big
white title 'you do not have to hand-roll this', grey subtitle 'everything in this
talk, packaged: one Danish ex-Laravel engineer ships the whole stack as open source'.
Three cards, each with a rose 'THE MANUAL WAY' line, a down arrow, then the package
name and bullets: amber 'cboxdk/fpm-exporter' (manual way: status page + a PHP metrics
route + wiring Prometheus yourself; bullets: Go sidecar speaking FastCGI to the FPM
socket, pool metrics + per-pool OPcache with zero app code, ships Grafana dashboards
and alert rules, ~32 MB RAM, autodiscovers pools); cyan 'cboxdk/init' (manual way:
the max_children formula computed by hand per box; bullets: PID 1 for PHP containers,
reads cgroup RAM/CPU limits at boot, auto-computes pm.max_children from app profiles,
health checks + Prometheus built in); emerald 'cboxdk/laravel-queue-autoscale'
(manual way: supervisor numprocs=10 picked by vibes; bullets: declare an SLA like
picked up within 30s, worker count derived via Little's Law every 5s, backlog drain +
failure fuse, capped by measured host RAM/CPU). Credit line: cbox.dk, Sylvester
Damgaard, worked on the queue manager at Laravel until 2025. Footer: this demo repo
teaches the mechanics, these tools run them in production."

**queue-autoscale-demo** - "Draw a measured-results slide, no SVG diagram needed: big
white title '3,000 jobs hit the queue.' with emerald continuation 'nobody touched a
config.', grey subtitle 'measured on this repo: cboxdk/laravel-queue-autoscale, SLA
picked up within 10s, jobs of 100ms, worker cap 16'. A six-bar column chart of worker
count over time (bar height = workers, label above = workers, small grey label =
pending jobs): t=0 1 worker 3,000 pending; 5s 6 workers 2,910; 10s 10 workers 2,619;
15s 14 workers 2,209; 25s 16 workers (amber, capped) 956; 35s 16 workers 0 pending.
Below, four fact cards: emerald '35s burst fully drained'; white '3,000 / 3,000 jobs
done, zero lost'; amber '30 -> 16, Little's Law asked for 30, resource cap won';
white 'SIGTERM: in-flight jobs finish before workers die'. Footer: workers =
(pending / SLA seconds) / jobs/s per worker, re-evaluated every 5s, supervisor
numprocs was a guess."

**queue-failure-fuse** - "Draw a big-contrast slide, no SVG diagram needed: big white
title 'an outage is not load', grey subtitle 'upstream died, every job fails, retries
refill the backlog: the math sees a traffic spike. measured on this repo, 2,000
poisoned jobs'. Two huge number cards with an arrow between: rose '999.5, workers the
backlog math demanded' then emerald '1, workers the fuse allowed'. Below, three story
cards: rose 'TRIP: failure rate hit 100% over 28 jobs (threshold 50% over 20 in a 60s
window), scaling up would just hammer the dead upstream harder'; amber 'HOLD: queue
pinned at workers.min = 1, the one worker keeps probing so recovery is noticed the
moment the upstream heals'; emerald 'HEAL: failures age out of the window, fuse closes
at 0% over 387 jobs, normal scaling resumes 6 then 9 workers, no restart, no human'.
Footer: cboxdk/laravel-queue-autoscale v4, try it: make queue-burst COUNT=2000 FAIL=1."

**horizon-vs-autoscale** - "Draw a respectful two-column comparison slide, no SVG
diagram needed: big white title 'horizon balances. the autoscaler sizes.', grey
subtitle 'they answer different questions, and they are not enemies'. Left card (rose
top border) 'Laravel Horizon', grey question 'how do I split my workers across
queues?', bullets: balances a worker pool across queues by load; the pool size
itself: maxProcesses, still a number you pick; beautiful dashboard, metrics,
failed-job UI; redis only. Right card (emerald top border) 'SLA autoscaling', grey
question 'how many workers should exist at all?', bullets: derives the count from an
SLA + Little's Law every 5s; capped by measured host CPU/RAM, not a guess; failure
fuse: an outage is not load; any driver: redis, database, SQS. Amber pill: 'the gap
in both worlds was never balancing, it was that someone still picks the number. stop
picking the number.' Footer: measured in this repo, 3,000-job burst sized itself
1 to 16 workers and back, zero config edits, zero lost jobs."

**monday-checklist** - "Draw a five-row checklist slide, no SVG diagram needed: big
white title 'five numbers to check monday morning', grey subtitle 'if you look at
nothing else, look at these, each one is a user-facing problem before it is an
alert'. Five horizontal rows, each with a colored numbered circle, a metric name +
tiny grey source, and a healthy/bad column (healthy value emerald, consequence
rose): 1 cyan 'max children reached' (fpm status page / exporter counter), healthy
0, rising = pool ceiling hit, requests queuing while CPU may be idle; 2 emerald
'listen queue' (fpm status page, the queue PHP never sees), healthy 0, any
sustained value = users already waiting before PHP runs; 3 violet 'opcache hit
rate + wasted %' (opcache_get_status() / our /metrics route), healthy >99% hit
<5% wasted, full + wasted = restart storms, compile tax per request; 4 amber
'memory per worker RSS' (ps / htop measured warm under load), the unit behind
max_children = RAM / this, growing = leak, recycling saves you; 5 rose 'oldest
job age' (queue metrics, not queue depth), healthy within your SLA, depth lies,
10k fast jobs is fine, 10 slow ones may not be. Footer: everything on this list
is one command in the demo repo, github.com/dragoonis/scaling-php."
