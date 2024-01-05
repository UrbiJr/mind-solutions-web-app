<?php

namespace App\Command;

use App\Controller\BackupController;
use App\Entity\Backup;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
            ->addOption('data', null, InputOption::VALUE_REQUIRED, 'Specify data type to restore');;
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

        $dataType = $input->getOption('data');
        if (!$dataType) {
            $output->write("Error: you must specify a data type to restore.");
            return Command::FAILURE;
        }

        switch ($dataType) {
            case BackupController::INVENTORY_DATA:
                break;

            case BackupController::SETTINGS_DATA:
                try {
                    $this->settingsRestore($userId, $backupId);
                } catch (Exception $e) {
                    $output->write($e->getMessage());
                    return Command::FAILURE;
                }
                break;

            default:
                $output->write("Error: unrecognized data type.");
                return Command::FAILURE;
        }

        $output->write("success");

        return Command::SUCCESS;
    }

    private function settingsRestore($userId, $backupId)
    {
        $userRepository = $this->em->getRepository(User::class);
        $backupRepository = $this->em->getRepository(Backup::class);

        $user = $userRepository->getById($userId);
        if (!$user) {
            throw new Exception("Error: user with id {$userId} not found.");
        }

        /** @var Backup $backup */
        $backup = $backupRepository->find($backupId);
        if (!$user) {
            throw new Exception("Error: backup with id {$userId} not found.");
        }

        if ($backup->getUser()->getId() != $userId) {
            throw new Exception("Error: given backup and user do not correspond.");
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
    }
}
