<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

include('../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("config", READ);

$action = $_POST['action'] ?? $_POST["action"];

switch ($action) {
    case 'valide_cra_challenge':
        $response = Webhook::validateCRAChallenge($_POST['target_url'], 'validate_cra_challenge', $_POST['secret']);
        echo json_encode($response);
        break;
    case 'get_events_from_itemtype':
        echo Dropdown::showFromArray(
            "event",
            Webhook::getDefaultEventsList(),
            ['display' => false]
        );
        break;
    case 'get_items_from_itemtype':
        if (array_key_exists($_POST['itemtype'], Webhook::getSubItemForAssistance())) {
            $object = new $_POST['itemtype']();
            $data = $object->find();
            $values = [];
            foreach ($data as $items_id => $items_data) {
                if (is_a($object::getType(), CommonITILTask::class, true)) {
                    switch ($object::getType()) {
                        case TicketTask::class:
                            $foreign_key = "tickets_id";
                            break;
                        case ChangeTask::class:
                            $foreign_key = "changes_id";
                            break;
                        case ProblemTask::class:
                            $foreign_key = "problems_id";
                            break;
                    }
                    $values[$items_id] = getItemtypeForForeignKeyField($foreign_key)::getType() . " " . $items_data[$foreign_key] . " => " . $object::getTypeName(0) . " " . $items_id;
                } else {
                    $values[$items_id] = $items_data['itemtype']::getTypeName(0) . " " . $items_data['items_id'] . " => " . $object::getTypeName(0) . " " . $items_id;
                }
            }
            echo Dropdown::showFromArray('items_id', $values, [
                'display' => false
            ]);
        } else {
            if (!empty($_POST['itemtype'])) {
                echo Dropdown::show(
                    $_POST['itemtype'],
                    [
                        'name' => 'items_id',
                        'display' => false
                    ]
                );
            } else {
                echo Dropdown::showFromArray(
                    "items_id",
                    [],
                    [
                        'display' => false,
                        'display_emptychoice' => true
                    ]
                );
            }
        }
        break;
    case 'get_webhook_body':
        $webhook = new Webhook();
        $itemtype = $_POST['itemtype'];
        $items_id =  $_POST['items_id'];
        $event =  $_POST['event'];

        $error = [];
        if (!$itemtype) {
            $error[] = __('Please select an itemtype');
        }

        if (!$items_id) {
            $error[] = __('Please select an item');
        }

        if (!$event) {
            $error[] = __('Please select an event');
        }

        if (count($error) > 0) {
            array_unshift($error, __("Result can't be loaded :"));
            echo implode("<br>&nbsp; - ", $error);
        } else {
            $obj = new $itemtype();
            $obj->getFromDB($items_id);
            $path = $webhook->getPathByItem($obj);
            echo $webhook->callAPI($path, $event, $itemtype);
        }

        break;
}
