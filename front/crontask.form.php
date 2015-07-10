<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief Form to edit Cron Task
*/

include ('../inc/includes.php');

Session::checkRight("config", UPDATE);

$crontask = new CronTask();

if (isset($_POST['execute'])) {
   if (is_numeric($_POST['execute'])) {
      // Execute button from list.
      $name = CronTask::launch(CronTask::MODE_INTERNAL, intval($_POST['execute']));
   } else {
      // Execute button from Task form (force)
      $name = CronTask::launch(-CronTask::MODE_INTERNAL, 1, $_POST['execute']);
   }
   if ($name) {
      //TRANS: %s is a task name
      Session::addMessageAfterRedirect(sprintf(__('Task %s executed'), $name));
   }
   Html::back();
} else if (isset($_POST["update"])) {
   Session::checkRight('config', UPDATE);
   $crontask->update($_POST);
   Html::back();

} else if (isset($_POST['resetdate'])
           && isset($_POST["id"])) {
   Session::checkRight('config', UPDATE);
   if ($crontask->getFromDB($_POST["id"])) {
       $crontask->resetDate();
   }
   Html::back();

} else if (isset($_POST['resetstate'])
           && isset($_POST["id"])) {
   Session::checkRight('config', UPDATE);
   if ($crontask->getFromDB($_POST["id"])) {
       $crontask->resetState();
   }
   Html::back();

}else {
   if (!isset($_GET["id"]) || empty($_GET["id"])) {
      exit();
   }
   Html::header(Crontask::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'config', 'crontask');
   $crontask->display(array('id' =>$_GET["id"]));
   Html::footer();
}
?>