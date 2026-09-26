#!/usr/bin/env bash

pass=0; fail=0
ok()   { echo "  ✅ $1"; pass=$((pass+1)); }
bad()  { echo "  ❌ $1"; fail=$((fail+1)); }

echo "🛫 scaling-php preflight"
echo

code() { curl -s -o /dev/null -w '%{http_code}' --max-time 8 "$1" 2>/dev/null; }

[ "$(code http://localhost:8088/products/42)" = "200" ] && ok "FPM :8088 serves /products/42" || bad "FPM :8088 not answering (make up && make setup)"
[ "$(code http://localhost:8080/products/42)" = "200" ] && ok "FrankenPHP classic :8080 serves" || bad "classic :8080 not answering (make up-franken)"
[ "$(code http://localhost:8081/products/42)" = "200" ] && ok "Octane worker :8081 serves" || bad "worker :8081 not answering (make up-worker)"

runtime=$(curl -s --max-time 8 http://localhost:8081/runtime 2>/dev/null)
if echo "$runtime" | grep -q '"octane_worker_mode":true'; then
    ok "worker mode is ON (octane)"
else
    bad "worker NOT in octane mode (make rebuild)"
fi

n1=$(curl -s --max-time 8 http://localhost:8088/products 2>/dev/null | grep -o '"id"' | wc -l | tr -d ' ')
[ "${n1:-0}" -ge 10000 ] && ok "demo data seeded ($n1 products)" || bad "products missing (make setup)"

[ "$(code http://localhost:2019/metrics)" = "200" ] && ok "classic admin metrics :2019" || bad "classic admin :2019 down"
[ "$(code http://localhost:2020/metrics)" = "200" ] && ok "worker admin metrics :2020" || bad "worker admin :2020 down"

targets=$(curl -s --max-time 8 http://localhost:9090/api/v1/targets 2>/dev/null | grep -o '"health":"up"' | wc -l | tr -d ' ')
[ "${targets:-0}" -ge 3 ] && ok "prometheus: $targets targets up" || bad "prometheus targets down (make up-grafana && make up-exporter)"

[ "$(code http://localhost:3000/api/health)" = "200" ] && ok "grafana :3000 up" || bad "grafana down (make up-grafana)"

if command -v ember >/dev/null 2>&1; then
    if ember --help 2>/dev/null | grep -q -- --stdin-logs; then
        ok "ember $(ember --version 2>/dev/null | head -1 | awk '{print $2}') with --stdin-logs"
    else
        bad "ember too old, freezes the server (make ember-install)"
    fi
else
    bad "ember not installed (make ember-install)"
fi

command -v k6 >/dev/null 2>&1 && ok "k6 installed" || bad "k6 missing (brew install k6)"

echo
if [ "$fail" -eq 0 ]; then
    echo "🎉 all $pass checks passed - go give the talk"
else
    echo "⚠️  $fail failed, $pass passed - fix the ❌ lines above"
    exit 1
fi
