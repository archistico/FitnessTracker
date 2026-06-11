<?php

namespace App\Service;

use App\Entity\Exercise;

final class AvailableExerciseFilter
{
    /**
     * @param list<Exercise> $exercises
     * @param list<string> $availableEquipmentSlugs
     * @return list<Exercise>
     */
    public function filter(array $exercises, array $availableEquipmentSlugs, bool $availableOnly): array
    {
        if (!$availableOnly) {
            return array_values($exercises);
        }

        $availableLookup = array_fill_keys($availableEquipmentSlugs, true);

        return array_values(array_filter(
            $exercises,
            static function (Exercise $exercise) use ($availableLookup): bool {
                $equipment = $exercise->getDefaultEquipment();

                if ($equipment === null) {
                    return true;
                }

                return isset($availableLookup[$equipment->getSlug()]);
            }
        ));
    }
}
