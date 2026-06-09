<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

readonly class AddOrderCommand
{
    public function __construct(
        public int $customerId,
        public string $orderNumber,
        public string $totalAmount,
        public string $status,
        public array $items,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
