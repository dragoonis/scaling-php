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

use Doctrine\DBAL\Connection;

final readonly class ProductSummaryProjectionBuilder
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function buildForProduct(int $productId): ?ProductSummaryProjection
    {
        $data = $this->getProductSummaryData($productId);
        if (!$data) {
            return null;
        }

        return new ProductSummaryProjection(
            productId: (int) $data['id'],
            name: $data['name'],
            description: $data['description'],
            price: $data['price'],
            createdAt: new \DateTimeImmutable($data['created_at'])
        );
    }

    public function getAllProductIds(): array
    {
        return $this->connection->fetchFirstColumn('SELECT id FROM product');
    }

    private function getProductSummaryData(int $productId): ?array
    {
        $sql = 'SELECT id, name, description, price, created_at FROM product WHERE id = :productId';

        return $this->connection->fetchAssociative($sql, ['productId' => $productId]) ?: null;
    }
}
