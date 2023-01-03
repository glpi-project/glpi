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

use Glpi\Http\Response;

include('../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

// Read parameters
$context  = $_POST['context'] ?? '';
$itemtype = $_POST["itemtype"] ?? '';

/** @global array $CFG_GLPI */

// Check for required params
if (empty($itemtype)) {
    Response::sendError(400, "Bad request: itemtype cannot be empty", Response::CONTENT_TYPE_TEXT_HTML);
    die;
}

// Check if itemtype is valid in the given context
if ($context == "impact") {
    $isValidItemtype = Impact::isEnabled($itemtype);
} else {
    $isValidItemtype = CommonITILObject::isPossibleToAssignType($itemtype);
}

// Make a select box
if ($isValidItemtype) {
    $table = getTableForItemType($itemtype);

    $rand = $_POST["rand"] ?? mt_rand();

    // Message for post-only
    if (!isset($_POST["admin"]) || ($_POST["admin"] == 0)) {
        echo "<span class='text-muted'>" .
         __('Enter the first letters (user, item name, serial or asset number)')
         . "</span>";
    }
    $field_id = Html::cleanId("dropdown_" . $_POST['myname'] . $rand);
    $p = [
        'itemtype'            => $itemtype,
        'entity_restrict'     => $_POST['entity_restrict'],
        'table'               => $table,
        'multiple'            => (int) ($_POST["multiple"] ?? 0) !== 0,
        'myname'              => $_POST["myname"],
        'rand'                => $_POST["rand"],
        'width'               => 'calc(100% - 25px)',
        '_idor_token'         => Session::getNewIDORToken($itemtype, [
            'entity_restrict' => $_POST['entity_restrict'],
        ]),
    ];

    if (isset($_POST["used"]) && !empty($_POST["used"])) {
        if (isset($_POST["used"][$itemtype])) {
            $p["used"] = $_POST["used"][$itemtype];
        }
    }

   // Add context if defined
    if (!empty($context)) {
        $p["context"] = $context;
    }

    echo Html::jsAjaxDropdown(
        $_POST['myname'],
        $field_id,
        $CFG_GLPI['root_doc'] . "/ajax/getDropdownFindNum.php",
        $p
    );

   // Auto update summary of active or just solved tickets
    echo "<span id='item_ticket_selection_information{$_POST["myname"]}_$rand' class='ms-1'></span>";
    Ajax::updateItemOnSelectEvent(
        $field_id,
        "item_ticket_selection_information{$_POST["myname"]}_$rand",
        $CFG_GLPI["root_doc"] . "/ajax/ticketiteminformation.php",
        [
            'items_id' => '__VALUE__',
            'itemtype' => $_POST['itemtype']
        ]
    );
}
