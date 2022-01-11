<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

use Glpi\Http\Response;

$AJAX_INCLUDE = 1;
include('../inc/includes.php');
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();


if (isset($_POST['action']) && isset($_POST['id'])) {
    $agent = new Agent();
    if (!$agent->getFromDB($_POST['id'])) {
        Response::sendError(404, 'Unable to load agent #' . $_POST['id']);
        return;
    };
    $answer = [];

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
