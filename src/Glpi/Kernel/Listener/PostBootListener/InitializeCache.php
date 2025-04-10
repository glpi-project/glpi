<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Kernel\Listener\PostBootListener;

use Glpi\Cache\CacheManager;
use Glpi\Debug\Profiler;
use Glpi\Kernel\ListenersPriority;
use Glpi\Kernel\PostBootEvent;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class InitializeCache implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PostBootEvent::class => ['onPostBoot', ListenersPriority::POST_BOOT_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onPostBoot(): void
    {
        /** @var CacheInterface|null $GLPI_CACHE */
        global $GLPI_CACHE;

        Profiler::getInstance()->start('InitializeCache::execute', Profiler::CATEGORY_BOOT);

        $cache_manager = new CacheManager();
        $GLPI_CACHE = $cache_manager->getCoreCacheInstance();

        Profiler::getInstance()->stop('InitializeCache::execute');
    }
}
