<?php

namespace App\Service;

use App\Entity\GymProfile;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

final readonly class GymProfileProvider
{
    public function __construct(
        private CurrentUserProvider $currentUserProvider,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getCurrentGym(): GymProfile
    {
        $gymProfile = $this->entityManager->getRepository(GymProfile::class)->findOneBy([
            'appUser' => $this->currentUserProvider->getUser(),
        ]);

        if (!$gymProfile instanceof GymProfile) {
            throw new RuntimeException('Default gym profile not found. Run doctrine fixtures before using the application.');
        }

        return $gymProfile;
    }
}
