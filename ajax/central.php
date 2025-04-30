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

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (
    (!isset($_REQUEST['params']['_idor_token']) || empty($_REQUEST['params']['_idor_token'])) || !isset($_REQUEST['itemtype'])
    || !isset($_REQUEST['widget'])
) {
    throw new BadRequestHttpException();
}

$idor = $_REQUEST['params']['_idor_token'];
unset($_REQUEST['params']['_idor_token']);

if (
    !Session::validateIDOR([
        'itemtype'     => $_REQUEST['itemtype'],
        '_idor_token'  => $idor,
    ] + $_REQUEST['params'])
) {
    throw new BadRequestHttpException();
}

/** @var class-string<CommonGLPI> $itemtype */
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
            if (is_subclass_of($itemtype, CommonITILObject::class) || is_subclass_of($itemtype, CommonITILTask::class)) {
                $showgrouptickets = isset($params['showgrouptickets']) ? ($params['showgrouptickets'] !== 'false') : false;
                $itemtype::showCentralList($params['start'], $params['status'] ?? 'process', $showgrouptickets);
            }
        } elseif ($itemtype === RSSFeed::class) {
            $personal = $params['personal'] !== 'false';
            $itemtype::showListForCentral($personal);
        } elseif ($itemtype === Planning::class) {
            $itemtype::showCentral($params['who']);
        } elseif ($itemtype === Reminder::class) {
            $personal = ($params['personal'] ?? true) !== 'false';
            $itemtype::showListForCentral($personal);
        } elseif ($itemtype === Project::class) {
            $itemtype::showListForCentral($params['itemtype']);
        } elseif ($itemtype === ProjectTask::class) {
            $itemtype::showListForCentral($params['itemtype']);
        }
        break;
    default:
        echo __s('Invalid widget');
}
