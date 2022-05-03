<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (
    (!isset($_REQUEST['params']['_idor_token']) || empty($_REQUEST['params']['_idor_token'])) || !isset($_REQUEST['itemtype'])
    || !isset($_REQUEST['widget'])
) {
    http_response_code(400);
    die();
}

$idor = $_REQUEST['params']['_idor_token'];
unset($_REQUEST['params']['_idor_token']);

if (
    !Session::validateIDOR([
        'itemtype'     => $_REQUEST['itemtype'],
        '_idor_token'  => $idor
    ] + $_REQUEST['params'])
) {
    http_response_code(400);
    die();
}

$itemtype = $_REQUEST['itemtype'];
$params = $_REQUEST['params'];

switch ($_REQUEST['widget']) {
    case 'central_count':
        if (method_exists($itemtype, 'showCentralCount')) {
            $itemtype::showCentralCount($params['foruser'] ?? false);
        }
        break;
    case 'central_list':
        if (method_exists($itemtype, 'showCentralList')) {
            if (is_subclass_of($itemtype, CommonITILObject::class)) {
                $showgrouptickets = isset($params['showgrouptickets']) ? ($params['showgrouptickets'] !== 'false') : false;
                $itemtype::showCentralList($params['start'], $params['status'] ?? 'process', $showgrouptickets);
            }
        } else if ($itemtype === RSSFeed::class) {
            $personal = $params['personal'] !== 'false';
            $itemtype::showListForCentral($personal);
        } else if ($itemtype === Planning::class) {
            $itemtype::showCentral($params['who']);
        } else if ($itemtype === Reminder::class) {
            $personal = ($params['personal'] ?? true) !== 'false';
            $itemtype::showListForCentral($personal);
        }
        break;
    default:
        echo __('Invalid widget');
}
