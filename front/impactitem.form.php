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

use Glpi\Http\Response;

include('../inc/includes.php');

$impact_item = new ImpactItem();

if (isset($_POST["update"])) {
    $id = $_POST["id"] ?? 0;

    // Can't update, id is missing
    if ($id === 0) {
        Response::sendError(400, "Can't update the target impact item, id is missing", Response::CONTENT_TYPE_TEXT_HTML);
    }

    // Load item and check rights
    $impact_item->getFromDB($id);
    Session::checkRight($impact_item->fields['itemtype']::$rightname, UPDATE);

    // Update item and back
    $impact_item->update($_POST);
    Html::redirect(Html::getBackUrl() . "#list");
}
