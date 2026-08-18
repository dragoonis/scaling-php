import http from 'k6/http';
import { check } from 'k6';
import { htmlReport } from 'https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js';
import { textSummary } from "https://jslib.k6.io/k6-summary/0.0.1/index.js";

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

export let options = {
    vus: 100,
    iterations: 10000,
};

export function setup() {
    console.log(`Fetching product IDs from ${BASE_URL}/products...`);
    const response = http.get(BASE_URL + '/products');

    if (response.status !== 200) {
        console.error(`Failed to fetch products. Status: ${response.status}`);
        return { productIds: [] };
    }

    const data = response.json();

    if (!data.products || !Array.isArray(data.products)) {
        console.error('Invalid response format or no products found');
        return { productIds: [] };
    }

    const ids = data.products.map(p => p.id);
    console.log(`Loaded ${ids.length} product IDs for testing`);
    return { productIds: ids };
}

export default function (data) {
    if (!data.productIds || data.productIds.length === 0) {
        console.error('No product IDs available');
        return;
    }

    const index = (__VU - 1 + __ITER) % data.productIds.length;
    const productId = data.productIds[index];
    const url = BASE_URL + '/products/' + productId;

    const res = http.get(url);

    const isValid = check(res, {
        'status is 200': (r) => r.status === 200,
        'returns product data': (r) => {
            if (r.status !== 200) {
                if (__ITER === 0) {
                    console.log(`[ERROR] Non-200 status for product ${productId}: ${r.status}`);
                }
                return false;
            }
            try {
                const respData = r.json();
                // Response format: {"product": {"id": ..., "name": ..., ...}}
                const valid = respData && respData.product && respData.product.id !== undefined;
                if (!valid && __ITER === 0) {
                    console.log(`[ERROR] Invalid response for product ${productId}:`, JSON.stringify(respData).substring(0, 200));
                }
                return valid;
            } catch (e) {
                if (__ITER === 0) {
                    console.log(`[ERROR] JSON parse error for product ${productId}: ${e.message}, Body: ${r.body.substring(0, 200)}`);
                }
                return false;
            }
        },
    });
}

export function handleSummary(data) {
    const now = new Date().toISOString().replace(/[:]/g, '-');
    const port = BASE_URL.includes(':8081') ? '8081' : BASE_URL.includes(':8088') ? '8088' : '8080';
    const filename = `./k6/report-product-by-id-random-${port}-${now}.html`;

    const avgLatency = data.metrics.http_req_duration.values.avg;
    const maxLatency = data.metrics.http_req_duration.values.max;
    const p95Latency = data.metrics.http_req_duration.values['p(95)'];
    const reqRate = data.metrics.http_reqs.values.rate;
    const totalReqs = data.metrics.http_reqs.values.count;
    const dataReceived = data.metrics.data_received.values.count;
    const transferRate = data.metrics.data_received.values.rate;

    const duration = data.state.testRunDurationMs / 1000;

    console.log('\n========================================');
    console.log(`wrk-style benchmark @ ${BASE_URL}/products/{id}`);
    console.log('========================================');
    console.log(`  100 virtual users, 10000 iterations`);
    console.log(`  Cycling through product IDs from database`);
    console.log('');
    console.log('  Thread Stats   Avg      Max      p(95)');
    console.log(`    Latency      ${avgLatency.toFixed(2)}ms   ${maxLatency.toFixed(2)}ms   ${p95Latency.toFixed(2)}ms`);
    console.log(`    Req/Sec      ${(reqRate/4).toFixed(2)}     -        -`);
    console.log('');
    console.log(`  ${totalReqs} requests in ${duration.toFixed(1)}s, ${(dataReceived/1024/1024).toFixed(2)}MB read`);
    console.log(`Requests/sec:    ${reqRate.toFixed(2)}`);
    console.log(`Transfer/sec:    ${(transferRate/1024/1024).toFixed(2)}MB`);
    console.log('========================================\n');

    return {
        [filename]: htmlReport(data),
        stdout: textSummary(data, { indent: " ", enableColors: true }),
    };
}
