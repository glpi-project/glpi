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


$item = new SlaLevel();

if (isset($_POST["update"])) {
    $item->check($_POST["id"], UPDATE);

    $item->update($_POST);

    Event::log(
        $_POST["id"],
        "slas",
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s updates a sla level'), $_SESSION["glpiname"])
    );

    Html::back();
} else if (isset($_POST["add"])) {
    $item->check(-1, CREATE, $_POST);

    if ($item->add($_POST)) {
        Event::log(
            $_POST["slas_id"],
            "slas",
            4,
            "setup",
            //TRANS: %s is the user login
            sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($item->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["purge"])) {
    if (isset($_POST['id'])) {
        $item->check($_POST['id'], PURGE);
        if ($item->delete($_POST, 1)) {
            Event::log(
                $_POST["id"],
                "slas",
                4,
                "setup",
                //TRANS: %s is the user login
                sprintf(__('%s purges a sla level'), $_SESSION["glpiname"])
            );
        }
        $item->redirectToList();
    }

    Html::back();
} else if (isset($_POST["add_action"])) {
    $item->check($_POST['slalevels_id'], UPDATE);

    $action = new SlaLevelAction();
    $action->add($_POST);

    Html::back();
} else if (isset($_POST["add_criteria"])) {
    $item->check($_POST['slalevels_id'], UPDATE);
    $criteria = new SlaLevelCriteria();
    $criteria->add($_POST);

    Html::back();
} else if (isset($_GET["id"]) && ($_GET["id"] > 0)) {
    $menus = ["config", "slm", "slalevel"];
    SlaLevel::displayFullPageForItem($_GET["id"], $menus);
}
