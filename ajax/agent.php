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

use Glpi\Http\Response;

$AJAX_INCLUDE = 1;
include('../inc/includes.php');
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['action']) && isset($_POST['id'])) {
    $agent = new Agent();
    if (!$agent->getFromDB($_POST['id']) || !$agent->canView()) {
        Response::sendError(404, 'Unable to load agent #' . $_POST['id']);
        return;
    }
    $answer = [];

    Session::writeClose();
    switch ($_POST['action']) {
        case Agent::ACTION_INVENTORY:
            $answer = $agent->requestInventory();
            break;

        case Agent::ACTION_STATUS:
            $answer = $agent->requestStatus();
            break;
    }

    echo json_encode($answer);
}
