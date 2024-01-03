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

use Glpi\Event;

include('../inc/includes.php');

Session::checkCentralAccess();

$certif_item = new Certificate_Item();

if (isset($_POST["add"])) {
    $certif_item->check(-1, CREATE, $_POST);
    if ($certif_item->add($_POST)) {
        Event::log(
            $_POST["certificates_id"],
            "certificates",
            4,
            "certificate",
            //TRANS: %s is the user login
            sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else if (isset($_POST["delete"])) {
    foreach ($_POST["item"] as $key => $val) {
        $input = ['id' => $key];
        if ($val == 1) {
            $certif_item->check($key, UPDATE);
            $certif_item->delete($input);
        }
    }
    Html::back();
}

Html::displayErrorAndDie("lost");
