<?php

namespace Glpi\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class FirewallListener implements EventSubscriberInterface
{
    public const STRATEGY_KEY = '_glpi_security_strategy';

    public function __construct(private Firewall $firewall)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 0]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $this->firewall->applyStrategy(
            $request->server->get('PHP_SELF'),
            $request->attributes->get(self::STRATEGY_KEY),
        );
    }
}
