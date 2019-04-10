<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Application\Middleware;

use Glpi\Application\Router;
use Glpi\Application\View\TwigView;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Reload
{
    /**
     * @var TwigView
     */
    protected $view;

   /**
    * @var Router
    */
    protected $router;

    public function __construct(TwigView $view, Router $router)
    {
        $this->view = $view;
        $this->router = $router;
    }

    /**
     * Switch middleware (to get UI reloaded after switching
     * debug mode, language, ...
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        $get = $request->getQueryParams();

        $route = $request->getAttribute('route');
        if (!$route) {
            return $next($request, $response);
        }
        $arguments = $route->getArguments();
        $this->view->getEnvironment()->addGlobal(
            "current_itemtype",
            isset($arguments['itemtype']) ? $arguments['itemtype'] : ''
        );

        if (isset($get['switch'])) {
            $switch_route = $get['switch'];
            $uri = $request->getUri();
            $route_name = $route->getName();

            $_SESSION['glpi_switch_route'] = [
                'name'      => $route_name,
                'arguments' => $arguments
            ];

            return $response->withRedirect($this->router->pathFor($switch_route), 302);
        }
        return $next($request, $response);
    }
}
