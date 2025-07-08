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

Html::header_nocache();

$setupdisplay = new DisplayPreference();

if (isset($_POST['users_id']) && (int) $_POST['users_id'] !== (int) Session::getLoginUserID()) {
    Session::checkRight('search_config', DisplayPreference::GENERAL);
}

if (isset($_POST["activate"])) {
    $setupdisplay->activatePerso($_POST);
} elseif (isset($_POST["disable"])) {
    if ($_POST['users_id'] == Session::getLoginUserID()) {
        $setupdisplay->deleteByCriteria(['users_id' => $_POST['users_id'],
            'itemtype' => $_POST['itemtype'],
        ]);
    }
} elseif (isset($_POST['action']) && $_POST['action'] === 'update_order') {
    if (!isset($_POST['itemtype'], $_POST['users_id'], $_POST['opts'])) {
        throw new BadRequestHttpException();
    }
    $setupdisplay->updateOrder(
        $_POST['itemtype'],
        $_POST['users_id'],
        $_POST['opts'],
        $_POST['interface'] ?? 'central'
    );
} else {
    throw new BadRequestHttpException();
}
