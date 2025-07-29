<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\HttpException;
use Glpi\Features\KanbanInterface;
use Glpi\Features\TeamworkInterface;

use function Safe\json_encode;
use function Safe\preg_split;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (!isset($_REQUEST['action'])) {
    throw new BadRequestHttpException("Missing action parameter");
}
$action = $_REQUEST['action'];

$nonkanban_actions = ['update', 'bulk_add_item', 'add_item', 'move_item', 'delete_item', 'load_item_panel',
    'add_teammember', 'delete_teammember', 'restore_item', 'load_teammember_form',
];
/** @var ?CommonDBTM $item */
$itemtype = null;
$item = null;
if (isset($_REQUEST['itemtype'])) {
    if (!in_array($_REQUEST['action'], $nonkanban_actions) && !is_a($_REQUEST['itemtype'], KanbanInterface::class, true)) {
        // Bad request
        // For all actions, except those in $nonkanban_actions, we expect to be manipulating the Kanban itself.
        throw new BadRequestHttpException("Invalid itemtype parameter");
    }
    $itemtype = $_REQUEST['itemtype'];
    $item = getItemForItemtype($itemtype);
}

if (isset($_REQUEST['items_id'])) {
    $_REQUEST['items_id'] = (int) $_REQUEST['items_id'];
}

// Rights Checks
if ($item !== null) {
    if (in_array($action, ['refresh', 'get_switcher_dropdown', 'get_column', 'load_item_panel'])) {
        if (!$item->canView()) {
            // Missing rights
            throw new AccessDeniedHttpException();
        }
    }
    if (in_array($action, ['update', 'load_item_panel', 'delete_teammember'])) {
        if (!$item->can($_REQUEST['items_id'], UPDATE)) {
            // Missing rights
            throw new AccessDeniedHttpException();
        }
    }
    if (in_array($action, ['load_teammember_form', 'add_teammember'])) {
        $item->getFromDB($_REQUEST['items_id']);
        $can_assign = method_exists($item, 'canAssign') ? $item->canAssign() : $item->can($_REQUEST['items_id'], UPDATE);
        if (!$can_assign) {
            // Missing rights
            throw new AccessDeniedHttpException();
        }
    }
    if (in_array($action, ['bulk_add_item', 'add_item'])) {
        if (!$item->canCreate()) {
            // Missing rights
            throw new AccessDeniedHttpException();
        }
    }
    if (in_array($action, ['delete_item'])) {
        $maybe_deleted = $item->maybeDeleted();
        if (($maybe_deleted && !$item::canDelete()) || (!$maybe_deleted && $item::canPurge())) {
            // Missing rights
            throw new AccessDeniedHttpException();
        }
    }
    if ($action === 'restore_item') {
        $maybe_deleted = $item->maybeDeleted();
        if (($maybe_deleted && !$item::canDelete())) {
            // Missing rights
            throw new AccessDeniedHttpException();
        }
    }
}

// Helper to check required parameters
$checkParams = static function ($required) {
    foreach ($required as $param) {
        if (!isset($_REQUEST[$param])) {
            throw new BadRequestHttpException("Missing $param parameter");
        }
    }
};

// Action Processing
if (($_POST['action'] ?? null) === 'update') {
    $checkParams(['column_field', 'column_value']);
    // Update project or task based on changes made in the Kanban
    $item->update([
        'id'                   => (int) $_POST['items_id'],
        $_POST['column_field'] => $_POST['column_value'],
    ]);
} elseif (($_POST['action'] ?? null) === 'add_item') {
    $checkParams(['inputs']);

    $item = getItemForItemtype($itemtype);
    if (!$item) {
        throw new BadRequestHttpException();
    }

    $inputs = $_POST['inputs'];

    if (!$item->can(-1, CREATE, $inputs)) {
        throw new AccessDeniedHttpException();
    }

    $result = $item->add($inputs);
    if (!$result) {
        throw new BadRequestHttpException();
    }
} elseif (($_POST['action'] ?? null) === 'bulk_add_item') {
    $checkParams(['inputs']);

    $item = getItemForItemtype($itemtype);
    if (!$item) {
        throw new BadRequestHttpException();
    }

    $inputs = $_POST['inputs'];

    $bulk_item_list = preg_split('/\r\n|[\r\n]/', $inputs['bulk_item_list']);
    if ($bulk_item_list !== []) {
        unset($inputs['bulk_item_list']);
        foreach ($bulk_item_list as $item_entry) {
            $item_entry = trim($item_entry);
            if (!empty($item_entry)) {
                $item_input = $inputs + ['name' => $item_entry, 'content' => ''];
                if ($item->can(-1, CREATE, $item_input)) {
                    $item->add($item_input);
                }
            }
        }
    }
} elseif (($_POST['action'] ?? null) === 'move_item') {
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
} elseif (($_POST['action'] ?? null) === 'show_column') {
    $checkParams(['column', 'kanban']);
    Item_Kanban::showColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} elseif (($_POST['action'] ?? null) === 'hide_column') {
    $checkParams(['column', 'kanban']);
    Item_Kanban::hideColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} elseif (($_POST['action'] ?? null) === 'collapse_column') {
    $checkParams(['column', 'kanban']);
    Item_Kanban::collapseColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} elseif (($_POST['action'] ?? null) === 'expand_column') {
    $checkParams(['column', 'kanban']);
    Item_Kanban::expandColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column']);
} elseif (($_POST['action'] ?? null) === 'move_column') {
    $checkParams(['column', 'kanban', 'position']);
    Item_Kanban::moveColumn($_POST['kanban']['itemtype'], $_POST['kanban']['items_id'], $_POST['column'], $_POST['position']);
} elseif ($_REQUEST['action'] === 'refresh') {
    $checkParams(['column_field']);
    // Get all columns to refresh the kanban
    header("Content-Type: application/json; charset=UTF-8", true);
    $force_columns = Item_Kanban::getAllShownColumns($itemtype, $_REQUEST['items_id']);
    $columns = $itemtype::getKanbanColumns($_REQUEST['items_id'], $_REQUEST['column_field'], $force_columns, true);
    echo json_encode($columns, JSON_FORCE_OBJECT);
} elseif ($_REQUEST['action'] === 'get_switcher_dropdown') {
    $values = $itemtype::getAllForKanban();
    Dropdown::showFromArray('kanban-board-switcher', $values, [
        'value' => $_REQUEST['items_id'] ?? '',
    ]);
} elseif ($_REQUEST['action'] === 'get_kanbans') {
    header("Content-Type: application/json; charset=UTF-8", true);
    echo json_encode($itemtype::getAllForKanban(true, (int) ($_REQUEST['items_id'] ?? -1)));
} elseif ($_REQUEST['action'] === 'get_url') {
    if (!($item instanceof KanbanInterface)) {
        throw new BadRequestHttpException();
    }

    $checkParams(['items_id']);
    if ($_REQUEST['items_id'] == -1) {
        echo $itemtype::getGlobalKanbanUrl(true);
        return;
    }
    $item->getFromDB($_REQUEST['items_id']);
    echo $item->getKanbanUrlWithID($_REQUEST['items_id'], true);
} elseif (($_POST['action'] ?? null) === 'create_column') {
    $checkParams(['column_field', 'items_id', 'column_name']);
    $column_field = $_POST['column_field'];
    $column_item = getItemForForeignKeyField($column_field);
    if (!$column_item::canCreate() || !$column_item::canView()) {
        // Missing rights
        throw new AccessDeniedHttpException();
    }
    $params = $_POST['params'] ?? [];
    $column_item->add([
        'name'   => $_POST['column_name'],
    ] + $params);
} elseif (($_POST['action'] ?? null) === 'save_column_state') {
    if (!isset($_POST['state'])) {
        // Do nothing with the state unless it isn't saved yet. Could be that no columns are shown or an error occurred.
        // If the state is supposed to be cleared, it should come through as a clear_column_state request.
        if (Item_Kanban::hasStateForItem($_POST['itemtype'], $_POST['items_id'])) {
            return;
        }
        Item_Kanban::saveStateForItem($_POST['itemtype'], $_POST['items_id'], []);
        return;
    }
    $checkParams(['items_id', 'state']);
    Item_Kanban::saveStateForItem($_POST['itemtype'], $_POST['items_id'], $_POST['state']);
} elseif ($_REQUEST['action'] === 'load_column_state') {
    $checkParams(['items_id', 'last_load']);
    header("Content-Type: application/json; charset=UTF-8", true);
    $response = [
        'state'     => Item_Kanban::loadStateForItem($_REQUEST['itemtype'], $_REQUEST['items_id'], $_REQUEST['last_load']),
        'timestamp' => $_SESSION['glpi_currenttime'],
    ];
    echo json_encode($response, JSON_FORCE_OBJECT);
} elseif ($_REQUEST['action'] === 'clear_column_state') {
    $checkParams(['items_id']);
    $result = Item_Kanban::clearStateForItem($_REQUEST['itemtype'], $_REQUEST['items_id']);
    if (!$result) {
        throw new HttpException(500);
    }

    return;
} elseif ($_REQUEST['action'] === 'list_columns') {
    $checkParams(['column_field']);
    header("Content-Type: application/json; charset=UTF-8", true);
    echo json_encode($itemtype::getAllKanbanColumns($_REQUEST['column_field']));
} elseif ($_REQUEST['action'] === 'get_column') {
    Session::writeClose();
    $checkParams(['column_id', 'column_field', 'items_id']);
    header("Content-Type: application/json; charset=UTF-8", true);
    $column = $itemtype::getKanbanColumns($_REQUEST['items_id'], $_REQUEST['column_field'], [$_REQUEST['column_id']]);
    echo json_encode($column, JSON_FORCE_OBJECT);
} elseif (($_POST['action'] ?? null) === 'delete_item') {
    $checkParams(['items_id']);
    $item->getFromDB($_POST['items_id']);
    // Check if the item can be trashed and if the request isn't forcing deletion (purge)
    $maybe_deleted = $item->maybeDeleted() && !($_REQUEST['force'] ?? false);
    if (($maybe_deleted && $item->can($_POST['items_id'], DELETE)) || (!$maybe_deleted && $item->can($_POST['items_id'], PURGE))) {
        $item->delete(['id' => $_POST['items_id']], !$maybe_deleted);
        // Check if the item was deleted or purged
        header("Content-Type: application/json; charset=UTF-8", true);
        echo json_encode([
            'purged' => $item->getFromDB($_POST['items_id']) === false,
        ]);
    } else {
        throw new AccessDeniedHttpException();
    }
} elseif (($_POST['action'] ?? null) === 'restore_item') {
    $checkParams(['items_id']);
    $item->getFromDB($_POST['items_id']);
    // Check if the item can be restored
    $maybe_deleted = $item->maybeDeleted();
    if (($maybe_deleted && $item->can($_POST['items_id'], DELETE))) {
        $item->restore(['id' => $_POST['items_id']]);
    } else {
        throw new AccessDeniedHttpException();
    }
} elseif (($_POST['action'] ?? null) === 'add_teammember') {
    if (!($item instanceof TeamworkInterface)) {
        throw new BadRequestHttpException();
    }
    $checkParams(['itemtype_teammember', 'items_id_teammember']);
    $item->addTeamMember($_POST['itemtype_teammember'], (int) $_POST['items_id_teammember'], [
        'role' => $_POST['role'],
    ]);
} elseif (($_POST['action'] ?? null) === 'delete_teammember') {
    if (!($item instanceof TeamworkInterface)) {
        throw new BadRequestHttpException();
    }
    $checkParams(['itemtype_teammember', 'items_id_teammember']);
    $item->deleteTeamMember($_POST['itemtype_teammember'], (int) $_POST['items_id_teammember'], [
        'role'   => (int) $_POST['role'],
    ]);
} elseif (($_REQUEST['action'] ?? null) === 'load_item_panel') {
    if (isset($itemtype, $item)) {
        TemplateRenderer::getInstance()->display('components/kanban/item_panels/default_panel.html.twig', [
            'itemtype' => $itemtype,
            'item_fields' => $item->fields,
            'team' => $item instanceof TeamworkInterface ? $item->getTeam() : [],
        ]);
    } else {
        throw new BadRequestHttpException();
    }
} elseif (($_REQUEST['action'] ?? null) === 'load_teammember_form') {
    if (isset($itemtype, $item) && $item instanceof TeamworkInterface) {
        echo $item::getTeamMemberForm($item);
    } else {
        throw new BadRequestHttpException();
    }
} else {
    throw new BadRequestHttpException();
}
