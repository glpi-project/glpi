<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Features\Kanban;
use Glpi\Features\Teamwork;
use Glpi\Http\Response;
use Glpi\Toolbox\Sanitizer;

/** @var array $_UPOST */
global $_UPOST;

$AJAX_INCLUDE = 1;

include('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (!isset($_REQUEST['action'])) {
    Response::sendError(400, "Missing action parameter", Response::CONTENT_TYPE_TEXT_HTML);
}
$action = $_REQUEST['action'];

$nonkanban_actions = ['update', 'bulk_add_item', 'add_item', 'move_item', 'delete_item', 'load_item_panel',
    'add_teammember', 'delete_teammember', 'restore_item', 'load_teammember_form',
];

$itemtype = null;
$item = null;
if (isset($_REQUEST['itemtype'])) {
    if (!in_array($_REQUEST['action'], $nonkanban_actions) && !Toolbox::hasTrait($_REQUEST['itemtype'], Kanban::class)) {
       // Bad request
       // For all actions, except those in $nonkanban_actions, we expect to be manipulating the Kanban itself.
        Response::sendError(400, "Invalid itemtype parameter", Response::CONTENT_TYPE_TEXT_HTML);
    }
    /** @var CommonDBTM $item */
    $itemtype = $_REQUEST['itemtype'];
    $item = new $itemtype();
}

// Rights Checks
if ($item !== null) {
    if (in_array($action, ['refresh', 'get_switcher_dropdown', 'get_column', 'load_item_panel'])) {
        if (!$item->canView()) {
           // Missing rights
            http_response_code(403);
            return;
        }
    }
    if (in_array($action, ['update', 'load_item_panel', 'delete_teammember'])) {
        if (!$item->can($_REQUEST['items_id'], UPDATE)) {
            // Missing rights
            http_response_code(403);
            return;
        }
    }
    if (in_array($action, ['load_teammember_form', 'add_teammember'])) {
        $item->getFromDB($_REQUEST['items_id']);
        $can_assign = method_exists($item, 'canAssign') ? $item->canAssign() : $item->can($_REQUEST['items_id'], UPDATE);
        if (!$can_assign) {
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
    if (in_array($action, ['delete_item'])) {
        $maybe_deleted = $item->maybeDeleted();
        if (($maybe_deleted && !$item::canDelete()) || (!$maybe_deleted && $item::canPurge())) {
           // Missing rights
            http_response_code(403);
            return;
        }
    }
    if ($action === 'restore_item') {
        $maybe_deleted = $item->maybeDeleted();
        if (($maybe_deleted && !$item::canDelete())) {
            // Missing rights
            http_response_code(403);
            return;
        }
    }
}

// Helper to check required parameters
$checkParams = static function ($required) {
    foreach ($required as $param) {
        if (!isset($_REQUEST[$param])) {
            Response::sendError(400, "Missing $param parameter");
        }
    }
};

// Action Processing
if (($_POST['action'] ?? null) === 'update') {
    $checkParams(['column_field', 'column_value']);
   // Update project or task based on changes made in the Kanban
    $item->update([
        'id'                   => $_POST['items_id'],
        $_POST['column_field'] => $_POST['column_value']
    ]);
} else if (($_POST['action'] ?? null) === 'add_item') {
    $checkParams(['inputs']);

    $item = getItemForItemtype($itemtype);
    if (!$item) {
        http_response_code(400);
        return;
    }

    $inputs = [];
    parse_str($_UPOST['inputs'], $inputs);
    $inputs = Sanitizer::sanitize($inputs);

    if (!$item->can(-1, CREATE, $inputs)) {
        http_response_code(403);
        return;
    }

    $result = $item->add($inputs);
    if (!$result) {
        http_response_code(400);
        return;
    }
} else if (($_POST['action'] ?? null) === 'bulk_add_item') {
    $checkParams(['inputs']);

    $item = getItemForItemtype($itemtype);
    if (!$item) {
        http_response_code(400);
        return;
    }

    $inputs = [];
    parse_str($_UPOST['inputs'], $inputs);

    $bulk_item_list = preg_split('/\r\n|[\r\n]/', $inputs['bulk_item_list']);
    if (!empty($bulk_item_list)) {
        unset($inputs['bulk_item_list']);
        foreach ($bulk_item_list as $item_entry) {
            $item_entry = trim($item_entry);
            if (!empty($item_entry)) {
                $item_input = Sanitizer::sanitize($inputs + ['name' => $item_entry, 'content' => '']);
                if ($item->can(-1, CREATE, $item_input)) {
                    $item->add($item_input);
                }
            }
        }
    }
} else if (($_POST['action'] ?? null) === 'move_item') {
    $checkParams(['card', 'column', 'position', 'kanban']);
    $kanban = getItemForItemtype($_POST['kanban']['itemtype']);
    $can_move = false;
    if (method_exists($kanban, 'canOrderKanbanCard')) {
        $can_move = $kanban->canOrderKanbanCard($_POST['kanban']['items_id']);
    }
    if ($can_move) {
        Item_Kanban::moveCard(
            $_POST['kanban']['itemtype'],
            $_POST['kanban']['items_id'],
            $_POST['card'],
            $_POST['column'],
            $_POST['position']
        );
    }
} else if (($_POST['action'] ?? null) === 'show_column') {
    $checkParams(['column', 'kanban']);
    Item_Kanban::showColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} else if (($_POST['action'] ?? null) === 'hide_column') {
    $checkParams(['column', 'kanban']);
    Item_Kanban::hideColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} else if (($_POST['action'] ?? null) === 'collapse_column') {
    $checkParams(['column', 'kanban']);
    Item_Kanban::collapseColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} else if (($_POST['action'] ?? null) === 'expand_column') {
    $checkParams(['column', 'kanban']);
    Item_Kanban::expandColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} else if (($_POST['action'] ?? null) === 'move_column') {
    $checkParams(['column', 'kanban', 'position']);
    Item_Kanban::moveColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column'], $_POST['position']);
} else if ($_REQUEST['action'] === 'refresh') {
    $checkParams(['column_field']);
   // Get all columns to refresh the kanban
    header("Content-Type: application/json; charset=UTF-8", true);
    $force_columns = Item_Kanban::getAllShownColumns($itemtype, $_REQUEST['items_id']);
    $columns = $itemtype::getKanbanColumns($_REQUEST['items_id'], $_REQUEST['column_field'], $force_columns, true);
    echo json_encode($columns, JSON_FORCE_OBJECT);
} else if ($_REQUEST['action'] === 'get_switcher_dropdown') {
    $values = $itemtype::getAllForKanban();
    Dropdown::showFromArray('kanban-board-switcher', $values, [
        'value'  => $_REQUEST['items_id'] ?? ''
    ]);
} else if ($_REQUEST['action'] === 'get_url') {
    $checkParams(['items_id']);
    if ($_REQUEST['items_id'] == -1) {
        echo $itemtype::getGlobalKanbanUrl(true);
        return;
    }
    $item->getFromDB($_REQUEST['items_id']);
    echo $item->getKanbanUrlWithID($_REQUEST['items_id'], true);
} else if (($_POST['action'] ?? null) === 'create_column') {
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
} else if (($_POST['action'] ?? null) === 'save_column_state') {
    $checkParams(['items_id', 'state']);
    Item_Kanban::saveStateForItem($_POST['itemtype'], $_POST['items_id'], $_POST['state']);
} else if ($_REQUEST['action'] === 'load_column_state') {
    $checkParams(['items_id', 'last_load']);
    header("Content-Type: application/json; charset=UTF-8", true);
    $response = [
        'state'     => Item_Kanban::loadStateForItem($_REQUEST['itemtype'], $_REQUEST['items_id'], $_REQUEST['last_load']),
        'timestamp' => $_SESSION['glpi_currenttime']
    ];
    echo json_encode($response, JSON_FORCE_OBJECT);
} else if ($_REQUEST['action'] === 'list_columns') {
    $checkParams(['column_field']);
    header("Content-Type: application/json; charset=UTF-8", true);
    echo json_encode($itemtype::getAllKanbanColumns($_REQUEST['column_field']));
} else if ($_REQUEST['action'] === 'get_column') {
    Session::writeClose();
    $checkParams(['column_id', 'column_field', 'items_id']);
    header("Content-Type: application/json; charset=UTF-8", true);
    $column = $itemtype::getKanbanColumns($_REQUEST['items_id'], $_REQUEST['column_field'], [$_REQUEST['column_id']]);
    echo json_encode($column, JSON_FORCE_OBJECT);
} else if (($_POST['action'] ?? null) === 'delete_item') {
    $checkParams(['items_id']);
    $item->getFromDB($_POST['items_id']);
   // Check if the item can be trashed and if the request isn't forcing deletion (purge)
    $maybe_deleted = $item->maybeDeleted() && !($_REQUEST['force'] ?? false);
    if (($maybe_deleted && $item->can($_POST['items_id'], DELETE)) || (!$maybe_deleted && $item->can($_POST['items_id'], PURGE))) {
        $item->delete(['id' => $_POST['items_id']], !$maybe_deleted);
    } else {
        http_response_code(403);
        return;
    }
} else if (($_POST['action'] ?? null) === 'restore_item') {
    $checkParams(['items_id']);
    $item->getFromDB($_POST['items_id']);
    // Check if the item can be restored
    $maybe_deleted = $item->maybeDeleted();
    if (($maybe_deleted && $item->can($_POST['items_id'], DELETE))) {
        $item->restore(['id' => $_POST['items_id']]);
    } else {
        http_response_code(403);
        return;
    }
} else if (($_POST['action'] ?? null) === 'add_teammember') {
    $checkParams(['itemtype_teammember', 'items_id_teammember']);
    $item->addTeamMember($_POST['itemtype_teammember'], (int) $_POST['items_id_teammember'], [
        'role' => $_POST['role']
    ]);
} else if (($_POST['action'] ?? null) === 'delete_teammember') {
    $checkParams(['itemtype_teammember', 'items_id_teammember']);
    $item->deleteTeamMember($_POST['itemtype_teammember'], (int) $_POST['items_id_teammember'], [
        'role'   => (int) $_POST['role']
    ]);
} else if (($_REQUEST['action'] ?? null) === 'load_item_panel') {
    if (isset($itemtype, $item)) {
        TemplateRenderer::getInstance()->display('components/kanban/item_panels/default_panel.html.twig', [
            'itemtype'     => $itemtype,
            'item_fields'  => $item->fields,
            'team'         => Toolbox::hasTrait($item, Teamwork::class) ? $item->getTeam() : []
        ]);
    } else {
        http_response_code(400);
        return;
    }
} else if (($_REQUEST['action'] ?? null) === 'load_teammember_form') {
    if (isset($itemtype, $item) && Toolbox::hasTrait($_REQUEST['itemtype'], Teamwork::class)) {
        echo $item::getTeamMemberForm($item, $itemtype);
    } else {
        http_response_code(400);
        return;
    }
} else {
    http_response_code(400);
    return;
}
