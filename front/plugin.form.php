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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Exception\Http\BadRequestHttpException;

/**
 * @since 0.84
 */

Session::checkRight("config", UPDATE);

$plugin = new Plugin();

$id     = isset($_POST['id']) && is_numeric($_POST['id']) ? (int) $_POST['id'] : null;
$action = $_POST['action'] ?? null;

switch ($action) {
    case 'install':
    case 'activate':
    case 'unactivate':
    case 'uninstall':
    case 'clean':
        if (!$id) {
            throw new BadRequestHttpException();
        }
        $plugin->{$action}($id);
        break;
    case 'resume_all_execution':
        $plugin->resumeAllPluginsExecution();
        Session::addMessageAfterRedirect(__s('Execution of all active plugins has been resumed.'));
        break;
    case 'suspend_all_execution':
        $plugin->suspendAllPluginsExecution();
        Session::addMessageAfterRedirect(__s('Execution of all active plugins has been suspended.'));
        break;
    default:
        throw new BadRequestHttpException();
}

Html::back();
