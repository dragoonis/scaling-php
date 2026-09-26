# Disposable Workers - the demo

Long-running PHP leaks memory eventually, so both runtimes recycle workers on purpose.
A leak cannot grow forever if the worker does not live forever. Recycling is graceful:
the request in flight finishes, then the worker is replaced. Slide:
`docs/images/disposable-workers.png`.

## FPM: watch children die and respawn (60 seconds)

Terminal 1 - the process list:

```bash
make fpm-htop
```

Terminal 2 - swap in the demo pool config (pm.max_requests=50 instead of 1000, so
recycling happens constantly instead of once per thousand requests), then send load:

```bash
make fpm-recycle-demo
EMBER_TARGETS=fpm make ember-load
```

In terminal 1 the child PIDs churn: every worker retires after 50 requests and the
master forks a fresh one. On this machine one 15 second burst replaced every child
(PIDs 11-15 before, PIDs 209-245 after, ~230 forks) with zero failed requests.

Afterwards, back to the real config:

```bash
make fpm-recycle-restore
```

## Octane: same principle, one flag

The worker runs with `--max-requests=250` (docker-compose.yml). Under load every
worker restarts after 250 requests. Measured: 37,779 requests in 15s means each of
the 16 workers restarted about 9 times, zero failures, all 16 ready afterwards.

The on-stage stunt - recycle every worker manually in the middle of a load wave:

```bash
make ember-load        # terminal 1 - the wave
make octane-reload     # terminal 2 - mid-wave
```

k6 finishes with 0 failed requests. That is also your zero-downtime deploy story:
octane:reload is what you run after a code change.

## The one-liner for the slide

FPM `pm.max_requests = 1000` and Octane `--max-requests=250` are the same idea:
workers are cattle, not pets.
