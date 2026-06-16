<?php

namespace App\Controller\Admin;

use App\Entity\HouseRule;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/property/house-rules')]
class HouseRuleController extends AbstractAdminController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PropertyRepository $propertyRepository,
    ) {
    }

    #[Route('/intro', name: 'admin_house_rules_intro_update', methods: ['POST'])]
    public function updateIntro(Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $property = $this->propertyRepository->getOrCreate();
        $property->setRulesIntroPt($request->request->getString('rulesIntroPt'));
        $property->setRulesIntroEn($request->request->getString('rulesIntroEn'));
        $property->touch();
        $this->em->flush();

        $this->addFlash('success', 'Introdução das regras atualizada.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'rules-content']);
    }

    #[Route('/{id}', name: 'admin_house_rule_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(HouseRule $rule, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $rule->setTitlePt($request->request->getString('titlePt'));
        $rule->setTitleEn($request->request->getString('titleEn'));
        $rule->setBodyPt($request->request->getString('bodyPt'));
        $rule->setBodyEn($request->request->getString('bodyEn'));
        $rule->setActive($request->request->getBoolean('active'));

        $rule->getProperty()?->touch();
        $this->em->flush();

        $this->addFlash('success', 'Regra atualizada.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'rules-content']);
    }
}
