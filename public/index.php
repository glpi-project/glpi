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

// Check PHP version not to have trouble
// Need to be the very fist step before any include
if (version_compare(PHP_VERSION, '7.0.8') < 0) {
    die('PHP >= 7.0.8 required');
}

use Tracy\Debugger;

//Load GLPI constants
define('GLPI_ROOT', __DIR__ . '/..');
include_once GLPI_ROOT . "/inc/based_config.php";
include_once GLPI_ROOT . "/inc/define.php";

//define('DO_NOT_CHECK_HTTP_REFERER', 1);

RunTracy\Helpers\Profiler\Profiler::enable();
Debugger::enable(Debugger::DEVELOPMENT, GLPI_LOG_DIR);
Debugger::timer();

// If config_db doesn't exist -> start installation
if (!file_exists(GLPI_CONFIG_DIR . "/db.yaml") && !file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
    include_once GLPI_ROOT . "/inc/autoload.function.php";
    Html::redirect("../install/install.php");
    die();
} else {
    include(GLPI_ROOT . "/inc/includes.php");

    RunTracy\Helpers\Profiler\Profiler::start('initApp');
    $app = $CONTAINER->get(Slim\App::class);
    RunTracy\Helpers\Profiler\Profiler::finish('initApp');

    // Put router into global scope in order to be able to fetch routes in legacy code
    // while loaded from Slim application context.
    // Outside Slim application context, global $router will be null and legacy URL will be used.
    global $router;
    $router = $CONTAINER->get('router');

    //handle redirections from old ui
    if (isset($_GET['uiredirect'])) {
        $params = [];
        $route  = null;
        foreach ($_GET as $key => $param) {
            if ($key === 'route') {
                $route = $param;
            } else {
                $params[$key] = $param;
            }
        }
        $new_uri = $router->pathFor($route, $params);
        header("Location: $new_uri");
        die();
    }

    RunTracy\Helpers\Profiler\Profiler::start('runApp');
    $app->run();
    RunTracy\Helpers\Profiler\Profiler::finish('runApp');
}
