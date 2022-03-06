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

use Glpi\Event;

include ('../inc/includes.php');

Session::checkRight("planning", READ);

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}

$extevent = new PlanningExternalEvent();

if (isset($_POST["add"])) {
   $extevent->check(-1, CREATE, $_POST);

   if ($newID = $extevent->add($_POST)) {
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($extevent->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $extevent->check($_POST["id"], DELETE);
   $extevent->delete($_POST);
   $extevent->redirectToList();

} else if (isset($_POST["restore"])) {
   $extevent->check($_POST["id"], DELETE);
   $extevent->restore($_POST);
   $extevent->redirectToList();

} else if (isset($_POST["purge"])) {
   $extevent->check($_POST["id"], PURGE);
   $extevent->delete($_POST, 1);
   $extevent->redirectToList();

} else if (isset($_POST["purge_instance"])) {
   $extevent->check($_POST["id"], PURGE);
   $extevent->deleteInstance((int) $_POST["id"], $_POST['day']);
   $extevent->redirectToList();

} else if (isset($_POST["update"])) {
   $extevent->check($_POST["id"], UPDATE);
   $extevent->update($_POST);
   Html::back();

} else {
   Html::header(
      PlanningExternalEvent::getTypeName(Session::getPluralNumber()),
      $_SERVER['PHP_SELF'],
      "helpdesk",
      "planning",
      "external"
   );
   $extevent->display(['id' => $_GET["id"]]);
   Html::footer();
}
