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

use Glpi\Asset\AssetDefinitionManager;
use Glpi\Debug\Profiler;
use Glpi\Dropdown\DropdownDefinitionManager;
use Glpi\Kernel\KernelListenerTrait;
use Glpi\Kernel\ListenersPriority;
use Glpi\Kernel\PostBootEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class CustomObjectsAutoloaderRegistration implements EventSubscriberInterface
{
    use KernelListenerTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            PostBootEvent::class => ['onPostBoot', ListenersPriority::POST_BOOT_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onPostBoot(): void
    {
        if (!$this->isDatabaseUsable()) {
            // Requires the database to be available.
            return;
        }

        Profiler::getInstance()->start('CustomObjectsAutoloaderRegistration::execute', Profiler::CATEGORY_BOOT);

        AssetDefinitionManager::getInstance()->registerAutoload();
        DropdownDefinitionManager::getInstance()->registerAutoload();

        Profiler::getInstance()->stop('CustomObjectsAutoloaderRegistration::execute');
    }
}
