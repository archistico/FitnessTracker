<?php

namespace App\Repository;

use App\Entity\AppUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppUser>
 */
class AppUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppUser::class);
    }

    public function findDefaultUser(): ?AppUser
    {
        return $this->findOneBy(['isDefault' => true], ['id' => 'ASC'])
            ?? $this->findOneBy([], ['id' => 'ASC']);
    }
}
