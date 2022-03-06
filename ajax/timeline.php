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

include ('../inc/includes.php');

Session::checkLoginUser();

if (($_POST['action'] ?? null) === 'change_task_state') {
   header("Content-Type: application/json; charset=UTF-8");

   if (!isset($_POST['tasks_id'])
       || !isset($_POST['parenttype']) || ($parent = getItemForItemtype($_POST['parenttype'])) === false) {
      exit();
   }

   $taskClass = $parent->getType()."Task";
   $task = new $taskClass;
   $task->getFromDB(intval($_POST['tasks_id']));
   if (!in_array($task->fields['state'], [0, Planning::INFO])) {
      $new_state = ($task->fields['state'] == Planning::DONE)
                        ? Planning::TODO
                        : Planning::DONE;
      $new_label = Planning::getState($new_state);
      echo json_encode([
         'state'  => $new_state,
         'label'  => $new_label
      ]);

      $foreignKey = $parent->getForeignKeyField();
      $task->update([
         'id'        => intval($_POST['tasks_id']),
         $foreignKey => intval($_POST[$foreignKey]),
         'state'     => $new_state
      ]);
   }
} else if (($_REQUEST['action'] ?? null) === 'viewsubitem') {
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();

   if (!isset($_REQUEST['type'])) {
      exit();
   }
   if (!isset($_REQUEST['parenttype'])) {
      exit();
   }

   $item = getItemForItemtype($_REQUEST['type']);
   $parent = getItemForItemtype($_REQUEST['parenttype']);

   $manage_locks = static function($itemtype, $items_id) {
      $ol = ObjectLock::isLocked($itemtype, $items_id );
      if ($ol && (Session::getLoginUserID() != $ol->fields['users_id'])) {
         ObjectLock::setReadOnlyProfile();
      }
   };

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
      $id = isset($_REQUEST['id']) && (int)$_REQUEST['id'] > 0 ? $_REQUEST['id'] : null;
      if ($id) {
         $solution->getFromDB($id);
      }
      $manage_locks($_REQUEST['parenttype'], $parent->getID());
      $solution->showForm($id, $sol_params);
   } else if ($_REQUEST['type'] == "ITILFollowup") {
      $parent->getFromDB($_REQUEST[$parent->getForeignKeyField()]);

      $fup_params = [
         'item'      => $parent
      ];

      $fup = new ITILFollowup();
      $id = isset($_REQUEST['id']) && (int)$_REQUEST['id'] > 0 ? $_REQUEST['id'] : null;
      if ($id) {
         $fup->getFromDB($id);
      }
      $manage_locks($_REQUEST['parenttype'], $parent->getID());
      $fup->showForm($id, $fup_params);
   } else if (substr_compare($_REQUEST['type'], 'Validation', -10) === 0) {
      $parent->getFromDB($_REQUEST[$parent->getForeignKeyField()]);
      $validation = new $_REQUEST['type']();
      $id = isset($_REQUEST['id']) && (int)$_REQUEST['id'] > 0 ? $_REQUEST['id'] : null;
      if ($id) {
         $validation->getFromDB($id);
      }
      $manage_locks($_REQUEST['parenttype'], $parent->getID());
      $validation->showForm($id, ['parent' => $parent]);
   } else if (isset($_REQUEST[$parent->getForeignKeyField()])
         && isset($_REQUEST["id"])
         && $parent->getFromDB($_REQUEST[$parent->getForeignKeyField()])) {

      $manage_locks($_REQUEST['parenttype'], $parent->getID());
      $foreignKey = $parent->getForeignKeyField();
      $parent::showSubForm($item, $_REQUEST["id"], ['parent' => $parent,
                                                   $foreignKey => $_REQUEST[$foreignKey]]);
   } else {
      echo __('Access denied');
   }

   Html::ajaxFooter();
}
