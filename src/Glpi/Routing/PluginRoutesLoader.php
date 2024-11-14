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

namespace Glpi\Routing;

use Plugin;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Loader\AttributeDirectoryLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PluginRoutesLoader extends Loader
{
    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        $routes = new RouteCollection();

        $plugins = Plugin::getPlugins();

        $paths = [];

        foreach ($plugins as $k => $plugin_name) {
            $paths[$k] = Plugin::getPhpDir($plugin_name) . '/src/Controller/';
        }

        $loader = new AttributeDirectoryLoader(
            new FileLocator($paths),
            new AttributeRouteControllerLoader($this->env),
        );

        foreach ($plugins as $k => $plugin_name) {
            $plugin_path = $paths[$k];
            $plugin_routes = $loader->load($plugin_path, 'attribute');
            if (!$plugin_routes) {
                // No route found in the plugin
                continue;
            }

            foreach ($plugin_routes as $route) {
                /** @var Route $route */
                $prefix = '/plugins/' . $plugin_name . '/';
                if (!\str_starts_with($route->getPath(), $prefix)) {
                    $route->setPath($prefix . \ltrim($route->getPath(), '/'));
                }
            }

            $routes->addCollection($plugin_routes);
        }

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $resource === 'glpi_routes';
    }
}
