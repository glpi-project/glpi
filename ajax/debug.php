<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Application\ErrorHandler;

include('../inc/includes.php');
Html::header_nocache();

Session::checkLoginUser();

if ($_SESSION['glpi_use_mode'] !== Session::DEBUG_MODE) {
    http_response_code(403);
    die();
}

\Glpi\Debug\Profiler::getInstance()->disable();

if (isset($_GET['ajax_id'])) {
    // Get debug data for a specific ajax call
    $ajax_id = $_GET['ajax_id'];
    $profile = \Glpi\Debug\Profile::pull($ajax_id);

    // Close session ASAP to not block other requests.
    // DO NOT do it before call to `\Glpi\Debug\Profile::pull()`,
    // as we have to delete profile from `$_SESSION` during the pull operation.
    session_write_close();

    if ($profile) {
        $data = $profile->getDebugInfo();
        if ($data) {
            header('Content-Type: application/json');
            echo json_encode($data);
            die();
        }
    }
    http_response_code(404);
    die();
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'get_itemtypes') {
        $loaded = get_declared_classes();
        $glpi_classes = array_filter($loaded, static function ($class) {
            if (!is_subclass_of($class, 'CommonDBTM')) {
                return false;
            }

            $reflection_class = new ReflectionClass($class);
            if ($reflection_class->isAbstract()) {
                return false;
            }

            return true;
        });
        sort($glpi_classes);
        header('Content-Type: application/json');
        echo json_encode($glpi_classes);
        die();
    }
    if ($action === 'get_search_options' && isset($_GET['itemtype'])) {
        header('Content-Type: application/json');
        $class = $_GET['itemtype'];
        if (!class_exists($class) || !is_subclass_of($class, 'CommonDBTM')) {
            echo '[]';
            die();
        }
        $reflection_class = new ReflectionClass($class);
        if ($reflection_class->isAbstract()) {
            echo '[]';
            die();
        }
        // In some cases, a class that isn't a proper itemtype may show in the selection box and this would trigger a SQL error that cannot be caught.
        ErrorHandler::getInstance()->disableOutput();
        try {
            /** @var CommonGLPI $item */
            $item = new $_GET['itemtype']();
            $options = Search::getOptions($item::getType());
        } catch (Throwable $e) {
            $options = [];
        }
        $options = array_filter($options, static function ($k) {
            return is_numeric($k);
        }, ARRAY_FILTER_USE_KEY);
        echo json_encode($options);
        die();
    }
}

http_response_code(400);
die();
