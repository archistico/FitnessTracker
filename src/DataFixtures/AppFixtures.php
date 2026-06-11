<?php

namespace App\DataFixtures;

use App\Entity\AppUser;
use App\Entity\Equipment;
use App\Entity\Exercise;
use App\Entity\GymEquipment;
use App\Entity\GymProfile;
use App\Entity\WorkoutPlan;
use App\Entity\WorkoutPlanExercise;
use App\Enum\EquipmentType;
use App\Enum\ExerciseTrackingMode;
use App\Enum\ExerciseType;
use App\Enum\ProgressionType;
use App\Enum\WorkoutGoal;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = (new AppUser())
            ->setName('Utente demo')
            ->setIsDefault(true);
        $manager->persist($user);

        $gym = (new GymProfile())
            ->setAppUser($user)
            ->setName('Palestra principale')
            ->setNotes('Profilo palestra iniziale. Disattivare le attrezzature non disponibili prima di creare le schede.');
        $manager->persist($gym);

        $equipmentBySlug = [];
        foreach ($this->equipmentSeed() as $item) {
            $equipment = (new Equipment())
                ->setName($item['name'])
                ->setSlug($item['slug'])
                ->setType($item['type'])
                ->setDescription($item['description'])
                ->setUsageInstructions($item['usage'])
                ->setIsMachine($item['isMachine']);

            $equipmentBySlug[$item['slug']] = $equipment;
            $manager->persist($equipment);

            $manager->persist((new GymEquipment())
                ->setGymProfile($gym)
                ->setEquipment($equipment)
                ->setIsAvailable(true));
        }

        $exerciseBySlug = [];
        foreach ($this->exerciseSeed() as $item) {
            $exercise = (new Exercise())
                ->setName($item['name'])
                ->setSlug($item['slug'])
                ->setDescription($item['description'])
                ->setExecutionInstructions($item['instructions'])
                ->setPrimaryMuscles($item['primaryMuscles'])
                ->setSecondaryMuscles($item['secondaryMuscles'])
                ->setTrackingMode($item['trackingMode'])
                ->setExerciseType($item['exerciseType'])
                ->setDefaultEquipment($equipmentBySlug[$item['equipment']] ?? null)
                ->setSecondaryEquipmentNotes($item['secondaryEquipmentNotes'] ?? null)
                ->setDefaultIncrementKg($item['incrementKg'])
                ->setIsFundamental($item['isFundamental']);

            $exerciseBySlug[$item['slug']] = $exercise;
            $manager->persist($exercise);
        }

        $this->loadWorkoutPlans($manager, $user, $exerciseBySlug);

        $manager->flush();
    }


    /** @param array<string, Exercise> $exerciseBySlug */
    private function loadWorkoutPlans(ObjectManager $manager, AppUser $user, array $exerciseBySlug): void
    {
        $legs = (new WorkoutPlan())
            ->setAppUser($user)
            ->setName('Lunedì - Gambe')
            ->setSlug('lunedi-gambe')
            ->setGoal(WorkoutGoal::StrengthHypertrophy)
            ->setSuggestedDayOfWeek(1)
            ->setDescription('Scheda seed di esempio: riscaldamento, fondamentale con Trenino e complementari macchina.');
        $manager->persist($legs);

        $this->addPlanExercise($manager, $legs, $exerciseBySlug['cyclette'], 1, ProgressionType::Cardio, null, null, null, 600, null, 'Riscaldamento iniziale.');
        $this->addPlanExercise($manager, $legs, $exerciseBySlug['squat-bilanciere'], 2, ProgressionType::TreninoInvictus, 4, null, null, null, 150, 'Fondamentale della giornata: usare progressione Trenino.');
        $this->addPlanExercise($manager, $legs, $exerciseBySlug['leg-press'], 3, ProgressionType::Fixed, 3, 10, 12, null, 120, null);
        $this->addPlanExercise($manager, $legs, $exerciseBySlug['leg-extension'], 4, ProgressionType::Fixed, 3, 12, 15, null, 90, null);

        $back = (new WorkoutPlan())
            ->setAppUser($user)
            ->setName('Mercoledì - Dorso e braccia')
            ->setSlug('mercoledi-dorso-braccia')
            ->setGoal(WorkoutGoal::Hypertrophy)
            ->setSuggestedDayOfWeek(3)
            ->setDescription('Scheda seed di esempio per tirate verticali/orizzontali e lavoro accessorio.');
        $manager->persist($back);

        $this->addPlanExercise($manager, $back, $exerciseBySlug['lat-machine-avanti-petto'], 1, ProgressionType::Fixed, 4, 8, 12, null, 120, null);
        $this->addPlanExercise($manager, $back, $exerciseBySlug['pulley-basso'], 2, ProgressionType::Fixed, 3, 8, 12, null, 120, null);
        $this->addPlanExercise($manager, $back, $exerciseBySlug['trazioni'], 3, ProgressionType::Manual, 3, null, null, null, 150, 'Registrare reps reali ed eventuale zavorra più avanti.');
    }

    private function addPlanExercise(
        ObjectManager $manager,
        WorkoutPlan $plan,
        Exercise $exercise,
        int $position,
        ProgressionType $progressionType,
        ?int $sets,
        ?int $repMin,
        ?int $repMax,
        ?int $durationSeconds,
        ?int $restSeconds,
        ?string $notes
    ): void {
        $manager->persist((new WorkoutPlanExercise())
            ->setWorkoutPlan($plan)
            ->setExercise($exercise)
            ->setPosition($position)
            ->setProgressionType($progressionType)
            ->setPlannedSets($sets)
            ->setPlannedRepMin($repMin)
            ->setPlannedRepMax($repMax)
            ->setPlannedDurationSeconds($durationSeconds)
            ->setPlannedRestSeconds($restSeconds)
            ->setNotes($notes));
    }

    /** @return list<array{name:string,slug:string,type:EquipmentType,description:string,usage:?string,isMachine:bool}> */
    private function equipmentSeed(): array
    {
        return [
            ['name' => 'Bilanciere', 'slug' => 'bilanciere', 'type' => EquipmentType::FreeWeight, 'description' => 'Attrezzo libero usato per esercizi multiarticolari e complementari.', 'usage' => 'Usare con dischi adeguati e blocchi di sicurezza quando necessari.', 'isMachine' => false],
            ['name' => 'Manubri', 'slug' => 'manubri', 'type' => EquipmentType::FreeWeight, 'description' => 'Pesi liberi usati per movimenti mono o multiarticolari.', 'usage' => 'Scegliere carichi gestibili e mantenere controllo in entrambe le fasi del movimento.', 'isMachine' => false],
            ['name' => 'Panca piana', 'slug' => 'panca-piana', 'type' => EquipmentType::Bench, 'description' => 'Panca orizzontale usata per distensioni, croci e appoggi.', 'usage' => 'Verificare stabilità e posizione prima di iniziare la serie.', 'isMachine' => false],
            ['name' => 'Rack / supporti squat', 'slug' => 'rack-supporti-squat', 'type' => EquipmentType::Accessory, 'description' => 'Supporto per bilanciere, utile per squat e panca.', 'usage' => 'Impostare altezza dei supporti e sicurezze in base all’esercizio.', 'isMachine' => false],
            ['name' => 'Multipower', 'slug' => 'multipower', 'type' => EquipmentType::Machine, 'description' => 'Macchina con bilanciere vincolato su guide.', 'usage' => 'Regolare i fermi di sicurezza e mantenere una traiettoria controllata.', 'isMachine' => true],
            ['name' => 'Lat machine', 'slug' => 'lat-machine', 'type' => EquipmentType::Machine, 'description' => 'Attrezzo ideato per allenare principalmente i dorsali. Nel movimento vengono coinvolti anche bicipiti e deltoide posteriore.', 'usage' => 'Sedersi sotto il cavo, busto eretto, petto in fuori e gambe bloccate. Tirare la sbarra verso il petto controllando la risalita.', 'isMachine' => true],
            ['name' => 'Pulley', 'slug' => 'pulley', 'type' => EquipmentType::Machine, 'description' => 'Attrezzo per tirate orizzontali che stimola romboidi, trapezio medio, gran dorsale e grande rotondo.', 'usage' => 'Sedersi con piedi sui supporti e tronco perpendicolare al terreno. Tirare la maniglia verso di sé mantenendo schiena neutra.', 'isMachine' => true],
            ['name' => 'Leg extension', 'slug' => 'leg-extension', 'type' => EquipmentType::Machine, 'description' => 'Macchina creata per l’allenamento dei quadricipiti.', 'usage' => 'Sedersi con schiena appoggiata, regolare il rullo e distendere le ginocchia controllando il ritorno.', 'isMachine' => true],
            ['name' => 'Pressa', 'slug' => 'pressa', 'type' => EquipmentType::Machine, 'description' => 'Attrezzo per l’allenamento delle gambe con traiettoria guidata simile allo squat.', 'usage' => 'Posizionare i piedi sulla pedana, mantenere la schiena appoggiata e controllare discesa e spinta.', 'isMachine' => true],
            ['name' => 'Cavi', 'slug' => 'cavi', 'type' => EquipmentType::Cable, 'description' => 'Stazione versatile per esercizi su quasi tutti i gruppi muscolari.', 'usage' => 'Regolare altezza, maniglia e carico in base all’esercizio. Assumere una posizione stabile.', 'isMachine' => true],
            ['name' => 'Corpo libero', 'slug' => 'corpo-libero', 'type' => EquipmentType::Bodyweight, 'description' => 'Esercizi eseguiti senza carico esterno.', 'usage' => 'Usare un’esecuzione controllata e scalare difficoltà tramite varianti.', 'isMachine' => false],
            ['name' => 'Cyclette', 'slug' => 'cyclette', 'type' => EquipmentType::Cardio, 'description' => 'Macchina cardio a pedalata, utile per riscaldamento e lavoro aerobico.', 'usage' => 'Regolare sella e resistenza, registrando durata e intensità.', 'isMachine' => true],
            ['name' => 'Vogatore', 'slug' => 'vogatore', 'type' => EquipmentType::Cardio, 'description' => 'Attrezzo utile per lavoro cardiovascolare e preparazione atletica, con forte coinvolgimento della catena posteriore.', 'usage' => 'Coordinare spinta di gambe, estensione del busto e tirata delle braccia.', 'isMachine' => true],
            ['name' => 'Gradino / box', 'slug' => 'gradino-box', 'type' => EquipmentType::Accessory, 'description' => 'Supporto usato per step-up, salite e varianti a corpo libero.', 'usage' => 'Scegliere un’altezza stabile e compatibile con il controllo del movimento.', 'isMachine' => false],
        ];
    }

    /** @return list<array<string,mixed>> */
    private function exerciseSeed(): array
    {
        return [
            ['name' => 'Panca piana con bilanciere', 'slug' => 'panca-piana-bilanciere', 'description' => 'Distensione orizzontale con bilanciere, fondamentale per pettorali, tricipiti e deltoide anteriore.', 'instructions' => 'Sdraiarsi sulla panca, scapole addotte, piedi stabili. Scendere controllando il bilanciere verso il petto e spingere mantenendo traiettoria stabile.', 'primaryMuscles' => ['pettorali'], 'secondaryMuscles' => ['tricipiti', 'deltoide anteriore'], 'trackingMode' => ExerciseTrackingMode::WeightReps, 'exerciseType' => ExerciseType::Strength, 'equipment' => 'bilanciere', 'secondaryEquipmentNotes' => 'Richiede panca piana e preferibilmente rack/supporti.', 'incrementKg' => 2.5, 'isFundamental' => true],
            ['name' => 'Squat con bilanciere', 'slug' => 'squat-bilanciere', 'description' => 'Esercizio fondamentale per arti inferiori e core.', 'instructions' => 'Posizionare il bilanciere in modo stabile, scendere mantenendo controllo del tronco e risalire spingendo con piedi ben appoggiati.', 'primaryMuscles' => ['quadricipiti', 'glutei'], 'secondaryMuscles' => ['femorali', 'core'], 'trackingMode' => ExerciseTrackingMode::WeightReps, 'exerciseType' => ExerciseType::Strength, 'equipment' => 'rack-supporti-squat', 'incrementKg' => 5.0, 'isFundamental' => true],
            ['name' => 'Stacco da terra', 'slug' => 'stacco-da-terra', 'description' => 'Tirata fondamentale da terra con forte coinvolgimento della catena posteriore.', 'instructions' => 'Posizionare il bilanciere vicino al corpo, mantenere schiena neutra e spingere il pavimento portando il carico in chiusura.', 'primaryMuscles' => ['femorali', 'glutei', 'dorsali'], 'secondaryMuscles' => ['avambracci', 'core'], 'trackingMode' => ExerciseTrackingMode::WeightReps, 'exerciseType' => ExerciseType::Strength, 'equipment' => 'bilanciere', 'incrementKg' => 5.0, 'isFundamental' => true],
            ['name' => 'Military press con bilanciere', 'slug' => 'military-press-bilanciere', 'description' => 'Spinta verticale fondamentale per le spalle.', 'instructions' => 'Partire con bilanciere davanti alle spalle, addome attivo e spingere sopra la testa senza compensare con iperestensione lombare.', 'primaryMuscles' => ['spalle'], 'secondaryMuscles' => ['tricipiti', 'core'], 'trackingMode' => ExerciseTrackingMode::WeightReps, 'exerciseType' => ExerciseType::Strength, 'equipment' => 'bilanciere', 'incrementKg' => 2.5, 'isFundamental' => true],
            ['name' => 'Rematore con bilanciere a busto flesso', 'slug' => 'rematore-bilanciere-busto-flesso', 'description' => 'Tirata orizzontale libera per dorsali e parte centrale della schiena.', 'instructions' => 'Inclinare il busto mantenendo schiena neutra, tirare il bilanciere verso il corpo e controllare la discesa.', 'primaryMuscles' => ['dorsali', 'romboidi'], 'secondaryMuscles' => ['bicipiti', 'deltoide posteriore'], 'trackingMode' => ExerciseTrackingMode::WeightReps, 'exerciseType' => ExerciseType::Strength, 'equipment' => 'bilanciere', 'incrementKg' => 2.5, 'isFundamental' => true],
            ['name' => 'Trazioni', 'slug' => 'trazioni', 'description' => 'Esercizio a corpo libero di tirata verticale.', 'instructions' => 'Partire da braccia distese, tirarsi verso la sbarra controllando scapole e gomiti. Registrare reps e, se presente, zavorra.', 'primaryMuscles' => ['dorsali'], 'secondaryMuscles' => ['bicipiti', 'deltoide posteriore'], 'trackingMode' => ExerciseTrackingMode::BodyweightReps, 'exerciseType' => ExerciseType::Bodyweight, 'equipment' => 'corpo-libero', 'incrementKg' => 2.5, 'isFundamental' => true],
            ['name' => 'Lat machine avanti al petto', 'slug' => 'lat-machine-avanti-petto', 'description' => 'Tirata verticale alla macchina per allenare principalmente i dorsali.', 'instructions' => 'Sedersi sotto il cavo, petto in fuori, gambe bloccate. Tirare la sbarra verso il petto e controllare la risalita.', 'primaryMuscles' => ['dorsali'], 'secondaryMuscles' => ['bicipiti', 'deltoide posteriore'], 'trackingMode' => ExerciseTrackingMode::WeightReps, 'exerciseType' => ExerciseType::Machine, 'equipment' => 'lat-machine', 'incrementKg' => 5.0, 'isFundamental' => false],
            ['name' => 'Pulley basso', 'slug' => 'pulley-basso', 'description' => 'Tirata orizzontale al pulley per schiena centrale e dorsali.', 'instructions' => 'Sedersi con piedi sui supporti, tronco stabile e tirare la maniglia portando i gomiti indietro.', 'primaryMuscles' => ['romboidi', 'trapezio medio', 'dorsali'], 'secondaryMuscles' => ['bicipiti'], 'trackingMode' => ExerciseTrackingMode::WeightReps, 'exerciseType' => ExerciseType::Machine, 'equipment' => 'pulley', 'incrementKg' => 5.0, 'isFundamental' => false],
            ['name' => 'Leg extension', 'slug' => 'leg-extension', 'description' => 'Esercizio alla macchina per l’allenamento dei quadricipiti.', 'instructions' => 'Seduto con schiena appoggiata, estendere le ginocchia senza slanci e controllare il ritorno.', 'primaryMuscles' => ['quadricipiti'], 'secondaryMuscles' => [], 'trackingMode' => ExerciseTrackingMode::WeightReps, 'exerciseType' => ExerciseType::Machine, 'equipment' => 'leg-extension', 'incrementKg' => 5.0, 'isFundamental' => false],
            ['name' => 'Leg press', 'slug' => 'leg-press', 'description' => 'Esercizio guidato per le gambe alla pressa.', 'instructions' => 'Piedi sulla pedana, schiena appoggiata, discesa controllata e spinta senza bloccare violentemente le ginocchia.', 'primaryMuscles' => ['quadricipiti', 'glutei'], 'secondaryMuscles' => ['femorali'], 'trackingMode' => ExerciseTrackingMode::WeightReps, 'exerciseType' => ExerciseType::Machine, 'equipment' => 'pressa', 'incrementKg' => 10.0, 'isFundamental' => false],
            ['name' => 'Cyclette', 'slug' => 'cyclette', 'description' => 'Lavoro cardio a pedalata, utile anche come riscaldamento iniziale.', 'instructions' => 'Regolare sella e resistenza. Registrare durata, livello e percezione dello sforzo.', 'primaryMuscles' => ['cardio'], 'secondaryMuscles' => ['quadricipiti'], 'trackingMode' => ExerciseTrackingMode::CardioMachine, 'exerciseType' => ExerciseType::Cardio, 'equipment' => 'cyclette', 'incrementKg' => 0.0, 'isFundamental' => false],
            ['name' => 'Addominali a terra', 'slug' => 'addominali-a-terra', 'description' => 'Esercizio a corpo libero per il core, registrato principalmente a ripetizioni.', 'instructions' => 'Eseguire reps controllate evitando slanci. Annotare eventuale fastidio lombare.', 'primaryMuscles' => ['addome'], 'secondaryMuscles' => [], 'trackingMode' => ExerciseTrackingMode::RepsOnly, 'exerciseType' => ExerciseType::Bodyweight, 'equipment' => 'corpo-libero', 'incrementKg' => 0.0, 'isFundamental' => false],
            ['name' => 'Step sul gradino', 'slug' => 'step-sul-gradino', 'description' => 'Esercizio a corpo libero o con sovraccarico per arti inferiori.', 'instructions' => 'Salire sul gradino in modo controllato, stabilizzare il ginocchio e scendere senza cadute o rimbalzi.', 'primaryMuscles' => ['quadricipiti', 'glutei'], 'secondaryMuscles' => ['polpacci'], 'trackingMode' => ExerciseTrackingMode::RepsOnly, 'exerciseType' => ExerciseType::Accessory, 'equipment' => 'gradino-box', 'incrementKg' => 0.0, 'isFundamental' => false],
        ];
    }
}
