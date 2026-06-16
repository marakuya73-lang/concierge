<?php

namespace App\Controller\Admin;

use App\Entity\FaqItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/property/faq-items')]
class FaqItemController extends AbstractAdminController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/{id}', name: 'admin_faq_item_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(FaqItem $item, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $item->setQuestionPt($request->request->getString('questionPt'));
        $item->setQuestionEn($request->request->getString('questionEn'));
        $item->setAnswerPt($request->request->getString('answerPt'));
        $item->setAnswerEn($request->request->getString('answerEn'));
        $item->setActive($request->request->getBoolean('active'));

        $item->getProperty()?->touch();
        $this->em->flush();

        $this->addFlash('success', 'FAQ atualizado.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'faq-content']);
    }
}
