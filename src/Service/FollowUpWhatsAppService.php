<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\BookingExtra;

/**
 * Prepares Rajaaram therapy follow-up WhatsApp messages in the guest's language.
 */
class FollowUpWhatsAppService
{
    public const REVIEW_URL = 'https://g.page/r/CXTiyCQxGUFEEBM/review';

    public const PARTNERSHIP_URL = 'https://www.rajaaram.com.br/pt/partnership';

    public const LOCALE_PT = 'pt';

    public const LOCALE_EN = 'en';

    /** @var list<string> */
    public const LOCALES = [self::LOCALE_PT, self::LOCALE_EN];

    /** @var array<string, string> */
    private const KIND_LABELS = [
        'before' => 'Dia anterior',
        'same_day' => 'No dia',
        'day_2' => '2 dias',
        'week_1' => '1 semana',
        'week_1_review' => 'Pedido de review',
        'week_2' => '2 semanas',
        'month_1' => '1 mês',
        'month_1_referral' => 'Parceria',
    ];

    /**
     * @return array{
     *   defaultLocale: string,
     *   recipients: list<array{
     *     id: string,
     *     displayName: string,
     *     whatsappDisplay: string,
     *     hasWhatsapp: bool,
     *     experience: string,
     *     sessionWhen: string,
     *     locales: array<string, list<array{id: string, label: string, text: string, url: ?string}>>
     *   }>
     * }
     */
    public function panelForBooking(Booking $booking): array
    {
        return [
            'defaultLocale' => $this->normalizeLocale($booking->getGuestLocale()),
            'recipients' => $this->recipientsForBooking($booking),
        ];
    }

    /**
     * @return list<array{
     *   id: string,
     *   displayName: string,
     *   whatsappDisplay: string,
     *   hasWhatsapp: bool,
     *   experience: string,
     *   sessionWhen: string,
     *   locales: array<string, list<array{id: string, label: string, text: string, url: ?string}>>
     * }>
     */
    private function recipientsForBooking(Booking $booking): array
    {
        if ($booking->hasRajaaramSession()) {
            $recipients = [];
            $hasTransfer = $this->bookingHasTransfer($booking);
            $recipients[] = $this->buildRecipient(
                'g1',
                trim((string) ($booking->getRajaaramGuest1Name() ?: $booking->getGuestName())),
                (string) ($booking->getRajaaramGuest1Whatsapp() ?: $booking->getGuestWhatsapp() ?: ''),
                (string) ($booking->getRajaaramTherapy() ?? ''),
                $booking->getRajaaramTherapyDate(),
                (string) ($booking->getRajaaramTherapyTime() ?? ''),
                $hasTransfer,
            );

            $guest2 = trim((string) ($booking->getRajaaramGuest2Name() ?? ''));
            if ('' !== $guest2) {
                $recipients[] = $this->buildRecipient(
                    'g2',
                    $guest2,
                    (string) ($booking->getRajaaramGuest2Whatsapp() ?? ''),
                    (string) ($booking->getRajaaramTherapy2() ?: $booking->getRajaaramTherapy() ?? ''),
                    $booking->getRajaaramTherapy2Date() ?? $booking->getRajaaramTherapyDate(),
                    (string) ($booking->getRajaaramTherapy2Time() ?: $booking->getRajaaramTherapyTime() ?? ''),
                    $hasTransfer,
                );
            }

            return $recipients;
        }

        return [$this->buildRecipient(
            'guest',
            $booking->getGuestName(),
            (string) ($booking->getGuestWhatsapp() ?? ''),
            '',
            null,
            '',
            $this->bookingHasTransfer($booking),
        )];
    }

    /**
     * @return array{
     *   id: string,
     *   displayName: string,
     *   whatsappDisplay: string,
     *   hasWhatsapp: bool,
     *   experience: string,
     *   sessionWhen: string,
     *   locales: array<string, list<array{id: string, label: string, text: string, url: ?string}>>
     * }
     */
    private function buildRecipient(
        string $id,
        string $fullName,
        string $whatsappRaw,
        string $therapyCode,
        ?\DateTimeImmutable $date,
        string $time,
        bool $hasTransfer,
    ): array {
        $digits = $this->waMeDigits($whatsappRaw);
        $locales = [];
        foreach (self::LOCALES as $locale) {
            $experience = $this->experienceName($therapyCode, $locale);
            $messages = [];
            foreach (self::KIND_LABELS as $kind => $label) {
                $text = $this->compose($kind, $locale, $fullName, $experience, $time, $hasTransfer);
                $messages[] = [
                    'id' => $kind,
                    'label' => $label,
                    'text' => $text,
                    'url' => null !== $digits ? $this->whatsAppUrl($digits, $text) : null,
                ];
            }
            $locales[$locale] = $messages;
        }

        $fullName = trim($fullName);
        if (Booking::GUEST_NAME_PENDING === $fullName || str_starts_with($fullName, 'Reserva direct')) {
            $fullName = '';
        }

        return [
            'id' => $id,
            'displayName' => '' !== $fullName ? $fullName : 'Cliente',
            'whatsappDisplay' => '' !== trim($whatsappRaw) ? trim($whatsappRaw) : 'sem WhatsApp',
            'hasWhatsapp' => null !== $digits,
            'experience' => $this->experienceName($therapyCode, self::LOCALE_PT),
            'sessionWhen' => $this->formatSessionWhen($date, $time),
            'hasTransfer' => $hasTransfer,
            'locales' => $locales,
        ];
    }

    public function compose(
        string $kind,
        string $locale,
        string $fullName,
        string $experience,
        string $time,
        bool $hasTransfer = false,
    ): string {
        $locale = $this->normalizeLocale($locale);
        $name = $this->firstName($fullName);
        $timeLabel = $this->formatTime($time, $locale);
        $session = $this->sessionNoun($experience, $locale);

        return match ($kind) {
            'before' => $this->messageBefore($locale, $name, $session, $timeLabel, $hasTransfer),
            'same_day' => $this->messageSameDay($locale, $name),
            'day_2' => $this->messageDay2($locale, $name, $session),
            'week_1' => $this->messageWeek1($locale, $name, $session),
            'week_1_review' => $this->messageWeek1Review($locale, $name),
            'week_2' => $this->messageWeek2($locale, $name),
            'month_1' => $this->messageMonth1($locale, $name, $session),
            'month_1_referral' => $this->messageMonth1Referral($locale, $name),
            default => throw new \InvalidArgumentException('Unknown follow-up kind: '.$kind),
        };
    }

    public function whatsAppUrl(string $digits, string $text): string
    {
        return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
    }

    public function experienceName(string $therapyCode, string $locale = self::LOCALE_PT): string
    {
        $locale = $this->normalizeLocale($locale);
        $names = [
            Booking::RAJAARAM_THERAPY_RESET_EXPRESS => ['pt' => 'Reset Express', 'en' => 'Reset Express'],
            Booking::RAJAARAM_THERAPY_RESET_CEREMONY => ['pt' => 'Cerimônia Reset', 'en' => 'Reset Ceremony'],
            Booking::RAJAARAM_THERAPY_DEEP_DIVE => ['pt' => 'Mergulho Profundo', 'en' => 'Deep Dive'],
            Booking::RAJAARAM_THERAPY_CHAKRA_ALIGNMENT_EXPRESS => ['pt' => 'Alinhamento dos Chakras', 'en' => 'Chakra Alignment'],
            Booking::RAJAARAM_THERAPY_COMPLETE_CHAKRA => ['pt' => 'Chakras Completos', 'en' => 'Complete Chakras'],
        ];

        return $names[$therapyCode][$locale] ?? '';
    }

    public function waMeDigits(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', trim($raw)) ?? '';
        if ('' === $digits) {
            return null;
        }

        if (!str_starts_with($digits, '55') && (10 === strlen($digits) || 11 === strlen($digits))) {
            return '55'.$digits;
        }

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? $digits : null;
    }

    private function messageBefore(string $locale, string $name, string $session, string $time, bool $hasTransfer): string
    {
        $hello = $this->hello($locale, $name);
        if (self::LOCALE_EN === $locale) {
            $when = '' !== $time ? ', at '.$time : '';
            $reminder = $hello."\nTomorrow is your {$session}{$when}.\nCan you confirm that still works?";
            if ($hasTransfer) {
                return $reminder."\nYour transfer is already arranged — we'll come get you. If the time changes, just let me know.";
            }

            return $reminder."\n\n".$this->directionsEnglish();
        }

        $when = '' !== $time ? ', às '.$time : '';
        $reminder = $hello."\nAmanhã é a sua {$session}{$when}.\nDá para confirmar se ainda está tudo certo?";
        if ($hasTransfer) {
            return $reminder."\nO transfer já está combinado — a gente busca você. Qualquer mudança de horário, me avisa.";
        }

        return $reminder."\n\n".$this->directionsPortuguese();
    }

    public function bookingHasTransfer(Booking $booking): bool
    {
        foreach ($booking->getBookingExtras() as $extra) {
            if (BookingExtra::STATUS_CANCELLED === $extra->getStatus()) {
                continue;
            }
            if ('transfer' === ($extra->getExtra()?->getCategory() ?? '')) {
                return true;
            }
            $name = strtolower($extra->getDisplayName());
            if (str_contains($name, 'transfer') || str_contains($name, 'translado')) {
                return true;
            }
        }

        return false;
    }

    private function directionsPortuguese(): string
    {
        return implode("\n", [
            '📍 Como chegar ao Rajaaram',
            '',
            'Ao sair de Alto Paraíso de Goiás, siga em direção ao povoado do Moinho.',
            '',
            'No caminho, você encontra uma bifurcação. Mantenha-se à esquerda, seguindo as placas para o Moinho.',
            '',
            'Ao chegar à placa “Bem-vindos ao Moinho”, atravesse a ponte e continue em direção ao Solarion e às cachoeiras Anjos e Arcanjos.',
            '',
            'Logo após a ponte:',
            '• Pegue a primeira rua à esquerda',
            '• Em seguida, a primeira rua à direita',
            '• Siga até o final da rua',
            '• No final, vire à esquerda na estrada de terra, pelo caminho das cachoeiras',
            '',
            'Depois de uns 200 metros, você vê uma subida à direita — postes laranjas dos dois lados e uma placa do Rajaaram.',
            '',
            'Sobe essa rua até o final. Você chegou quando encontrar a placa “Ater Tumti” e ouvir os cachorros dando as boas-vindas 🐶✨. Se estiverem soltos, fica tranquilo: são dóceis e amigáveis.',
            '',
            '🚗 Estacionamento',
            'Pode estacionar no local. Ao chegar, toque o sino que a gente vai receber vocês.',
            '',
            '🚙 Sobre o acesso',
            'O último trecho é estrada de terra, com irregularidades. Melhor ir com um carro mais alto.',
            '',
            'Se o carro for muito baixo ou você não se sentir à vontade, temos um translado gratuito entre o povoado do Moinho e o Rajaaram. É só avisar com antecedência.',
            '',
            '🚐 Transfer opcional',
            'Também fazemos transfer saindo de Alto Paraíso, com valor adicional, se quiser relaxar desde o começo.',
            '',
            'Qualquer dúvida no caminho, escreve que a gente ajuda. 💛',
        ]);
    }

    private function directionsEnglish(): string
    {
        return implode("\n", [
            '📍 How to get to Rajaaram',
            '',
            'When you leave Alto Paraíso de Goiás, head toward the village of Moinho.',
            '',
            'On the way you will reach a fork. Stay left, following the signs to Moinho.',
            '',
            'When you see the “Bem-vindos ao Moinho” sign, cross the bridge and continue toward Solarion and the Anjos and Arcanjos waterfalls.',
            '',
            'Right after the bridge:',
            '• Take the first street on the left',
            '• Then the first street on the right',
            '• Follow it to the end',
            '• At the end, turn left onto the dirt road along the waterfall route',
            '',
            'After about 200 meters you will see an uphill turn on the right — orange posts on both sides and a Rajaaram sign.',
            '',
            'Drive up that road to the end. You have arrived when you see the “Ater Tumti” sign and hear our dogs welcoming you 🐶✨. If they are loose, no worry: they are gentle and friendly.',
            '',
            '🚗 Parking',
            'Feel free to park on site. When you arrive, ring the bell and we will come to welcome you.',
            '',
            '🚙 About the road',
            'The last stretch is a dirt road with some bumps. A higher car is more comfortable.',
            '',
            'If your car is very low or you do not feel comfortable driving it, we offer a free shuttle between Moinho village and Rajaaram. Just let us know in advance.',
            '',
            '🚐 Optional transfer',
            'We also offer a transfer from Alto Paraíso for an extra fee, if you would rather relax from the start.',
            '',
            'If anything is unclear on the way, write to us — we are happy to help. 💛',
        ]);
    }

    private function messageSameDay(string $locale, string $name): string
    {
        $hello = $this->hello($locale, $name);
        if (self::LOCALE_EN === $locale) {
            return $hello."\nHow are you feeling after today's session?\nDrink some water, take it easy — and if you want to share how you feel, I'm here.";
        }

        return $hello."\nComo você está depois da sessão de hoje?\nBebe água, vai com calma. Se quiser contar como se sente, estou aqui.";
    }

    private function messageDay2(string $locale, string $name, string $session): string
    {
        $hello = $this->hello($locale, $name, false);
        if (self::LOCALE_EN === $locale) {
            return $hello."\nHow has your body been responding since the {$session}?\nIf anything comes up — a question, a dream, something uncomfortable — you can write to me.";
        }

        return $hello."\nComo o corpo tem respondido desde a {$session}?\nSe algo surgiu — uma dúvida, um sonho, um incômodo — pode escrever.";
    }

    private function messageWeek1(string $locale, string $name, string $session): string
    {
        $hello = $this->hello($locale, $name, false);
        if (self::LOCALE_EN === $locale) {
            return $hello."\nIt's been a week since your {$session}. How has it been for you?\nIf you noticed any shift, even a small one, I'd love to hear.";
        }

        return $hello."\nJá passou uma semana da sua {$session}. Como tem sido por aí?\nSe notou alguma mudança, mesmo pequena, adoro saber.";
    }

    private function messageWeek1Review(string $locale, string $name): string
    {
        $who = '' !== $name ? $name : '';
        if (self::LOCALE_EN === $locale) {
            $open = '' !== $who ? "{$who}, thank you for coming." : 'Thank you for coming.';

            return $open."\nIf the session stayed with you, a few lines on Google help others find this work:\n".self::REVIEW_URL."\nOnly if it feels right.";
        }

        $open = '' !== $who ? "{$who}, obrigada por ter vindo." : 'Obrigada por ter vindo.';

        return $open."\nSe a sessão ficou com você, umas linhas no Google ajudam outras pessoas a encontrar este trabalho:\n".self::REVIEW_URL."\nSó se fizer sentido para você.";
    }

    private function messageWeek2(string $locale, string $name): string
    {
        $hello = $this->hello($locale, $name, false);
        if (self::LOCALE_EN === $locale) {
            return $hello."\nJust checking in.\nI hope what moved in the session is still settling gently. I'm here if you need anything.";
        }

        return $hello."\nPassei por aqui só para saber de você.\nEspero que o que se moveu na sessão continue a assentar com calma. Qualquer coisa, estou por aqui.";
    }

    private function messageMonth1(string $locale, string $name, string $session): string
    {
        $hello = $this->hello($locale, $name, false);
        if (self::LOCALE_EN === $locale) {
            return $hello."\nIt's been about a month since your {$session}.\nHow do you feel now, with a bit more distance? Sometimes the deeper changes only show themselves later.";
        }

        return $hello."\nFaz cerca de um mês da sua {$session}.\nComo você se sente agora, com um pouco mais de distância? Às vezes o que muda por dentro só aparece depois.";
    }

    private function messageMonth1Referral(string $locale, string $name): string
    {
        $who = '' !== $name ? $name : '';
        if (self::LOCALE_EN === $locale) {
            $open = '' !== $who ? "{$who}, thank you for trusting this work." : 'Thank you for trusting this work.';

            return $open."\nIf someone close to you might benefit, you can share our partnership page — everything is explained there:\n".self::PARTNERSHIP_URL;
        }

        $open = '' !== $who ? "{$who}, obrigada pela confiança." : 'Obrigada pela confiança.';

        return $open."\nSe alguém próximo puder se beneficiar deste trabalho, pode compartilhar a nossa página de parceria — o caminho está explicado lá:\n".self::PARTNERSHIP_URL;
    }

    private function hello(string $locale, string $name, bool $leaf = true): string
    {
        if (self::LOCALE_EN === $locale) {
            return '' !== $name ? "Hi {$name}".($leaf ? ' 🌿' : '') : 'Hi'.($leaf ? ' 🌿' : '');
        }

        return '' !== $name ? "Oi, {$name}".($leaf ? ' 🌿' : '') : 'Oi'.($leaf ? ' 🌿' : '');
    }

    private function sessionNoun(string $experience, string $locale): string
    {
        if ('' === $experience) {
            return self::LOCALE_EN === $locale ? 'session' : 'sessão';
        }

        return self::LOCALE_EN === $locale ? $experience : 'sessão de '.$experience;
    }

    private function firstName(string $fullName): string
    {
        $fullName = trim($fullName);
        if ('' === $fullName
            || Booking::GUEST_NAME_PENDING === $fullName
            || str_starts_with($fullName, 'Reserva direct')) {
            return '';
        }

        return explode(' ', $fullName)[0];
    }

    private function formatTime(string $time, string $locale): string
    {
        $time = trim($time);
        if ('' === $time) {
            return '';
        }

        $dt = \DateTimeImmutable::createFromFormat('H:i', $time, new \DateTimeZone('America/Sao_Paulo'));
        if (false === $dt) {
            $dt = \DateTimeImmutable::createFromFormat('H:i:s', $time, new \DateTimeZone('America/Sao_Paulo'));
        }
        if (false === $dt) {
            return $time;
        }

        if (self::LOCALE_EN === $locale) {
            return $dt->format('g:i a');
        }

        $minutes = $dt->format('i');

        return '00' === $minutes ? $dt->format('G').'h' : $dt->format('G').'h'.$minutes;
    }

    private function formatSessionWhen(?\DateTimeImmutable $date, string $time): string
    {
        $dateLabel = $date ? $date->format('d/m/Y') : '';
        $timeLabel = $this->formatTime($time, self::LOCALE_PT);
        if ('' === $dateLabel && '' === $timeLabel) {
            return '';
        }
        if ('' === $timeLabel) {
            return $dateLabel;
        }
        if ('' === $dateLabel) {
            return $timeLabel;
        }

        return $dateLabel.' · '.$timeLabel;
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = strtolower(substr(trim($locale), 0, 2));

        return self::LOCALE_EN === $locale ? self::LOCALE_EN : self::LOCALE_PT;
    }
}
