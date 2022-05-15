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

include('../inc/includes.php');

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

switch ($_POST['action']) {
    case 'getItemsFromItemtype':
        if ($_POST['itemtype'] && class_exists($_POST['itemtype'])) {
            $_POST['itemtype']::dropdown(['name'                => $_POST['dom_name'],
                'display_emptychoice' => true,
                'rand' => $_POST['dom_rand']
            ]);
        }
        break;

    case 'getNetworkPortFromItem':
        NetworkPort::dropdown(['name'                => 'networkports_id',
            'display_emptychoice' => true,
            'condition'           => ["items_id" => $_POST['items_id'],
                "itemtype" => $_POST['itemtype']
            ]
        ]);
        break;
}
