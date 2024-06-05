<?php

namespace Glpi\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class LegacyRouter implements EventSubscriberInterface
{
    public function __construct(
        private LegacyRouterRunner $legacyRouterRunner
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $response = $this->legacyRouterRunner->run($request);

        if ($response) {
            $event->setResponse($response);
        }
    }
}
