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

use App\Command\ListCustomersCommand;
use App\Projection\CustomerProjectionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ListCustomersCommandHandler
{
    public function __construct(
        private readonly CustomerProjectionRepository $projectionRepository,
    ) {
    }

    public function __invoke(ListCustomersCommand $command): array
    {
        return $this->projectionRepository->findAll();
    }
}
