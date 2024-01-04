<?php

namespace App\Command;

use App\Entity\Backup;
use App\Entity\User;
use App\Repository\BackupRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
            //->addOption('settings-only', null, InputOption::VALUE_NONE, 'Save only settings')
        ;
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

        /*
        if ($input->getOption('option1')) {
            // ...
        }
        */

        $userRepository = $this->em->getRepository(User::class);
        $backupRepository = $this->em->getRepository(Backup::class);

        /** @var User $user */
        $user = $userRepository->getById($userId);
        if (!$user) {
            $output->write("Error: user with id {$userId} not found.");
            return Command::FAILURE;
        }
        // Check the number of existing backups for the user
        $existingBackupsCount = $backupRepository->count(['user' => $user]);

        // Check and enforce the maximum number of backups
        $maxBackupsPerUser = 3;
        if ($existingBackupsCount >= $maxBackupsPerUser) {
            $output->write('Error: maximum number of backups reached. Delete existing backups before creating a new one.');
            return Command::FAILURE;
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

        $output->write("success");

        return Command::SUCCESS;
    }
}
