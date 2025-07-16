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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Exception\Http\BadRequestHttpException;

Session::checkCentralAccess();

$ios = new Item_OperatingSystem();

if (isset($_POST['update'])) {
    $ios->check($_POST['id'], UPDATE);
    //update existing OS
    $ios->update($_POST);

    $item = getItemForItemtype($_POST['itemtype']);
    $url = $item->getFormURLWithID($_POST['items_id']);
    Html::redirect($url);
} elseif (isset($_POST['add'])) {
    $ios->check(-1, CREATE, $_POST);
    $ios->add($_POST);

    $item = getItemForItemtype($_POST['itemtype']);
    $url = $item->getFormURLWithID($_POST['items_id']);
    Html::redirect($url);
} elseif (isset($_POST['purge'])) {
    $ios->check($_POST['id'], PURGE);
    $ios->delete($_POST, true);

    $item = getItemForItemtype($_POST['itemtype']);
    $url = $item->getFormURLWithID($_POST['items_id']);
    Html::redirect($url);
}

if (!isset($_GET['itemtype']) && !isset($_GET['items_id']) && !isset($_GET['id'])) {
    throw new BadRequestHttpException();
}

$params = [];
if (isset($_GET['id'])) {
    $params['id'] = $_GET['id'];
} else {
    $params = [
        'itemtype'  => $_GET['itemtype'],
        'items_id'  => $_GET['items_id'],
    ];
}

$menus = ["assets", "computer"];
Item_OperatingSystem::displayFullPageForItem($params['id'] ?? 0, $menus, $params);
