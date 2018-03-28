<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

/**
 * @since 0.85
 */

use Glpi\Event;

include ('../inc/includes.php');

Session::checkCentralAccess();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["projects_id"])) {
   $_GET["projects_id"] = "";
}
if (!isset($_GET["projecttasks_id"])) {
   $_GET["projecttasks_id"] = "";
}
$task = new ProjectTask();

if (isset($_POST["add"])) {
   $task->check(-1, CREATE, $_POST);
   $newID = $task->add($_POST);

   Event::log($task->fields['projects_id'], 'project', 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s adds a task'), $_SESSION["glpiname"]));
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($task->getLinkURL());
   } else {
      Html::redirect(ProjectTask::getFormURL()."?projects_id=".$task->fields['projects_id']);
   }

} else if (isset($_POST["purge"])) {
   $task->check($_POST['id'], PURGE);
   $task->delete($_POST, 1);

   Event::log($task->fields['projects_id'], 'project', 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s purges a task'), $_SESSION["glpiname"]));
   Html::redirect(Project::getFormURLWithID($task->fields['projects_id']));

} else if (isset($_POST["update"])) {
   $task->check($_POST["id"], UPDATE);
   $task->update($_POST);

   Event::log($task->fields['projects_id'], 'project', 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s updates a task'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_GET['_in_modal'])) {
   Html::popHeader(Budget::getTypeName(1), $_SERVER['PHP_SELF']);
   $project->showForm($_GET["id"], ['withtemplate' => $_GET["withtemplate"]]);
   Html::popFooter();

} else {
   Html::header(ProjectTask::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "project");
   $task->display($_GET);
   Html::footer();
}

