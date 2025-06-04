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

Session::checkCentralAccess();

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["itemtype"])) {
    $_GET["itemtype"] = "";
}
if (!isset($_GET["items_id"])) {
    $_GET["items_id"] = "";
}

$antivirus = new ItemAntivirus();
if (isset($_POST["add"])) {
    $antivirus->check(-1, CREATE, $_POST);

    if ($antivirus->add($_POST)) {
        Event::log(
            $_POST['items_id'],
            $_POST['itemtype'],
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s adds an antivirus'), $_SESSION["glpiname"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($antivirus->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $antivirus->check($_POST["id"], PURGE);

    if ($antivirus->delete($_POST, 1)) {
        Event::log(
            $antivirus->fields['items_id'],
            $antivirus->fields['itemtype'],
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s purges an antivirus'), $_SESSION["glpiname"])
        );
    }
    $itemtype = $antivirus->fields['itemtype'];
    $item = getItemForItemtype($itemtype);
    $item->getFromDB($antivirus->fields['items_id']);
    Html::redirect(Toolbox::getItemTypeFormURL($antivirus->fields['itemtype']) . '?id=' . $antivirus->fields['items_id'] .
                  ($item->fields['is_template'] ? "&withtemplate=1" : ""));
} elseif (isset($_POST["update"])) {
    $antivirus->check($_POST["id"], UPDATE);

    if ($antivirus->update($_POST)) {
        Event::log(
            $antivirus->fields['items_id'],
            $antivirus->fields['itemtype'],
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(__('%s updates an antivirus'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else {
    if ($antivirus->getFromDB($_GET['id'])) {
        $menus = ['assets', $antivirus->fields['itemtype']];
    } else {
        $menus = ['assets', $_GET['itemtype']];
    }

    ItemAntivirus::displayFullPageForItem($_GET["id"], $menus, [
        'itemtype' => $_GET["itemtype"],
        'items_id' => $_GET["items_id"],
    ]);
}
