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

$item_line = new Item_Line();
$line = new Line();

if (isset($_POST['update'])) {
    $item_line->check($_POST['id'], UPDATE);
    //update existing relation
    if ($item_line->update($_POST)) {
        $url = $line->getFormURLWithID($_POST['lines_id']);
    } else {
        $url = $item_line->getFormURLWithID($_POST['id']);
    }
    Html::redirect($url);
} elseif (isset($_POST['add'])) {
    $item_line->check(-1, CREATE, $_POST);
    $item_line->add($_POST);
    if (isset($_POST['_from']) && $_POST['_from'] === 'item') {
        $url = $_POST['itemtype']::getFormURLWithID($_POST['items_id']);
    } else {
        $url = $line->getFormURLWithID($_POST['lines_id']);
    }
    Html::redirect($url);
} elseif (isset($_POST['purge'])) {
    $item_line->check($_POST['id'], PURGE);
    $item_line->delete($_POST, true);
    if (isset($_POST['_from']) && $_POST['_from'] === 'item') {
        $url = $_POST['itemtype']::getFormURLWithID($_POST['items_id']);
    } else {
        $url = $line->getFormURLWithID($_POST['lines_id']);
    }
    Html::redirect($url);
}

if (!isset($_REQUEST['line']) && !isset($_REQUEST['id']) && !isset($_REQUEST['items_id'])) {
    throw new BadRequestHttpException();
}

$params = [];
if (isset($_REQUEST['id'])) {
    $params['id'] = $_REQUEST['id'];
} elseif (isset($_REQUEST['line'])) {
    $params = [
        'lines_id'  => $_REQUEST['line'],
        '_from'     => 'line',
    ];
} elseif (isset($_REQUEST['items_id'])) {
    $params = [
        'itemtype'  => $_REQUEST['itemtype'],
        'items_id'  => $_REQUEST['items_id'],
        '_from'     => 'item',
    ];
}

throw new BadRequestHttpException();
