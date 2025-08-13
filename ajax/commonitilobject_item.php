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

use Glpi\Exception\Http\BadRequestHttpException;

global $DB;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

// Should be defined by other files that include this file.
// See: change_item.php, item_problem.php, item_ticket.php and item_ticketrecurrent.php
$obj ??= null;
$item_obj ??= null;
$valid_obj = $obj instanceof CommonITILObject || $obj instanceof TicketRecurrent;
if (!$valid_obj || !($item_obj instanceof CommonItilObject_Item)) {
    throw new BadRequestHttpException();
}

switch ($_POST['action']) {
    case 'add':
        if (!empty($_POST['my_items'])) {
            [$_POST['itemtype'], $_POST['items_id']] = explode('_', $_POST['my_items']);
        }
        if (isset($_POST['itemtype']) && !empty($_POST['items_id'])) {
            $_POST['params']['items_id'][$_POST['itemtype']][$_POST['items_id']] = $_POST['items_id'];
        }
        $item_obj::itemAddForm($obj, $_POST['params'] ?? []);
        break;

    case 'delete':
        if (isset($_POST['itemtype']) && !empty($_POST['items_id'])) {
            if ($_POST['params']['id'] > 0) {
                if ($item_obj->canPurge()) {
                    $iterator = $DB->request([
                        'FROM' => $item_obj::getTable(),
                        'WHERE' => [
                            'tickets_id' => $_POST['params']['id'],
                            'items_id' => $_POST['items_id'],
                            'itemtype' => $_POST['itemtype'],
                        ],
                    ]);
                    foreach ($iterator as $data) {
                        $item_obj->getFromDB($data['id']);
                        if ($item_obj->can($data['id'], DELETE)) {
                            $item_obj->delete(['id' => $data['id']]);
                        }
                    }
                }
                unset($_POST['params']['items_id'][$_POST['itemtype']][array_search($_POST['items_id'], $_POST['params']['items_id'][$_POST['itemtype']])]);
            }
            $item_obj::itemAddForm($obj, $_POST['params'] ?? []);
        }

        break;
}
