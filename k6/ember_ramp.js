import http from 'k6/http';
import { check, sleep } from 'k6';

/*
 * 🔥 Ember load wave.
 *
 * One long-running scenario that ramps virtual users UP, then DOWN, then UP
 * higher, then drains away - a real-ish wave of traffic. It drives all three
 * runtimes at once (FPM, FrankenPHP classic, FrankenPHP worker) so you can watch
 * them react side by side in `make ember`.
 *
 *   make ember-load                          # all three runtimes
 *   EMBER_TARGETS=worker make ember-load     # just the worker
 *   EMBER_TARGETS=fpm,worker make ember-load # pick a couple
 *
 * Run this in one terminal and `make ember` in another.
 */

const TARGETS = {
    fpm: 'http://localhost:8088',
    franken: 'http://localhost:8080',
    worker: 'http://localhost:8081',
};

// The wave. Same shape for every runtime so the comparison is fair.
// The app MUST run in prod mode (APP_ENV=prod) - in dev mode FrankenPHP's threads deadlock
// rebuilding the cache. With prod, classic happily handles a few hundred RPS. We keep a low
// VU count with sharp up/down bursts so the Ember graphs spike clearly without dipping to 0.
const STAGES = [
    { duration: '8s',  target: 12 },  // SPIKE
    { duration: '6s',  target: 2 },   // crash
    { duration: '8s',  target: 16 },  // BIGGER SPIKE
    { duration: '6s',  target: 3 },   // crash
    { duration: '8s',  target: 10 },  // spike
    { duration: '6s',  target: 2 },   // crash
    { duration: '8s',  target: 18 },  // BIGGEST SPIKE
    { duration: '8s',  target: 0 },   // drain
];

const enabled = (__ENV.EMBER_TARGETS || 'fpm,franken,worker')
    .split(',')
    .map((s) => s.trim())
    .filter((s) => TARGETS[s]);

const execFor = { fpm: 'hitFpm', franken: 'hitFranken', worker: 'hitWorker' };

const scenarios = {};
for (const name of enabled) {
    scenarios[name] = {
        executor: 'ramping-vus',
        exec: execFor[name],
        startVUs: 0,
        stages: STAGES,
        tags: { target: name },
    };
}

export const options = {
    insecureSkipTLSVerify: true,
    scenarios,
};

// Load a pool of real product IDs once, up front, so every request hits a valid
// row. We always pull this list from FPM (8088) - it's the most resilient runtime,
// so warm-up never stalls even when we're stress-testing the worker.
export function setup() {
    const base = TARGETS.fpm;
    console.log(`Ember wave warming up - fetching product IDs from ${base}/en/products/db`);

    const res = http.get(`${base}/en/products/db`);
    if (res.status !== 200) {
        console.error(`Could not load product IDs (status ${res.status}). Did you run 'make setup'?`);
        return { ids: [] };
    }

    const data = res.json();
    const ids = (data.products || []).map((p) => p.id);
    console.log(`Loaded ${ids.length} product IDs. Targets: ${enabled.join(', ')}`);
    return { ids };
}

function hit(base, data) {
    if (!data.ids || data.ids.length === 0) {
        return;
    }
    const id = data.ids[(__VU + __ITER) % data.ids.length];
    const res = http.get(`${base}/en/products/db/${id}`);
    check(res, { 'status is 200': (r) => r.status === 200 });
    sleep(0.02); // tiny pause; each VU still hammers ~40+ req/s
}

export function hitFpm(data) { hit(TARGETS.fpm, data); }
export function hitFranken(data) { hit(TARGETS.franken, data); }
export function hitWorker(data) { hit(TARGETS.worker, data); }
