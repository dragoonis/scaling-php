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

use App\Command\GetCustomerCommand;
use App\Projection\CustomerProjectionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetCustomerCommandHandler
{
    public function __construct(
        private readonly CustomerProjectionRepository $projectionRepository,
    ) {
    }

    public function __invoke(GetCustomerCommand $command): ?array
    {
        $projection = $this->projectionRepository->find($command->id);

        if (!$projection) {
            return null;
        }

        return $projection->toArray();
    }
}
