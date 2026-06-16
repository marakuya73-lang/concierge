<?php

namespace App\Controller\Admin;

use App\Form\PropertyType;
use App\Repository\ActivityItemRepository;
use App\Repository\FaqItemRepository;
use App\Repository\HouseRuleRepository;
use App\Repository\GuideSpotRepository;
use App\Repository\KitchenPhotoRepository;
use App\Repository\KitchenUtensilRepository;
use App\Repository\PropertyPhotoRepository;
use App\Repository\PropertyRepository;
use App\Service\IcalSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/property')]
class PropertyController extends AbstractAdminController
{
    public function __construct(
        private PropertyRepository $propertyRepository,
        private PropertyPhotoRepository $photoRepository,
        private GuideSpotRepository $guideSpotRepository,
        private FaqItemRepository $faqItemRepository,
        private HouseRuleRepository $houseRuleRepository,
        private ActivityItemRepository $activityItemRepository,
        private KitchenPhotoRepository $kitchenPhotoRepository,
        private KitchenUtensilRepository $kitchenUtensilRepository,
        private EntityManagerInterface $em,
        private IcalSyncService $icalSyncService,
    ) {
    }

    #[Route('', name: 'admin_property')]
    public function edit(Request $request): Response
    {
        $property = $this->propertyRepository->getOrCreate();
        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $property->touch();
            $this->em->flush();

            if ($property->getAirbnbIcalUrl()) {
                $result = $this->icalSyncService->sync();
                if (isset($result['message'])) {
                    $this->addFlash('success', 'Propriedade atualizada.');
                } else {
                    $this->addFlash('success', sprintf(
                        'Propriedade actualizada. Airbnb sincronizado: %d novas, %d actualizadas, %d Site.',
                        $result['created'] ?? 0,
                        $result['updated'] ?? 0,
                        $result['siteBookings'] ?? 0,
                    ));
                }
            } else {
                $this->addFlash('success', 'Propriedade atualizada.');
            }

            return $this->redirectToRoute('admin_property');
        }

        return $this->render('admin/property/edit.html.twig', [
            'form' => $form,
            'property' => $property,
            'photos' => $this->photoRepository->findByPropertyOrdered($property),
            'guideSpots' => $this->guideSpotRepository->findByPropertyOrdered($property),
            'faqItems' => $this->faqItemRepository->findByPropertyOrdered($property),
            'houseRules' => $this->houseRuleRepository->findByPropertyOrdered($property),
            'activityItems' => $this->activityItemRepository->findByPropertyOrdered($property),
            'kitchenPhotos' => $this->kitchenPhotoRepository->findByPropertyOrdered($property),
            'kitchenUtensils' => $this->kitchenUtensilRepository->findByPropertyOrdered($property),
        ]);
    }
}
