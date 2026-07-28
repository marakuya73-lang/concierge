<?php

namespace App\Controller\Admin;

use App\Entity\Extra;
use App\Form\ExtraType;
use App\Repository\ExtraRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/extras')]
class ExtraController extends AbstractAdminController
{
    public function __construct(
        private ExtraRepository $extraRepository,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'admin_extras')]
    public function index(): Response
    {
        return $this->render('admin/extras/index.html.twig', [
            'extras' => $this->extraRepository->findBy([], ['namePt' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_extra_new')]
    public function new(Request $request): Response
    {
        $extra = new Extra();
        $form = $this->createForm(ExtraType::class, $extra);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($extra);
            $this->em->flush();
            $this->addFlash('success', 'Extra criado.');

            return $this->redirectToRoute('admin_extras');
        }

        return $this->render('admin/extras/form.html.twig', [
            'form' => $form,
            'extra' => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_extra_edit', requirements: ['id' => '\d+'])]
    public function edit(Extra $extra, Request $request): Response
    {
        $form = $this->createForm(ExtraType::class, $extra);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Extra atualizado.');

            return $this->redirectToRoute('admin_extras');
        }

        return $this->render('admin/extras/form.html.twig', [
            'form' => $form,
            'extra' => $extra,
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_extra_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Extra $extra, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $extra->setActive(!$extra->isActive());
        $this->em->flush();

        $this->addFlash(
            'success',
            $extra->isActive()
                ? 'Extra activado: '.$extra->getNamePt().'.'
                : 'Extra pausado: '.$extra->getNamePt().'.',
        );

        return $this->redirectToRoute('admin_extras');
    }

    #[Route('/{id}/delete', name: 'admin_extra_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Extra $extra, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $this->em->remove($extra);
        $this->em->flush();
        $this->addFlash('success', 'Extra removido.');

        return $this->redirectToRoute('admin_extras');
    }
}
