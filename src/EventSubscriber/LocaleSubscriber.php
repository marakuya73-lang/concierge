<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED = ['pt', 'en'];

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
        }

        $request->setLocale($locale);
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => [['onKernelRequest', 20]]];
    }
}
