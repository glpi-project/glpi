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

use Glpi\DependencyInjection\PluginContainer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Router;

class PluginsRouterListener implements EventSubscriberInterface
{
    public const ROUTE_NAME = 'glpi_plugin';

    public function __construct(
        private readonly PluginContainer $plugin_container,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::LEGACY_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->attributes->get('_controller')) {
            // routing is already done
            return;
        }

        $route_name = $request->attributes->get('_route');
        $route_params = $request->attributes->get('_route_params');

        if ($route_name !== self::ROUTE_NAME) {
            // not plugins
            return;
        }

        [
            'plugins_or_marketplace' => $plugins_or_marketplace,
            'plugin_name' => $plugin_name,
            'path_rest' => $path_rest,
        ] = $route_params;

        if (!\Plugin::isPluginActive($plugin_name)) {
            $request->attributes->remove('_route');
            $request->attributes->remove('_controller');
            $request->attributes->remove('_route_params');

            foreach ($route_params as $k => $_) {
                $request->attributes->remove($k);
            }

            throw new NotFoundHttpException('No route found.');
        }


        $router = $this->plugin_container->get('glpi_plugins_router');
        if (!$router instanceof Router) {
            throw new \RuntimeException('Incorrectly set Router in GLPI\'s Plugin container.');
        }

        $matches = [];
        try {
            $matches = $router->matchRequest($request);
        } catch (ResourceNotFoundException) {
            // No route found, let Symfony do the rest of the work.
            return;
        }

        foreach ($matches as $attribute => $value) {
            $request->attributes->set($attribute, $value);
        }
        $request->attributes->set('_route_params', $matches);
    }
}
