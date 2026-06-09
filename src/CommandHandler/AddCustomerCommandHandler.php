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

use App\Command\AddCustomerCommand;
use App\Entity\Customer;
use App\Projection\CustomerProjectionService;
use App\Repository\CustomerRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AddCustomerCommandHandler
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerProjectionService $projectionService,
    ) {
    }

    public function __invoke(AddCustomerCommand $command): void
    {
        $customer = new Customer();
        $customer->setName($command->name);
        $customer->setEmail($command->email);
        $customer->setAddress($command->address);
        $customer->setCity($command->city);
        $customer->setPostalCode($command->postalCode);
        $customer->setCountry($command->country);
        $customer->setCreatedAt($command->createdAt);

        $this->customerRepository->save($customer, true);
        $this->projectionService->updateProjection($customer);
    }
}
