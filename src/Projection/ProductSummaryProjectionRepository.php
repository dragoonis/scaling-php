<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Projection;

use Predis\Client as Redis;

final class ProductSummaryProjectionRepository
{
    private const KEY_PREFIX = 'product_summary:';
    private const KEY_ALL = 'product_summaries:all';

    public function __construct(
        private Redis $redis,
    ) {
    }

    public function save(ProductSummaryProjection $projection): void
    {
        $key = self::KEY_PREFIX.$projection->productId;
        $this->redis->hmset($key, $projection->toArray());
        $this->redis->expire($key, 3600);
        $this->redis->sadd(self::KEY_ALL, [$projection->productId]);
    }

    public function findById(int $productId): ?ProductSummaryProjection
    {
        $key = self::KEY_PREFIX.$productId;
        $data = $this->redis->hgetall($key);
        if (!$data || empty($data['product_id'])) {
            return null;
        }

        return ProductSummaryProjection::fromArray($data);
    }

    public function findAll(): array
    {
        $productIds = $this->redis->smembers(self::KEY_ALL);
        $projections = [];

        foreach ($productIds as $productId) {
            $projection = $this->findById((int) $productId);
            if ($projection) {
                $projections[] = $projection;
            }
        }

        return $projections;
    }

    public function remove(int $productId): void
    {
        $key = self::KEY_PREFIX.$productId;
        $this->redis->del($key);
        $this->redis->srem(self::KEY_ALL, $productId);
    }
}
