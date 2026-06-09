<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Projection\ProductSummaryProjectionBuilder;
use App\Projection\ProductSummaryProjectionRepository;
use Psr\Log\LoggerInterface;

final readonly class ProductSummaryProjectionService
{
    public function __construct(
        private ProductSummaryProjectionBuilder $builder,
        private ProductSummaryProjectionRepository $repository,
        private LoggerInterface $logger,
    ) {
    }

    public function updateProductSummary(int $productId): void
    {
        try {
            $projection = $this->builder->buildForProduct($productId);
            if ($projection) {
                $this->repository->save($projection);
                $this->logger->info('Product projection updated', [
                    'product_id' => $productId,
                    'projection_type' => 'ProductSummary',
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Product projection update failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function rebuildAllProjections(): void
    {
        $productIds = $this->builder->getAllProductIds();
        foreach ($productIds as $productId) {
            $this->updateProductSummary($productId);
        }
    }
}
