<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Repository\AppUserRepository;
use RuntimeException;

final readonly class CurrentUserProvider
{
    public function __construct(private AppUserRepository $appUserRepository)
    {
    }

    public function getUser(): AppUser
    {
        $user = $this->appUserRepository->findDefaultUser();

        if (!$user instanceof AppUser) {
            throw new RuntimeException('Default user not found. Run doctrine fixtures before using the application.');
        }

        return $user;
    }
}
