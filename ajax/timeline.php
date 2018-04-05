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

include ('../inc/includes.php');

Session::checkLoginUser();

if (!isset($_REQUEST['action'])) {
   exit;
}

if ($_REQUEST['action'] == 'change_task_state') {
   header("Content-Type: application/json; charset=UTF-8");
} else {
   header("Content-Type: text/html; charset=UTF-8");
}

switch ($_REQUEST['action']) {
   case "change_task_state":
      if (!isset($_REQUEST['tasks_id'])) {
         exit();
      }
      $task = new TicketTask;
      $task->getFromDB(intval($_REQUEST['tasks_id']));
      if (!in_array($task->fields['state'], [0, Planning::INFO])) {
         $new_state = ($task->fields['state'] == Planning::DONE)
                           ? Planning::TODO
                           : Planning::DONE;
         $new_label = Planning::getState($new_state);
         echo json_encode([
            'state'  => $new_state,
            'label'  => $new_label
         ]);

         $task->update([
            'id'         => intval($_REQUEST['tasks_id']),
            'tickets_id' => intval($_REQUEST['tickets_id']),
            'state'      => $new_state
         ]);
      }
      break;
   case "viewsubitem":
      Html::header_nocache();
      if (!isset($_REQUEST['type'])) {
         exit();
      }
      if (!isset($_REQUEST['parenttype'])) {
         exit();
      }

      $item = getItemForItemtype($_REQUEST['type']);
      $parent = getItemForItemtype($_REQUEST['parenttype']);

      if ($_REQUEST['type'] == "Solution") {
         $parent->getFromDB($_REQUEST[$parent->getForeignKeyField()]);

         if (!isset($_REQUEST['load_kb_sol'])) {
            $_REQUEST['load_kb_sol'] = 0;
         }

         $sol_params = [
            'item'         => $parent,
            'kb_id_toload' => $_REQUEST['load_kb_sol']
         ];

         $solution = new ITILSolution();
         if (isset($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $solution->getFromDB($_REQUEST['id']);
         }
         $solution->showForm(null, $sol_params);
      } else if (isset($_REQUEST[$parent->getForeignKeyField()])
            && isset($_REQUEST["id"])
            && $parent->getFromDB($_REQUEST[$parent->getForeignKeyField()])) {

         $ol = ObjectLock::isLocked( $_REQUEST['parenttype'], $parent->getID() );
         if ($ol && (Session::getLoginUserID() != $ol->fields['users_id'])) {
            ObjectLock::setReadOnlyProfile( );
         }

         Ticket::showSubForm($item, $_REQUEST["id"], ['parent' => $parent,
                                                      'tickets_id' => $_REQUEST["tickets_id"]]);
      } else {
         echo __('Access denied');
      }

      Html::ajaxFooter();
      break;
}
