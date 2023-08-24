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

include('../inc/includes.php');

if (!basename($_SERVER['SCRIPT_NAME']) == "helpdesk.faq.php") {
    Session::checkLoginUser();
}

/** @global array $_UGET */

// Manage tabs
if (
    isset($_GET['itemtype'])
    && (
        isset($_GET['tab'])
        || isset($_GET['tab_key'])
    )
) {
    if (isset($_GET['tab_key'])) {
        // Prefered way, load tab key directly, avoid unneeded call to Toolbox::getAvailablesTabs
        Session::setActiveTab($_UGET['itemtype'], $_UGET['tab_key']);
    } else {
        // Deprecated, use tab_key if possible
        Toolbox::deprecated("'tab' parameter is deprecated, use 'tab_key' instead");

        $tabs = Toolbox::getAvailablesTabs($_UGET['itemtype'], $_GET['id'] ?? null);
        $current      = 0;
        foreach (array_keys($tabs) as $key) {
            if ($current == $_GET['tab']) {
                Session::setActiveTab($_UGET['itemtype'], $key);
                break;
            }
            $current++;
        }
    }
}
