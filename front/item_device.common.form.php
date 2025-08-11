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

use Glpi\Event;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;

/**
 * @since 0.85
 */

global $CFG_GLPI;

/**
 * Following variables have to be defined before inclusion of this file:
 * @var Item_Devices $item_device
 */

/** @var Item_Devices|null $item_device */
if (!($item_device instanceof Item_Devices)) {
    throw new BadRequestHttpException();
}
if (!$item_device->canView()) {
    throw new AccessDeniedHttpException();
}


if (isset($_POST["id"])) {
    $_GET["id"] = $_POST["id"];
} elseif (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

if (isset($_POST["add"])) {
    $item_device->check(-1, CREATE, $_POST);
    if ($newID = $item_device->add($_POST)) {
        Event::log(
            $newID,
            get_class($item_device),
            4,
            "setup",
            sprintf(__('%1$s adds an item'), $_SESSION["glpiname"])
        );

        if ($_SESSION['glpibackcreated']) {
            Html::redirect($item_device->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $item_device->check($_POST["id"], PURGE);
    $item_device->delete($_POST, true);

    Event::log(
        $_POST["id"],
        get_class($item_device),
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );

    $device = $item_device->getOnePeer(1);
    Html::redirect($device->getLinkURL());
} elseif (isset($_POST["update"])) {
    $item_device->check($_POST["id"], UPDATE);
    $item_device->update($_POST);

    Event::log(
        $_POST["id"],
        get_class($item_device),
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    if (in_array($item_device->getType(), $CFG_GLPI['devices_in_menu'])) {
        $menus = ["assets", strtolower($item_device->getType())];
    } else {
        $menus = ["config", "commondevice", $item_device->getType()];
    }

    $item_device::displayFullPageForItem($_GET["id"], $menus, $options ?? []);
}
