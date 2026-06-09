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

use App\Command\ListOrdersCommand;
use App\Projection\OrderProjectionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ListOrdersCommandHandler
{
    public function __construct(
        private readonly OrderProjectionRepository $projectionRepository,
    ) {
    }

    public function __invoke(ListOrdersCommand $command): array
    {
        return $this->projectionRepository->findAll();
    }
}
