<?php

namespace App\Service;

use App\Entity\Equipment;
use App\Entity\GymEquipment;
use App\Entity\GymProfile;
use Doctrine\ORM\EntityManagerInterface;

final class GymEquipmentCatalogSynchronizer
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Ensures that the gym page always exposes one GymEquipment row for every
     * Equipment item in the catalog. Missing links are created as available by
     * default, so the user can explicitly disable what is not present.
     *
     * @return list<GymEquipment>
     */
    public function synchronizeAndReturnItems(GymProfile $gymProfile): array
    {
        $equipmentList = $this->getAllEquipment();
        [$items, $created] = $this->synchronizeGymProfile($gymProfile, $equipmentList);

        if ($created > 0) {
            $this->entityManager->flush();
        }

        $this->sortItems($items);

        return $items;
    }

    public function synchronizeAllGymProfiles(): int
    {
        $equipmentList = $this->getAllEquipment();
        $created = 0;

        foreach ($this->entityManager->getRepository(GymProfile::class)->findAll() as $gymProfile) {
            if (!$gymProfile instanceof GymProfile) {
                continue;
            }

            [, $createdForGym] = $this->synchronizeGymProfile($gymProfile, $equipmentList);
            $created += $createdForGym;
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        return $created;
    }

    /** @return list<Equipment> */
    private function getAllEquipment(): array
    {
        $equipmentList = $this->entityManager->getRepository(Equipment::class)->findBy([], ['name' => 'ASC']);

        return array_values(array_filter($equipmentList, static fn (mixed $equipment): bool => $equipment instanceof Equipment));
    }

    /**
     * @param list<Equipment> $equipmentList
     * @return array{0:list<GymEquipment>,1:int}
     */
    private function synchronizeGymProfile(GymProfile $gymProfile, array $equipmentList): array
    {
        $items = $this->entityManager->getRepository(GymEquipment::class)->findBy(['gymProfile' => $gymProfile]);
        $itemsByEquipmentSlug = [];

        foreach ($items as $item) {
            if (!$item instanceof GymEquipment) {
                continue;
            }

            $itemsByEquipmentSlug[$item->getEquipment()->getSlug()] = $item;
        }

        $created = 0;

        foreach ($equipmentList as $equipment) {
            $slug = $equipment->getSlug();
            if (isset($itemsByEquipmentSlug[$slug])) {
                continue;
            }

            $item = (new GymEquipment())
                ->setGymProfile($gymProfile)
                ->setEquipment($equipment)
                ->setIsAvailable(true);

            $this->entityManager->persist($item);
            $itemsByEquipmentSlug[$slug] = $item;
            ++$created;
        }

        return [array_values($itemsByEquipmentSlug), $created];
    }

    /** @param list<GymEquipment> $items */
    private function sortItems(array &$items): void
    {
        usort($items, static function (GymEquipment $left, GymEquipment $right): int {
            $leftEquipment = $left->getEquipment();
            $rightEquipment = $right->getEquipment();

            return [$leftEquipment->getType()->value, strtolower($leftEquipment->getName())]
                <=> [$rightEquipment->getType()->value, strtolower($rightEquipment->getName())];
        });
    }
}
