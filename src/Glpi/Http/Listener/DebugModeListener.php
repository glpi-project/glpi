<?php

namespace Glpi\Http\Listener;

use Glpi\Http\ListenersPriority;
use Glpi\Kernel\PostBootEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DebugModeListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PostBootEvent::class => ['onPostBoot', ListenersPriority::POST_BOOT_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onPostBoot(): void
    {
        if (
            isCommandLine()
            && !defined('TU_USER') // In test suite context, used --debug option is the atoum one
            && isset($_SERVER['argv'])
        ) {
            $key = array_search('--debug', $_SERVER['argv']);
            if ($key) {
                $_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
                unset($_SERVER['argv'][$key]);
                $_SERVER['argv']           = array_values($_SERVER['argv']);
                $_SERVER['argc']--;
            }
        }

    }
}
