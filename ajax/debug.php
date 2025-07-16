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
use Glpi\Debug\Profile;
use Glpi\Debug\Profiler;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\UI\ThemeManager;

use function Safe\json_encode;
use function Safe\session_write_close;

Html::header_nocache();

if ($_SESSION['glpi_use_mode'] !== Session::DEBUG_MODE) {
    throw new AccessDeniedHttpException();
}

Profiler::getInstance()->disable();

if (isset($_GET['ajax_id'])) {
    // Get debug data for a specific ajax call
    $ajax_id = $_GET['ajax_id'];
    $profile = Profile::pull($ajax_id);

    // Close session ASAP to not block other requests.
    // DO NOT do it before call to `\Glpi\Debug\Profile::pull()`,
    // as we have to delete profile from `$_SESSION` during the pull operation.
    session_write_close();

    header('Content-Type: application/json');
    if ($profile) {
        $data = $profile->getDebugInfo();
        if ($data) {
            echo json_encode($data);
        }
    }
    return;
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
        return;
    }
    if ($action === 'get_search_options' && isset($_GET['itemtype'])) {
        header('Content-Type: application/json');
        $class = $_GET['itemtype'];
        if (!class_exists($class) || !is_subclass_of($class, 'CommonDBTM')) {
            echo '[]';
            return;
        }
        $reflection_class = new ReflectionClass($class);
        if ($reflection_class->isAbstract()) {
            echo '[]';
            return;
        }
        try {
            /** @var CommonGLPI $item */
            $item = getItemForItemtype($_GET['itemtype']);
            $options = Search::getOptions($item::getType());
        } catch (Throwable $e) {
            $options = [];
        }
        $options = array_filter($options, static fn($k) => is_numeric($k), ARRAY_FILTER_USE_KEY);
        echo json_encode($options);
        return;
    }
    if ($action === 'get_themes') {
        header('Content-Type: application/json');
        $themes = ThemeManager::getInstance()->getAllThemes();
        echo json_encode($themes);
        return;
    }
}

throw new BadRequestHttpException();
