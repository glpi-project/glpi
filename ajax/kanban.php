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

use Glpi\Features\Kanban;

$AJAX_INCLUDE = 1;

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (!isset($_REQUEST['action'])) {
   Toolbox::logError("Missing action parameter");
   http_response_code(400);
   return;
}
$action = $_REQUEST['action'];

$nonkanban_actions = ['update', 'bulk_add_item', 'add_item', 'move_item'];
if (isset($_REQUEST['itemtype'])) {
   $traits = class_uses($_REQUEST['itemtype'], true);
   if (!in_array($_REQUEST['action'], $nonkanban_actions) && (!$traits || !in_array(Kanban::class, $traits, true))) {
      // Bad request
      // For all actions, except those in $nonkanban_actions, we expect to be manipulating the Kanban itself.
      Toolbox::logError("Invalid itemtype parameter");
      http_response_code(400);
      return;
   }
   /** @var CommonDBTM $item */
   $itemtype = $_REQUEST['itemtype'];
   $item = new $itemtype();
}

// Rights Checks
if (isset($itemtype)) {
   if (in_array($action, ['refresh', 'get_switcher_dropdown', 'get_column'])) {
      if (!$item->canView()) {
         // Missing rights
         http_response_code(403);
         return;
      }
   }
   if (in_array($action, ['update'])) {
      $item->getFromDB($_REQUEST['items_id']);
      if (!$item->canUpdateItem()) {
         // Missing rights
         http_response_code(403);
         return;
      }
   }
   if (in_array($action, ['bulk_add_item', 'add_item'])) {
      if (!$item->canCreate()) {
         // Missing rights
         http_response_code(403);
         return;
      }
   }
}

// Helper to check required parameters
$checkParams = function($required) {
   foreach ($required as $param) {
      if (!isset($_REQUEST[$param])) {
         Toolbox::logError("Missing $param parameter");
         http_response_code(400);
         die();
      }
   }
};

// Action Processing
if (($_POST['action'] ?? null) == 'update') {
   $checkParams(['column_field', 'column_value']);
   // Update project or task based on changes made in the Kanban
   $item->update([
      'id'                   => $_POST['items_id'],
      $_POST['column_field'] => $_POST['column_value']
   ]);
} else if (($_POST['action'] ?? null) == 'add_item') {
   $checkParams(['inputs']);
   $item = new $itemtype();
   $inputs = [];
   parse_str($_POST['inputs'], $inputs);

   $item->add(Toolbox::clean_cross_side_scripting_deep($inputs));
} else if (($_POST['action'] ?? null) == 'bulk_add_item') {
   $checkParams(['inputs']);
   $item = new $itemtype();
   $inputs = [];
   parse_str($_POST['inputs'], $inputs);

   $bulk_item_list = preg_split('/\r\n|[\r\n]/', $inputs['bulk_item_list']);
   if (!empty($bulk_item_list)) {
      unset($inputs['bulk_item_list']);
      foreach ($bulk_item_list as $item_entry) {
         $item_entry = trim($item_entry);
         if (!empty($item_entry)) {
            $item->add(Toolbox::clean_cross_side_scripting_deep($inputs + ['name' => $item_entry]));
         }
      }
   }
} else if (($_POST['action'] ?? null) == 'move_item') {
   $checkParams(['card', 'column', 'position', 'kanban']);
   /** @var Kanban|CommonDBTM $kanban */
   $kanban = new $_POST['kanban']['itemtype'];
   $can_move = $kanban->canOrderKanbanCard($_POST['kanban']['items_id']);
   if ($can_move) {
      Item_Kanban::moveCard($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'],
         $_POST['card'], $_POST['column'], $_POST['position']);
   }
} else if (($_POST['action'] ?? null) == 'show_column') {
   $checkParams(['column', 'kanban']);
   Item_Kanban::showColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} else if (($_POST['action'] ?? null) == 'hide_column') {
   $checkParams(['column', 'kanban']);
   Item_Kanban::hideColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} else if (($_POST['action'] ?? null) == 'collapse_column') {
   $checkParams(['column', 'kanban']);
   Item_Kanban::collapseColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} else if (($_POST['action'] ?? null) == 'expand_column') {
   $checkParams(['column', 'kanban']);
   Item_Kanban::expandColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} else if (($_POST['action'] ?? null) == 'move_column') {
   $checkParams(['column', 'kanban', 'position']);
   Item_Kanban::moveColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'],
      $_POST['column'], $_POST['position']);
} else if ($_REQUEST['action'] == 'refresh') {
   $checkParams(['column_field']);
   // Get all columns to refresh the kanban
   header("Content-Type: application/json; charset=UTF-8", true);
   $force_columns = Item_Kanban::getAllShownColumns($itemtype, $_REQUEST['items_id']);
   $columns = $itemtype::getKanbanColumns($_REQUEST['items_id'], $_REQUEST['column_field'], $force_columns, true);
   echo json_encode($columns, JSON_FORCE_OBJECT);
} else if ($_REQUEST['action'] == 'get_switcher_dropdown') {
   $values = $itemtype::getAllForKanban();
   Dropdown::showFromArray('kanban-board-switcher', $values, [
      'value'  => isset($_REQUEST['items_id']) ? $_REQUEST['items_id'] : ''
   ]);
} else if ($_REQUEST['action'] == 'get_url') {
   $checkParams(['items_id']);
   if ($_REQUEST['items_id'] == -1) {
      echo $itemtype::getFormURL(true).'?showglobalkanban=1';
      return;
   }
   $item->getFromDB($_REQUEST['items_id']);
   $tabs = $item->defineTabs();
   $tab_id = array_search(__('Kanban'), $tabs);
   if (is_null($tab_id) || false === $tab_id) {
      Toolbox::logError("Itemtype does not have a Kanban tab!");
      http_response_code(400);
      return;
   }
   echo $itemtype::getFormURLWithID($_REQUEST['items_id'], true)."&forcetab={$tab_id}";
} else if (($_POST['action'] ?? null) == 'create_column') {
   $checkParams(['column_field', 'items_id', 'column_name']);
   $column_field = $_POST['column_field'];
   $column_itemtype = getItemtypeForForeignKeyField($column_field);
   if (!$column_itemtype::canCreate() || !$column_itemtype::canView()) {
      // Missing rights
      http_response_code(403);
      return;
   }
   $params = $_POST['params'] ?? [];
   $column_item = new $column_itemtype();
   $column_id = $column_item->add([
      'name'   => $_POST['column_name']
   ] + $params);
   header("Content-Type: application/json; charset=UTF-8", true);
   $column = $itemtype::getKanbanColumns($_POST['items_id'], $column_field, [$column_id]);
   echo json_encode($column);
} else if (($_POST['action'] ?? null) == 'save_column_state') {
   $checkParams(['items_id', 'state']);
   Item_Kanban::saveStateForItem($_POST['itemtype'], $_POST['items_id'], $_POST['state']);
} else if ($_REQUEST['action'] == 'load_column_state') {
   $checkParams(['items_id', 'last_load']);
   header("Content-Type: application/json; charset=UTF-8", true);
   $response = [
      'state'     => Item_Kanban::loadStateForItem($_REQUEST['itemtype'], $_REQUEST['items_id'], $_REQUEST['last_load']),
      'timestamp' => $_SESSION['glpi_currenttime']
   ];
   echo json_encode($response, JSON_FORCE_OBJECT);
} else if ($_REQUEST['action'] == 'list_columns') {
   $checkParams(['column_field']);
   header("Content-Type: application/json; charset=UTF-8", true);
   echo json_encode($itemtype::getAllKanbanColumns($_REQUEST['column_field']));
} else if ($_REQUEST['action'] == 'get_column') {
   $checkParams(['column_id', 'column_field', 'items_id']);
   header("Content-Type: application/json; charset=UTF-8", true);
   $column = $itemtype::getKanbanColumns($_REQUEST['items_id'], $_REQUEST['column_field'], [$_REQUEST['column_id']]);
   echo json_encode($column, JSON_FORCE_OBJECT);
}
