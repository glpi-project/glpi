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

Html::header_nocache();

Session::checkLoginUser();

if (
    isset($_REQUEST["urgency"])
    && isset($_REQUEST["impact"])
) {
    // Read predefined templates fields
    $predefined_fields  = array_key_exists('_predefined_fields', $_REQUEST) ? Toolbox::decodeArrayFromInput($_REQUEST["_predefined_fields"]) : [];

    // Fallback to Form value -> Template values -> Medium
    $priority = Ticket::computePriority(
        $_REQUEST["urgency"] ?: $predefined_fields['urgency'] ?? 3 /* Medium */,
        $_REQUEST["impact"]  ?: $predefined_fields['impact']  ?? 3 /* Medium */
    );

    if (isset($_REQUEST['getJson'])) {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(['priority' => $priority]);
    } elseif ($_REQUEST["priority"]) {
        // Send UTF8 Headers
        header("Content-Type: text/html; charset=UTF-8");
        echo "<script type='text/javascript' >\n";
        echo Html::jsSetDropdownValue($_REQUEST["priority"], $priority);
        echo "\n</script>";
    } else {
        echo Ticket::getPriorityName($priority);
    }
}
