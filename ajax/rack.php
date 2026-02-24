<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

$AJAX_INCLUDE = 1;
include('../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (!Session::haveRight('datacenter', UPDATE)) {
    http_response_code(403);
    die;
}
if (!isset($_REQUEST['action'])) {
    exit();
}

$answer = [];
if (($_GET['action'] ?? null) === 'show_pdu_form') {
    $rack_id = (int) $_GET['racks_id'];

    $rack = new Rack();
    if (!$rack->can($rack_id, READ)) {
        return;
    }

    PDU_Rack::showFirstForm($rack_id);
} elseif (isset($_POST['action'])) {
    header("Content-Type: application/json; charset=UTF-8", true);
    switch ($_POST['action']) {
        case 'move_item':
            $id = (int) $_POST['id'];

            $item_rack = new Item_Rack();
            if (!$item_rack->getFromDB($id) || !$item_rack->can($id, UPDATE)) {
                return;
            }

            $answer['status'] = $item_rack->update([
                'id'       => $id,
                'position' => (int) $_POST['position'],
                'hpos'     => (int) $_POST['hpos'],
            ]);
            break;

        case 'move_pdu':
            $id = (int) $_POST['id'];

            $pdu_rack = new PDU_Rack();
            if (!$pdu_rack->getFromDB($id) || !$pdu_rack->can($id, UPDATE)) {
                return;
            }

            $answer['status'] = $pdu_rack->update([
                'id'       => $id,
                'position' => (int) $_POST['position'],
            ]);
            break;

        case 'move_rack':
            $id = (int) $_POST['id'];

            $rack = new Rack();
            if (!$rack->getFromDB($id) || !$rack->can($id, UPDATE)) {
                return;
            }

            $answer['status'] = $rack->update([
                'id'         => $id,
                'dcrooms_id' => (int) $_POST['dcrooms_id'],
                'position'   => (int) $_POST['x'] . "," . (int) $_POST['y'],
            ]);
            break;
    }

    echo json_encode($answer);
}
