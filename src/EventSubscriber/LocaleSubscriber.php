<?php

namespace App\EventSubscriber;

use App\Service\GuestLocaleResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED = ['pt', 'en'];

    public function __construct(
        private GuestLocaleResolver $guestLocaleResolver,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = 'pt';

        if ($request->hasSession()) {
            $locale = $request->getSession()->get('_locale', 'pt');
        }

        if ($request->query->has('_locale')) {
            $requested = $request->query->get('_locale');
            if (in_array($requested, self::SUPPORTED, true)) {
                $locale = $requested;
                if ($request->hasSession()) {
                    $request->getSession()->set('_locale', $locale);
                }
            }
        } else {
            $bookingLocale = $this->guestLocaleResolver->resolveFromRequest($request);
            if ($bookingLocale && in_array($bookingLocale, self::SUPPORTED, true)) {
                $locale = $bookingLocale;
                if ($request->hasSession()) {
                    $request->getSession()->set('_locale', $locale);
                }
            }
        }

        $request->setLocale($locale);
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => [['onKernelRequest', 20]]];
    }
}
