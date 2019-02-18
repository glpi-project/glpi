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
use Glpi\Application\View\TwigView;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RequireJs
{
    /**
     * @var TwigView
     */
    protected $view;

    /**
     * @var ConfigParams
     */
    protected $configParams;

    public function __construct(TwigView $view, ConfigParams $configParams)
    {
        $this->view = $view;
        $this->configParams = $configParams;
    }

    /**
     * Detect javascript dependencies
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        $route = $request->getAttribute('route');
        $arguments = [];
        if ($route) {
            $arguments = $route->getArguments();
            $key = $route->getName();
        }

        if (isset($arguments['itemtype'])) {
            $key = mb_strtolower($arguments['itemtype']);
        }

        if (!isset($key)) {
            return $next($request, $response);
        }

        $requirements = \Html::getJsRequirements($this->configParams['javascript'], $key);

        $css_paths = [];
        $js_paths = [];

        $lib_path = 'public/lib';
        if (in_array('fullcalendar', $requirements)) {
            $css_paths[] = $lib_path . '/fullcalendar/fullcalendar.css';
            $css_paths[] = [
               'path'   => $lib_path . '/fullcalendar/fullcalendar.print.css',
               'media'  => 'print'
            ];

            $js_paths[] = $lib_path . '/moment/moment.js';
            $js_paths[] = $lib_path . '/fullcalendar/fullcalendar.js';

            if (isset($_SESSION['glpilanguage'])) {
                foreach (['fullcalendar', 'moment'] as $lib_name) {
                    foreach ([2, 3] as $loc) {
                        $filename = $lib_path . "/" . $lib_name . "/locale/"
                            . strtolower($this->configParams["languages"][$_SESSION['glpilanguage']][$loc]).".js";
                        if (file_exists(GLPI_ROOT . '/' . $filename)) {
                            $js_paths[] = $filename;
                            break;
                        }
                    }
                }
            }
        }

        if (in_array('gantt', $requirements)) {
            $css_paths[] = 'lib/jqueryplugins/jquery-gantt/css/style.css';
            $js_paths[] = 'lib/jqueryplugins/jquery-gantt/js/jquery.fn.gantt.js';
        }

        if (in_array('rateit', $requirements)) {
            $css_paths[] = $lib_path . '/jquery.rateit/rateit.css';
            $js_paths[] = $lib_path . '/jquery.rateit/jquery.rateit.js';
        }

        if (in_array('colorpicker', $requirements)) {
            $css_paths[] = $lib_path . '/spectrum-colorpicker/spectrum.css';
            $js_paths[] = $lib_path . '/spectrum-colorpicker/spectrum.js';
        }

        if (in_array('gridstack', $requirements)) {
            $css_paths[] = $lib_path . '/gridstack/gridstack.css';
            $css_paths[] = $lib_path . '/gridstack/gridstack-extra.css';

            $js_paths[] = $lib_path . '/lodash/lodash.js';
            $js_paths[] = $lib_path . '/gridstack/gridstack.js';
            $js_paths[] = $lib_path . '/gridstack/gridstack.jQueryUI.js';
            $js_paths[] = 'js/rack.js';
        }

        if (in_array('tinymce', $requirements)) {
            $js_paths[] = $lib_path . '/tinymce/tinymce.js';
        }

        if (in_array('clipboard', $requirements)) {
            $js_paths[] = 'js/clipboard.js';
        }

        if (in_array('charts', $requirements)) {
            $css_paths[] = $lib_path . '/chartist/chartist.css';
            $css_paths[] = $lib_path . '/chartist-plugin-tooltips/chartist-plugin-tooltip.css';

            $js_paths[] = $lib_path . '/chartist/chartist.js';
            $js_paths[] = $lib_path . '/chartist-plugin-legend/chartist-plugin-legend.js';
            $js_paths[] = $lib_path . '/chartist-plugin-tooltips/chartist-plugin-tooltip.js';
        }

        $this->view->getEnvironment()->addGlobal(
            "css_paths",
            $css_paths
        );
        $this->view->getEnvironment()->addGlobal(
            "js_paths",
            $js_paths
        );

        return $next($request, $response);
    }
}
