<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

include ('../inc/includes.php');

Session::checkCentralAccess();

if (!isset($_REQUEST["action"])) {
   exit;
}

if ($_REQUEST["action"] == "get_events") {
   header("Content-Type: application/json; charset=UTF-8");
   echo json_encode(Reservation::getEvents($_REQUEST));
   exit;
}

if ($_REQUEST["action"] == "get_resources") {
   header("Content-Type: application/json; charset=UTF-8");
   echo json_encode(Reservation::getResources());
   exit;
}

if ($_REQUEST["action"] == "update_event") {
   $result = Reservation::updateEvent($_REQUEST);
   echo json_encode(['result' => $result]);
   exit;
}

Html::header_nocache();
header("Content-Type: text/html; charset=UTF-8");

if ($_REQUEST["action"] == "add_reservation_fromselect") {
   $reservation = new Reservation;
   $reservation->showForm(0, [
      'item'  => [(int) $_REQUEST['id']],
      'begin' => $_REQUEST['start'],
      'end'   => $_REQUEST['end'],
   ]);
}

Html::ajaxFooter();
