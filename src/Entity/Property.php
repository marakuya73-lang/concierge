<?php

namespace App\Entity;

use App\Repository\PropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
class Property
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $namePt = 'Domo Xangô | Vista Sagrada da Selva';

    #[ORM\Column(type: Types::TEXT)]
    private string $taglinePt = 'Bem-vindo à sua retiro na selva. Que sua estadia seja de descanso, reconexão e memórias inesquecíveis.';

    #[ORM\Column(type: Types::TEXT)]
    private string $descriptionPt = 'Aqui, natureza e sofisticação se entrelaçam para oferecer uma vivência sensorial e memorável. Permita-se desacelerar, contemplar o céu estrelado e habitar o silêncio deste refúgio sagrado. Cada detalhe foi pensado para que você simplesmente... esteja.';

    #[ORM\Column(type: Types::TEXT)]
    private string $checkInInstructionsPt = "Das 14h às 18h.\n\nPedimos gentilmente que nos avise por WhatsApp assim que estiver saindo de Alto Paraíso. Assim, poderemos preparar sua chegada com todo cuidado e atenção.\n\nAo chegar, buzine e aguarde. O anfitrião irá ao seu encontro para recebê-lo pessoalmente.\n\nNossos cães são mansos e costumam vir dar as boas-vindas com entusiasmo.";

    #[ORM\Column(type: Types::TEXT)]
    private string $checkOutInstructionsPt = "Até as 11:00.\n\nAntes de partir, pedimos gentilmente que nos envie uma mensagem para que possamos encerrar sua estadia com cuidado e gratidão.\n\nPor gentileza, leve consigo seus pertences e resíduos, sua atenção contribui para mantermos este refúgio sempre acolhedor.\n\nCaso precise sair sem nos encontrar, por favor, nos envie uma mensagem avisando que deixou o domo.";

    #[ORM\Column(type: Types::TEXT)]
    private string $locationDetailsPt = 'Moinho, Alto Paraíso de Goiás, 14 km do centro, em meio à natureza intocada da Chapada dos Veadeiros. A 10 minutos a pé das cachoeiras Anjos e Arcanjos.';

    #[ORM\Column(type: Types::TEXT)]
    private string $addressPt = 'Estrada de terra do km 14 da GO-239, 2,3 km após o entroncamento, Alto Paraíso de Goiás, GO';

    #[ORM\Column(type: Types::TEXT)]
    private string $addressEn = 'Dirt road at km 14 of GO-239, 2.3 km after the junction, Alto Paraíso de Goiás, GO, Brazil';

    #[ORM\Column(type: Types::TEXT)]
    private string $rulesIntroPt = 'Nosso desejo é que sua estadia aqui seja memorável. Para isso, pedimos atenção a algumas orientações essenciais que preservam o conforto, a segurança e a harmonia deste espaço sagrado:';

    #[ORM\Column(type: Types::TEXT)]
    private string $rulesIntroEn = 'We want your stay here to be memorable. To help with that, we ask you to follow a few essential guidelines that preserve the comfort, safety, and harmony of this sacred space:';

    #[ORM\Column(type: Types::TEXT)]
    private string $activitiesIntroPt = 'Descubra trilhas, vivências e encontros especiais nos arredores do Domo.';

    #[ORM\Column(type: Types::TEXT)]
    private string $activitiesIntroEn = 'Discover trails, experiences, and special encounters around Domo.';

    #[ORM\Column(type: Types::TEXT)]
    private string $kitchenIntroPt = 'Sua cozinha ao ar livre está totalmente equipada para que você prepare refeições com tranquilidade, cercado pela natureza da Chapada.';

    #[ORM\Column(type: Types::TEXT)]
    private string $kitchenIntroEn = 'Your open-air kitchen is fully equipped so you can cook at ease, surrounded by the nature of Chapada dos Veadeiros.';

    #[ORM\Column(type: Types::TEXT)]
    private string $arrivalInstructionsPt = "Ao sair de Alto Paraíso de Goiás, siga em direção ao povoado do Moinho. Recomendamos Google Maps (não Waze) e baixar o mapa offline antes de iniciar a rota.\n\n1. No caminho, você encontrará uma bifurcação — mantenha-se à esquerda, seguindo as placas indicativas para o Moinho.\n2. Ao chegar à placa \"Bem-vindos ao Moinho\", atravesse a ponte e continue em direção ao Solarion e às cachoeiras Anjos e Arcanjos.\n3. Logo após a ponte, pegue a primeira rua à esquerda.\n4. Em seguida, a primeira rua à direita.\n5. Siga até o final da rua e, no final, vire à esquerda na estrada de terra, continuando pelo caminho das cachoeiras.\n6. Após cerca de 200 metros, você verá uma subida à direita — postes laranjas dos dois lados da entrada e uma placa do Rajaaram. Suba essa rua até o final.\n\nChegando: você saberá que chegou ao encontrar a placa \"Ater Tumti\" e ao ser recebido(a) pelos nossos cachorros. São extremamente dóceis e amigáveis — fique tranquilo(a) se estiverem soltos.\n\n🚗 Estacionamento: fiquem à vontade para estacionar no local. Ao chegar, toque o sino e logo iremos recebê-los.\n\n🚙 Acesso: recomendamos veículos mais altos, pois o último trecho possui irregularidades típicas de estrada de terra. Se estiver com carro baixo ou não se sentir confortável, oferecemos translado gratuito entre o povoado do Moinho e o Rajaaram — avise com antecedência.\n\n🚐 Transfer opcional: também oferecemos serviço de transfer saindo de Alto Paraíso, mediante valor adicional, para que você relaxe desde o início da jornada.\n\nQualquer dúvida durante o trajeto, entre em contato conosco — teremos prazer em ajudar.";

    #[ORM\Column(length: 255)]
    private string $nameEn = 'Domo Xangô | Sacred Jungle View';

    #[ORM\Column(type: Types::TEXT)]
    private string $taglineEn = 'Welcome to your jungle retreat. May your stay bring rest, reconnection, and unforgettable memories.';

    #[ORM\Column(type: Types::TEXT)]
    private string $descriptionEn = 'Here, nature and sophistication intertwine for a sensory, memorable stay. Slow down, gaze at the starry sky, and inhabit the silence of this sacred refuge. Every detail was designed so you can simply... be.';

    #[ORM\Column(type: Types::TEXT)]
    private string $checkInInstructionsEn = "From 2pm to 6pm.\n\nPlease message us on WhatsApp when leaving Alto Paraíso so we can prepare your arrival with care.\n\nWhen you arrive, honk and wait. Your host will come to greet you personally.\n\nOur dogs are gentle and may welcome you enthusiastically.";

    #[ORM\Column(type: Types::TEXT)]
    private string $checkOutInstructionsEn = "By 11:00am.\n\nBefore leaving, please send us a message so we can close your stay with care and gratitude.\n\nPlease take your belongings and waste with you.\n\nIf you must leave without meeting us, please message us to let us know you have departed safely.";

    #[ORM\Column(type: Types::TEXT)]
    private string $locationDetailsEn = 'Moinho, Alto Paraíso de Goiás, 14 km from town center, in untouched Chapada dos Veadeiros nature. 10-minute walk to Anjos and Arcanjos waterfalls.';

    #[ORM\Column(type: Types::TEXT)]
    private string $arrivalInstructionsEn = "When leaving Alto Paraíso de Goiás, head toward the village of Moinho. We recommend Google Maps (not Waze) and downloading offline maps before you start.\n\n1. On the way, you will reach a fork — stay left, following signs to Moinho.\n2. When you reach the \"Bem-vindos ao Moinho\" sign, cross the bridge and continue toward Solarion and the Anjos and Arcanjos waterfalls.\n3. Right after the bridge, take the first street on the left.\n4. Then the first street on the right.\n5. Follow to the end of the street and, at the end, turn left onto the dirt road continuing along the waterfall trail.\n6. After about 200 meters, you will see an uphill turn on the right — orange posts on both sides of the entrance and a Rajaaram sign. Drive up that road to the end.\n\nArrival: you will know you have arrived when you see the \"Ater Tumti\" sign and are greeted by our dogs. They are extremely gentle and friendly — don't worry if they are roaming freely.\n\n🚗 Parking: feel free to park on site. When you arrive, ring the bell and we will come to welcome you.\n\n🚙 Road access: we recommend higher vehicles, as the last stretch has typical dirt-road bumps. If you have a low car or don't feel comfortable, we offer a free shuttle between Moinho village and Rajaaram — just let us know in advance.\n\n🚐 Optional transfer: we also offer a transfer from Alto Paraíso for an additional fee, so you can relax from the very start of your journey.\n\nIf you have any questions along the way, contact us — we will be happy to help.";

    #[ORM\Column(length: 100)]
    private string $wifiName = 'MARA DECK';

    #[ORM\Column(length: 100)]
    private string $wifiPassword = 'UaiFai Cosmico';

    #[ORM\Column(length: 100)]
    private string $wifiSecondaryName = 'd.sucodealegria';

    #[ORM\Column(length: 100)]
    private string $wifiSecondaryPassword = 'estacionoudireito?';

    #[ORM\Column(length: 10)]
    private string $checkInTime = '14:00';

    #[ORM\Column(length: 10)]
    private string $checkInTimeEnd = '18:00';

    #[ORM\Column(length: 10)]
    private string $checkOutTime = '11:00';

    #[ORM\Column(length: 255)]
    private string $petsPolicy = 'Nossos cães fazem parte da experiência, avise se preferir distância';

    #[ORM\Column(length: 255)]
    private string $smokingPolicy = 'Proibido fumar (RPPN, multa IBAMA)';

    #[ORM\Column(length: 255)]
    private string $silencePolicy = 'Descalço ou meias limpas dentro do domo';

    #[ORM\Column(length: 255)]
    private string $visitsPolicy = 'Sem velas, incensos ou fogo aberto (RPPN)';

    #[ORM\Column]
    private float $rating = 4.9;

    #[ORM\Column]
    private int $bedrooms = 2;

    #[ORM\Column]
    private int $bathrooms = 1;

    #[ORM\Column]
    private int $maxGuests = 5;

    #[ORM\Column(length: 500)]
    private string $mapUrl = 'https://maps.app.goo.gl/ZbJV4Bdd977fVCyV7';

    #[ORM\Column(length: 50)]
    private string $latitude = '-14.1306';

    #[ORM\Column(length: 50)]
    private string $longitude = '-47.5083';

    #[ORM\Column(length: 255)]
    private string $pixKey = 'domo.xango@gmail.com';

    #[ORM\Column(length: 30)]
    private string $contactPhone = '+55 (61) 99997-2991';

    #[ORM\Column(length: 255)]
    private string $contactEmail = 'domo.xango@gmail.com';

    #[ORM\Column(length: 100)]
    private string $instagramHandle = '@DOMOXANGO';

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $airbnbIcalUrl = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $airbnbIcalLastSyncAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, PropertyPhoto> */
    #[ORM\OneToMany(targetEntity: PropertyPhoto::class, mappedBy: 'property', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $photos;

    /** @var Collection<int, GuideSpot> */
    #[ORM\OneToMany(targetEntity: GuideSpot::class, mappedBy: 'property', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $guideSpots;

    /** @var Collection<int, FaqItem> */
    #[ORM\OneToMany(targetEntity: FaqItem::class, mappedBy: 'property', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $faqItems;

    /** @var Collection<int, HouseRule> */
    #[ORM\OneToMany(targetEntity: HouseRule::class, mappedBy: 'property', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $houseRules;

    /** @var Collection<int, ActivityItem> */
    #[ORM\OneToMany(targetEntity: ActivityItem::class, mappedBy: 'property', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $activityItems;

    /** @var Collection<int, KitchenPhoto> */
    #[ORM\OneToMany(targetEntity: KitchenPhoto::class, mappedBy: 'property', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $kitchenPhotos;

    /** @var Collection<int, KitchenUtensil> */
    #[ORM\OneToMany(targetEntity: KitchenUtensil::class, mappedBy: 'property', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $kitchenUtensils;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->photos = new ArrayCollection();
        $this->guideSpots = new ArrayCollection();
        $this->faqItems = new ArrayCollection();
        $this->houseRules = new ArrayCollection();
        $this->activityItems = new ArrayCollection();
        $this->kitchenPhotos = new ArrayCollection();
        $this->kitchenUtensils = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->nameEn : $this->namePt;
    }

    public function getTagline(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->taglineEn : $this->taglinePt;
    }

    public function getDescription(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->descriptionEn : $this->descriptionPt;
    }

    public function getCheckInInstructions(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->checkInInstructionsEn : $this->checkInInstructionsPt;
    }

    public function getCheckOutInstructions(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->checkOutInstructionsEn : $this->checkOutInstructionsPt;
    }

    public function getLocationDetails(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->locationDetailsEn : $this->locationDetailsPt;
    }

    public function getAddress(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->addressEn : $this->addressPt;
    }

    public function getRulesIntro(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->rulesIntroEn : $this->rulesIntroPt;
    }

    public function getActivitiesIntro(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->activitiesIntroEn : $this->activitiesIntroPt;
    }

    public function getArrivalInstructions(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->arrivalInstructionsEn : $this->arrivalInstructionsPt;
    }

    public function getNamePt(): string { return $this->namePt; }
    public function setNamePt(string $v): static { $this->namePt = $v; return $this; }
    public function getNameEn(): string { return $this->nameEn; }
    public function setNameEn(string $v): static { $this->nameEn = $v; return $this; }
    public function getTaglinePt(): string { return $this->taglinePt; }
    public function setTaglinePt(string $v): static { $this->taglinePt = $v; return $this; }
    public function getTaglineEn(): string { return $this->taglineEn; }
    public function setTaglineEn(string $v): static { $this->taglineEn = $v; return $this; }
    public function getDescriptionPt(): string { return $this->descriptionPt; }
    public function setDescriptionPt(string $v): static { $this->descriptionPt = $v; return $this; }
    public function getDescriptionEn(): string { return $this->descriptionEn; }
    public function setDescriptionEn(string $v): static { $this->descriptionEn = $v; return $this; }
    public function getCheckInInstructionsPt(): string { return $this->checkInInstructionsPt; }
    public function setCheckInInstructionsPt(string $v): static { $this->checkInInstructionsPt = $v; return $this; }
    public function getCheckInInstructionsEn(): string { return $this->checkInInstructionsEn; }
    public function setCheckInInstructionsEn(string $v): static { $this->checkInInstructionsEn = $v; return $this; }
    public function getCheckOutInstructionsPt(): string { return $this->checkOutInstructionsPt; }
    public function setCheckOutInstructionsPt(string $v): static { $this->checkOutInstructionsPt = $v; return $this; }
    public function getCheckOutInstructionsEn(): string { return $this->checkOutInstructionsEn; }
    public function setCheckOutInstructionsEn(string $v): static { $this->checkOutInstructionsEn = $v; return $this; }
    public function getLocationDetailsPt(): string { return $this->locationDetailsPt; }
    public function setLocationDetailsPt(string $v): static { $this->locationDetailsPt = $v; return $this; }
    public function getLocationDetailsEn(): string { return $this->locationDetailsEn; }
    public function setLocationDetailsEn(string $v): static { $this->locationDetailsEn = $v; return $this; }
    public function getAddressPt(): string { return $this->addressPt; }
    public function setAddressPt(string $v): static { $this->addressPt = $v; return $this; }
    public function getAddressEn(): string { return $this->addressEn; }
    public function setAddressEn(string $v): static { $this->addressEn = $v; return $this; }
    public function getArrivalInstructionsPt(): string { return $this->arrivalInstructionsPt; }
    public function setArrivalInstructionsPt(string $v): static { $this->arrivalInstructionsPt = $v; return $this; }
    public function getArrivalInstructionsEn(): string { return $this->arrivalInstructionsEn; }
    public function setArrivalInstructionsEn(string $v): static { $this->arrivalInstructionsEn = $v; return $this; }
    public function getWifiName(): string { return $this->wifiName; }
    public function setWifiName(string $v): static { $this->wifiName = $v; return $this; }
    public function getWifiPassword(): string { return $this->wifiPassword; }
    public function setWifiPassword(string $v): static { $this->wifiPassword = $v; return $this; }
    public function getWifiSecondaryName(): string { return $this->wifiSecondaryName; }
    public function setWifiSecondaryName(string $v): static { $this->wifiSecondaryName = $v; return $this; }
    public function getWifiSecondaryPassword(): string { return $this->wifiSecondaryPassword; }
    public function setWifiSecondaryPassword(string $v): static { $this->wifiSecondaryPassword = $v; return $this; }
    public function getCheckInTime(): string { return $this->checkInTime; }
    public function setCheckInTime(string $v): static { $this->checkInTime = $v; return $this; }
    public function getCheckInTimeEnd(): string { return $this->checkInTimeEnd; }
    public function setCheckInTimeEnd(string $v): static { $this->checkInTimeEnd = $v; return $this; }
    public function getCheckOutTime(): string { return $this->checkOutTime; }
    public function setCheckOutTime(string $v): static { $this->checkOutTime = $v; return $this; }
    public function getPetsPolicy(): string { return $this->petsPolicy; }
    public function setPetsPolicy(string $v): static { $this->petsPolicy = $v; return $this; }
    public function getSmokingPolicy(): string { return $this->smokingPolicy; }
    public function setSmokingPolicy(string $v): static { $this->smokingPolicy = $v; return $this; }
    public function getSilencePolicy(): string { return $this->silencePolicy; }
    public function setSilencePolicy(string $v): static { $this->silencePolicy = $v; return $this; }
    public function getVisitsPolicy(): string { return $this->visitsPolicy; }
    public function setVisitsPolicy(string $v): static { $this->visitsPolicy = $v; return $this; }
    public function getRating(): float { return $this->rating; }
    public function setRating(float $v): static { $this->rating = $v; return $this; }
    public function getBedrooms(): int { return $this->bedrooms; }
    public function setBedrooms(int $v): static { $this->bedrooms = $v; return $this; }
    public function getBathrooms(): int { return $this->bathrooms; }
    public function setBathrooms(int $v): static { $this->bathrooms = $v; return $this; }
    public function getMaxGuests(): int { return $this->maxGuests; }
    public function setMaxGuests(int $v): static { $this->maxGuests = $v; return $this; }
    public function getMapUrl(): string { return $this->mapUrl; }
    public function setMapUrl(string $v): static { $this->mapUrl = $v; return $this; }
    public function getLatitude(): string { return $this->latitude; }
    public function setLatitude(string $v): static { $this->latitude = $v; return $this; }
    public function getLongitude(): string { return $this->longitude; }
    public function setLongitude(string $v): static { $this->longitude = $v; return $this; }
    public function getPixKey(): string { return $this->pixKey; }
    public function setPixKey(string $v): static { $this->pixKey = $v; return $this; }
    public function getContactPhone(): string { return $this->contactPhone; }
    public function setContactPhone(string $v): static { $this->contactPhone = $v; return $this; }
    public function getContactEmail(): string { return $this->contactEmail; }
    public function setContactEmail(string $v): static { $this->contactEmail = $v; return $this; }
    public function getInstagramHandle(): string { return $this->instagramHandle; }
    public function setInstagramHandle(string $v): static { $this->instagramHandle = $v; return $this; }
    public function getAirbnbIcalUrl(): ?string { return $this->airbnbIcalUrl; }
    public function setAirbnbIcalUrl(?string $v): static { $this->airbnbIcalUrl = $v; return $this; }
    public function getAirbnbIcalLastSyncAt(): ?\DateTimeImmutable { return $this->airbnbIcalLastSyncAt; }
    public function setAirbnbIcalLastSyncAt(?\DateTimeImmutable $v): static { $this->airbnbIcalLastSyncAt = $v; return $this; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): static { $this->updatedAt = new \DateTimeImmutable(); return $this; }

    /** @return Collection<int, PropertyPhoto> */
    public function getPhotos(): Collection { return $this->photos; }

    public function addPhoto(PropertyPhoto $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setProperty($this);
        }

        return $this;
    }

    public function removePhoto(PropertyPhoto $photo): static
    {
        $this->photos->removeElement($photo);

        return $this;
    }

    /** @return Collection<int, GuideSpot> */
    public function getGuideSpots(): Collection { return $this->guideSpots; }

    public function addGuideSpot(GuideSpot $spot): static
    {
        if (!$this->guideSpots->contains($spot)) {
            $this->guideSpots->add($spot);
            $spot->setProperty($this);
        }

        return $this;
    }

    public function removeGuideSpot(GuideSpot $spot): static
    {
        $this->guideSpots->removeElement($spot);

        return $this;
    }

    /** @return Collection<int, FaqItem> */
    public function getFaqItems(): Collection { return $this->faqItems; }

    public function addFaqItem(FaqItem $item): static
    {
        if (!$this->faqItems->contains($item)) {
            $this->faqItems->add($item);
            $item->setProperty($this);
        }

        return $this;
    }

    public function removeFaqItem(FaqItem $item): static
    {
        $this->faqItems->removeElement($item);

        return $this;
    }

    public function getRulesIntroPt(): string { return $this->rulesIntroPt; }
    public function setRulesIntroPt(string $v): static { $this->rulesIntroPt = $v; return $this; }
    public function getRulesIntroEn(): string { return $this->rulesIntroEn; }
    public function setRulesIntroEn(string $v): static { $this->rulesIntroEn = $v; return $this; }

    /** @return Collection<int, HouseRule> */
    public function getHouseRules(): Collection { return $this->houseRules; }

    public function addHouseRule(HouseRule $rule): static
    {
        if (!$this->houseRules->contains($rule)) {
            $this->houseRules->add($rule);
            $rule->setProperty($this);
        }

        return $this;
    }

    public function removeHouseRule(HouseRule $rule): static
    {
        $this->houseRules->removeElement($rule);

        return $this;
    }

    public function getActivitiesIntroPt(): string { return $this->activitiesIntroPt; }
    public function setActivitiesIntroPt(string $v): static { $this->activitiesIntroPt = $v; return $this; }
    public function getActivitiesIntroEn(): string { return $this->activitiesIntroEn; }
    public function setActivitiesIntroEn(string $v): static { $this->activitiesIntroEn = $v; return $this; }

    /** @return Collection<int, ActivityItem> */
    public function getActivityItems(): Collection { return $this->activityItems; }

    public function addActivityItem(ActivityItem $item): static
    {
        if (!$this->activityItems->contains($item)) {
            $this->activityItems->add($item);
            $item->setProperty($this);
        }

        return $this;
    }

    public function removeActivityItem(ActivityItem $item): static
    {
        $this->activityItems->removeElement($item);

        return $this;
    }

    public function getKitchenIntroPt(): string { return $this->kitchenIntroPt; }
    public function setKitchenIntroPt(string $v): static { $this->kitchenIntroPt = $v; return $this; }
    public function getKitchenIntroEn(): string { return $this->kitchenIntroEn; }
    public function setKitchenIntroEn(string $v): static { $this->kitchenIntroEn = $v; return $this; }
    public function getKitchenIntro(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->kitchenIntroEn : $this->kitchenIntroPt;
    }

    /** @return Collection<int, KitchenPhoto> */
    public function getKitchenPhotos(): Collection { return $this->kitchenPhotos; }

    public function addKitchenPhoto(KitchenPhoto $photo): static
    {
        if (!$this->kitchenPhotos->contains($photo)) {
            $this->kitchenPhotos->add($photo);
            $photo->setProperty($this);
        }

        return $this;
    }

    public function removeKitchenPhoto(KitchenPhoto $photo): static
    {
        $this->kitchenPhotos->removeElement($photo);

        return $this;
    }

    /** @return Collection<int, KitchenUtensil> */
    public function getKitchenUtensils(): Collection { return $this->kitchenUtensils; }

    public function addKitchenUtensil(KitchenUtensil $utensil): static
    {
        if (!$this->kitchenUtensils->contains($utensil)) {
            $this->kitchenUtensils->add($utensil);
            $utensil->setProperty($this);
        }

        return $this;
    }

    public function removeKitchenUtensil(KitchenUtensil $utensil): static
    {
        $this->kitchenUtensils->removeElement($utensil);

        return $this;
    }
}
