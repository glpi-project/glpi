<?php

namespace Glpi\Config;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class InitializeAppConfigListener implements EventSubscriberInterface
{
    public function __construct(private readonly InitializeAppConfig $initializeAppConfig)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(): void
    {
        $this->initializeAppConfig->initialize();
    }
}
