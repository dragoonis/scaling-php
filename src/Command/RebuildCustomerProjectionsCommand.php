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

use App\Projection\CustomerProjectionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rebuild-customer-projections',
    description: 'Rebuild customer projections from database',
)]
final class RebuildCustomerProjectionsCommand extends Command
{
    public function __construct(
        private readonly CustomerProjectionService $projectionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Rebuilding Customer Projections');

        $io->section('Clearing existing projections...');
        $this->projectionService->rebuildAll();

        $io->success('Customer projections rebuilt successfully!');

        return Command::SUCCESS;
    }
}
