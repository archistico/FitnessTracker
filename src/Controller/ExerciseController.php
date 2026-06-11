<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Entity\Exercise;
use App\Entity\GymEquipment;
use App\Enum\ExerciseTrackingMode;
use App\Enum\ExerciseType;
use App\Service\AvailableExerciseFilter;
use App\Service\CurrentUserProvider;
use App\Service\GymProfileProvider;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/exercises')]
final class ExerciseController extends AbstractController
{
    #[Route('', name: 'app_exercise_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
        GymProfileProvider $gymProfileProvider,
        AvailableExerciseFilter $availableExerciseFilter,
    ): Response {
        /** @var list<Exercise> $exercises */
        $exercises = $entityManager->getRepository(Exercise::class)->findBy([], ['name' => 'ASC']);
        $availableOnly = $request->query->getBoolean('available');

        $availableEquipmentSlugs = $this->getAvailableEquipmentSlugs($entityManager, $gymProfileProvider);
        $filteredExercises = $availableExerciseFilter->filter($exercises, $availableEquipmentSlugs, $availableOnly);

        return $this->render('exercise/index.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'exercises' => $filteredExercises,
            'availableOnly' => $availableOnly,
            'availableEquipmentSlugs' => $availableEquipmentSlugs,
        ]);
    }

    #[Route('/new', name: 'app_exercise_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
    ): Response {
        $exercise = new Exercise();
        $formErrors = [];
        $formData = [];
        $formSubmitted = $request->isMethod('POST');

        if ($formSubmitted) {
            $formData = $request->request->all();
            $formErrors = $this->fillExerciseFromRequest($exercise, $request, $entityManager);

            if ($formErrors === []) {
                $entityManager->persist($exercise);
                $entityManager->flush();

                $this->addFlash('success', sprintf('Esercizio "%s" creato.', $exercise->getName()));

                return $this->redirectToRoute('app_exercise_show', ['slug' => $exercise->getSlug()]);
            }

            foreach ($formErrors as $error) {
                $this->addFlash('danger', $error);
            }
        }

        return $this->renderExerciseForm($currentUserProvider, $entityManager, $exercise, 'new', $formErrors, $formData, $formSubmitted);
    }

    #[Route('/{slug}/edit', name: 'app_exercise_edit', methods: ['GET', 'POST'])]
    public function edit(
        string $slug,
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
    ): Response {
        $exercise = $this->findExerciseBySlug($entityManager, $slug);
        $formErrors = [];
        $formData = [];
        $formSubmitted = $request->isMethod('POST');

        if ($formSubmitted) {
            $formData = $request->request->all();
            $formErrors = $this->fillExerciseFromRequest($exercise, $request, $entityManager);

            if ($formErrors === []) {
                $entityManager->flush();

                $this->addFlash('success', sprintf('Esercizio "%s" aggiornato.', $exercise->getName()));

                return $this->redirectToRoute('app_exercise_show', ['slug' => $exercise->getSlug()]);
            }

            foreach ($formErrors as $error) {
                $this->addFlash('danger', $error);
            }
        }

        return $this->renderExerciseForm($currentUserProvider, $entityManager, $exercise, 'edit', $formErrors, $formData, $formSubmitted);
    }

    #[Route('/{slug}/delete', name: 'app_exercise_delete', methods: ['POST'])]
    public function delete(string $slug, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $exercise = $this->findExerciseBySlug($entityManager, $slug);

        if (!$this->isCsrfTokenValid('delete_exercise_'.$exercise->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF non valido.');
        }

        $name = $exercise->getName();

        try {
            $entityManager->remove($exercise);
            $entityManager->flush();
            $this->addFlash('warning', sprintf('Esercizio "%s" eliminato.', $name));
        } catch (ForeignKeyConstraintViolationException) {
            $entityManager->clear();
            $this->addFlash('danger', sprintf('Non posso eliminare "%s" perché è già usato in schede, sessioni o calibrazioni. Per ora modificalo invece di eliminarlo.', $name));
        }

        return $this->redirectToRoute('app_exercise_index');
    }

    #[Route('/{slug}', name: 'app_exercise_show', methods: ['GET'])]
    public function show(
        string $slug,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
        GymProfileProvider $gymProfileProvider,
    ): Response {
        $exercise = $this->findExerciseBySlug($entityManager, $slug);

        $availableEquipmentSlugs = $this->getAvailableEquipmentSlugs($entityManager, $gymProfileProvider);
        $equipment = $exercise->getDefaultEquipment();
        $isAvailable = $equipment === null || in_array($equipment->getSlug(), $availableEquipmentSlugs, true);

        return $this->render('exercise/show.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'exercise' => $exercise,
            'isAvailable' => $isAvailable,
        ]);
    }

    private function renderExerciseForm(
        CurrentUserProvider $currentUserProvider,
        EntityManagerInterface $entityManager,
        Exercise $exercise,
        string $mode,
        array $formErrors = [],
        array $formData = [],
        bool $formSubmitted = false,
    ): Response {
        return $this->render('exercise/form.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'exercise' => $exercise,
            'equipmentList' => $entityManager->getRepository(Equipment::class)->findBy([], ['name' => 'ASC']),
            'trackingModes' => ExerciseTrackingMode::cases(),
            'exerciseTypes' => ExerciseType::cases(),
            'formErrors' => $formErrors,
            'formData' => $formData,
            'formSubmitted' => $formSubmitted,
            'mode' => $mode,
        ]);
    }

    /** @return list<string> */
    private function fillExerciseFromRequest(Exercise $exercise, Request $request, EntityManagerInterface $entityManager): array
    {
        $name = trim((string) $request->request->get('name'));
        $slug = trim((string) $request->request->get('slug'));
        $description = trim((string) $request->request->get('description'));
        $executionInstructions = trim((string) $request->request->get('executionInstructions'));
        $imagePath = trim((string) $request->request->get('imagePath'));
        $primaryMuscles = $this->splitCsv((string) $request->request->get('primaryMuscles'));
        $secondaryMuscles = $this->splitCsv((string) $request->request->get('secondaryMuscles'));
        $trackingModeValue = trim((string) $request->request->get('trackingMode'));
        $exerciseTypeValue = trim((string) $request->request->get('exerciseType'));
        $equipmentId = trim((string) $request->request->get('defaultEquipment'));
        $secondaryEquipmentNotes = trim((string) $request->request->get('secondaryEquipmentNotes'));
        $defaultIncrementKg = $this->nullableFloat($request->request->get('defaultIncrementKg')) ?? 2.5;
        $isFundamental = $request->request->has('isFundamental');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Il nome è obbligatorio.';
        }

        if ($description === '') {
            $errors['description'] = 'La descrizione è obbligatoria.';
        }

        $trackingMode = ExerciseTrackingMode::tryFrom($trackingModeValue);
        if (!$trackingMode instanceof ExerciseTrackingMode) {
            $errors['trackingMode'] = 'La modalità di registrazione non è valida.';
        }

        $exerciseType = ExerciseType::tryFrom($exerciseTypeValue);
        if (!$exerciseType instanceof ExerciseType) {
            $errors['exerciseType'] = 'Il tipo esercizio non è valido.';
        }

        $equipment = null;
        if ($equipmentId !== '') {
            $equipment = $entityManager->getRepository(Equipment::class)->find((int) $equipmentId);
            if (!$equipment instanceof Equipment) {
                $errors['defaultEquipment'] = 'L’attrezzatura selezionata non esiste.';
            }
        }

        if ($defaultIncrementKg < 0) {
            $errors['defaultIncrementKg'] = 'L’incremento default non può essere negativo.';
        }

        if ($slug === '' && $name !== '') {
            $slug = $this->slugify($name);
        } else {
            $slug = $this->slugify($slug);
        }

        if ($slug === '') {
            $errors['slug'] = 'Lo slug non può essere vuoto.';
        } elseif (!$this->isSlugAvailable($entityManager, $slug, $exercise)) {
            $errors['slug'] = sprintf('Lo slug "%s" è già usato da un altro esercizio.', $slug);
        }

        if ($errors !== []) {
            return $errors;
        }

        $exercise
            ->setName($name)
            ->setSlug($slug)
            ->setDescription($description)
            ->setExecutionInstructions($executionInstructions !== '' ? $executionInstructions : null)
            ->setImagePath($imagePath !== '' ? $imagePath : null)
            ->setPrimaryMuscles($primaryMuscles)
            ->setSecondaryMuscles($secondaryMuscles)
            ->setTrackingMode($trackingMode)
            ->setExerciseType($exerciseType)
            ->setDefaultEquipment($equipment)
            ->setSecondaryEquipmentNotes($secondaryEquipmentNotes !== '' ? $secondaryEquipmentNotes : null)
            ->setDefaultIncrementKg($defaultIncrementKg)
            ->setIsFundamental($isFundamental);

        return [];
    }

    /** @return list<string> */
    private function getAvailableEquipmentSlugs(EntityManagerInterface $entityManager, GymProfileProvider $gymProfileProvider): array
    {
        $items = $entityManager->getRepository(GymEquipment::class)->findBy([
            'gymProfile' => $gymProfileProvider->getCurrentGym(),
            'isAvailable' => true,
        ]);

        $slugs = [];
        foreach ($items as $item) {
            if ($item instanceof GymEquipment) {
                $slugs[] = $item->getEquipment()->getSlug();
            }
        }

        return $slugs;
    }

    private function findExerciseBySlug(EntityManagerInterface $entityManager, string $slug): Exercise
    {
        $exercise = $entityManager->getRepository(Exercise::class)->findOneBy(['slug' => $slug]);

        if (!$exercise instanceof Exercise) {
            throw $this->createNotFoundException('Esercizio non trovato.');
        }

        return $exercise;
    }

    private function isSlugAvailable(EntityManagerInterface $entityManager, string $slug, Exercise $currentExercise): bool
    {
        $existing = $entityManager->getRepository(Exercise::class)->findOneBy(['slug' => $slug]);

        return !$existing instanceof Exercise || $existing === $currentExercise;
    }

    /** @return list<string> */
    private function splitCsv(string $value): array
    {
        $items = array_map(static fn (string $item): string => trim($item), explode(',', $value));
        $items = array_filter($items, static fn (string $item): bool => $item !== '');

        return array_values(array_unique($items));
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    private function slugify(string $value): string
    {
        $slugger = new AsciiSlugger('it');

        return strtolower((string) $slugger->slug($value));
    }
}
