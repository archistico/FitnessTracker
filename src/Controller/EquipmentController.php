<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Enum\EquipmentType;
use App\Service\CurrentUserProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/equipment')]
final class EquipmentController extends AbstractController
{
    #[Route('', name: 'app_equipment_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): Response
    {
        $equipment = $entityManager->getRepository(Equipment::class)->findBy([], ['name' => 'ASC']);

        return $this->render('equipment/index.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'equipmentList' => $equipment,
        ]);
    }

    #[Route('/new', name: 'app_equipment_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
    ): Response {
        $equipment = new Equipment();
        $formErrors = [];
        $formData = [];
        $formSubmitted = $request->isMethod('POST');

        if ($formSubmitted) {
            $formData = $request->request->all();
            $formErrors = $this->fillEquipmentFromRequest($equipment, $request, $entityManager);

            if ($formErrors === []) {
                $entityManager->persist($equipment);
                $entityManager->flush();

                $this->addFlash('success', sprintf('Attrezzatura "%s" creata.', $equipment->getName()));

                return $this->redirectToRoute('app_equipment_show', ['slug' => $equipment->getSlug()]);
            }

            foreach ($formErrors as $error) {
                $this->addFlash('danger', $error);
            }
        }

        return $this->render('equipment/form.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'equipment' => $equipment,
            'equipmentTypes' => EquipmentType::cases(),
            'formErrors' => $formErrors,
            'formData' => $formData,
            'formSubmitted' => $formSubmitted,
            'mode' => 'new',
        ]);
    }

    #[Route('/{slug}/edit', name: 'app_equipment_edit', methods: ['GET', 'POST'])]
    public function edit(
        string $slug,
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProvider $currentUserProvider,
    ): Response {
        $equipment = $this->findEquipmentBySlug($entityManager, $slug);
        $formErrors = [];
        $formData = [];
        $formSubmitted = $request->isMethod('POST');

        if ($formSubmitted) {
            $formData = $request->request->all();
            $formErrors = $this->fillEquipmentFromRequest($equipment, $request, $entityManager);

            if ($formErrors === []) {
                $entityManager->flush();

                $this->addFlash('success', sprintf('Attrezzatura "%s" aggiornata.', $equipment->getName()));

                return $this->redirectToRoute('app_equipment_show', ['slug' => $equipment->getSlug()]);
            }

            foreach ($formErrors as $error) {
                $this->addFlash('danger', $error);
            }
        }

        return $this->render('equipment/form.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'equipment' => $equipment,
            'equipmentTypes' => EquipmentType::cases(),
            'formErrors' => $formErrors,
            'formData' => $formData,
            'formSubmitted' => $formSubmitted,
            'mode' => 'edit',
        ]);
    }

    #[Route('/{slug}/delete', name: 'app_equipment_delete', methods: ['POST'])]
    public function delete(string $slug, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $equipment = $this->findEquipmentBySlug($entityManager, $slug);

        if (!$this->isCsrfTokenValid('delete_equipment_'.$equipment->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF non valido.');
        }

        $name = $equipment->getName();
        $entityManager->remove($equipment);
        $entityManager->flush();

        $this->addFlash('warning', sprintf('Attrezzatura "%s" eliminata.', $name));

        return $this->redirectToRoute('app_equipment_index');
    }

    #[Route('/{slug}', name: 'app_equipment_show', methods: ['GET'])]
    public function show(string $slug, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider): Response
    {
        $equipment = $this->findEquipmentBySlug($entityManager, $slug);

        return $this->render('equipment/show.html.twig', [
            'currentUser' => $currentUserProvider->getUser(),
            'equipment' => $equipment,
        ]);
    }

    /** @return list<string> */
    private function fillEquipmentFromRequest(Equipment $equipment, Request $request, EntityManagerInterface $entityManager): array
    {
        $name = trim((string) $request->request->get('name'));
        $slug = trim((string) $request->request->get('slug'));
        $typeValue = trim((string) $request->request->get('type'));
        $description = trim((string) $request->request->get('description'));
        $usageInstructions = trim((string) $request->request->get('usageInstructions'));
        $imagePath = trim((string) $request->request->get('imagePath'));
        $isMachine = $request->request->has('isMachine');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Il nome è obbligatorio.';
        }

        if ($description === '') {
            $errors['description'] = 'La descrizione è obbligatoria.';
        }

        $type = EquipmentType::tryFrom($typeValue);
        if (!$type instanceof EquipmentType) {
            $errors['type'] = 'Il tipo attrezzatura non è valido.';
        }

        if ($slug === '' && $name !== '') {
            $slug = $this->slugify($name);
        } else {
            $slug = $this->slugify($slug);
        }

        if ($slug === '') {
            $errors['slug'] = 'Lo slug non può essere vuoto.';
        } elseif (!$this->isSlugAvailable($entityManager, $slug, $equipment)) {
            $errors['slug'] = sprintf('Lo slug "%s" è già usato da un’altra attrezzatura.', $slug);
        }

        if ($errors !== []) {
            return $errors;
        }

        $equipment
            ->setName($name)
            ->setSlug($slug)
            ->setType($type)
            ->setDescription($description)
            ->setUsageInstructions($usageInstructions !== '' ? $usageInstructions : null)
            ->setImagePath($imagePath !== '' ? $imagePath : null)
            ->setIsMachine($isMachine);

        return [];
    }

    private function findEquipmentBySlug(EntityManagerInterface $entityManager, string $slug): Equipment
    {
        $equipment = $entityManager->getRepository(Equipment::class)->findOneBy(['slug' => $slug]);

        if (!$equipment instanceof Equipment) {
            throw $this->createNotFoundException('Attrezzatura non trovata.');
        }

        return $equipment;
    }

    private function isSlugAvailable(EntityManagerInterface $entityManager, string $slug, Equipment $currentEquipment): bool
    {
        $existing = $entityManager->getRepository(Equipment::class)->findOneBy(['slug' => $slug]);

        return !$existing instanceof Equipment || $existing === $currentEquipment;
    }

    private function slugify(string $value): string
    {
        $slugger = new AsciiSlugger('it');

        return strtolower((string) $slugger->slug($value));
    }
}
