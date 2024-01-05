<?php

namespace App\Command;

use App\Controller\BackupController;
use App\Entity\Backup;
use App\Entity\User;
use App\Repository\BackupRepository;
use App\Repository\UserRepository;
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
    name: 'backup_data',
    description: 'Backup user data',
)]
class BackupDataCommand extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user', InputArgument::OPTIONAL, "User ID")
            ->addOption('data', null, InputOption::VALUE_REQUIRED, 'Specify data type to backup');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('user');

        /*
        if ($userId) {
            $io->note(sprintf('You passed user ID: %s', $userId));
        }
        */

        $dataType = $input->getOption('data');
        if (!$dataType) {
            $output->write("Error: you must specify a data type to backup.");
            return Command::FAILURE;
        }

        switch ($dataType) {
            case BackupController::INVENTORY_DATA:
                break;

            case BackupController::SETTINGS_DATA:
                try {
                    $this->settingsBackup($userId);
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

    private function settingsBackup($userId)
    {
        $userRepository = $this->em->getRepository(User::class);
        $backupRepository = $this->em->getRepository(Backup::class);

        /** @var User $user */
        $user = $userRepository->getById($userId);
        if (!$user) {
            throw new Exception("Error: user with id {$userId} not found.");
        }
        // Check the number of existing backups for the user
        $existingBackupsCount = $backupRepository->count(['user' => $user]);

        // Check and enforce the maximum number of backups
        $maxBackupsPerUser = 3;
        if ($existingBackupsCount >= $maxBackupsPerUser) {
            throw new Exception('Error: maximum number of backups reached. Delete existing backups before creating a new one.');
        }

        // Create a new backup record in the Backups table
        $backup = new Backup();
        $backup->setUser($user);
        $backup->setFirstName($user->getFirstname());
        $backup->setLastName($user->getLastName());
        $backup->setConnections($user->getConnections());
        $backup->setAbout($user->getAbout());
        $backup->setCurrency($user->getCurrency());
        $backup->setCaptchaProviderApiKey($user->getCaptchaProviderApiKey());
        $backup->setCaptchaProvider($user->getCaptchaProvider());
        $backup->setTimestamp(new \DateTime());

        $this->em->persist($backup);
        $this->em->flush();
    }
}
