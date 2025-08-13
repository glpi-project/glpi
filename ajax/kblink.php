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
 * Retrieve the knowledgebase links associated to a category
 * @since   9.2
 */

global $DB;

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (isset($_POST["table"], $_POST["value"])) {
    // Security
    if (!$DB->tableExists($_POST['table'])) {
        return;
    }

    if (isset($_POST['withlink'])) {
        $item = getItemForTable($_POST["table"]);
        if (
            !Session::validateIDOR([
                'itemtype'    => $item::class,
                '_idor_token' => $_POST['_idor_token'] ?? "",
            ])
        ) {
            return;
        }
        $item->getFromDB((int) $_POST["value"]);
        if (method_exists($item, 'getLinks')) {
            echo '&nbsp;' . $item->getLinks();
        }
    }
}
