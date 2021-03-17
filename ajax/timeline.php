<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;

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

$objType = $_REQUEST['parenttype']::getType();
$foreignKey = $_REQUEST['parenttype']::getForeignKeyField();

switch ($_REQUEST['action']) {
   case "change_task_state":
      if (!isset($_REQUEST['tasks_id'])) {
         exit();
      }

      $taskClass = $objType."Task";
      $task = new $taskClass;
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
            $foreignKey => intval($_REQUEST[$foreignKey]),
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

      $twig = TemplateRenderer::getInstance();
      $template = null;
      if (isset($_REQUEST[$parent::getForeignKeyField()])) {
         $parent->getFromDB($_REQUEST[$parent::getForeignKeyField()]);
      }
      $id = isset($_REQUEST['id']) && (int)$_REQUEST['id'] > 0 ? $_REQUEST['id'] : null;
      if ($id) {
         $item->getFromDB($id);
      }
      $params = [
         'item'      => $parent,
         'subitem'   => $item
      ];

      if ($_REQUEST['type'] === ITILFollowup::class) {
         $template = 'form_followup';
      } else if (is_subclass_of($_REQUEST['type'], ITILSolution::class)) {
         $template = 'form_solution';
         $params['kb_id_toload'] = $_REQUEST['load_kb_sol'] ?? 0;
      } else if (is_subclass_of($_REQUEST['type'], CommonITILTask::class)) {
         $template = 'form_task';
      } else if (is_subclass_of($_REQUEST['type'], CommonITILValidation::class)) {
         $template = 'form_validation';
      } else if ($id !== null && $parent->getID() >= 0) {
         $ol = ObjectLock::isLocked( $_REQUEST['parenttype'], $parent->getID() );
         if ($ol && (Session::getLoginUserID() != $ol->fields['users_id'])) {
            ObjectLock::setReadOnlyProfile();
         }
         $params[$foreignKey] = $_REQUEST[$foreignKey];
         $parent::showSubForm($item, $_REQUEST["id"], ['parent' => $parent, $foreignKey => $_REQUEST[$foreignKey]]);
         Html::ajaxFooter();
         break;
      }
      if ($template === null) {
         echo __('Access denied');
         Html::ajaxFooter();
         break;
      }
      $twig->display("components/itilobject/{$template}.html.twig", $params);
      break;
}
