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
    PDU_Rack::showFirstForm((int) $_GET['racks_id']);
} else if (isset($_POST['action'])) {
    header("Content-Type: application/json; charset=UTF-8", true);
    switch ($_POST['action']) {
        case 'move_item':
            $item_rack = new Item_Rack();
            $item_rack->getFromDB((int) $_POST['id']);
            $answer['status'] = $item_rack->update([
                'id'       => (int) $_POST['id'],
                'position' => (int) $_POST['position'],
                'hpos'     => (int) $_POST['hpos'],
            ]);
            break;

        case 'move_pdu':
            $pdu_rack = new PDU_Rack();
            $pdu_rack->getFromDB((int) $_POST['id']);
            $answer['status'] = $pdu_rack->update([
                'id'       => (int) $_POST['id'],
                'position' => (int) $_POST['position']
            ]);
            break;

        case 'move_rack':
            $rack = new Rack();
            $rack->getFromDB((int) $_POST['id']);
            $answer['status'] = $rack->update([
                'id'         => (int) $_POST['id'],
                'dcrooms_id' => (int) $_POST['dcrooms_id'],
                'position'   => (int) $_POST['x'] . "," . (int) $_POST['y'],
            ]);
            break;
    }

    echo json_encode($answer);
}
