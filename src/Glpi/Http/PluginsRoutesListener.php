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

namespace Glpi\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class PluginsRoutesListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::LEGACY_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $controller = $request->attributes->get('_controller');

        if (!\str_starts_with($controller, \NS_PLUG)) {
            return;
        }

        $regex = '~^' . \rtrim(\NS_PLUG, '\\') . '\\\\([^\\\\]+)\\\\.*$~isUu';
        $plugin_name = preg_replace($regex, '$1', $controller);

        if (!$plugin_name || $plugin_name === $controller) {
            throw new \RuntimeException(\sprintf(
                'Controller "%s" should have matched a plugin, but could not get the plugin name from its FQCN.',
                $controller,
            ));
        }

        if (!\Plugin::isPluginLoaded(strtolower($plugin_name))) {
            // Plugin not active = routes not loaded.
            // Removing attributes in case an exception listener would want to use them.
            $request->attributes->remove('_route');
            $request->attributes->remove('_controller');
            $request->attributes->remove('_route_params');

            throw new NotFoundHttpException('Route not found');
        }
    }
}
