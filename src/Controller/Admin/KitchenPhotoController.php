<?php

namespace App\Controller\Admin;

use App\Entity\KitchenPhoto;
use App\Repository\KitchenPhotoRepository;
use App\Repository\PropertyRepository;
use App\Service\PropertyPhotoUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/property/kitchen/photos')]
class KitchenPhotoController extends AbstractAdminController
{
    public function __construct(
        private PropertyRepository $propertyRepository,
        private KitchenPhotoRepository $photoRepository,
        private PropertyPhotoUploader $uploader,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/upload', name: 'admin_kitchen_photo_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $property = $this->propertyRepository->getOrCreate();
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
        $file = $request->files->get('photo');

        if (!$file) {
            $this->addFlash('error', 'Selecione uma foto para enviar.');

            return $this->redirectToRoute('admin_property', ['_fragment' => 'kitchen-content']);
        }

        $photo = new KitchenPhoto();
        $photo->setProperty($property);
        $photo->setFilename($this->uploader->upload($file));
        $photo->setSortOrder($this->photoRepository->getNextSortOrder($property));
        $photo->setCaptionPt($request->request->getString('captionPt') ?: null);
        $photo->setCaptionEn($request->request->getString('captionEn') ?: null);

        $this->em->persist($photo);
        $property->touch();
        $this->em->flush();

        $this->addFlash('success', 'Foto da cozinha adicionada.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'kitchen-content']);
    }

    #[Route('/{id}', name: 'admin_kitchen_photo_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(KitchenPhoto $photo, Request $request): Response
    {
        $this->validateAdminCsrf($request);

        $photo->setCaptionPt($request->request->getString('captionPt') ?: null);
        $photo->setCaptionEn($request->request->getString('captionEn') ?: null);
        $photo->setActive($request->request->getBoolean('active'));

        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
        $file = $request->files->get('photo');
        if ($file) {
            $this->uploader->remove($photo->getFilename());
            $photo->setFilename($this->uploader->upload($file));
        }

        $photo->getProperty()?->touch();
        $this->em->flush();

        $this->addFlash('success', 'Foto da cozinha atualizada.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'kitchen-content']);
    }

    #[Route('/{id}/delete', name: 'admin_kitchen_photo_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(KitchenPhoto $photo, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $property = $photo->getProperty();
        $this->uploader->remove($photo->getFilename());
        $this->em->remove($photo);
        $property?->touch();
        $this->em->flush();

        $this->addFlash('success', 'Foto da cozinha removida.');

        return $this->redirectToRoute('admin_property', ['_fragment' => 'kitchen-content']);
    }

    #[Route('/{id}/move', name: 'admin_kitchen_photo_move', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function move(KitchenPhoto $photo, Request $request): Response
    {
        $this->validateAdminCsrf($request);
        $property = $photo->getProperty();
        if (!$property) {
            return $this->redirectToRoute('admin_property');
        }

        $photos = $this->photoRepository->findByPropertyOrdered($property);
        $index = array_search($photo, $photos, true);

        if ('up' === $request->request->get('direction') && $index > 0) {
            $swap = $photos[$index - 1];
            $order = $photo->getSortOrder();
            $photo->setSortOrder($swap->getSortOrder());
            $swap->setSortOrder($order);
        } elseif ('down' === $request->request->get('direction') && false !== $index && $index < count($photos) - 1) {
            $swap = $photos[$index + 1];
            $order = $photo->getSortOrder();
            $photo->setSortOrder($swap->getSortOrder());
            $swap->setSortOrder($order);
        }

        $property->touch();
        $this->em->flush();

        return $this->redirectToRoute('admin_property', ['_fragment' => 'kitchen-content']);
    }
}
