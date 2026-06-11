<?php

namespace App\Controller;

use App\Entity\Exercise;
use App\Entity\WorkoutPlan;
use App\Entity\WorkoutPlanExercise;
use App\Enum\ExerciseTrackingMode;
use App\Enum\ProgressionType;
use App\Enum\WorkoutGoal;
use App\Service\CurrentUserProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/workout-plans')]
final class WorkoutPlanController extends AbstractController
{
    #[Route('', name: 'app_workout_plan_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): Response
    {
        $currentUser = $currentUserProvider->getUser();

        /** @var list<WorkoutPlan> $plans */
        $plans = $entityManager->getRepository(WorkoutPlan::class)->findBy(
            ['appUser' => $currentUser],
            ['isActive' => 'DESC', 'name' => 'ASC']
        );

        return $this->render('workout_plan/index.html.twig', [
            'currentUser' => $currentUser,
            'plans' => $plans,
        ]);
    }

    #[Route('/new', name: 'app_workout_plan_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): Response
    {
        $currentUser = $currentUserProvider->getUser();
        $plan = (new WorkoutPlan())
            ->setAppUser($currentUser)
            ->setIsActive(true);
        $formErrors = [];
        $formData = [];
        $formSubmitted = $request->isMethod('POST');

        if ($formSubmitted) {
            $formData = $request->request->all();
            $formErrors = $this->fillPlanFromRequest($plan, $request, $entityManager);

            if ($formErrors === []) {
                $entityManager->persist($plan);
                $entityManager->flush();

                $this->addFlash('success', 'Scheda creata. Ora puoi aggiungere gli esercizi.');

                return $this->redirectToRoute('app_workout_plan_show', ['slug' => $plan->getSlug()]);
            }

            foreach ($formErrors as $error) {
                $this->addFlash('danger', $error);
            }
        }

        return $this->renderPlanForm($currentUser, $plan, 'new', $formErrors, $formData, $formSubmitted);
    }

    #[Route('/{slug}/edit', name: 'app_workout_plan_edit', methods: ['GET', 'POST'])]
    public function edit(string $slug, Request $request, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): Response
    {
        $currentUser = $currentUserProvider->getUser();
        $plan = $this->findPlan($entityManager, $currentUserProvider, $slug);
        $formErrors = [];
        $formData = [];
        $formSubmitted = $request->isMethod('POST');

        if ($formSubmitted) {
            $formData = $request->request->all();
            $formErrors = $this->fillPlanFromRequest($plan, $request, $entityManager);

            if ($formErrors === []) {
                $entityManager->flush();

                $this->addFlash('success', sprintf('Scheda "%s" aggiornata.', $plan->getName()));

                return $this->redirectToRoute('app_workout_plan_show', ['slug' => $plan->getSlug()]);
            }

            foreach ($formErrors as $error) {
                $this->addFlash('danger', $error);
            }
        }

        return $this->renderPlanForm($currentUser, $plan, 'edit', $formErrors, $formData, $formSubmitted);
    }

    #[Route('/{slug}/duplicate', name: 'app_workout_plan_duplicate', methods: ['POST'])]
    public function duplicate(string $slug, Request $request, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): RedirectResponse
    {
        $sourcePlan = $this->findPlan($entityManager, $currentUserProvider, $slug);

        if (!$this->isCsrfTokenValid('duplicate_workout_plan_'.$sourcePlan->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF non valido.');
        }

        $copy = (new WorkoutPlan())
            ->setAppUser($sourcePlan->getAppUser())
            ->setName($this->buildCopyName($entityManager, $sourcePlan))
            ->setDescription($sourcePlan->getDescription())
            ->setGoal($sourcePlan->getGoal())
            ->setSuggestedDayOfWeek($sourcePlan->getSuggestedDayOfWeek())
            ->setIsActive(false);

        $copy->setSlug($this->buildUniqueSlug($entityManager, $copy->getName()));
        $entityManager->persist($copy);

        foreach ($sourcePlan->getExercises() as $sourceItem) {
            $copyItem = (new WorkoutPlanExercise())
                ->setWorkoutPlan($copy)
                ->setExercise($sourceItem->getExercise())
                ->setPosition($sourceItem->getPosition())
                ->setProgressionType($sourceItem->getProgressionType())
                ->setPlannedSets($sourceItem->getPlannedSets())
                ->setPlannedRepMin($sourceItem->getPlannedRepMin())
                ->setPlannedRepMax($sourceItem->getPlannedRepMax())
                ->setPlannedDurationSeconds($sourceItem->getPlannedDurationSeconds())
                ->setPlannedRestSeconds($sourceItem->getPlannedRestSeconds())
                ->setNotes($sourceItem->getNotes());

            $entityManager->persist($copyItem);
        }

        $entityManager->flush();

        $this->addFlash('success', sprintf('Scheda duplicata da "%s". La copia è disattivata finché non la controlli.', $sourcePlan->getName()));

        return $this->redirectToRoute('app_workout_plan_show', ['slug' => $copy->getSlug()]);
    }

    #[Route('/{slug}/delete', name: 'app_workout_plan_delete', methods: ['POST'])]
    public function delete(string $slug, Request $request, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): RedirectResponse
    {
        $plan = $this->findPlan($entityManager, $currentUserProvider, $slug);

        if (!$this->isCsrfTokenValid('delete_workout_plan_'.$plan->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF non valido.');
        }

        $name = $plan->getName();
        $entityManager->remove($plan);
        $entityManager->flush();

        $this->addFlash('warning', sprintf('Scheda "%s" eliminata. Le sessioni storiche restano nel diario ma senza collegamento alla scheda.', $name));

        return $this->redirectToRoute('app_workout_plan_index');
    }

    #[Route('/{slug}', name: 'app_workout_plan_show', methods: ['GET'])]
    public function show(string $slug, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): Response
    {
        $plan = $this->findPlan($entityManager, $currentUserProvider, $slug);

        /** @var list<Exercise> $exercises */
        $exercises = $entityManager->getRepository(Exercise::class)->findBy([], ['name' => 'ASC']);

        return $this->render('workout_plan/show.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'plan' => $plan,
            'exercises' => $exercises,
            'progressionTypes' => ProgressionType::cases(),
        ]);
    }

    #[Route('/{slug}/toggle-active', name: 'app_workout_plan_toggle_active', methods: ['POST'])]
    public function toggleActive(string $slug, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): RedirectResponse
    {
        $plan = $this->findPlan($entityManager, $currentUserProvider, $slug);
        $plan->setIsActive(!$plan->isActive());
        $entityManager->flush();

        return $this->redirectToRoute('app_workout_plan_index');
    }

    #[Route('/{slug}/exercises', name: 'app_workout_plan_add_exercise', methods: ['POST'])]
    public function addExercise(string $slug, Request $request, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): RedirectResponse
    {
        $plan = $this->findPlan($entityManager, $currentUserProvider, $slug);
        $exercise = $entityManager->getRepository(Exercise::class)->find((int) $request->request->get('exerciseId'));

        if (!$exercise instanceof Exercise) {
            $this->addFlash('danger', 'Esercizio non valido.');

            return $this->redirectToRoute('app_workout_plan_show', ['slug' => $plan->getSlug()]);
        }

        $validationErrors = $this->validatePlanExerciseRequest($exercise, $request);
        if ($validationErrors !== []) {
            foreach ($validationErrors as $error) {
                $this->addFlash('danger', $error);
            }

            return $this->redirectToRoute('app_workout_plan_show', [
                'slug' => $plan->getSlug(),
                '_fragment' => 'add-exercise',
            ]);
        }

        $item = (new WorkoutPlanExercise())
            ->setWorkoutPlan($plan)
            ->setExercise($exercise)
            ->setPosition($plan->getNextPosition());

        $this->fillPlanExerciseFromRequest($item, $request);

        $entityManager->persist($item);
        $entityManager->flush();

        $this->addFlash('success', 'Esercizio aggiunto alla scheda.');

        return $this->redirectToRoute('app_workout_plan_show', ['slug' => $plan->getSlug(), '_fragment' => 'item-' . $item->getId()]);
    }

    #[Route('/{slug}/exercises/{itemId}/edit', name: 'app_workout_plan_edit_exercise', methods: ['POST'])]
    public function editExercise(string $slug, int $itemId, Request $request, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): RedirectResponse
    {
        $plan = $this->findPlan($entityManager, $currentUserProvider, $slug);
        $item = $this->findPlanExercise($entityManager, $plan, $itemId);

        $validationErrors = $this->validatePlanExerciseRequest($item->getExercise(), $request);
        if ($validationErrors !== []) {
            foreach ($validationErrors as $error) {
                $this->addFlash('danger', $error);
            }

            return $this->redirectToRoute('app_workout_plan_show', [
                'slug' => $plan->getSlug(),
                '_fragment' => 'item-' . $item->getId(),
            ]);
        }

        $this->fillPlanExerciseFromRequest($item, $request);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Riga "%s" aggiornata.', $item->getExercise()->getName()));

        return $this->redirectToRoute('app_workout_plan_show', ['slug' => $plan->getSlug(), '_fragment' => 'item-' . $item->getId()]);
    }

    #[Route('/{slug}/exercises/{itemId}/move-up', name: 'app_workout_plan_exercise_move_up', methods: ['POST'])]
    public function moveExerciseUp(string $slug, int $itemId, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): RedirectResponse
    {
        $plan = $this->findPlan($entityManager, $currentUserProvider, $slug);
        $item = $this->findPlanExercise($entityManager, $plan, $itemId);
        $this->swapWithNeighbor($plan, $item, -1);
        $entityManager->flush();

        return $this->redirectToRoute('app_workout_plan_show', ['slug' => $plan->getSlug(), '_fragment' => 'item-' . $item->getId()]);
    }

    #[Route('/{slug}/exercises/{itemId}/move-down', name: 'app_workout_plan_exercise_move_down', methods: ['POST'])]
    public function moveExerciseDown(string $slug, int $itemId, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): RedirectResponse
    {
        $plan = $this->findPlan($entityManager, $currentUserProvider, $slug);
        $item = $this->findPlanExercise($entityManager, $plan, $itemId);
        $this->swapWithNeighbor($plan, $item, 1);
        $entityManager->flush();

        return $this->redirectToRoute('app_workout_plan_show', ['slug' => $plan->getSlug(), '_fragment' => 'item-' . $item->getId()]);
    }

    #[Route('/{slug}/exercises/{itemId}/remove', name: 'app_workout_plan_remove_exercise', methods: ['POST'])]
    public function removeExercise(string $slug, int $itemId, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): RedirectResponse
    {
        $plan = $this->findPlan($entityManager, $currentUserProvider, $slug);
        $item = $entityManager->getRepository(WorkoutPlanExercise::class)->find($itemId);

        if ($item instanceof WorkoutPlanExercise && $item->getWorkoutPlan() === $plan) {
            $entityManager->remove($item);
            $entityManager->flush();
            $this->renumberPlanExercises($plan);
            $entityManager->flush();
            $this->addFlash('success', 'Esercizio rimosso dalla scheda.');
        }

        return $this->redirectToRoute('app_workout_plan_show', ['slug' => $plan->getSlug()]);
    }

    private function renderPlanForm(object $currentUser, WorkoutPlan $plan, string $mode, array $formErrors = [], array $formData = [], bool $formSubmitted = false): Response
    {
        return $this->render('workout_plan/form.html.twig', [
            'currentUser' => $currentUser,
            'plan' => $plan,
            'goals' => WorkoutGoal::cases(),
            'formErrors' => $formErrors,
            'formData' => $formData,
            'formSubmitted' => $formSubmitted,
            'mode' => $mode,
        ]);
    }

    /** @return list<string> */
    private function fillPlanFromRequest(WorkoutPlan $plan, Request $request, EntityManagerInterface $entityManager): array
    {
        $name = trim((string) $request->request->get('name'));
        $slug = trim((string) $request->request->get('slug'));
        $description = trim((string) $request->request->get('description'));
        $goal = WorkoutGoal::tryFrom((string) $request->request->get('goal')) ?? WorkoutGoal::General;
        $suggestedDay = $this->nullablePositiveInt($request->request->get('suggestedDayOfWeek'));
        $isActive = $request->request->has('isActive');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Il nome della scheda è obbligatorio.';
        }

        if ($slug === '' && $name !== '') {
            $slug = $this->slugify($name);
        } else {
            $slug = $this->slugify($slug);
        }

        if ($slug === '') {
            $errors['slug'] = 'Lo slug non può essere vuoto.';
        } elseif (!$this->isSlugAvailable($entityManager, $slug, $plan)) {
            $errors['slug'] = sprintf('Lo slug "%s" è già usato da un’altra scheda.', $slug);
        }

        if ($errors !== []) {
            return $errors;
        }

        $plan
            ->setName($name)
            ->setSlug($slug)
            ->setDescription($description !== '' ? $description : null)
            ->setGoal($goal)
            ->setSuggestedDayOfWeek($suggestedDay)
            ->setIsActive($isActive);

        return [];
    }

    /** @return list<string> */
    private function validatePlanExerciseRequest(Exercise $exercise, Request $request): array
    {
        $progressionType = ProgressionType::tryFrom((string) $request->request->get('progressionType')) ?? ProgressionType::Fixed;
        $trackingMode = $exercise->getTrackingMode();

        $plannedSets = $this->nullablePositiveInt($request->request->get('plannedSets'));
        $plannedRepMin = $this->nullablePositiveInt($request->request->get('plannedRepMin'));
        $plannedRepMax = $this->nullablePositiveInt($request->request->get('plannedRepMax'));
        $plannedDurationSeconds = $this->nullablePositiveInt($request->request->get('plannedDurationSeconds'));
        $plannedRestSeconds = $this->nullablePositiveInt($request->request->get('plannedRestSeconds'));

        $errors = [];

        if ($plannedRepMin !== null && $plannedRepMax !== null && $plannedRepMin > $plannedRepMax) {
            $errors[] = 'Il range ripetizioni non è valido: Rep min non può essere maggiore di Rep max.';
        }

        if ($this->hasInvalidPositiveInteger($request->request->get('plannedSets'))) {
            $errors[] = 'Il numero di serie deve essere maggiore di zero.';
        }

        if ($this->hasInvalidPositiveInteger($request->request->get('plannedRepMin'))) {
            $errors[] = 'Rep min deve essere maggiore di zero.';
        }

        if ($this->hasInvalidPositiveInteger($request->request->get('plannedRepMax'))) {
            $errors[] = 'Rep max deve essere maggiore di zero.';
        }

        if ($this->hasInvalidPositiveInteger($request->request->get('plannedDurationSeconds'))) {
            $errors[] = 'La durata deve essere maggiore di zero.';
        }

        if ($this->hasInvalidPositiveInteger($request->request->get('plannedRestSeconds'))) {
            $errors[] = 'Il recupero deve essere maggiore di zero.';
        }

        if ($progressionType === ProgressionType::TreninoInvictus) {
            if (!$trackingMode->usesWeight() || !$trackingMode->usesReps()) {
                $errors[] = 'Trenino Invictus può essere usato solo con esercizi registrati come Peso + reps.';
            }

            return $errors;
        }

        if ($progressionType === ProgressionType::TimeBased) {
            if (!$trackingMode->usesDuration()) {
                $errors[] = 'La progressione A tempo richiede un esercizio con durata.';
            }

            if ($plannedDurationSeconds === null) {
                $errors[] = 'Per la progressione A tempo devi indicare la durata.';
            }

            if (in_array($trackingMode, [ExerciseTrackingMode::Time, ExerciseTrackingMode::IsometricTime], true) && $plannedSets === null) {
                $errors[] = 'Per esercizi a tempo o isometrici devi indicare anche il numero di serie.';
            }

            return $errors;
        }

        if ($progressionType === ProgressionType::Cardio) {
            if (!in_array($trackingMode, [ExerciseTrackingMode::CardioMachine, ExerciseTrackingMode::TimeDistance], true)) {
                $errors[] = 'La progressione Cardio richiede un esercizio Cardio macchina oppure Tempo + distanza.';
            }

            if ($plannedDurationSeconds === null) {
                $errors[] = 'Per la progressione Cardio devi indicare la durata.';
            }

            return $errors;
        }

        if ($progressionType === ProgressionType::None) {
            return $errors;
        }

        if ($trackingMode->usesReps()) {
            if ($plannedSets === null) {
                $errors[] = 'Per esercizi a ripetizioni devi indicare il numero di serie.';
            }

            if ($plannedRepMin === null) {
                $errors[] = 'Per esercizi a ripetizioni devi indicare almeno Rep min.';
            }
        }

        if ($trackingMode->usesDuration()) {
            if ($plannedDurationSeconds === null) {
                $errors[] = 'Per esercizi basati sulla durata devi indicare la durata.';
            }

            if (in_array($trackingMode, [ExerciseTrackingMode::Time, ExerciseTrackingMode::IsometricTime], true) && $plannedSets === null) {
                $errors[] = 'Per esercizi a tempo o isometrici devi indicare il numero di serie.';
            }
        }

        return $errors;
    }

    private function fillPlanExerciseFromRequest(WorkoutPlanExercise $item, Request $request): void
    {
        $item
            ->setProgressionType(ProgressionType::tryFrom((string) $request->request->get('progressionType')) ?? ProgressionType::Fixed)
            ->setPlannedSets($this->nullablePositiveInt($request->request->get('plannedSets')))
            ->setPlannedRepMin($this->nullablePositiveInt($request->request->get('plannedRepMin')))
            ->setPlannedRepMax($this->nullablePositiveInt($request->request->get('plannedRepMax')))
            ->setPlannedDurationSeconds($this->nullablePositiveInt($request->request->get('plannedDurationSeconds')))
            ->setPlannedRestSeconds($this->nullablePositiveInt($request->request->get('plannedRestSeconds')))
            ->setNotes($request->request->get('notes') !== null ? (string) $request->request->get('notes') : null);
    }

    private function findPlan(EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider, string $slug): WorkoutPlan
    {
        $plan = $entityManager->getRepository(WorkoutPlan::class)->findOneBy([
            'slug' => $slug,
            'appUser' => $currentUserProvider->getUser(),
        ]);

        if (!$plan instanceof WorkoutPlan) {
            throw $this->createNotFoundException('Scheda non trovata.');
        }

        return $plan;
    }

    private function findPlanExercise(EntityManagerInterface $entityManager, WorkoutPlan $plan, int $itemId): WorkoutPlanExercise
    {
        $item = $entityManager->getRepository(WorkoutPlanExercise::class)->find($itemId);

        if (!$item instanceof WorkoutPlanExercise || $item->getWorkoutPlan() !== $plan) {
            throw $this->createNotFoundException('Riga esercizio non trovata.');
        }

        return $item;
    }

    private function swapWithNeighbor(WorkoutPlan $plan, WorkoutPlanExercise $item, int $direction): void
    {
        $items = $plan->getExercises()->toArray();
        usort($items, static fn (WorkoutPlanExercise $a, WorkoutPlanExercise $b): int => $a->getPosition() <=> $b->getPosition());

        $currentIndex = array_search($item, $items, true);
        if ($currentIndex === false) {
            return;
        }

        $neighborIndex = $currentIndex + $direction;
        if (!isset($items[$neighborIndex])) {
            return;
        }

        $neighbor = $items[$neighborIndex];
        $currentPosition = $item->getPosition();
        $item->setPosition($neighbor->getPosition());
        $neighbor->setPosition($currentPosition);
    }

    private function renumberPlanExercises(WorkoutPlan $plan): void
    {
        $items = $plan->getExercises()->toArray();
        usort($items, static fn (WorkoutPlanExercise $a, WorkoutPlanExercise $b): int => $a->getPosition() <=> $b->getPosition());

        $position = 1;
        foreach ($items as $item) {
            $item->setPosition($position);
            ++$position;
        }
    }

    private function isSlugAvailable(EntityManagerInterface $entityManager, string $slug, WorkoutPlan $currentPlan): bool
    {
        $existing = $entityManager->getRepository(WorkoutPlan::class)->findOneBy(['slug' => $slug]);

        return !$existing instanceof WorkoutPlan || $existing === $currentPlan;
    }

    private function hasInvalidPositiveInteger(mixed $value): bool
    {
        if ($value === null || trim((string) $value) === '') {
            return false;
        }

        return (int) $value <= 0;
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function slugify(string $value): string
    {
        $slugger = new AsciiSlugger('it');
        $slug = strtolower((string) $slugger->slug(trim($value)));

        return $slug !== '' ? $slug : 'scheda';
    }
}
