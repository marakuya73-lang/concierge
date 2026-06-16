<?php

namespace App\Controller\Admin;

use App\Entity\PropertyPhoto;
use App\Repository\PropertyPhotoRepository;
use App\Repository\PropertyRepository;
use App\Service\PropertyPhotoUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/property/photos')]
class PropertyPhotoController extends AbstractAdminController
{
    public function __construct(
        private PropertyRepository $propertyRepository,
        private PropertyPhotoRepository $photoRepository,
        private PropertyPhotoUploader $uploader,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/upload', name: 'admin_property_photo_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $property = $this->propertyRepository->getOrCreate();
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
        $file = $request->files->get('photo');

        if (!$file) {
            $this->addFlash('error', 'Selecione uma foto para enviar.');

            return $this->redirectToRoute('admin_property');
        }

        $filename = $this->uploader->upload($file);
        $photo = new PropertyPhoto();
        $photo->setProperty($property);
        $photo->setFilename($filename);
        $photo->setSortOrder($this->photoRepository->getNextSortOrder($property));
        $photo->setCaptionPt($request->request->getString('captionPt') ?: null);
        $photo->setCaptionEn($request->request->getString('captionEn') ?: null);

        $this->em->persist($photo);
        $property->touch();
        $this->em->flush();

        $this->addFlash('success', 'Foto adicionada.');

        return $this->redirectToRoute('admin_property');
    }

    #[Route('/{id}/delete', name: 'admin_property_photo_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(PropertyPhoto $photo, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $property = $photo->getProperty();
        $this->uploader->remove($photo->getFilename());
        $this->em->remove($photo);
        if ($property) {
            $property->touch();
        }
        $this->em->flush();

        $this->addFlash('success', 'Foto removida.');

        return $this->redirectToRoute('admin_property');
    }

    #[Route('/{id}/move', name: 'admin_property_photo_move', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function move(PropertyPhoto $photo, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $direction = $request->request->get('direction');
        $property = $photo->getProperty();
        if (!$property) {
            return $this->redirectToRoute('admin_property');
        }

        $photos = $this->photoRepository->findByPropertyOrdered($property);
        $index = array_search($photo, $photos, true);

        if ('up' === $direction && $index > 0) {
            $swap = $photos[$index - 1];
            $order = $photo->getSortOrder();
            $photo->setSortOrder($swap->getSortOrder());
            $swap->setSortOrder($order);
        } elseif ('down' === $direction && false !== $index && $index < count($photos) - 1) {
            $swap = $photos[$index + 1];
            $order = $photo->getSortOrder();
            $photo->setSortOrder($swap->getSortOrder());
            $swap->setSortOrder($order);
        }

        $property->touch();
        $this->em->flush();

        return $this->redirectToRoute('admin_property');
    }
}
