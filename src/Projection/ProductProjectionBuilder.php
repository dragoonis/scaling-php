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

final class ProductProjectionBuilder
{
    public function build(Product $product): ProductProjection
    {
        return new ProductProjection(
            $product->getId(),
            $product->getName(),
            $product->getDescription(),
            $product->getPrice(),
            $product->getCreatedAt()
        );
    }
}
