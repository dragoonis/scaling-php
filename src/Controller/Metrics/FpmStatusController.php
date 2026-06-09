<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Metrics;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FpmStatusController
{
    #[Route('/api/fpm-status', name: 'fpm_status', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        try {
            // Fetch FPM status from the FPM status endpoint
            $fpmUrl = $_ENV['FPM_STATUS_URL'] ?? 'http://localhost/fpm-status?full&json';

            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'ignore_errors' => true,
                ],
            ]);

            $response = @file_get_contents($fpmUrl, false, $context);

            if (false === $response) {
                return new JsonResponse([
                    'error' => 'Failed to fetch FPM status',
                    'message' => 'Could not connect to FPM status endpoint',
                ], 500);
            }

            $statusData = json_decode($response, true);

            if (\JSON_ERROR_NONE !== json_last_error()) {
                return new JsonResponse([
                    'error' => 'Failed to parse FPM status',
                    'message' => json_last_error_msg(),
                ], 500);
            }

            return new JsonResponse($statusData);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to fetch FPM status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/api/fpm-metrics', name: 'fpm_prometheus_metrics', methods: ['GET'])]
    public function prometheusMetrics(): Response
    {
        try {
            // Fetch FPM status
            $fpmUrl = $_ENV['FPM_STATUS_URL'] ?? 'http://localhost/fpm-status?full&json';

            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'ignore_errors' => true,
                ],
            ]);

            $response = @file_get_contents($fpmUrl, false, $context);

            if (false === $response) {
                throw new \Exception('Could not connect to FPM status endpoint');
            }

            $status = json_decode($response, true);

            if (\JSON_ERROR_NONE !== json_last_error()) {
                throw new \Exception('Failed to parse FPM status: '.json_last_error_msg());
            }

            $metrics = [];
            $pool = $status['pool'] ?? 'www';

            // Pool information
            if (isset($status['pool'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_pool_info',
                    'PHP-FPM pool information',
                    'gauge',
                    1,
                    ['pool' => $status['pool']]
                );
            }

            // Process manager type
            if (isset($status['process manager'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_process_manager_type',
                    'PHP-FPM process manager type',
                    'gauge',
                    'dynamic' === $status['process manager'] ? 1 : ('static' === $status['process manager'] ? 2 : 0),
                    ['type' => $status['process manager'], 'pool' => $pool]
                );
            }

            // Start time
            if (isset($status['start time'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_start_time',
                    'PHP-FPM start time (Unix timestamp)',
                    'gauge',
                    $status['start time'],
                    ['pool' => $pool]
                );
            }

            // Start since (uptime in seconds)
            if (isset($status['start since'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_up_seconds',
                    'PHP-FPM uptime in seconds',
                    'counter',
                    $status['start since'],
                    ['pool' => $pool]
                );
            }

            // Accepted connections
            if (isset($status['accepted conn'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_accepted_conn',
                    'Total number of accepted connections',
                    'counter',
                    $status['accepted conn'],
                    ['pool' => $pool]
                );
            }

            // Listen queue
            if (isset($status['listen queue'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_listen_queue',
                    'Number of requests in the listen queue',
                    'gauge',
                    $status['listen queue'],
                    ['pool' => $pool]
                );
            }

            // Max listen queue
            if (isset($status['max listen queue'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_max_listen_queue',
                    'Maximum number of requests in the listen queue',
                    'gauge',
                    $status['max listen queue'],
                    ['pool' => $pool]
                );
            }

            // Listen queue length
            if (isset($status['listen queue len'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_listen_queue_len',
                    'Size of the listen queue',
                    'gauge',
                    $status['listen queue len'],
                    ['pool' => $pool]
                );
            }

            // Idle processes
            if (isset($status['idle processes'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_idle_processes',
                    'Number of idle processes',
                    'gauge',
                    $status['idle processes'],
                    ['pool' => $pool]
                );
            }

            // Active processes
            if (isset($status['active processes'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_active_processes',
                    'Number of active processes',
                    'gauge',
                    $status['active processes'],
                    ['pool' => $pool]
                );
            }

            // Total processes
            if (isset($status['total processes'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_total_processes',
                    'Total number of processes',
                    'gauge',
                    $status['total processes'],
                    ['pool' => $pool]
                );
            }

            // Max active processes
            if (isset($status['max active processes'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_max_active_processes',
                    'Maximum number of active processes',
                    'gauge',
                    $status['max active processes'],
                    ['pool' => $pool]
                );
            }

            // Max children reached
            if (isset($status['max children reached'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_max_children_reached',
                    'Number of times max children limit was reached',
                    'counter',
                    $status['max children reached'],
                    ['pool' => $pool]
                );
            }

            // Slow requests
            if (isset($status['slow requests'])) {
                $metrics[] = $this->formatMetric(
                    'phpfpm_slow_requests',
                    'Number of slow requests',
                    'counter',
                    $status['slow requests'],
                    ['pool' => $pool]
                );
            }

            // Calculate aggregated metrics from process data
            if (isset($status['processes']) && \is_array($status['processes'])) {
                $durations = [];
                $cpuValues = [];
                $memoryValues = [];

                foreach ($status['processes'] as $process) {
                    if (isset($process['request duration']) && $process['request duration'] > 0) {
                        $durations[] = $process['request duration'];
                    }
                    if (isset($process['last request cpu'])) {
                        $cpuValues[] = $process['last request cpu'];
                    }
                    if (isset($process['last request memory'])) {
                        $memoryValues[] = $process['last request memory'];
                    }
                }

                // Average request duration (convert microseconds to milliseconds)
                if (!empty($durations)) {
                    $avgDuration = array_sum($durations) / \count($durations) / 1000;
                    $metrics[] = $this->formatMetric(
                        'phpfpm_request_duration_avg',
                        'Average request duration in milliseconds',
                        'gauge',
                        round($avgDuration, 2),
                        ['pool' => $pool]
                    );

                    // Max request duration
                    $maxDuration = max($durations) / 1000;
                    $metrics[] = $this->formatMetric(
                        'phpfpm_request_duration_max',
                        'Maximum request duration in milliseconds',
                        'gauge',
                        round($maxDuration, 2),
                        ['pool' => $pool]
                    );

                    // P95 request duration
                    sort($durations);
                    $p95Index = (int) ceil(\count($durations) * 0.95) - 1;
                    $p95Duration = $durations[$p95Index] / 1000;
                    $metrics[] = $this->formatMetric(
                        'phpfpm_request_duration_p95',
                        '95th percentile request duration in milliseconds',
                        'gauge',
                        round($p95Duration, 2),
                        ['pool' => $pool]
                    );

                    // P99 request duration
                    $p99Index = (int) ceil(\count($durations) * 0.99) - 1;
                    $p99Duration = $durations[$p99Index] / 1000;
                    $metrics[] = $this->formatMetric(
                        'phpfpm_request_duration_p99',
                        '99th percentile request duration in milliseconds',
                        'gauge',
                        round($p99Duration, 2),
                        ['pool' => $pool]
                    );
                }

                // Average CPU
                if (!empty($cpuValues)) {
                    $avgCpu = array_sum($cpuValues) / \count($cpuValues);
                    $metrics[] = $this->formatMetric(
                        'phpfpm_cpu_avg',
                        'Average CPU usage percentage',
                        'gauge',
                        round($avgCpu, 2),
                        ['pool' => $pool]
                    );
                }

                // Average memory
                if (!empty($memoryValues)) {
                    $avgMemory = array_sum($memoryValues) / \count($memoryValues);
                    $metrics[] = $this->formatMetric(
                        'phpfpm_memory_avg',
                        'Average memory usage in bytes',
                        'gauge',
                        (int) $avgMemory,
                        ['pool' => $pool]
                    );
                }
            }

            // Process details
            if (isset($status['processes']) && \is_array($status['processes'])) {
                foreach ($status['processes'] as $process) {
                    $pid = $process['pid'] ?? 'unknown';

                    // Process state
                    if (isset($process['state'])) {
                        $stateValue = match ($process['state']) {
                            'Idle' => 0,
                            'Running' => 1,
                            'Reading headers' => 2,
                            'Finishing' => 3,
                            default => -1,
                        };

                        $metrics[] = $this->formatMetric(
                            'phpfpm_process_state',
                            'PHP-FPM process state',
                            'gauge',
                            $stateValue,
                            ['pool' => $pool, 'pid' => $pid, 'state' => $process['state']]
                        );
                    }

                    // Process requests
                    if (isset($process['requests'])) {
                        $metrics[] = $this->formatMetric(
                            'phpfpm_process_requests',
                            'Total requests handled by process',
                            'counter',
                            $process['requests'],
                            ['pool' => $pool, 'pid' => $pid]
                        );
                    }

                    // Process request duration (in milliseconds for consistency)
                    if (isset($process['request duration'])) {
                        $metrics[] = $this->formatMetric(
                            'phpfpm_process_request_duration',
                            'Current request duration in milliseconds',
                            'gauge',
                            round($process['request duration'] / 1000, 2),
                            ['pool' => $pool, 'pid' => $pid]
                        );
                    }

                    // Process CPU usage
                    if (isset($process['last request cpu'])) {
                        $metrics[] = $this->formatMetric(
                            'phpfpm_process_cpu',
                            'CPU percentage of last request',
                            'gauge',
                            $process['last request cpu'],
                            ['pool' => $pool, 'pid' => $pid]
                        );
                    }

                    // Process memory usage
                    if (isset($process['last request memory'])) {
                        $metrics[] = $this->formatMetric(
                            'phpfpm_process_memory',
                            'Memory usage of last request in bytes',
                            'gauge',
                            $process['last request memory'],
                            ['pool' => $pool, 'pid' => $pid]
                        );
                    }
                }
            }

            $content = implode("\n", $metrics)."\n";

            return new Response($content, 200, ['Content-Type' => 'text/plain; version=0.0.4']);
        } catch (\Exception $e) {
            $content = "# Failed to fetch FPM status\n";
            $content .= '# Error: '.$e->getMessage()."\n";
            $content .= "# HELP phpfpm_up PHP-FPM status scrape success\n";
            $content .= "# TYPE phpfpm_up gauge\n";
            $content .= "phpfpm_up 0\n";

            return new Response($content, 200, ['Content-Type' => 'text/plain; version=0.0.4']);
        }
    }

    private function formatMetric(string $name, string $help, string $type, $value, array $labels = []): string
    {
        $lines = [];
        $lines[] = "# HELP {$name} {$help}";
        $lines[] = "# TYPE {$name} {$type}";

        if (empty($labels)) {
            $lines[] = "{$name} {$value}";
        } else {
            $labelPairs = [];
            foreach ($labels as $key => $val) {
                $labelPairs[] = "{$key}=\"{$val}\"";
            }
            $labelString = implode(',', $labelPairs);
            $lines[] = "{$name}{{$labelString}} {$value}";
        }

        return implode("\n", $lines);
    }
}
