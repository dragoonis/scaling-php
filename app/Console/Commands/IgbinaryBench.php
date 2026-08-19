<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Redis;

class IgbinaryBench extends Command
{
    protected $signature = 'app:igbinary-bench {--iterations=20000} {--redis-ops=5000} {--json-out=}';

    protected $description = 'Benchmark igbinary vs PHP serialize vs json, locally and through Redis';

    public function handle(): int
    {
        if (! extension_loaded('igbinary') || ! extension_loaded('redis')) {
            $this->error('Needs ext-igbinary and ext-redis. Run make rebuild first.');

            return self::FAILURE;
        }

        $iterations = (int) $this->option('iterations');
        $redisOps = (int) $this->option('redis-ops');
        $payloads = $this->payloads();
        $results = ['payloads' => [], 'redis' => [], 'meta' => [
            'php' => PHP_VERSION,
            'igbinary' => phpversion('igbinary'),
            'phpredis' => phpversion('redis'),
            'iterations' => $iterations,
            'redis_ops' => $redisOps,
        ]];

        foreach ($payloads as $name => $payload) {
            $row = $this->benchPayload($name, $payload, $iterations);
            $results['payloads'][$name] = $row;
            $this->table(
                [$name, 'bytes', 'serialize ops/s', 'unserialize ops/s'],
                collect($row)->map(fn ($r, $fmt) => [
                    $fmt,
                    number_format($r['bytes']),
                    number_format($r['ser_ops']),
                    number_format($r['unser_ops']),
                ])->all(),
            );
        }

        $results['redis'] = $this->benchRedis($payloads['session'], $redisOps);
        $this->table(
            ['redis (phpredis)', 'SET ops/s', 'GET ops/s', 'MEMORY USAGE/key', '1k sessions'],
            collect($results['redis'])->map(fn ($r, $mode) => [
                $mode,
                number_format($r['set_ops']),
                number_format($r['get_ops']),
                number_format($r['bytes_per_key']).' B',
                number_format($r['bytes_1k'] / 1024, 1).' KiB',
            ])->all(),
        );

        if ($out = $this->option('json-out')) {
            file_put_contents($out, json_encode($results, JSON_PRETTY_PRINT));
            $this->info("JSON written to $out");
        }

        return self::SUCCESS;
    }

    private function payloads(): array
    {
        mt_srand(42);
        $faker = fn (int $n) => Str::random($n);

        $session = [
            '_token' => $faker(40),
            'user' => [
                'id' => 105069,
                'name' => 'Kevin Abrar Khansa',
                'email' => 'kevariable@gmail.com',
                'roles' => ['admin', 'speaker', 'developer'],
                'preferences' => ['theme' => 'dark', 'locale' => 'en', 'tz' => 'Europe/Copenhagen', 'notifications' => true],
                'last_login' => '2026-08-19T14:00:00Z',
            ],
            'cart' => array_map(fn ($i) => [
                'product_id' => 1000 + $i,
                'sku' => 'SKU-'.str_pad((string) ($i * 991), 8, '0', STR_PAD_LEFT),
                'name' => 'product '.$faker(12),
                'qty' => ($i % 3) + 1,
                'price' => 990 + $i * 137,
            ], range(1, 12)),
            'flash' => ['status' => 'Order updated', 'level' => 'success'],
            'url' => ['intended' => 'https://localhost/orders/1234'],
            'history' => array_map(fn ($i) => '/products/'.($i * 77), range(1, 20)),
        ];

        $product = Product::query()->find(1069)?->toArray() ?? [
            'id' => 1069, 'name' => 'fallback', 'sku' => 'SKU-0', 'description' => $faker(80),
            'price' => 1000, 'stock' => 5, 'created_at' => now()->toISOString(), 'updated_at' => now()->toISOString(),
        ];

        $productList = Product::query()->limit(100)->get()->toArray();
        if (count($productList) < 100) {
            $productList = array_map(fn ($i) => $product + ['id' => $i], range(1, 100));
        }

        $nested = [];
        for ($i = 0; $i < 1000; $i++) {
            $nested['k'.$i] = ['id' => $i, 'value' => $i * 3.14159, 'label' => 'item-'.$i, 'flags' => [true, false, null]];
        }

        return [
            'session' => $session,
            'product' => $product,
            'product_list_100' => $productList,
            'nested_1000' => $nested,
        ];
    }

    private function benchPayload(string $name, mixed $payload, int $iterations): array
    {
        $formats = [
            'php_serialize' => [fn ($v) => serialize($v), fn ($s) => unserialize($s)],
            'igbinary' => [fn ($v) => igbinary_serialize($v), fn ($s) => igbinary_unserialize($s)],
            'json' => [fn ($v) => json_encode($v), fn ($s) => json_decode($s, true)],
        ];

        $out = [];
        foreach ($formats as $fmt => [$enc, $dec]) {
            $blob = $enc($payload);
            $n = max(200, (int) ($iterations / max(1, strlen($blob) / 2000)));

            $t = hrtime(true);
            for ($i = 0; $i < $n; $i++) {
                $enc($payload);
            }
            $serOps = $n / ((hrtime(true) - $t) / 1e9);

            $t = hrtime(true);
            for ($i = 0; $i < $n; $i++) {
                $dec($blob);
            }
            $unserOps = $n / ((hrtime(true) - $t) / 1e9);

            $out[$fmt] = [
                'bytes' => strlen($blob),
                'ser_ops' => (int) $serOps,
                'unser_ops' => (int) $unserOps,
                'n' => $n,
            ];
        }

        return $out;
    }

    private function benchRedis(array $session, int $ops): array
    {
        $modes = [
            'php_serializer' => Redis::SERIALIZER_PHP,
            'igbinary_serializer' => Redis::SERIALIZER_IGBINARY,
        ];

        $out = [];
        foreach ($modes as $mode => $serializer) {
            $r = new Redis();
            $r->connect(env('REDIS_HOST', 'redis'), (int) env('REDIS_PORT', 6379));
            $r->select(9);
            $r->flushDB();
            $r->setOption(Redis::OPT_SERIALIZER, $serializer);

            $t = hrtime(true);
            for ($i = 0; $i < $ops; $i++) {
                $r->set('bench:'.($i % 100), $session);
            }
            $setOps = $ops / ((hrtime(true) - $t) / 1e9);

            $t = hrtime(true);
            for ($i = 0; $i < $ops; $i++) {
                $r->get('bench:'.($i % 100));
            }
            $getOps = $ops / ((hrtime(true) - $t) / 1e9);

            $bytesPerKey = (int) $r->rawCommand('MEMORY', 'USAGE', $r->getOption(Redis::OPT_PREFIX).'bench:1');

            $r->flushDB();
            for ($i = 0; $i < 1000; $i++) {
                $r->set('sess:'.$i, $session);
            }
            $info = $r->info('memory');
            $bytes1k = 1000 * (int) $r->rawCommand('MEMORY', 'USAGE', $r->getOption(Redis::OPT_PREFIX).'sess:1');
            $r->flushDB();
            $r->close();

            $out[$mode] = [
                'set_ops' => (int) $setOps,
                'get_ops' => (int) $getOps,
                'bytes_per_key' => $bytesPerKey,
                'bytes_1k' => $bytes1k,
                'used_memory_human' => $info['used_memory_human'] ?? null,
            ];
        }

        return $out;
    }
}
