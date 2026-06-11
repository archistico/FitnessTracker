<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Exercise;
use App\Entity\ExerciseLoadProfile;
use Doctrine\ORM\EntityManagerInterface;

final class LoadSuggestionService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function suggestWeightForRepRange(AppUser $appUser, Exercise $exercise, ?int $repMin, ?int $repMax): ?float
    {
        if ($repMin === null || $repMax === null) {
            return null;
        }

        $profile = $this->findLatestProfile($appUser, $exercise);

        return $profile?->getSuggestedWeightForRepRange($repMin, $repMax);
    }

    private function findLatestProfile(AppUser $appUser, Exercise $exercise): ?ExerciseLoadProfile
    {
        $profile = $this->entityManager->getRepository(ExerciseLoadProfile::class)->findOneBy(
            ['appUser' => $appUser, 'exercise' => $exercise],
            ['validFrom' => 'DESC', 'id' => 'DESC']
        );

        return $profile instanceof ExerciseLoadProfile ? $profile : null;
    }
}
