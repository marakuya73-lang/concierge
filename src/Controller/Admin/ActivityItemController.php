<?php

namespace App\Controller\Admin;

use App\Entity\ActivityItem;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/property/activity-items')]
class ActivityItemController extends AbstractAdminController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PropertyRepository $propertyRepository,
    ) {
    }

    #[Route('/intro', name: 'admin_activity_items_intro_update', methods: ['POST'])]
    public function updateIntro(Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $property = $this->propertyRepository->getOrCreate();
        $property->setActivitiesIntroPt($request->request->getString('activitiesIntroPt'));
        $property->setActivitiesIntroEn($request->request->getString('activitiesIntroEn'));
        $property->touch();
        $this->em->flush();

        $this->addFlash('success', 'Introdução das atividades atualizada.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'activities-content']);
    }

    #[Route('/{id}', name: 'admin_activity_item_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(ActivityItem $item, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $item->setIcon($request->request->getString('icon') ?: '✦');
        $item->setTitlePt($request->request->getString('titlePt'));
        $item->setTitleEn($request->request->getString('titleEn'));
        $item->setBodyPt($request->request->getString('bodyPt'));
        $item->setBodyEn($request->request->getString('bodyEn'));
        $item->setLinkUrl($request->request->getString('linkUrl') ?: null);
        $item->setLinkUrl2($request->request->getString('linkUrl2') ?: null);
        $item->setActive($request->request->getBoolean('active'));

        $item->getProperty()?->touch();
        $this->em->flush();

        $this->addFlash('success', 'Atividade atualizada.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'activities-content']);
    }
}
