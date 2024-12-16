<?php

namespace Glpi\Http\Listener;

use Glpi\Http\ListenersPriority;
use Glpi\Kernel\PostBootEvent;
use Session;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoadLanguageListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PostBootEvent::class => ['onPostBoot', ListenersPriority::POST_BOOT_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onPostboot(): void
    {
        Session::loadLanguage();
    }
}
