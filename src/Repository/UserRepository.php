<?php

namespace App\Repository;

use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use App\Entity\User;
use App\Service\Utils;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{

    public function __construct(
        ManagerRegistry $registry,
        private readonly Utils $utils,
        protected readonly InventoryItemRepository $inventoryItemRepo,
        private readonly string $projectDir
    ) {
        parent::__construct($registry, User::class);
    }

    // Allows a user to login with their email or discord username
    public function loadUserByIdentifier($identifier): ?User
    {
        $entityManager = $this->getEntityManager();

        return $entityManager->createQuery(
            'SELECT u
                FROM App\Entity\User u
                WHERE u.username = :query
                OR u.discordUsername = :query'
        )
            ->setParameter('query', $identifier)
            ->getOneOrNullResult();
    }

    public function getById($id): ?User
    {
        return $this->find($id);
    }

    public function getByUsername($username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    public function getByDiscordUsername($discordUsername): ?User
    {
        return $this->findOneBy(['discordUsername' => $discordUsername]);
    }

    public function getAll(): array
    {
        return $this->findAll();
    }

    public function unbindLicenseKey($licenseKey)
    {
        $user = $this->findOneBy(['licenseKey' => $licenseKey]);

        if (!$user) {
            throw new \Exception("User not found");
        }

        $user->setLicenseKey(null);
        $this->_em->flush();
    }

    public function update(User $user): void
    {
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();
    }

    public function exportInventoryToCSV(User $user)
    {
        $inventory = $this->inventoryItemRepo->getAllByUserId($user->getId());

        $fileName = "user_{$user->getId()}_inventory.csv";
        $filePath = $this->utils->pathCombine([$this->projectDir, 'downloads', $fileName]);
        $csvFile = fopen($filePath, 'w');

        // Add CSV header
        $csvHeader = [
            'ItemId',
            'ViagogoEventId',
            'ViagogoCategoryId',
            'Name',
            'Country',
            'City',
            'Location',
            'Section',
            'Row',
            'Seats',
            'TicketType',
            'TicketGenre',
            'Retailer',
            'IndividualTicketCostAmount',
            'IndividualTicketCostCurrency',
            'OrderNumber',
            'OrderEmail',
            'Status',
            'YourPricePerTicketAmount',
            'YourPricePerTicketCurrency',
            'TotalPayoutAmount',
            'TotalPayoutCurrency',
            'Quantity',
            'QuantityRemain',
            'Platform',
            'SaleId',
            'ListingId',
            'ListingRestrictions',
            'ListingTicketDetails',
            'SaleDate',
            'EventDate',
            'PurchaseDate',
            'SaleEndDate',
            'DateLastModified',
        ];

        fputcsv($csvFile, $csvHeader, ";");

        // Loop through inventory items for the user
        foreach ($inventory as $inventoryItem) {
            $csvRow = array_values($inventoryItem->toArray()); // Convert InventoryItem object to an array
            fputcsv($csvFile, $csvRow, ";");
        }

        fclose($csvFile);

        return [$fileName, $filePath];
    }
}
