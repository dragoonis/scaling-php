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

use App\Entity\Order;
use App\Repository\OrderRepository;

final class OrderProjectionService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderProjectionRepository $projectionRepository,
        private readonly OrderProjectionBuilder $projectionBuilder,
    ) {
    }

    public function rebuildAll(): void
    {
        $this->projectionRepository->clear();

        $orders = $this->orderRepository->findAll();

        foreach ($orders as $order) {
            $projection = $this->projectionBuilder->build($order);
            $this->projectionRepository->save($projection);
        }
    }

    public function updateProjection(Order $order): void
    {
        $projection = $this->projectionBuilder->build($order);
        $this->projectionRepository->save($projection);
    }

    public function deleteProjection(int $id): void
    {
        $this->projectionRepository->delete($id);
    }
}
