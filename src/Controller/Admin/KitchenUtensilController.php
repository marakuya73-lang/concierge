<?php

namespace App\Controller\Admin;

use App\Entity\KitchenUtensil;
use App\Repository\KitchenUtensilRepository;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/property/kitchen/utensils')]
class KitchenUtensilController extends AbstractAdminController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PropertyRepository $propertyRepository,
        private KitchenUtensilRepository $utensilRepository,
    ) {
    }

    #[Route('/create', name: 'admin_kitchen_utensil_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $property = $this->propertyRepository->getOrCreate();
        $utensil = new KitchenUtensil();
        $utensil->setProperty($property);
        $utensil->setNamePt($request->request->getString('namePt'));
        $utensil->setNameEn($request->request->getString('nameEn'));
        $utensil->setCategoryPt($request->request->getString('categoryPt'));
        $utensil->setCategoryEn($request->request->getString('categoryEn'));
        $utensil->setSortOrder($this->utensilRepository->getNextSortOrder($property));

        $this->em->persist($utensil);
        $property->touch();
        $this->em->flush();

        $this->addFlash('success', 'Utensílio adicionado.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'kitchen-content']);
    }

    #[Route('/{id}', name: 'admin_kitchen_utensil_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(KitchenUtensil $utensil, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $utensil->setNamePt($request->request->getString('namePt'));
        $utensil->setNameEn($request->request->getString('nameEn'));
        $utensil->setCategoryPt($request->request->getString('categoryPt'));
        $utensil->setCategoryEn($request->request->getString('categoryEn'));
        $utensil->setActive($request->request->getBoolean('active'));

        $utensil->getProperty()?->touch();
        $this->em->flush();

        $this->addFlash('success', 'Utensílio atualizado.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'kitchen-content']);
    }

    #[Route('/{id}/delete', name: 'admin_kitchen_utensil_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(KitchenUtensil $utensil, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $property = $utensil->getProperty();
        $this->em->remove($utensil);
        $property?->touch();
        $this->em->flush();

        $this->addFlash('success', 'Utensílio removido.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'kitchen-content']);
    }

    #[Route('/{id}/move', name: 'admin_kitchen_utensil_move', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function move(KitchenUtensil $utensil, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $property = $utensil->getProperty();
        if (!$property) {
            return $this->redirectToRoute('admin_property');
        }

        $items = $this->utensilRepository->findByPropertyOrdered($property);
        $index = array_search($utensil, $items, true);

        if ('up' === $request->request->get('direction') && $index > 0) {
            $swap = $items[$index - 1];
            $order = $utensil->getSortOrder();
            $utensil->setSortOrder($swap->getSortOrder());
            $swap->setSortOrder($order);
        } elseif ('down' === $request->request->get('direction') && false !== $index && $index < count($items) - 1) {
            $swap = $items[$index + 1];
            $order = $utensil->getSortOrder();
            $utensil->setSortOrder($swap->getSortOrder());
            $swap->setSortOrder($order);
        }

        $property->touch();
        $this->em->flush();

        return $this->redirectToRoute('admin_property', ['_fragment' => 'kitchen-content']);
    }
}
