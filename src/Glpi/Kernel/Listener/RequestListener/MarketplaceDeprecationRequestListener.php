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

namespace Glpi\Kernel\Listener\RequestListener;

use Glpi\Kernel\ListenersPriority;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Toolbox;

class MarketplaceDeprecationRequestListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $path = $event->getRequest()->getPathInfo();

        // All plugins resources should now be accessed using the `/plugins/${plugin_key}/${resource_path}`.
        if (\str_starts_with($path, '/marketplace/')) {

            // /!\ `/marketplace/` URLs were massively used prior to GLPI 11.0.
            //
            // To not break URLs than can be found in the wild (in e-mail, forums, external apps configuration, ...),
            // please do not remove this behaviour before, at least, 2030 (about 5 years after GLPI 11.0.0 release).
            Toolbox::deprecated(
                sprintf(
                    'Accessing the plugins resources from the `/marketplace/` path is deprecated. Use the `%s` path instead of `%s`.',
                    preg_replace('#^/marketplace/#', '/plugins/', $path),
                    $path
                )
            );
        }
    }
}
