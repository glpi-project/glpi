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

/**
 * @since 0.83
 */

/** @var array $CFG_GLPI */
global $CFG_GLPI;

// Direct access to file
if (strpos($_SERVER['PHP_SELF'], "planningcheck.php")) {
    $AJAX_INCLUDE = 1;
    include('../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();

$append_params = [
    "checkavailability" => "checkavailability",
];

if (isset($_POST['users_id']) && ($_POST['users_id'] > 0)) {
    $append_params["itemtype"] = User::class;
    $append_params[User::getForeignKeyField()] = $_POST['users_id'];
} elseif (
    isset($_POST['parent_itemtype']) && class_exists($_POST['parent_itemtype'])
    && isset($_POST['parent_items_id']) && ($_POST['parent_items_id'] > 0)
    && isset($_POST['parent_fk_field']) && ($_POST['parent_fk_field'] != '')
) {
    $append_params["itemtype"] = $_POST['parent_itemtype'];
    $append_params[$_POST['parent_fk_field']] = $_POST['parent_items_id'];
}

if (count($append_params) > 1) {
    $rand = mt_rand();
    echo "<a href='#' title=\"" . __s('Availability') . "\" data-bs-toggle='modal' data-bs-target='#planningcheck$rand'>";
    echo "<i class='far fa-calendar-alt'></i>";
    echo "<span class='sr-only'>" . __('Availability') . "</span>";
    echo "</a>";
    Ajax::createIframeModalWindow(
        'planningcheck' . $rand,
        $CFG_GLPI["root_doc"] . "/front/planning.php?" . Toolbox::append_params($append_params),
        ['title'  => __('Availability')]
    );
}
