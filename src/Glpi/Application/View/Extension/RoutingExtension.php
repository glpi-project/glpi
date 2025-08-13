<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Application\View\Extension;

use Html;
use Session;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class RoutingExtension extends AbstractExtension
{
    public function __construct(
        private readonly ?UrlGeneratorInterface $router = null
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('index_path', [$this, 'indexPath']),
            new TwigFunction('path', [$this, 'path']),
            new TwigFunction('url', [$this, 'url']),
        ];
    }

    /**
     * Return index path.
     *
     * @return string
     */
    public function indexPath(): string
    {
        $index = '/index.php';
        if (Session::getLoginUserID() !== false) {
            $index = Session::getCurrentInterface() == 'helpdesk'
            ? 'Helpdesk'
            : 'front/central.php';
        }
        return Html::getPrefixedUrl($index);
    }

    /**
     * Return domain-relative path of a resource.
     *
     * @param string $resource
     *
     * @return string
     */
    public function path(string $resource, array $parameters = []): string
    {
        if ($this->router) {
            try {
                // Symfony's router must take precedence over GLPI's router, for forwards compatibility
                return $this->router->generate($resource, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
            } catch (RouteNotFoundException) {
                // Fallback to GLPI router in this case for consistency
            }
        }

        $url = Html::getPrefixedUrl($resource);

        if ($parameters) {
            $url .= '?' . http_build_query($parameters);
        }

        return $url;
    }

    /**
     * Return absolute URL of a resource.
     *
     * @param string $resource
     *
     * @return string
     */
    public function url(string $resource, array $parameters = []): string
    {
        if ($this->router) {
            try {
                // Symfony's router must take precedence over GLPI's router, for forwards compatibility
                return $this->router->generate($resource, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
            } catch (RouteNotFoundException) {
                // Fallback to GLPI router in this case for consistency
            }
        }

        global $CFG_GLPI;

        $prefix = $CFG_GLPI['url_base'];
        if (!str_starts_with($resource, '/')) {
            $prefix .= '/';
        }

        $url = $prefix . $resource;

        if ($parameters) {
            $url .= '?' . http_build_query($parameters);
        }

        return $url;
    }
}
