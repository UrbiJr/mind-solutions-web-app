<?php

namespace App\Command;

use App\Entity\Backup;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'restore_data',
    description: 'Restore user data from a backup',
)]
class RestoreDataCommand extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('backup', InputArgument::OPTIONAL, "Backup ID to restore")
            ->addArgument('user', InputArgument::OPTIONAL, "Target user ID")
            //->addOption('settings-only', null, InputOption::VALUE_NONE, 'Save only settings')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $backupId = $input->getArgument('backup');
        $userId = $input->getArgument('user');

        /*
        if ($userId) {
            $io->note(sprintf('You passed user ID: %s', $userId));
        }
        */

        /*
        if ($input->getOption('option1')) {
            // ...
        }
        */

        $userRepository = $this->em->getRepository(User::class);
        $backupRepository = $this->em->getRepository(Backup::class);

        $user = $userRepository->getById($userId);
        if (!$user) {
            $output->write("Error: user with id {$userId} not found.");
            return Command::FAILURE;
        }

        /** @var Backup $backup */
        $backup = $backupRepository->find($backupId);
        if (!$user) {
            $output->write("Error: backup with id {$userId} not found.");
            return Command::FAILURE;
        }

        if ($backup->getUser()->getId() != $userId) {
            $output->write("Error: given backup and user do not correspond.");
            return Command::FAILURE;
        }

        // Edit user with backup data
        $user->setFirstName($backup->getFirstname());
        $user->setLastName($backup->getLastName());
        $user->setConnections($backup->getConnections());
        $user->setAbout($backup->getAbout());
        $user->setCurrency($backup->getCurrency());
        $user->setCaptchaProviderApiKey($backup->getCaptchaProviderApiKey());
        $user->setCaptchaProvider($backup->getCaptchaProvider());

        $this->em->flush();

        $output->write("success");

        return Command::SUCCESS;
    }
}
