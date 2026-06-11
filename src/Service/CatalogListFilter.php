<?php

namespace App\Service;

use App\Entity\Equipment;
use App\Entity\Exercise;

final class CatalogListFilter
{
    /**
     * @param list<Equipment> $equipmentList
     * @param array{q:string,type:string,kind:string} $filters
     * @return list<Equipment>
     */
    public function filterEquipment(array $equipmentList, array $filters): array
    {
        $query = $this->normalize($filters['q']);
        $type = $filters['type'];
        $kind = $filters['kind'];

        $filtered = array_filter($equipmentList, function (Equipment $equipment) use ($query, $type, $kind): bool {
            if ($type !== '' && $equipment->getType()->value !== $type) {
                return false;
            }

            if ($kind === 'machine' && !$equipment->isMachine()) {
                return false;
            }

            if ($kind === 'tool' && $equipment->isMachine()) {
                return false;
            }

            if ($query === '') {
                return true;
            }

            return $this->contains($equipment->getName(), $query)
                || $this->contains($equipment->getSlug(), $query)
                || $this->contains($equipment->getDescription(), $query)
                || $this->contains((string) $equipment->getUsageInstructions(), $query)
                || $this->contains($equipment->getType()->value, $query);
        });

        return array_values($filtered);
    }

    /**
     * @param list<Exercise> $exercises
     * @param list<string> $availableEquipmentSlugs
     * @param array{q:string,availableOnly:bool,trackingMode:string,exerciseType:string,muscle:string,equipmentSlug:string,fundamental:string} $filters
     * @return list<Exercise>
     */
    public function filterExercises(array $exercises, array $availableEquipmentSlugs, array $filters): array
    {
        $query = $this->normalize($filters['q']);
        $muscle = $this->normalize($filters['muscle']);
        $equipmentSlug = $filters['equipmentSlug'];
        $trackingMode = $filters['trackingMode'];
        $exerciseType = $filters['exerciseType'];
        $fundamental = $filters['fundamental'];

        $filtered = array_filter($exercises, function (Exercise $exercise) use ($availableEquipmentSlugs, $filters, $query, $muscle, $equipmentSlug, $trackingMode, $exerciseType, $fundamental): bool {
            $equipment = $exercise->getDefaultEquipment();

            if ($filters['availableOnly'] && $equipment !== null && !in_array($equipment->getSlug(), $availableEquipmentSlugs, true)) {
                return false;
            }

            if ($trackingMode !== '' && $exercise->getTrackingMode()->value !== $trackingMode) {
                return false;
            }

            if ($exerciseType !== '' && $exercise->getExerciseType()->value !== $exerciseType) {
                return false;
            }

            if ($equipmentSlug !== '') {
                if ($equipment === null || $equipment->getSlug() !== $equipmentSlug) {
                    return false;
                }
            }

            if ($fundamental === 'yes' && !$exercise->isFundamental()) {
                return false;
            }

            if ($fundamental === 'no' && $exercise->isFundamental()) {
                return false;
            }

            if ($muscle !== '' && !$this->exerciseHasMuscle($exercise, $muscle)) {
                return false;
            }

            if ($query === '') {
                return true;
            }

            return $this->exerciseMatchesQuery($exercise, $query);
        });

        return array_values($filtered);
    }

    /**
     * @param list<Exercise> $exercises
     * @return list<string>
     */
    public function collectMuscleOptions(array $exercises): array
    {
        $muscles = [];
        foreach ($exercises as $exercise) {
            foreach (array_merge($exercise->getPrimaryMuscles(), $exercise->getSecondaryMuscles()) as $muscle) {
                $muscle = trim($muscle);
                if ($muscle !== '') {
                    $muscles[$this->normalize($muscle)] = $muscle;
                }
            }
        }

        uasort($muscles, static fn (string $a, string $b): int => strcasecmp($a, $b));

        return array_values($muscles);
    }

    private function exerciseMatchesQuery(Exercise $exercise, string $query): bool
    {
        $equipment = $exercise->getDefaultEquipment();
        $haystacks = [
            $exercise->getName(),
            $exercise->getSlug(),
            $exercise->getDescription(),
            (string) $exercise->getExecutionInstructions(),
            $exercise->getTrackingMode()->label(),
            $exercise->getTrackingMode()->value,
            $exercise->getExerciseType()->value,
            (string) $exercise->getSecondaryEquipmentNotes(),
        ];

        if ($equipment !== null) {
            $haystacks[] = $equipment->getName();
            $haystacks[] = $equipment->getSlug();
        }

        foreach (array_merge($haystacks, $exercise->getPrimaryMuscles(), $exercise->getSecondaryMuscles()) as $haystack) {
            if ($this->contains($haystack, $query)) {
                return true;
            }
        }

        return false;
    }

    private function exerciseHasMuscle(Exercise $exercise, string $muscle): bool
    {
        foreach (array_merge($exercise->getPrimaryMuscles(), $exercise->getSecondaryMuscles()) as $candidate) {
            if ($this->normalize($candidate) === $muscle) {
                return true;
            }
        }

        return false;
    }

    private function contains(string $value, string $normalizedNeedle): bool
    {
        return str_contains($this->normalize($value), $normalizedNeedle);
    }

    private function normalize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($value === false) {
            $value = '';
        }

        return strtolower($value);
    }
}
