<?php

namespace App\Controller\Admin;

use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/property/kitchen')]
class KitchenContentController extends AbstractAdminController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PropertyRepository $propertyRepository,
    ) {
    }

    #[Route('/intro', name: 'admin_kitchen_intro_update', methods: ['POST'])]
    public function updateIntro(Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $property = $this->propertyRepository->getOrCreate();
        $property->setKitchenIntroPt($request->request->getString('kitchenIntroPt'));
        $property->setKitchenIntroEn($request->request->getString('kitchenIntroEn'));
        $property->touch();
        $this->em->flush();

        $this->addFlash('success', 'Introdução da cozinha atualizada.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'kitchen-content']);
    }
}
