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

use App\Command\DeleteCustomerCommand;
use App\Projection\CustomerProjectionService;
use App\Repository\CustomerRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteCustomerCommandHandler
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerProjectionService $projectionService,
    ) {
    }

    public function __invoke(DeleteCustomerCommand $command): void
    {
        $customer = $this->customerRepository->find($command->id);

        if ($customer) {
            $this->customerRepository->remove($customer, true);
            $this->projectionService->deleteProjection($command->id);
        }
    }
}
