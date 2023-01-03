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

if (strpos($_SERVER['PHP_SELF'], "private_public.php")) {
    include('../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
} else if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

if (isset($_POST['is_private'])) {
    Session::checkLoginUser();

    switch ($_POST['is_private']) {
        case true:
            echo "<input type='hidden' name='is_private' value='1'>\n";
            echo "<input type='hidden' name='entities_id' value='0'>\n";
            echo "<input type='hidden' name='is_recursive' value='0'>\n";
            $private =  __('Personal');
            $link    = "<a href='#' onClick='setPublic" . $_POST['rand'] . "();return false;'>" . __('Set public') . "</a>";
            printf(__('%1$s - %2$s'), $private, $link);
            break;

        case false:
            if (
                isset($_POST['entities_id'])
                && in_array($_POST['entities_id'], $_SESSION['glpiactiveentities'])
            ) {
                $val = $_POST['entities_id'];
            } else {
                $val = $_SESSION['glpiactive_entity'];
            }
            echo "<table class='w-100'>";
            echo "<tr><td>";
            echo "<input type='hidden' name='is_private' value='0'>\n";
            echo __('Public');
            echo "</td><td>";
            Entity::dropdown(['value' => $val]);
            echo "</td><td>" . __('Child entities') . "</td><td>";
            Dropdown::showYesNo('is_recursive', $_POST["is_recursive"]);
            echo "</td><td>";
            echo "<a href='#' onClick='setPrivate" . $_POST['rand'] . "();return false'>" . __('Set personal') . "</a>";
            echo "</td></tr></table>";
            break;
    }
}
