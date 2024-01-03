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

if (!defined('GLPI_ROOT')) {
    include('../inc/includes.php');
}


Html::popHeader(__('Setup'), $_SERVER['PHP_SELF'], true);

Session::checkRightsOr('search_config', [DisplayPreference::PERSONAL,
    DisplayPreference::GENERAL
]);

$setupdisplay = new DisplayPreference();



if (isset($_POST["activate"])) {
    $setupdisplay->activatePerso($_POST);
} else if (isset($_POST["disable"])) {
    if ($_POST['users_id'] == Session::getLoginUserID()) {
        $setupdisplay->deleteByCriteria(['users_id' => $_POST['users_id'],
            'itemtype' => $_POST['itemtype']
        ]);
    }
} else if (isset($_POST["add"])) {
    $setupdisplay->add($_POST);
} else if (isset($_POST["purge"]) || isset($_POST["purge_x"])) {
    $setupdisplay->delete($_POST, 1);
} else if (isset($_POST["up"]) || isset($_POST["up_x"])) {
    $setupdisplay->orderItem($_POST, 'up');
} else if (isset($_POST["down"]) || isset($_POST["down_x"])) {
    $setupdisplay->orderItem($_POST, 'down');
}

// Datas may come from GET or POST : use REQUEST
if (isset($_REQUEST["itemtype"])) {
    $setupdisplay->display(['displaytype' => $_REQUEST['itemtype']]);
}

Html::popFooter();
