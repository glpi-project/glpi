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

include('../inc/includes.php');

Session::checkCentralAccess();

$pra  = new \PDU_Rack();
$rack = new Rack();

if (isset($_POST['update'])) {
    $pra->check($_POST['id'], UPDATE);
   //update existing relation
    if ($pra->update($_POST)) {
        $url = $rack->getFormURLWithID($_POST['racks_id']);
    } else {
        $url = $pra->getFormURLWithID($_POST['id']);
    }
    Html::redirect($url);
} else if (isset($_POST['add'])) {
    $pra->check(-1, CREATE, $_POST);
    $pra->add($_POST);
    $url = $rack->getFormURLWithID($_POST['racks_id']);
    Html::redirect($url);
} else if (isset($_POST['purge'])) {
    $pra->check($_POST['id'], PURGE);
    $pra->delete($_POST, 1);
    $url = $rack->getFormURLWithID($_POST['racks_id']);
    Html::redirect($url);
}

$params = [];
if (isset($_GET['id'])) {
    $params['id'] = $_GET['id'];
} else {
    $params = [
        'racks_id'     => $_GET['racks_id'],
    ];
}

$_SESSION['glpilisturl'][PDU_Rack::getType()] = $rack->getSearchURL();

$ajax = isset($_REQUEST['ajax']) ? true : false;

if ($ajax) {
    $pra->display($params);
} else {
    $menus = ["assets", "rack"];
    PDU_Rack::displayFullPageForItem($_GET['id'] ?? 0, $menus, $params);
}
