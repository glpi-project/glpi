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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

readonly class PluginsRouter implements RouterInterface, RequestMatcherInterface
{
    public function __construct(
        private Router $internal_router,
        private Router $symfony_router,
    ) {
        $this->internal_router->getRouteCollection()->addCollection($this->symfony_router->getRouteCollection());
        $this->internal_router->setContext($this->symfony_router->getContext());
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->internal_router->getRouteCollection();
    }

    public function setContext(RequestContext $context): void
    {
        $this->internal_router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->internal_router->getContext();
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        return $this->internal_router->generate($name, $parameters, $referenceType);
    }

    public function match(string $pathinfo): array
    {
        return $this->internal_router->match($pathinfo);
    }

    public function matchRequest(Request $request): array
    {
        return $this->internal_router->matchRequest($request);
    }
}
