<?php
/*
 * @version $Id: timeline_viewsubitem.php 23588 2015-07-10 11:09:46Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
include ('../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");

Session::checkLoginUser();

if (!isset($_POST['action'])) {
   exit;
}

switch($_POST['action']) {
   case "change_task_state":
      if (!isset($_POST['tasks_id'])) {
         exit();
      }
      $task = new TicketTask;
      $task->getFromDB(intval($_POST['tasks_id']));
      if (!in_array($task->fields['state'], array(0, Planning::INFO))) {
         echo $new_state = ($task->fields['state'] == Planning::DONE)
                              ? Planning::TODO
                              : Planning::DONE;
         $task->update(array('id'         => intval($_POST['tasks_id']),
                             'tickets_id' => intval($_POST['tickets_id']),
                             'state'      => $new_state));
      }
      break;
   case "viewsubitem":
      Html::header_nocache();
      if (!isset($_POST['type'])) {
         exit();
      }
      if (!isset($_POST['parenttype'])) {
         exit();
      }

      if (($item = getItemForItemtype($_POST['type']))
          && ($parent = getItemForItemtype($_POST['parenttype']))) {
         if (isset($_POST[$parent->getForeignKeyField()])
             && isset($_POST["id"])
             && $parent->getFromDB($_POST[$parent->getForeignKeyField()])) {

            $ol = ObjectLock::isLocked( $_POST['parenttype'], $parent->getID() ) ;
            if( $ol && (Session::getLoginUserID() != $ol->fields['users_id'])) {
               ObjectLock::setReadOnlyProfile( ) ;
            }

            Ticket::showSubForm($item, $_POST["id"], array('parent' => $parent,
                                                                        'tickets_id' => $_POST["tickets_id"]));
         } else {
            _e('Access denied');
         }
      } else if ($_POST['type'] == "Solution") {
         $ticket = new Ticket;
         $ticket->getFromDB($_POST["tickets_id"]);

         if (!isset($_REQUEST['load_kb_sol'])) {
            $_REQUEST['load_kb_sol'] = 0;
         }
         $ticket->showSolutionForm($_REQUEST['load_kb_sol']);

         // show approbation form on top when ticket is solved
         if ($ticket->fields["status"] == CommonITILObject::SOLVED) {
            echo "<div class='approbation_form'>";
            $followup_obj = new TicketFollowup();
            $followup_obj->showApprobationForm($ticket);
            echo "</div>";
         }
      }
      Html::ajaxFooter();
      break;
}
?>