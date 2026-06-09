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

final class OrderProjectionBuilder
{
    public function build(Order $order): OrderProjection
    {
        return new OrderProjection(
            $order->getId(),
            $order->getCustomer()->getId(),
            $order->getCustomer()->getName(),
            $order->getOrderNumber(),
            $order->getTotalAmount(),
            $order->getStatus(),
            $order->getItems(),
            $order->getCreatedAt(),
            $order->getUpdatedAt()
        );
    }
}
