<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Config\ConfigManager;
use Glpi\Config\ConfigScope;
use Glpi\Http\Response;

$AJAX_INCLUDE = 1;

include('../inc/includes.php');

Session::checkCentralAccess();

$action = $_POST['action'] ?? false;
switch ($action) {
    case 'get_edit_form':
        if (!isset($_POST['context'], $_POST['name'], $_POST['scope'])) {
            Response::sendError(400, "Missing required parameters");
        }
        $config_manager = ConfigManager::getInstance();
        $option = $config_manager->getOption($_POST['context'], $_POST['name']);
        $config_manager->showEditForm($option, ConfigScope::fromKey($_POST['scope']), $_POST['scope_params'] ?? []);
        return;
    case 'set_option_value':
        if (!isset($_POST['context'], $_POST['name'], $_POST['scope'], $_POST['value'])) {
            Response::sendError(400, "Missing required parameters");
        }
        $config_manager = ConfigManager::getInstance();
        $config_manager->setOptionValue($_POST['context'], $_POST['name'], ConfigScope::fromKey($_POST['scope']), $_POST['value'], $_POST['scope_params'] ?? []);
        return;
    default:
        // Invalid action
        Response::sendError(400, "Invalid or missing value: action");
}
