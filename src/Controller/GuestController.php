<?php

namespace App\Controller;

use App\Repository\ActivityItemRepository;
use App\Repository\FaqItemRepository;
use App\Repository\HouseRuleRepository;
use App\Repository\GuideSpotRepository;
use App\Repository\KitchenPhotoRepository;
use App\Repository\KitchenUtensilRepository;
use App\Repository\PropertyPhotoRepository;
use App\Repository\PropertyRepository;
use App\Service\ConciergeService;
use App\Entity\KitchenUtensil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GuestController extends AbstractController
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
        private ConciergeService $conciergeService,
    ) {
    }

    #[Route('/', name: 'guest_home')]
    public function home(): Response
    {
        $property = $this->propertyRepository->getOrCreate();

        return $this->render('guest/home.html.twig', [
            'property' => $property,
            'photos' => $this->photoRepository->findByPropertyOrdered($property),
        ]);
    }

    #[Route('/verify-code', name: 'guest_verify_code', methods: ['POST'])]
    public function verifyCode(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $code = strtoupper(trim((string) ($data['code'] ?? '')));

        if (5 !== strlen($code)) {
            return $this->json(['error' => 'Por favor, insira o código completo de 5 caracteres.'], 400);
        }

        try {
            $stay = $this->conciergeService->verifyAccessCode($code, $request->getLocale());

            return $this->json($stay);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 401);
        }
    }

    #[Route('/stay/{code}', name: 'guest_stay', requirements: ['code' => '[A-Za-z0-9]{5}'])]
    public function stay(string $code, Request $request): Response
    {
        $code = strtoupper($code);

        try {
            $stay = $this->conciergeService->verifyAccessCode($code, $request->getLocale());
            $extras = $this->conciergeService->getExtrasForGuest($code, $request->getLocale());
            $foodExtras = $this->conciergeService->getFoodExtrasForGuest($code, $request->getLocale());
        } catch (\Throwable $e) {
            return $this->render('guest/denied.html.twig', ['error' => $e->getMessage()]);
        }

        $property = $this->propertyRepository->getOrCreate();
        $kitchenUtensils = $this->kitchenUtensilRepository->findActiveByPropertyOrdered($property);
        $marketGroups = $this->getMarketGroups();

        return $this->render('guest/stay.html.twig', [
            'code' => $code,
            'stay' => $stay,
            'extras' => $extras,
            'foodExtras' => $foodExtras,
            'requestedExtraIds' => array_column($extras['myRequests'], 'extraId'),
            'photos' => $this->photoRepository->findByPropertyOrdered($property),
            'guideSpots' => $this->guideSpotRepository->findActiveByPropertyOrdered($property),
            'faqItems' => $this->faqItemRepository->findActiveByPropertyOrdered($property),
            'houseRules' => $this->houseRuleRepository->findActiveByPropertyOrdered($property),
            'activityItems' => $this->activityItemRepository->findActiveByPropertyOrdered($property),
            'activityFallback' => $this->getActivityFallback(),
            'kitchenPhotos' => $this->kitchenPhotoRepository->findActiveByPropertyOrdered($property),
            'kitchenUtensilGroups' => $this->groupKitchenUtensils($kitchenUtensils, $request->getLocale()),
            'marketGroups' => $marketGroups,
            'property' => $property,
        ]);
    }

    /** @return array<string, list<array{key: string, name: string, mapUrl: string}>> */
    private function getMarketGroups(): array
    {
        return [
            'alto' => [
                ['key' => 'fernandes', 'name' => 'Fernandes', 'mapUrl' => 'https://g.co/kgs/LqzNfVt'],
                ['key' => 'smart', 'name' => 'Smart', 'mapUrl' => 'https://g.co/kgs/RGKKM9X'],
                ['key' => 'hortifruti', 'name' => 'Hortifruti', 'mapUrl' => 'https://g.co/kgs/WVZHJoM'],
            ],
            'moinho' => [
                ['key' => 'louro', 'name' => 'Louro & Zita', 'mapUrl' => 'https://www.google.com/maps/search/?api=1&query=Louro+%26+Zita+Padaria+Moinho+Alto+Para%C3%ADso'],
                ['key' => 'cici', 'name' => 'Cici', 'mapUrl' => 'https://www.google.com/maps/search/?api=1&query=Cici+p%C3%A3o+de+queijo+Moinho+Alto+Para%C3%ADso'],
                ['key' => 'irani', 'name' => 'Irani', 'mapUrl' => 'https://www.google.com/maps/search/?api=1&query=Irani+armaz%C3%A9m+Moinho+Alto+Para%C3%ADso'],
            ],
        ];
    }

    /** @return list<array{icon: string, titleKey: string, bodyKey: string, linkUrl: ?string, linkUrl2: ?string}> */
    private function getActivityFallback(): array
    {
        $wa = 'https://wa.me/+5561999972991';

        return [
            ['icon' => '💧', 'titleKey' => 'activities.waterfalls_title', 'bodyKey' => 'activities.waterfalls_text', 'linkUrl' => 'https://g.co/kgs/wEwiRTU', 'linkUrl2' => null],
            ['icon' => '🧘', 'titleKey' => 'activities.massage_title', 'bodyKey' => 'activities.massage_text', 'linkUrl' => 'http://www.rajaaram.com.br/', 'linkUrl2' => null],
            ['icon' => '🌿', 'titleKey' => 'activities.medicine_title', 'bodyKey' => 'activities.medicine_text', 'linkUrl' => $wa.'?text=Estou%20me%20hospedando%20no%20Domo%20Xang%C3%B4%2C%20gostaria%20de%20saber%20mais%20sobre%20as%20cerimonias%20de%20medicinas%20sagradas', 'linkUrl2' => null],
            ['icon' => '✨', 'titleKey' => 'activities.ufo_title', 'bodyKey' => 'activities.ufo_text', 'linkUrl' => 'https://www.google.com.br/maps/search/-14.059734,+-47.466942', 'linkUrl2' => null],
            ['icon' => '🗺️', 'titleKey' => 'activities.guides_title', 'bodyKey' => 'activities.guides_text', 'linkUrl' => $wa.'?text=Estou%20me%20hospedando%20no%20Domo%20Xang%C3%B4%2C%20gostaria%20de%20recomendações%20de%20guias%20locais', 'linkUrl2' => null],
            ['icon' => '🐴', 'titleKey' => 'activities.horse_title', 'bodyKey' => 'activities.horse_text', 'linkUrl' => $wa.'?text=Estou%20me%20hospedando%20no%20Domo%20Xang%C3%B4%2C%20gostaria%20de%20saber%20sobre%20passeios%20a%20cavalo', 'linkUrl2' => null],
            ['icon' => '🛒', 'titleKey' => 'activities.fair_title', 'bodyKey' => 'activities.fair_text', 'linkUrl' => 'https://g.co/kgs/tsmnBMD', 'linkUrl2' => 'https://g.co/kgs/CqXxt2r'],
        ];
    }

    /** @param KitchenUtensil[] $utensils */
    private function groupKitchenUtensils(array $utensils, string $locale): array
    {
        $groups = [];
        foreach ($utensils as $utensil) {
            $key = $utensil->getCategory($locale) ?: ('en' === $locale ? 'General' : 'Geral');
            $groups[$key][] = $utensil;
        }

        return $groups;
    }
}
