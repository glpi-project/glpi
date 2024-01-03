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

$ira = new \Item_Rack();
$rack = new Rack();

if (isset($_POST['update'])) {
    $ira->check($_POST['id'], UPDATE);
   //update existing relation
    if ($ira->update($_POST)) {
        $url = $rack->getFormURLWithID($_POST['racks_id']);
    } else {
        $url = $ira->getFormURLWithID($_POST['id']);
    }
    Html::redirect($url);
} else if (isset($_POST['add'])) {
    $ira->check(-1, CREATE, $_POST);
    $ira->add($_POST);
    $url = $rack->getFormURLWithID($_POST['racks_id']);
    Html::redirect($url);
} else if (isset($_POST['purge'])) {
    $ira->check($_POST['id'], PURGE);
    $ira->delete($_POST, 1);
    $url = $rack->getFormURLWithID($_POST['racks_id']);
    Html::redirect($url);
}

if (!isset($_GET['unit']) && !isset($_GET['orientation']) && !isset($_GET['rack']) && !isset($_GET['id'])) {
    Html::displayErrorAndDie('Lost');
}

$params = [];
if (isset($_GET['id'])) {
    $params['id'] = $_GET['id'];
} else {
    $params = [
        'racks_id'     => $_GET['racks_id'],
        'orientation'  => $_GET['orientation'],
        'position'     => $_GET['position']
    ];
    if (isset($_GET['_onlypdu'])) {
        $params['_onlypdu'] = $_GET['_onlypdu'];
    }
}
$ajax = isset($_REQUEST['ajax']) ? true : false;

if ($ajax) {
    $ira->display($params);
} else {
    $menus = ["assets", "rack"];
    Item_Rack::displayFullPageForItem($params['id'] ?? 0, $menus, $params);
}
