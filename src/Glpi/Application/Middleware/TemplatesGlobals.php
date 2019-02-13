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

use Glpi\ConfigParams;
use Glpi\Application\Router;
use Glpi\Application\View\TwigView;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TemplatesGlobals
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
     * @var ConfigParams
     */
    protected $configParams;

    public function __construct(TwigView $view, Router $router, ConfigParams $configParams)
    {
        $this->configParams = $configParams;
        $this->router = $router;
        $this->view = $view;
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
        $view = $this->view;
        $view->getEnvironment()->addGlobal(
            "current_path",
            $request->getUri()->getPath()
        );

        $view->getEnvironment()->addGlobal(
            "CFG_GLPI",
            $this->configParams
        );

        $view->getEnvironment()->addGlobal(
            'glpi_layout',
            $_SESSION['glpilayout']
        );

        $view->getEnvironment()->addGlobal(
            'glpi_debug',
            $_SESSION['glpi_use_mode'] == \Session::DEBUG_MODE
        );

        $view->getEnvironment()->addGlobal(
            'glpi_lang',
            $this->configParams["languages"][$_SESSION['glpilanguage']][3]
        );

        $view->getEnvironment()->addGlobal(
            'glpi_lang_name',
            \Dropdown::getLanguageName($_SESSION['glpilanguage'])
        );

        $view->getEnvironment()->addGlobal(
            'glpishow_count_on_tabs',
            $_SESSION['glpishow_count_on_tabs']
        );

        //TODO: change that!
        if (\Session::getLoginUserID()) {
            ob_start();
            \Html::showProfileSelecter($this->router->getBasePath());
            $selecter = ob_get_contents();
            ob_end_clean();

            $view->getEnvironment()->addGlobal(
                'glpi_profile_selecter',
                $selecter
            );
        }

        $logged = false;
        if (\Session::getLoginUserID()) {
            $logged = true;
            $view->getEnvironment()->addGlobal(
                'user_name',
                \formatUserName(
                    0,
                    $_SESSION["glpiname"],
                    $_SESSION["glpirealname"],
                    $_SESSION["glpifirstname"],
                    0,
                    20
                )
            );
        }
        $view->getEnvironment()->addGlobal('is_logged', $logged);

        $menus = \Html::generateMenuSession($this->router, ($_SESSION['glpi_use_mode'] == \Session::DEBUG_MODE));
        $view->getEnvironment()->addGlobal(
            'glpi_menus',
            $menus
        );

        return $next($request, $response);
    }
}
