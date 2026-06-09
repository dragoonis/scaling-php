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

use App\Entity\Product;
use App\Repository\ProductRepository;

final readonly class ProductProjectionService
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductProjectionRepository $projectionRepository,
        private ProductProjectionBuilder $projectionBuilder,
    ) {
    }

    public function rebuildAll(): void
    {
        $this->projectionRepository->clear();

        $products = $this->productRepository->findAll();

        foreach ($products as $product) {
            $projection = $this->projectionBuilder->build($product);
            $this->projectionRepository->save($projection);
        }
    }

    public function updateProjection(Product $product): void
    {
        $projection = $this->projectionBuilder->build($product);
        $this->projectionRepository->save($projection);
    }

    public function deleteProjection(int $id): void
    {
        $this->projectionRepository->delete($id);
    }
}
