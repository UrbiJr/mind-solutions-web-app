<?php

namespace App\Repository;

use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // Allows a user to login with their email or discord username
    public function loadUserByIdentifier($identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :identifier OR u.discordUsername = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
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
}
