<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Http\Listener;

use Glpi\Kernel\ListenersPriority;
use Session;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LoadSessionEntitiesListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        // Manage profile change
        if (
            $request->get("force_profile")
            && (($_SESSION['glpiactiveprofile']['id'] ?? -1) != $request->get("force_profile"))
            && isset($_SESSION['glpiprofiles'][$request->get("force_profile")])
        ) {
            Session::changeProfile($request->get("force_profile"));
        }

        // Manage entity change
        if (
            $request->get("force_entity")
            && (($_SESSION["glpiactive_entity"] ?? -1) != $request->get("force_entity"))
        ) {
            Session::changeActiveEntities($request->get("force_entity"), true);
        } elseif (Session::shouldReloadActiveEntities()) {
            Session::changeActiveEntities(
                $_SESSION["glpiactive_entity"],
                $_SESSION["glpiactive_entity_recursive"]
            );
        }

        // The user's current groups are stored in his session
        // If there was any change regarding groups membership and/or configuration, we
        // need to reset the data stored in his session
        if (
            isset($_SESSION['glpigroups'])
            && (
                !isset($_SESSION['glpigroups_cache_date'])
                || $_SESSION['glpigroups_cache_date'] < $GLPI_CACHE->get('last_group_change')
            )
        ) {
            Session::loadGroups();
        }
    }
}
