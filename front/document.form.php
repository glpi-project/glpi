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

use Glpi\Event;

include('../inc/includes.php');

Session::checkLoginUser();

if (!isset($_GET["id"])) {
    $_GET["id"] = -1;
}

$doc          = new Document();

if (isset($_POST["add"])) {
    $doc->check(-1, CREATE, $_POST);
    if (isset($_POST['_filename']) && is_array($_POST['_filename'])) {
        $fic = $_POST['_filename'];
        $tag = $_POST['_tag_filename'];
        $prefix = $_POST['_prefix_filename'];
        foreach (array_keys($fic) as $key) {
            $_POST['_filename']        = [$fic[$key]];
            $_POST['_tag_filename']    = [$tag[$key]];
            $_POST['_prefix_filename'] = [$prefix[$key]];
            if ($newID = $doc->add($_POST)) {
                Event::log(
                    $newID,
                    "documents",
                    4,
                    "login",
                    sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $doc->fields["name"])
                );
            }
        }
        if ($_SESSION['glpibackcreated'] && (!isset($_POST['itemtype']) || !isset($_POST['items_id']))) {
            Html::redirect($doc->getLinkURL());
        }
    } else if ($newID = $doc->add($_POST)) {
        Event::log(
            $newID,
            "documents",
            4,
            "login",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $doc->fields["name"])
        );
       // Not from item tab
        if ($_SESSION['glpibackcreated'] && (!isset($_POST['itemtype']) || !isset($_POST['items_id']))) {
            Html::redirect($doc->getLinkURL());
        }
    }

    Html::back();
} else if (isset($_POST["delete"])) {
    $doc->check($_POST["id"], DELETE);

    if ($doc->delete($_POST)) {
        Event::log(
            $_POST["id"],
            "documents",
            4,
            "document",
            //TRANS: %s is the user login
            sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
        );
    }
    $doc->redirectToList();
} else if (isset($_POST["restore"])) {
    $doc->check($_POST["id"], DELETE);

    if ($doc->restore($_POST)) {
        Event::log(
            $_POST["id"],
            "documents",
            4,
            "document",
            //TRANS: %s is the user login
            sprintf(__('%s restores an item'), $_SESSION["glpiname"])
        );
    }
    $doc->redirectToList();
} else if (isset($_POST["purge"])) {
    $doc->check($_POST["id"], PURGE);

    if ($doc->delete($_POST, 1)) {
        Event::log(
            $_POST["id"],
            "documents",
            4,
            "document",
            //TRANS: %s is the user login
            sprintf(__('%s purges an item'), $_SESSION["glpiname"])
        );
    }
    $doc->redirectToList();
} else if (isset($_POST["update"])) {
    $doc->check($_POST["id"], UPDATE);

    if ($doc->update($_POST)) {
        Event::log(
            $_POST["id"],
            "documents",
            4,
            "document",
            //TRANS: %s is the user login
            sprintf(__('%s updates an item'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else {
    $menus = ["management", "document"];
    Document::displayFullPageForItem($_GET["id"], $menus, [
        'formoptions'  => "data-track-changes=true"
    ]);
}
