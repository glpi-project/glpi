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
use Slim\Flash\Messages;

class Authentication
{
    /**
     * @var TwigView
     */
    protected $view;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Messages
     */
    protected $flash;

    public function __construct(TwigView $view, Router $router, Messages $flashMessages)
    {
        $this->view = $view;
        $this->router = $router;
        $this->flash = $flashMessages;
    }

    /**
     * Authentication middleware
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        //TODO: do!
        //die('You shall not pass!')

        $route = $request->getAttribute('route');
        if (!$route || in_array($route->getName(), ['login', 'slash', 'cron', 'asset', 'do-login', 'lost-password'])) {
            return $next($request, $response);
        }

        //redirect to login page if user is not logged in
        if (!\Session::getLoginUserID()) {
            $arguments = $route->getArguments();
            $get = $request->getQueryParams();
            $arguments = $arguments + $get;
            //store current path for redirection
            $_SESSION['glpi_redirect'] = $this->router->pathFor($route->getName(), $arguments);

            $this->flash->addMessage('error', __('Authentication required'));
            return $response->withRedirect($this->router->pathFor('login'), 302);
        }

        return $next($request, $response);
    }
}
