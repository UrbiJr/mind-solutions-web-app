<?php

namespace App\Command;

use App\Entity\InventoryValue;
use App\Repository\UserRepository;
use App\Service\InventoryService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(
    name: 'app:set-inventory-value',
    description: 'Set inventory values for all users.',
)]
class SetInventoryValueCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly InventoryService $inventoryService,
    ) {
        parent::__construct();
    }

    // ...
    protected function configure(): void
    {
        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command is meant to be run regularly to keep track of user\'s inventory values in the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->userRepository->getAll();
        $output->writeln("Getting inventory value for " . sizeof($users) . " users");

        foreach ($users as $userId => $user) {
            $amount = $this->inventoryService->calculateInventoryValue($user);
            $output->writeln("User " . $userId . " has an inventory value of: " . $amount);
            $inventoryValue = new InventoryValue();
            $inventoryValue->setUser($user);
            $inventoryValue->setValue($amount);
            $inventoryValue->setTimestamp(new DateTime());
            $this->em->persist($inventoryValue);
        }

        $this->em->flush();

        return Command::SUCCESS;
    }
}
