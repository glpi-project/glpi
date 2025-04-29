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

/**
 * Following variables have to be defined before inclusion of this file:
 * @var CommonDropdown $dropdown
 */

use Glpi\Event;

if (!($dropdown instanceof CommonDropdown)) {
    Html::displayErrorAndDie('');
}
if (!$dropdown->canView()) {
    // Gestion timeout session
    Session::redirectIfNotLoggedIn();
    Html::displayRightError();
}


if (isset($_POST["id"])) {
    $_GET["id"] = $_POST["id"];
} elseif (!isset($_GET["id"])) {
    $_GET["id"] = -1;
}

if (isset($_POST["add"])) {
    $dropdown->check(-1, CREATE, $_POST);

    if ($newID = $dropdown->add($_POST)) {
        if ($dropdown instanceof CommonDevice) {
            Event::log(
                $newID,
                get_class($dropdown),
                4,
                "inventory",
                sprintf(
                    __('%1$s adds the item %2$s'),
                    $_SESSION["glpiname"],
                    $_POST["designation"]
                )
            );
        } else {
            Event::log(
                $newID,
                get_class($dropdown),
                4,
                "setup",
                sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
            );
        }
        if ($_SESSION['glpibackcreated']) {
            $url = $dropdown->getLinkURL();
            if (isset($_REQUEST['_in_modal'])) {
                $url .= "&_in_modal=1";
            }
            Html::redirect($url);
        }
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $dropdown->check($_POST["id"], PURGE);
    if (
        $dropdown->isUsed()
        && empty($_POST["forcepurge"])
    ) {
        Html::header(
            $dropdown->getTypeName(1),
            $_SERVER['PHP_SELF'],
            "config",
            $dropdown->second_level_menu,
            str_replace('glpi_', '', $dropdown->getTable())
        );
        $dropdown->showDeleteConfirmForm($_SERVER['PHP_SELF']);
        Html::footer();
    } else {
        $dropdown->delete($_POST, 1);

        Event::log(
            $_POST["id"],
            get_class($dropdown),
            4,
            "setup",
            //TRANS: %s is the user login
            sprintf(__('%s purges an item'), $_SESSION["glpiname"])
        );
        $dropdown->redirectToList();
    }
} elseif (isset($_POST["replace"])) {
    $dropdown->check($_POST["id"], PURGE);
    $dropdown->delete($_POST, 1);

    Event::log(
        $_POST["id"],
        get_class($dropdown),
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s replaces an item'), $_SESSION["glpiname"])
    );
    $dropdown->redirectToList();
} elseif (isset($_POST["update"])) {
    $dropdown->check($_POST["id"], UPDATE);
    $dropdown->update($_POST);

    Event::log(
        $_POST["id"],
        get_class($dropdown),
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} elseif (
    isset($_POST['execute'])
           && isset($_POST['_method'])
) {
    $method = 'execute' . $_POST['_method'];
    if (method_exists($dropdown, $method)) {
        call_user_func([&$dropdown, $method], $_POST);
        Html::back();
    } else {
        Html::displayErrorAndDie(__('No selected element or badly defined operation'));
    }
} elseif (isset($_GET['_in_modal'])) {
    Html::popHeader(
        $dropdown->getTypeName(1),
        $_SERVER['PHP_SELF'],
        true,
        $dropdown->first_level_menu,
        $dropdown->second_level_menu,
        $dropdown->getType()
    );
    $dropdown->showForm($_GET["id"]);
    Html::popFooter();
} else {
    if (!isset($options)) {
        $options = [];
    }
    $options['formoptions'] = ($options['formoptions'] ?? '') . ' data-track-changes=true';
    $options['id'] = $_GET['id'];

    $dropdown::displayFullPageForItem($_GET['id'], null, $options);
}
