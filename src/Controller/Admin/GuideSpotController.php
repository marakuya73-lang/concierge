<?php

namespace App\Controller\Admin;

use App\Entity\GuideSpot;
use App\Service\PropertyPhotoUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/property/guide-spots')]
class GuideSpotController extends AbstractAdminController
{
    public function __construct(
        private PropertyPhotoUploader $uploader,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/{id}', name: 'admin_guide_spot_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(GuideSpot $spot, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $spot->setTitlePt($request->request->getString('titlePt') ?: null);
        $spot->setTitleEn($request->request->getString('titleEn') ?: null);
        $spot->setBodyPt($request->request->getString('bodyPt'));
        $spot->setBodyEn($request->request->getString('bodyEn'));
        $spot->setActive($request->request->getBoolean('active'));

        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
        $file = $request->files->get('photo');
        if ($file) {
            if ($spot->getImageFilename()) {
                $this->uploader->remove($spot->getImageFilename());
            }
            $spot->setImageFilename($this->uploader->upload($file));
        }

        $spot->getProperty()?->touch();
        $this->em->flush();

        $this->addFlash('success', 'Conteúdo de boas-vindas atualizado.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'welcome-content']);
    }

    #[Route('/{id}/remove-image', name: 'admin_guide_spot_remove_image', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeImage(GuideSpot $spot, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        if ($spot->getImageFilename()) {
            $this->uploader->remove($spot->getImageFilename());
            $spot->setImageFilename(null);
            $spot->getProperty()?->touch();
            $this->em->flush();
        }

        $this->addFlash('success', 'Imagem removida.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'welcome-content']);
    }
}
