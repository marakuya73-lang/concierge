<?php

namespace App\Service;

use App\Entity\Extra;
use Symfony\Contracts\Translation\TranslatorInterface;

class BreakfastOfferService
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array{guests: int, couples: int, singles: int}
     */
    public function servingsForGuests(int $guests): array
    {
        $guests = max(1, $guests);

        return [
            'guests' => $guests,
            'couples' => intdiv($guests, 2),
            'singles' => $guests % 2,
        ];
    }

    /**
     * @param Extra[] $extras
     * @param int[]   $disabledIds
     *
     * @return list<array<string, mixed>>
     */
    public function buildOffers(
        array $extras,
        int $guests,
        array $disabledIds,
        string $locale,
        \DateTimeImmutable $checkInAt,
    ): array {
        $servings = $this->servingsForGuests($guests);
        $offers = [];

        foreach ($this->groupsByStyle($extras) as $group) {
            $offer = $this->composeOffer($group, $servings, $disabledIds, $locale, $checkInAt);
            if (null !== $offer) {
                $offers[] = $offer;
            }
        }

        usort($offers, static fn (array $a, array $b): int => strcmp((string) $a['breakfastStyle'], (string) $b['breakfastStyle']));

        return $offers;
    }

    /**
     * @param Extra[] $extras
     * @param int[]   $disabledIds
     *
     * @return array<string, mixed>|null
     */
    public function offerForRequestedExtra(
        Extra $requested,
        array $extras,
        int $guests,
        array $disabledIds,
        string $locale,
        \DateTimeImmutable $checkInAt,
    ): ?array {
        if (!$requested->isBreakfast()) {
            return null;
        }

        $style = $requested->getBreakfastStyle();
        foreach ($this->groupsByStyle($extras) as $group) {
            if ($group['style'] === $style) {
                return $this->composeOffer($group, $this->servingsForGuests($guests), $disabledIds, $locale, $checkInAt);
            }
        }

        return null;
    }

    /**
     * @param Extra[] $extras
     *
     * @return list<array{style: string, couple: ?Extra, single: ?Extra}>
     */
    private function groupsByStyle(array $extras): array
    {
        $groups = [];

        foreach ($extras as $extra) {
            if (!$extra->isBreakfast() || !$extra->isActive()) {
                continue;
            }

            $style = $extra->getBreakfastStyle();
            if (null === $style || 'other' === $style) {
                continue;
            }

            $groups[$style] ??= ['style' => $style, 'couple' => null, 'single' => null];
            if ($extra->isBreakfastCouple()) {
                $groups[$style]['couple'] = $extra;
            } elseif ($extra->isBreakfastSingle()) {
                $groups[$style]['single'] = $extra;
            }
        }

        return array_values($groups);
    }

    /**
     * @param array{style: string, couple: ?Extra, single: ?Extra} $group
     * @param array{guests: int, couples: int, singles: int}       $servings
     * @param int[]                                               $disabledIds
     *
     * @return array<string, mixed>|null
     */
    private function composeOffer(
        array $group,
        array $servings,
        array $disabledIds,
        string $locale,
        \DateTimeImmutable $checkInAt,
    ): ?array {
        $couple = $group['couple'];
        $single = $group['single'];
        $couplesNeeded = $servings['couples'];
        $singlesNeeded = $servings['singles'];

        if ($couplesNeeded > 0 && !$this->isUsable($couple, $disabledIds)) {
            return null;
        }
        if ($singlesNeeded > 0 && !$this->isUsable($single, $disabledIds)) {
            return null;
        }

        $primary = $couplesNeeded > 0 ? $couple : $single;
        if (!$primary instanceof Extra) {
            return null;
        }

        $unitPrice = ($couplesNeeded * ($couple?->getPrice() ?? 0.0))
            + ($singlesNeeded * ($single?->getPrice() ?? 0.0));
        $leadHours = $this->leadTimeHours($couple, $single);
        $bookable = $this->isBookable($couple, $couplesNeeded, $checkInAt)
            && $this->isBookable($single, $singlesNeeded, $checkInAt);
        $composition = $this->compositionLabel($couplesNeeded, $singlesNeeded, $locale);
        $name = $primary->getBreakfastBaseName($locale).' ('.$composition.')';
        $covers = $this->coversLabel($servings['guests'], $locale);
        $description = trim($covers.' '.$this->withoutCatalogGuestCount($primary->getDescription($locale)));

        return [
            'id' => $primary->getId(),
            'name' => $name,
            'namePt' => $primary->getBreakfastBaseName('pt').' ('.$this->compositionLabel($couplesNeeded, $singlesNeeded, 'pt').')',
            'nameEn' => $primary->getBreakfastBaseName('en').' ('.$this->compositionLabel($couplesNeeded, $singlesNeeded, 'en').')',
            'description' => $description,
            'price' => $unitPrice,
            'currency' => $primary->getCurrency(),
            'category' => $primary->getCategory(),
            'icon' => $primary->getIcon(),
            'isBreakfast' => true,
            'breakfastPackage' => true,
            'breakfastStyle' => $group['style'],
            'leadTimeHours' => $leadHours,
            'bookable' => $bookable,
            'isRajaaram' => false,
        ];
    }

    private function compositionLabel(int $couples, int $singles, string $locale): string
    {
        $parts = [];
        if (1 === $couples) {
            $parts[] = $this->translator->trans('app.breakfast_comp_couple', locale: $locale);
        } elseif ($couples > 1) {
            $parts[] = $this->translator->trans('app.breakfast_comp_couples', ['%count%' => $couples], locale: $locale);
        }
        if ($singles > 0) {
            $parts[] = $this->translator->trans('app.breakfast_comp_single', locale: $locale);
        }

        return implode(' + ', $parts);
    }

    private function coversLabel(int $guests, string $locale): string
    {
        if (1 === $guests) {
            return $this->translator->trans('app.breakfast_covers_one', locale: $locale);
        }

        return $this->translator->trans('app.breakfast_covers', ['%count%' => $guests], locale: $locale);
    }

    private function withoutCatalogGuestCount(string $description): string
    {
        $cleaned = preg_replace(
            '/\s*(Para 1 hóspede|Para 2 ou mais hóspedes|For 1 guest|For 2 or more guests)\.?\s*/iu',
            ' ',
            $description,
        ) ?? $description;

        return trim(preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned);
    }

    /** @param int[] $disabledIds */
    private function isUsable(?Extra $extra, array $disabledIds): bool
    {
        if (!$extra instanceof Extra || !$extra->isActive()) {
            return false;
        }

        return !\in_array($extra->getId(), $disabledIds, true);
    }

    private function isBookable(?Extra $extra, int $needed, \DateTimeImmutable $checkInAt): bool
    {
        if ($needed <= 0) {
            return true;
        }

        return $extra instanceof Extra && $extra->canBeBookedBefore($checkInAt);
    }

    private function leadTimeHours(?Extra $couple, ?Extra $single): ?int
    {
        $hours = array_values(array_filter(
            [$couple?->getLeadTimeHours(), $single?->getLeadTimeHours()],
            static fn (?int $value): bool => null !== $value && $value > 0,
        ));

        return [] === $hours ? null : max($hours);
    }
}
