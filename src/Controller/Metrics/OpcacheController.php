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

class OpcacheController
{
    #[Route('/opcache-stats', name: 'opcache_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        if (\function_exists('opcache_get_status')) {
            $status = opcache_get_status(false);

            return new JsonResponse($status);
        }

        return new JsonResponse(['error' => 'OPcache is not enabled or available.'], 500);
    }

    #[Route('/metrics', name: 'prometheus_metrics', methods: ['GET'])]
    public function prometheusMetrics(): Response
    {
        if (!\function_exists('opcache_get_status')) {
            $content = "# OPcache is not enabled or available\n";

            return new Response($content, 200, ['Content-Type' => 'text/plain']);
        }

        $status = opcache_get_status(false);
        $config = opcache_get_configuration();

        // Check if OPcache is actually enabled and working
        if (false === $status || false === $config) {
            $content = "# OPcache is not enabled or not working properly\n";
            $content .= "# HELP opcache_enabled OPcache enabled status\n";
            $content .= "# TYPE opcache_enabled gauge\n";
            $content .= "opcache_enabled 0\n";

            return new Response($content, 200, ['Content-Type' => 'text/plain; version=0.0.4']);
        }

        $metrics = [];

        // OPcache enabled status
        $metrics[] = $this->formatMetric(
            'opcache_enabled',
            'OPcache enabled status',
            'gauge',
            isset($status['opcache_enabled']) && $status['opcache_enabled'] ? 1 : 0
        );

        // Cache full status
        $metrics[] = $this->formatMetric(
            'opcache_cache_full',
            'OPcache cache full status',
            'gauge',
            isset($status['cache_full']) && $status['cache_full'] ? 1 : 0
        );

        // Restart pending
        $metrics[] = $this->formatMetric(
            'opcache_restart_pending',
            'OPcache restart pending status',
            'gauge',
            isset($status['restart_pending']) && $status['restart_pending'] ? 1 : 0
        );

        // Restart in progress
        $metrics[] = $this->formatMetric(
            'opcache_restart_in_progress',
            'OPcache restart in progress status',
            'gauge',
            isset($status['restart_in_progress']) && $status['restart_in_progress'] ? 1 : 0
        );

        // Memory usage metrics
        if (isset($status['memory_usage']) && \is_array($status['memory_usage'])) {
            $memory = $status['memory_usage'];

            if (isset($memory['used_memory'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_memory_used_bytes',
                    'OPcache memory used in bytes',
                    'gauge',
                    $memory['used_memory']
                );
            }

            if (isset($memory['free_memory'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_memory_free_bytes',
                    'OPcache memory free in bytes',
                    'gauge',
                    $memory['free_memory']
                );
            }

            if (isset($memory['wasted_memory'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_memory_wasted_bytes',
                    'OPcache memory wasted in bytes',
                    'gauge',
                    $memory['wasted_memory']
                );
            }

            if (isset($memory['current_wasted_percentage'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_memory_usage_ratio',
                    'OPcache memory usage ratio',
                    'gauge',
                    $memory['current_wasted_percentage'] / 100
                );
            }
        }

        // Interned strings metrics
        if (isset($status['interned_strings_usage']) && \is_array($status['interned_strings_usage'])) {
            $strings = $status['interned_strings_usage'];

            if (isset($strings['buffer_size'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_interned_strings_buffer_size_bytes',
                    'OPcache interned strings buffer size',
                    'gauge',
                    $strings['buffer_size']
                );
            }

            if (isset($strings['used_memory'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_interned_strings_used_memory_bytes',
                    'OPcache interned strings used memory',
                    'gauge',
                    $strings['used_memory']
                );
            }

            if (isset($strings['free_memory'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_interned_strings_free_memory_bytes',
                    'OPcache interned strings free memory',
                    'gauge',
                    $strings['free_memory']
                );
            }

            if (isset($strings['number_of_strings'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_interned_strings_count',
                    'OPcache interned strings count',
                    'gauge',
                    $strings['number_of_strings']
                );
            }
        }

        // Statistics metrics
        if (isset($status['opcache_statistics']) && \is_array($status['opcache_statistics'])) {
            $stats = $status['opcache_statistics'];

            if (isset($stats['hits'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_hits_total',
                    'OPcache hits total',
                    'counter',
                    $stats['hits']
                );
            }

            if (isset($stats['misses'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_misses_total',
                    'OPcache misses total',
                    'counter',
                    $stats['misses']
                );
            }

            if (isset($stats['blacklist_misses'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_blacklist_misses_total',
                    'OPcache blacklist misses total',
                    'counter',
                    $stats['blacklist_misses']
                );
            }

            if (isset($stats['blacklist_miss_ratio'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_blacklist_miss_ratio',
                    'OPcache blacklist miss ratio',
                    'gauge',
                    $stats['blacklist_miss_ratio']
                );
            }

            if (isset($stats['opcache_hit_rate'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_hit_ratio',
                    'OPcache hit ratio',
                    'gauge',
                    $stats['opcache_hit_rate'] / 100
                );
            }

            if (isset($stats['num_cached_scripts'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_num_cached_scripts',
                    'Number of cached scripts',
                    'gauge',
                    $stats['num_cached_scripts']
                );
            }

            if (isset($stats['num_cached_keys'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_num_cached_keys',
                    'Number of cached keys',
                    'gauge',
                    $stats['num_cached_keys']
                );
            }

            if (isset($stats['max_cached_keys'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_max_cached_keys',
                    'Maximum cached keys',
                    'gauge',
                    $stats['max_cached_keys']
                );
            }

            if (isset($stats['start_time'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_start_time',
                    'OPcache start time',
                    'gauge',
                    $stats['start_time']
                );
            }

            if (isset($stats['last_restart_time'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_last_restart_time',
                    'OPcache last restart time',
                    'gauge',
                    $stats['last_restart_time']
                );
            }

            if (isset($stats['oom_restarts'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_oom_restarts_total',
                    'OPcache out of memory restarts total',
                    'counter',
                    $stats['oom_restarts']
                );
            }

            if (isset($stats['hash_restarts'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_hash_restarts_total',
                    'OPcache hash restarts total',
                    'counter',
                    $stats['hash_restarts']
                );
            }

            if (isset($stats['manual_restarts'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_manual_restarts_total',
                    'OPcache manual restarts total',
                    'counter',
                    $stats['manual_restarts']
                );
            }
        }

        // Preload statistics
        if (isset($status['preload_statistics']) && \is_array($status['preload_statistics'])) {
            $preload = $status['preload_statistics'];

            if (isset($preload['memory_consumption'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_preload_memory_consumption_bytes',
                    'OPcache preload memory consumption in bytes',
                    'gauge',
                    $preload['memory_consumption']
                );
            }

            if (isset($preload['scripts']) && \is_array($preload['scripts'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_preload_scripts_count',
                    'Number of preloaded scripts',
                    'gauge',
                    \count($preload['scripts'])
                );
            }
        }

        // JIT statistics
        if (isset($status['jit']) && \is_array($status['jit'])) {
            $jit = $status['jit'];

            if (isset($jit['enabled'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_jit_enabled',
                    'OPcache JIT enabled status',
                    'gauge',
                    $jit['enabled'] ? 1 : 0
                );
            }

            if (isset($jit['on'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_jit_on',
                    'OPcache JIT on status',
                    'gauge',
                    $jit['on'] ? 1 : 0
                );
            }

            if (isset($jit['kind'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_jit_kind',
                    'OPcache JIT kind',
                    'gauge',
                    $jit['kind']
                );
            }

            if (isset($jit['opt_level'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_jit_opt_level',
                    'OPcache JIT optimization level',
                    'gauge',
                    $jit['opt_level']
                );
            }

            if (isset($jit['opt_flags'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_jit_opt_flags',
                    'OPcache JIT optimization flags',
                    'gauge',
                    $jit['opt_flags']
                );
            }

            if (isset($jit['buffer_size'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_jit_buffer_size_bytes',
                    'OPcache JIT buffer size in bytes',
                    'gauge',
                    $jit['buffer_size']
                );
            }

            if (isset($jit['buffer_free'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_jit_buffer_free_bytes',
                    'OPcache JIT buffer free in bytes',
                    'gauge',
                    $jit['buffer_free']
                );
            }
        }

        // Configuration metrics
        if (isset($config['directives']) && \is_array($config['directives'])) {
            $directives = $config['directives'];

            if (isset($directives['opcache.memory_consumption'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_memory_consumption_bytes',
                    'OPcache memory consumption configured',
                    'gauge',
                    $directives['opcache.memory_consumption']
                );
            }

            if (isset($directives['opcache.max_accelerated_files'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_max_accelerated_files',
                    'OPcache max accelerated files configured',
                    'gauge',
                    $directives['opcache.max_accelerated_files']
                );
            }

            if (isset($directives['opcache.max_wasted_percentage'])) {
                $metrics[] = $this->formatMetric(
                    'opcache_max_wasted_percentage',
                    'OPcache max wasted percentage configured',
                    'gauge',
                    $directives['opcache.max_wasted_percentage'] / 100
                );
            }
        }

        $content = implode("\n", $metrics)."\n";

        return new Response($content, 200, ['Content-Type' => 'text/plain; version=0.0.4']);
    }

    private function formatMetric(string $name, string $help, string $type, $value): string
    {
        $lines = [];
        $lines[] = "# HELP {$name} {$help}";
        $lines[] = "# TYPE {$name} {$type}";
        $lines[] = "{$name} {$value}";

        return implode("\n", $lines);
    }
}
