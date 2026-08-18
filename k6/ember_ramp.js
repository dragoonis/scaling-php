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

export function setup() {
    console.log(`Ember wave targets: ${enabled.join(', ')}`);
    return {};
}

function hit(base, data) {
    const res = http.get(`${base}/`);
    check(res, { 'status is 200': (r) => r.status === 200 });
    sleep(0.02); // tiny pause; each VU still hammers ~40+ req/s
}

export function hitFpm(data) { hit(TARGETS.fpm, data); }
export function hitFranken(data) { hit(TARGETS.franken, data); }
export function hitWorker(data) { hit(TARGETS.worker, data); }
