<?php

namespace App\Command;

use App\DataFixtures\FitnessCatalog;
use App\Entity\Equipment;
use App\Entity\Exercise;
use App\Enum\EquipmentType;
use App\Service\GymEquipmentCatalogSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:catalog:seed',
    description: 'Aggiunge o aggiorna il catalogo base di attrezzature ed esercizi senza cancellare i dati esistenti.'
)]
final class SeedFitnessCatalogCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GymEquipmentCatalogSynchronizer $gymEquipmentCatalogSynchronizer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        [$equipmentCreated, $equipmentUpdated, $equipmentBySlug] = $this->upsertEquipmentCatalog();
        [$exerciseCreated, $exerciseUpdated, $missingEquipmentSlugs] = $this->upsertExerciseCatalog($equipmentBySlug);

        $this->entityManager->flush();
        $gymEquipmentLinksCreated = $this->gymEquipmentCatalogSynchronizer->synchronizeAllGymProfiles();

        $io->success(sprintf(
            'Catalogo aggiornato: %d attrezzature create, %d attrezzature aggiornate, %d esercizi creati, %d esercizi aggiornati, %d collegamenti palestra-attrezzatura creati.',
            $equipmentCreated,
            $equipmentUpdated,
            $exerciseCreated,
            $exerciseUpdated,
            $gymEquipmentLinksCreated
        ));

        if ($missingEquipmentSlugs !== []) {
            $io->warning('Alcuni esercizi non hanno trovato l\'attrezzatura di default: '.implode(', ', array_values(array_unique($missingEquipmentSlugs))).'. Gli esercizi sono stati comunque salvati.');
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{0:int,1:int,2:array<string,Equipment>}
     */
    private function upsertEquipmentCatalog(): array
    {
        $repository = $this->entityManager->getRepository(Equipment::class);
        $equipmentBySlug = [];
        $created = 0;
        $updated = 0;

        foreach (array_merge($this->requiredBaseEquipmentSeed(), FitnessCatalog::equipmentSeed()) as $item) {
            $equipment = $repository->findOneBy(['slug' => $item['slug']]);

            if (!$equipment instanceof Equipment) {
                $equipment = new Equipment();
                ++$created;
            } else {
                ++$updated;
            }

            $equipment
                ->setName($item['name'])
                ->setSlug($item['slug'])
                ->setType($item['type'])
                ->setDescription($item['description'])
                ->setUsageInstructions($item['usage'])
                ->setIsMachine($item['isMachine']);

            $this->entityManager->persist($equipment);
            $equipmentBySlug[$item['slug']] = $equipment;
        }

        return [$created, $updated, $equipmentBySlug];
    }

    /**
     * @param array<string,Equipment> $equipmentBySlug
     * @return array{0:int,1:int,2:list<string>}
     */
    private function upsertExerciseCatalog(array $equipmentBySlug): array
    {
        $repository = $this->entityManager->getRepository(Exercise::class);
        $equipmentRepository = $this->entityManager->getRepository(Equipment::class);
        $created = 0;
        $updated = 0;
        $missingEquipmentSlugs = [];

        foreach (FitnessCatalog::exerciseSeed() as $item) {
            $exercise = $repository->findOneBy(['slug' => $item['slug']]);

            if (!$exercise instanceof Exercise) {
                $exercise = new Exercise();
                ++$created;
            } else {
                ++$updated;
            }

            $defaultEquipment = null;
            if ($item['equipment'] !== null) {
                $defaultEquipment = $equipmentBySlug[$item['equipment']] ?? $equipmentRepository->findOneBy(['slug' => $item['equipment']]);

                if (!$defaultEquipment instanceof Equipment) {
                    $missingEquipmentSlugs[] = $item['equipment'];
                    $defaultEquipment = null;
                }
            }

            $exercise
                ->setName($item['name'])
                ->setSlug($item['slug'])
                ->setDescription($item['description'])
                ->setExecutionInstructions($item['instructions'])
                ->setPrimaryMuscles($item['primaryMuscles'])
                ->setSecondaryMuscles($item['secondaryMuscles'])
                ->setTrackingMode($item['trackingMode'])
                ->setExerciseType($item['exerciseType'])
                ->setDefaultEquipment($defaultEquipment)
                ->setSecondaryEquipmentNotes($item['secondaryEquipmentNotes'])
                ->setDefaultIncrementKg($item['incrementKg'])
                ->setIsFundamental($item['isFundamental']);

            $this->entityManager->persist($exercise);
        }

        return [$created, $updated, $missingEquipmentSlugs];
    }

    /**
     * @return list<array{name:string,slug:string,type:EquipmentType,description:string,usage:?string,isMachine:bool}>
     */
    private function requiredBaseEquipmentSeed(): array
    {
        return [
            ['name' => 'Cavi', 'slug' => 'cavi', 'type' => EquipmentType::Cable, 'description' => 'Stazione versatile per esercizi su quasi tutti i gruppi muscolari.', 'usage' => 'Regolare altezza, maniglia e carico in base all’esercizio. Assumere una posizione stabile.', 'isMachine' => true],
            ['name' => 'Corpo libero', 'slug' => 'corpo-libero', 'type' => EquipmentType::Bodyweight, 'description' => 'Esercizi eseguiti senza carico esterno.', 'usage' => 'Usare un’esecuzione controllata e scalare difficoltà tramite varianti.', 'isMachine' => false],
        ];
    }
}
