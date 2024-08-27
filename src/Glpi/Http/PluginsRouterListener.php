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

use Glpi\Controller\AbstractController;
use Glpi\DependencyInjection\PluginContainer;
use Glpi\DependencyInjection\PublicService;
use Plugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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

        $plugin_name = $route_params['plugin_name'];

        if (!Plugin::isPluginActive($plugin_name)) {
            // plugin is inactive, forward to 404 error page
            throw new NotFoundHttpException(sprintf('Plugin `%s` is not active.', $plugin_name));
        }

        /** @var Router $router */
        $router = $this->plugin_container->get('glpi_plugin_router');

        try {
            $matches = $router->matchRequest($request);
        } catch (ResourceNotFoundException) {
            // No route found, let Symfony do the rest of the work.
            return;
        }

        if (!isset($matches['_controller'])) {
            throw new NotFoundHttpException(sprintf('Unable to find the controller for path "%s". The route is wrongly configured. Did you forget to set the "_controller" request attribute?', $request->getPathInfo()));
        }

        $matches['_controller'] = $this->resolveController($matches['_controller']);

        $request->attributes->add($matches);

        unset($matches['_route'], $matches['_controller']);
        $request->attributes->set('_route_params', $matches);
    }

    private function resolveController(mixed $controller): callable
    {
        if (\is_object($controller) || \is_callable($controller)) {
            // Nothing to do, already set.
            return $controller;
        }

        if (\is_array($controller)) {
            [$class, $method] = $controller;
        } elseif (\str_contains($controller, ':')) {
            [$class, $method] = \preg_split('~:+~', $controller, 2);
        } else {
            $class = $controller;
            $method = null;
        }

        if (!$class || !\is_string($class)) {
            throw new \RuntimeException('Wrongly formed controller array');
        }

        if (!$this->plugin_container->has($class)) {
            throw new \RuntimeException(\sprintf(
                'Expected controller class `%s` to be a public service, but did not find it in the service container.'
                    . ' You should either implement the `%s` interface, or extend the `%s` abstract class.',
                $class,
                PublicService::class,
                AbstractController::class
            ));
        }

        try {
            // Try to instantiate without any parameters
            $object = new $class();
        } catch (\Error) {
            // Try to load the service from the DI container
            $object = $this->plugin_container->get($class);
        }

        if (!\is_callable($object)) {
            return \sprintf(
                'Controller class `%s` cannot be called without a method name. You need to implement the `__invoke()` method, or add your route parameters on a public method with the `Route` attribute.',
                $class
            );
        }

        return $method ? [$object, $method] : $object;
    }
}
