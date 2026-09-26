import http from 'k6/http';
import { check } from 'k6';
import { htmlReport } from 'https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js';
import { textSummary } from "https://jslib.k6.io/k6-summary/0.0.1/index.js";

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const PRODUCT_ID = __ENV.PRODUCT_ID || '105069';
const ENDPOINT = BASE_URL + '/products/' + PRODUCT_ID;

export let options = {
    vus: 100,
    duration: '90s',
};

export default function () {
    const res = http.get(ENDPOINT);
    check(res, {
        'status is 200': (r) => r.status === 200,
        'returns product data': (r) => {
            try {
                const data = r.json();
                return data.id !== undefined;
            } catch (e) {
                return false;
            }
        },
    });
}

export function handleSummary(data) {
    const now = new Date().toISOString().replace(/[:]/g, '-');
    const port = BASE_URL.includes(':8081') ? '8081' : BASE_URL.includes(':8088') ? '8088' : '8080';
    const filename = `./k6/report-product-by-id-${port}-${now}.html`;

    const avgLatency = data.metrics.http_req_duration.values.avg;
    const maxLatency = data.metrics.http_req_duration.values.max;
    const p95Latency = data.metrics.http_req_duration.values['p(95)'];
    const stddev = data.metrics.http_req_duration.values['p(90)'] - data.metrics.http_req_duration.values['p(50)'];
    const reqRate = data.metrics.http_reqs.values.rate;
    const totalReqs = data.metrics.http_reqs.values.count;
    const dataReceived = data.metrics.data_received.values.count;
    const transferRate = data.metrics.data_received.values.rate;

    console.log('\n========================================');
    console.log(`wrk-style benchmark @ ${ENDPOINT}`);
    console.log('========================================');
    console.log(`  100 virtual users for 90 seconds`);
    console.log('');
    console.log('  Thread Stats   Avg      Max      p(95)');
    console.log(`    Latency      ${avgLatency.toFixed(2)}ms   ${maxLatency.toFixed(2)}ms   ${p95Latency.toFixed(2)}ms`);
    console.log(`    Req/Sec      ${(reqRate/4).toFixed(2)}     -        -`);
    console.log('');
    console.log(`  ${totalReqs} requests in 90s, ${(dataReceived/1024/1024).toFixed(2)}MB read`);
    console.log(`Requests/sec:    ${reqRate.toFixed(2)}`);
    console.log(`Transfer/sec:    ${(transferRate/1024/1024).toFixed(2)}MB`);
    console.log('========================================\n');

    return {
        [filename]: htmlReport(data),
        stdout: textSummary(data, { indent: " ", enableColors: true }),
    };
}
