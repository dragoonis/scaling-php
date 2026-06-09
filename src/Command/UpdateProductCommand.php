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

readonly class UpdateProductCommand
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $description = null,
        public ?float $price = null,
        public ?\DateTimeImmutable $createdAt = null,
    ) {
    }
}
