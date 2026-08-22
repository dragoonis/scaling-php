> ⚠️ **Legacy doc:** written for the Symfony version of this demo. The commands and endpoints may not match the Laravel branch yet.

# Web Server Monitoring Dashboards Guide

A comprehensive guide to understanding and using two Grafana dashboards for full-stack web server monitoring: **FrankenPHP + Caddy** optimized for Symfony applications and **PHP-FPM Performance** monitoring.

## Overview

This monitoring suite provides complete visibility into your web application stack through two specialized dashboards. Each dashboard focuses on different aspects of your infrastructure, working together to give you end-to-end insights into web server and PHP application performance.

---

## 🌐 FrankenPHP + Caddy Dashboard (Symfony Optimized)

**Dashboard Focus:** FrankenPHP/Caddy monitoring optimized for Symfony applications with emphasis on worker health, restarts, and performance.

### Dashboard Structure

The FrankenPHP + Caddy dashboard is organized into seven collapsible sections:

#### 1. 🚨 Critical Health Indicators

Instant health indicators requiring immediate attention:

- **🚨 Worker Restart Rate** - Rate of worker restarts per second (🟢 0, 🟡 <0.1, 🔴 >0.1)
  - HIGH VALUES = crashes/memory issues
- **Process Memory** - Resident memory usage (🟢 <512MB, 🟡 512MB-1GB, 🔴 >1GB)
  - Watch for growth indicating leaks
- **Thread Utilization %** - Percentage of PHP threads busy processing requests (🟢 <70%, 🟡 70-90%, 🔴 >90%)
- **Request Queue Depth** - Requests waiting for available worker (🟢 0, 🟡 1-5, 🔴 >10)

#### 2. ⚡ Worker Performance & Traffic

Real-time performance and traffic metrics:

- **Requests per Second** - Request rate across all workers (🟢 stable, 🟡 spikes, 🔴 sustained high)
- **Busy PHP Workers** - Workers currently processing requests (🟢 <8, 🟡 8-12, 🔴 >12)
- **Average Response Time** - Mean response time for all requests (🟢 <100ms, 🟡 100-500ms, 🔴 >500ms)
- **Total Worker Restarts** - Cumulative worker restarts since start (🟢 0, 🟡 1-5, 🔴 >5)

#### 3. 📊 Response Metrics & Errors

Response time and error rate indicators:

- **P50 Response Time** - Median response time (🟢 <50ms, 🟡 50-200ms, 🔴 >200ms)
- **P95 Response Time** - 95th percentile response time (🟢 <100ms, 🟡 100-500ms, 🔴 >500ms)
- **4xx Response %** - Percentage of 4xx client errors (🟢 <1%, 🟡 1-5%, 🔴 >5%)
- **5xx Response %** - Percentage of 5xx server errors (🟢 0%, 🟡 <1%, 🔴 >1%)

#### 4. 📈 Trends Over Time

Historical trends for pattern analysis:

- **Worker Restart Rate Over Time** - Worker restart rate trends (investigate spikes)
- **Memory Usage Trend** - Process memory usage (look for continuous growth)
- **Response Time** - Average response time showing system speed trends
- **PHP Thread Utilization** - PHP thread utilization patterns over time

#### 5. 🚀 OPcache Metrics

PHP OPcache performance and health:

**Cache Health Indicators:**
- **OPcache Hit Ratio** - Cache efficiency percentage (🔴 <80%, 🟡 80-95%, 🟢 >95%)
  - Values above 95% indicate good cache performance
- **OPcache Memory Usage** - Memory utilization percentage (🟢 <70%, 🟡 70-85%, 🔴 >85%)
  - High usage may indicate need for more memory allocation
- **Script Cache Usage** - Cached scripts vs maximum capacity (🟢 <80%, 🟡 80-90%, 🔴 >90%)
  - Shows how close you are to the script limit
- **OPcache Status** - Cache enabled/disabled indicator (🔴 Disabled, 🟢 Enabled)
  - Shows if cache is enabled, full, or if restarts are pending

**Cache Performance:**
- **Cache Hit/Miss Rate** - Time series showing hit vs miss trends (🟢 Hits, 🔴 Misses)
  - Consistent high hit rates indicate good cache performance
- **Memory Usage Breakdown** - Stacked chart showing used, free, and wasted memory (🔵 Used, 🟢 Free, 🟠 Wasted)
- **Interned Strings** - String deduplication efficiency metrics
  - Shows efficiency of string deduplication in PHP
- **JIT Status** - Just-In-Time compilation status table
  - Shows if JIT is enabled and buffer usage
- **Cache Restarts** - Stacked time series showing restart types:
  - Out-of-memory restarts (OOM)
  - Hash table full restarts
  - Manual restarts

#### 6. 🔧 Worker & PHP Details

Detailed worker and PHP thread management:

**Thread Management:**
- **Busy PHP Threads** - Number of threads currently processing requests (🟢 <10, 🟡 10-15, 🔴 >15)
- **Total PHP Threads** - Total number of PHP threads available

**Worker Management:**
- **Ready PHP Workers** - Running workers that successfully called frankenphp_handle_request
- **Worker Requests Total** - Total requests processed by PHP workers
- **Avg Worker Request Time** - Average request processing time per worker (🟢 <0.1s, 🟡 0.1-0.5s, 🔴 >0.5s)
- **Requests in Flight** - Number of requests currently being handled

**Time Series Visualizations:**
- **PHP Worker Status & Queue** - Worker utilization and queue depth over time
- **Worker Request Rate** - Worker request rate trends

#### 7. 📡 HTTP Request Details

Comprehensive HTTP request analysis:

**Request Distribution:**
- **HTTP Status Breakdown** - Pie chart showing 2xx, 3xx, 4xx, 5xx distribution
- **HTTP Method Breakdown** - Pie chart showing GET, POST, PUT, DELETE distribution

**Performance Analysis:**
- **Request Duration Percentiles** - P50, P75, P90, P95, P99 response times over time
- **Data Transfer Rate** - Request vs response data flow visualization
- **Rate of 4xx and 5xx Responses** - Error trends by status code

### Key Dashboard Variables
- **Datasource** - Prometheus instance selector
- **Job** - FrankenPHP/Caddy job name filter
- **Instance** - Specific server instance selector
- **Interval** - Metrics aggregation period (30s, 1m, 5m, etc.)

---

## 🐘 PHP-FPM Performance Dashboard

### Dashboard Structure

The PHP-FPM dashboard is organized into two main collapsible sections:

#### 1. PHP-FPM Metrics Section

**Critical Health Indicators (Top Row):**
- **Max Children Reached** - Times the process limit was hit (🟢 0, 🟡 ≥1, 🔴 ≥5)
- **Scrape Failures** - Monitoring system reliability (🟢 0, 🟡 ≥1, 🔴 ≥5)
- **Slow Requests** - Requests exceeding configured threshold (🟢 0, 🟡 ≥1, 🔴 ≥10)
- **Process Utilization** - Percentage of active processes (🟢 <70%, 🟡 70-90%, 🔴 >90%)
- **Uptime** - Pool runtime since last restart
- **Queue Depth** - Pending connections waiting for processes (🟢 0, 🟡 ≥1, 🔴 ≥10)

**Performance Trends:**
- **Request Rate** - Time series showing requests per second using `rate(phpfpm_accepted_connections[5m])`
- **Request Duration** - Dual-line chart showing average and maximum processing times
- **Process States** - Stacked area chart showing active vs idle process distribution
- **Process Details** - Color-coded table listing individual process states (🟢 Idle, 🔵 Running)

**Resource Monitoring:**
- **Memory Usage per Request** - Average and maximum memory consumption trends

#### 2. OPcache Metrics Section

**Cache Health Indicators (Top Row):**
- **OPcache Hit Ratio** - Cache efficiency percentage (🔴 <80%, 🟡 80-95%, 🟢 >95%)
- **OPcache Memory Usage** - Memory utilization percentage (🟢 <70%, 🟡 70-85%, 🔴 >85%)
- **Script Cache Usage** - Cached scripts vs maximum capacity (🟢 <80%, 🟡 80-90%, 🔴 >90%)
- **OPcache Status** - Enabled/disabled indicator (🔴 Disabled, 🟢 Enabled)

**Cache Performance:**
- **Cache Hit/Miss Rate** - Time series showing hit vs miss trends (🟢 Hits, 🔴 Misses)
- **Memory Usage Breakdown** - Stacked chart showing used, free, and wasted memory (🔵 Used, 🟢 Free, 🟠 Wasted)
- **Interned Strings** - String deduplication efficiency metrics
- **JIT Status** - Just-In-Time compilation details table with color-coded status

**Cache Management:**
- **Cache Restarts** - Stacked time series showing:
  - Out-of-memory restarts (OOM)
  - Hash table full restarts
  - Manual restarts

### Key Dashboard Variables
- **Datasource** - Prometheus instance selector
- **Pool** - PHP-FPM pool selector (dynamically populated)

---

## 📊 Understanding the Metrics

### FrankenPHP Worker Restart Monitoring

Worker restarts are a **critical indicator** of application health:

**Normal Restart Patterns:**
- Zero restarts during stable operation
- Occasional restarts after deployments (expected)
- Single restart after configuration changes

**Problem Indicators:**
- **Frequent restarts** (>0.1/sec) = memory leaks or crashes
- **Gradual memory growth** + restarts = memory leak
- **Sudden restart spikes** = code bugs or resource exhaustion
- **Continuous restart loop** = critical application error

**Action Items by Restart Rate:**
- **0 restarts/sec** - Healthy operation ✅
- **<0.05 restarts/sec** - Monitor, investigate patterns ⚠️
- **0.05-0.1 restarts/sec** - Review logs, check memory usage 🔍
- **>0.1 restarts/sec** - Critical issue, immediate investigation required 🚨

### Performance Indicators

**Green Indicators (Good Performance):**
- Low error rates (<1%)
- Fast response times (<100ms P95)
- High cache hit ratios (>95%)
- Low thread/process utilization (<70%)
- Empty request queues
- Zero or minimal worker restarts
- Stable memory usage

**Yellow Indicators (Warning):**
- Moderate error rates (1-5%)
- Elevated response times (100-500ms P95)
- Medium cache hit ratios (80-95%)
- Medium thread/process utilization (70-90%)
- Small request queues (1-5)
- Occasional worker restarts (<0.05/sec)
- Slowly growing memory usage

**Red Indicators (Critical Issues):**
- High error rates (>5%)
- Slow response times (>500ms P95)
- Low cache hit ratios (<80%)
- High thread/process utilization (>90%)
- Large request queues (>10)
- Frequent worker restarts (>0.1/sec)
- Rapidly growing or high memory usage

### Thread vs Worker vs Process

**FrankenPHP (Threads & Workers):**
- **Threads** - Lightweight concurrent execution units
- **Workers** - Long-lived PHP processes that handle multiple requests
- **Benefits** - Lower memory, faster request handling, shared state

**PHP-FPM (Processes):**
- **Processes** - Isolated PHP processes, one per request
- **Benefits** - Complete isolation, proven stability, easier debugging

---

## 🔍 Troubleshooting Guide

### High Worker Restart Rate

**Symptoms:**
- Worker Restart Rate > 0.1/sec
- Memory usage growing steadily
- Total Worker Restarts increasing rapidly

**Investigation Steps:**
1. Check application logs for PHP errors or warnings
2. Review memory usage trends - look for leaks
3. Examine recent code deployments or changes
4. Check for problematic routes or controllers
5. Review worker configuration (max_requests, memory limits)

**Common Causes:**
- Memory leaks in application code
- Insufficient memory limits
- Third-party package issues
- Circular references in objects
- Unclosed resources (DB connections, file handles)

### Low OPcache Hit Ratio

**Symptoms:**
- OPcache Hit Ratio < 95%
- Frequent cache misses
- Slow response times

**Investigation Steps:**
1. Check OPcache memory allocation
2. Review script cache capacity
3. Look for frequent cache restarts
4. Verify file modification patterns

**Solutions:**
- Increase `opcache.memory_consumption`
- Increase `opcache.max_accelerated_files`
- Disable file modification checks in production
- Review deployment process to avoid cache clears

### High Response Times

**Symptoms:**
- P95 Response Time > 500ms
- Slow average response times
- Request queue building up

**Investigation Steps:**
1. Check database query performance
2. Review external API calls
3. Examine CPU and memory usage
4. Look for N+1 query problems
5. Check for blocking operations

**Solutions:**
- Optimize slow database queries
- Add database indexes
- Implement caching strategies
- Use async processing for heavy tasks
- Scale worker/process count

### Thread/Process Saturation

**Symptoms:**
- Thread/Process Utilization > 90%
- Growing request queue
- Increased response times

**Solutions:**
- Increase worker/process count
- Optimize slow requests
- Implement request queueing
- Scale horizontally (more servers)
- Review and optimize slow endpoints

---

## 📈 Best Practices

### Monitoring Strategy

1. **Set up alerts** for critical metrics:
   - Worker restart rate > 0.1/sec
   - 5xx error rate > 1%
   - OPcache hit ratio < 90%
   - Thread/process utilization > 85%
   - Request queue depth > 5

2. **Regular health checks**:
   - Review dashboards daily
   - Check worker restart patterns
   - Monitor memory trends
   - Validate cache performance

3. **Performance baselines**:
   - Document normal operating ranges
   - Track P50/P95 response times
   - Monitor request rates
   - Establish memory usage baselines

### Optimization Workflow

1. **Identify bottlenecks** using dashboards
2. **Investigate** specific metrics and trends
3. **Test solutions** in staging environment
4. **Deploy changes** with monitoring
5. **Validate improvements** with metrics
6. **Document findings** and solutions

---

## 🎯 Dashboard Quick Reference

### FrankenPHP + Caddy Dashboard

**Primary Focus:** Worker health and restart monitoring
**Key Metrics:**
- Worker Restart Rate (target: 0)
- Thread Utilization (target: <70%)
- Response Times (P95 target: <100ms)
- OPcache Hit Ratio (target: >95%)

**Use Cases:**
- Debugging worker crashes
- Identifying memory leaks
- Optimizing thread configuration
- Monitoring deployment impact

### PHP-FPM Dashboard

**Primary Focus:** Process-based PHP execution
**Key Metrics:**
- Process Utilization (target: <70%)
- Request Duration (target: <100ms avg)
- Max Children Reached (target: 0)
- OPcache Hit Ratio (target: >95%)

**Use Cases:**
- Traditional PHP-FPM monitoring
- Process pool optimization
- Resource allocation tuning
- Slow request identification

---

## 📚 Additional Resources

- [FrankenPHP Documentation](https://frankenphp.dev/)
- [Caddy Documentation](https://caddyserver.com/docs/)
- [PHP-FPM Configuration](https://www.php.net/manual/en/install.fpm.configuration.php)
- [OPcache Documentation](https://www.php.net/manual/en/book.opcache.php)
- [Grafana Documentation](https://grafana.com/docs/)
- [Prometheus Metrics](https://prometheus.io/docs/concepts/metric_types/)
