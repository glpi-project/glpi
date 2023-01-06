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

/**
 * @since 0.85
 */

use Glpi\Event;

if (!defined('GLPI_ROOT')) {
    include('../inc/includes.php');
}

$link = new Change_Supplier();

Session::checkLoginUser();
Html::popHeader(__('Email followup'), $_SERVER['PHP_SELF']);

if (isset($_POST["update"])) {
    $link->check($_POST["id"], UPDATE);

    $link->update($_POST);
    echo "<script type='text/javascript' >\n";
    echo "window.parent.location.reload();";
    echo "</script>";
} else if (isset($_POST['delete'])) {
    $link->check($_POST['id'], DELETE);
    $link->delete($_POST);

    Event::log(
        $link->fields['changes_id'],
        "change",
        4,
        "maintain",
        sprintf(__('%s deletes an actor'), $_SESSION["glpiname"])
    );
    Html::redirect(Change::getFormURLWithID($link->fields['changes_id']));
} else if (isset($_GET["id"])) {
    $link->showSupplierNotificationForm($_GET["id"]);
} else {
    Html::displayErrorAndDie('Lost');
}

Html::popFooter();
