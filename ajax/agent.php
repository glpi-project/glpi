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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;

use function Safe\json_encode;

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

if (isset($_POST['action']) && isset($_POST['id'])) {
    $agent = new Agent();
    if (!$agent->getFromDB($_POST['id'])) {
        throw new NotFoundHttpException('Unable to load agent #' . $_POST['id']);
    }
    if (!$agent::canView()) {
        throw new AccessDeniedHttpException();
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
