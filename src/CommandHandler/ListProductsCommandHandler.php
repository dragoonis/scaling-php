<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\CommandHandler;

use App\Command\ListProductsCommand;
use App\Projection\ProductProjectionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ListProductsCommandHandler
{
    public function __construct(
        private readonly ProductProjectionRepository $projectionRepository,
    ) {
    }

    public function __invoke(ListProductsCommand $command): array
    {
        return $this->projectionRepository->findAll();
    }
}
