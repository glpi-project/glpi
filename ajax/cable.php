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
use Glpi\Socket;

global $CFG_GLPI;

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkCentralAccess();

$action = $_POST['action'] ?? $_GET["action"];

$itemtype = $_POST['itemtype'] ?? $_GET["itemtype"] ?? null;
$item = getItemForItemtype($itemtype);
if (
    !$item->canView()
    || (isset($_GET['items_id']) && !$item->can($_GET['items_id'], READ))
) {
    throw new AccessDeniedHttpException();
}

switch ($action) {
    case 'get_items_from_itemtype':
        if ($_POST['itemtype'] && class_exists($_POST['itemtype'])) {
            $_POST['itemtype']::dropdown(['name'                => $_POST['dom_name'],
                'rand'                => $_POST['dom_rand'],
                'display_emptychoice' => true,
                'display_dc_position' => in_array($_POST['itemtype'], $CFG_GLPI['rackable_types']),
                'width'               => '100%',
            ]);
        }
        break;

    case 'get_socket_dropdown':
        if (isset($_GET['itemtype'], $_GET['items_id']) && class_exists($_GET['itemtype'])) {
            Socket::dropdown(['name'         =>  $_GET['dom_name'],
                'condition'    => ['socketmodels_id'   => $_GET['socketmodels_id'] ?? 0,
                    'itemtype'           => $_GET['itemtype'],
                    'items_id'           => $_GET['items_id'],
                ],
                'used'         => (int) $_GET['items_id'] > 0 ? Socket::getSocketAlreadyLinked($_GET['itemtype'], (int) $_GET['items_id']) : [],
                'displaywith'  => ['itemtype', 'items_id', 'networkports_id'],
            ]);
        }
        break;

    case 'get_networkport_dropdown':
        NetworkPort::dropdown(['name'                => 'networkports_id',
            'display_emptychoice' => true,
            'condition'           => ['items_id' => $_GET['items_id'],
                'itemtype' => $_GET['itemtype'],
            ],
            'comments' => false,
        ]);
        break;


    case 'get_item_breadcrum':
        if (isset($_GET['itemtype'], $_GET['items_id']) && class_exists($_GET['itemtype']) && $_GET['items_id'] > 0) {
            if (method_exists($_GET['itemtype'], 'renderDcBreadcrumb')) {
                echo $_GET['itemtype']::renderDcBreadcrumb($_GET['items_id']);
            }
        } else {
            echo "";
        }
        break;
}
