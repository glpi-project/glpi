<?php

namespace Glpi\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LegacyGlobalsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [];
    }
}
