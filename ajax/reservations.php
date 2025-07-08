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

use function Safe\json_encode;

Session::checkRightsOr('reservation', [READ, ReservationItem::RESERVEANITEM]);

if (!isset($_REQUEST["action"])) {
    return;
}

if ($_REQUEST["action"] == "get_events") {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(Reservation::getEvents($_REQUEST));
    return;
}

Session::checkRight('reservation', ReservationItem::RESERVEANITEM);

if ($_REQUEST["action"] == "get_resources") {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(Reservation::getResources());
    return;
}

if (($_POST['action'] ?? null) === "update_event") {
    $result = Reservation::updateEvent($_REQUEST);
    echo json_encode(['result' => $result]);
    return;
}

Html::header_nocache();
header("Content-Type: text/html; charset=UTF-8");

if ($_REQUEST["action"] == "add_edit_reservation_fromselect") {
    $reservation = new Reservation();
    $reservation->showForm($_REQUEST['id'], $_REQUEST);
}
