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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;

use function Safe\json_encode;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("config", READ);

$action = $_REQUEST['action'] ?? null;

switch ($action) {
    case 'valide_cra_challenge':
        $webhook = new Webhook();
        if ($webhook->getFromDB($_POST['webhook_id'])) {
            $response = Webhook::validateCRAChallenge($webhook->fields['url'], 'validate_cra_challenge', $_POST['secret']);
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode($response);
            return;
        } else {
            throw new NotFoundHttpException();
        }
        // no break
    case 'get_events_from_itemtype':
        echo Dropdown::showFromArray(
            "event",
            Webhook::getDefaultEventsList(),
            ['display' => false]
        );
        return;
    case 'get_items_from_itemtype':
        if (array_key_exists($_POST['itemtype'], Webhook::getSubItemForAssistance())) {
            $object = getItemForItemtype($_POST['itemtype']);
            $data = $object->find();
            $values = [];
            foreach ($data as $items_id => $items_data) {
                if ($object instanceof CommonITILTask || $object instanceof CommonITILValidation) {
                    $itil_type = $object::getItilObjectItemType();
                    $foreign_key = getForeignKeyFieldForItemType($itil_type);
                    $values[$items_id] = $itil_type::getTypeName(0) . " " . $items_data[$foreign_key] . " => " . $object::getTypeName(0) . " " . $items_id;
                } else {
                    $values[$items_id] = $items_data['itemtype']::getTypeName(0) . " " . $items_data['items_id'] . " => " . $object::getTypeName(0) . " " . $items_id;
                }
            }
            echo Dropdown::showFromArray('items_id', $values, [
                'display' => false,
            ]);
        } else {
            if (!empty($_POST['itemtype'])) {
                echo Dropdown::show(
                    $_POST['itemtype'],
                    [
                        'name' => 'items_id',
                        'display' => false,
                    ]
                );
            } else {
                echo Dropdown::showFromArray(
                    "items_id",
                    [],
                    [
                        'display' => false,
                        'display_emptychoice' => true,
                    ]
                );
            }
        }
        return;
    case 'get_webhook_body':
        $webhook = new Webhook();
        $itemtype = $_POST['itemtype'];
        $items_id =  $_POST['items_id'];
        $event =  $_POST['event'];
        $raw_output = $_POST['raw_output'] ?? false;

        if (isset($_POST['webhook_id'])) {
            $webhook->getFromDB($_POST['webhook_id']);
        }

        $error = [];
        if (!$itemtype) {
            $error[] = __s('Please select an itemtype');
        }

        if (!$items_id) {
            $error[] = __s('Please select an item');
        }

        if (!$event) {
            $error[] = __s('Please select an event');
        }

        if (count($error) > 0) {
            array_unshift($error, __s("Result can't be loaded :"));
            echo implode("<br>&nbsp; - ", $error);
        } else {
            $obj = getItemForItemtype($itemtype);
            $obj->getFromDB($items_id);
            $path = $webhook->getApiPath($obj);
            echo $webhook->getResultForPath($path, $event, $itemtype, $items_id, $raw_output);
        }

        return;
    case 'update_payload_template':
        $webhook_id = $_POST['webhook_id'];
        $payload_template = $_POST['payload_template'] ?? '';
        $webhook = new Webhook();
        if ($webhook->getFromDB($webhook_id)) {
            if (!$webhook->canUpdateItem()) {
                throw new AccessDeniedHttpException();
            }
            if ($_POST['use_default_payload'] === 'true') {
                $webhook->update([
                    'id' => $webhook_id,
                    'use_default_payload' => 1,
                ]);
            } else {
                $webhook->update([
                    'id' => $webhook_id,
                    'payload' => $payload_template,
                    'use_default_payload' => 0,
                ]);
            }
        } else {
            throw new NotFoundHttpException();
        }
        return;
    case 'resend':
        $result = QueuedWebhook::sendById($_POST['id']);
        if (!$result) {
            throw new BadRequestHttpException();
        }
        return;
    case 'get_monaco_suggestions':
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(Webhook::getMonacoSuggestions($_GET['itemtype']), JSON_THROW_ON_ERROR);
        return;
}

throw new BadRequestHttpException();
