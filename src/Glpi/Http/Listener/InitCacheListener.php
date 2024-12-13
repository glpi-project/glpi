<?php

namespace Glpi\Http\Listener;

use Glpi\Cache\CacheManager;
use Glpi\Http\ListenersPriority;
use Glpi\Kernel\PostBootEvent;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InitCacheListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // priority = 1 to be executed before the default Symfony listeners
            PostBootEvent::class => ['onPostBoot', ListenersPriority::POST_BOOT_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onPostboot(): void
    {
        /** @var ?CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        if ($GLPI_CACHE) {
            // Don't override, it might have been set for specific reasons already, especially for some CLI scripts.
            return;
        }

        $cache_manager = new CacheManager();
        if (isset($_SESSION['is_installing'])) {
            $GLPI_CACHE = $cache_manager->getInstallerCacheInstance();
        } else {
            $GLPI_CACHE = $cache_manager->getCoreCacheInstance();
        }
    }
}
