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

include('../inc/includes.php');
Html::header_nocache();

Session::checkLoginUser();

$setupdisplay = new DisplayPreference();

if (isset($_POST['users_id']) && (int) $_POST['users_id'] !== (int) Session::getLoginUserID()) {
    Session::checkRight('search_config', DisplayPreference::GENERAL);
}

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
} else if ((isset($_POST["purge"]) || isset($_POST["purge_x"])) && isset($_POST['num'])) {
    $setupdisplay->deleteByCriteria([
        'itemtype' => $_POST['itemtype'],
        'users_id' => $_POST['users_id'],
        'num'      => $_POST['num']
    ], true);
} else if ((isset($_POST["up"]) || isset($_POST["up_x"])) && isset($_POST['num'])) {
    $setupdisplay->orderItem($_POST, 'up');
} else if ((isset($_POST["down"]) || isset($_POST["down_x"])) && isset($_POST['num'])) {
    $setupdisplay->orderItem($_POST, 'down');
} else {
    die(400);
}
