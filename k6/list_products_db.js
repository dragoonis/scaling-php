import http from 'k6/http';
import { check, sleep } from 'k6';
import { htmlReport } from 'https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js';
import { textSummary } from "https://jslib.k6.io/k6-summary/0.0.1/index.js";

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8088';
const ENDPOINT = BASE_URL + '/products';

export let options = {
    insecureSkipTLSVerify: true,
    vus: 100,
    iterations: 500,
};

export default function () {
    const res = http.get(ENDPOINT);
    check(res, {
        'status is 200': (r) => r.status === 200,
        'returns products array': (r) => {
            try {
                const data = r.json();
                return Array.isArray(data.products) && data.products.length > 0;
            } catch (e) {
                return false;
            }
        },
    });
    sleep(1);
}

export function handleSummary(data) {
    const now = new Date().toISOString().replace(/[:]/g, '-');
    const filename = `./k6/report-products-db-${now}.html`;
    return {
        [filename]: htmlReport(data),
        stdout: textSummary(data, { indent: " ", enableColors: true }),
    };
} 
