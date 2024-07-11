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

/** @var array $CFG_GLPI */
global $CFG_GLPI;

include('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

if (isset($_POST['entity_restrict'])) {
    $_POST['entity_restrict'] = Session::getMatchingActiveEntities($_POST['entity_restrict']);
}

// Make a select box
if ($_POST["idtable"] && class_exists($_POST["idtable"])) {
   // Link to user for search only > normal users
    $link = "getDropdownValue.php";

    if ($_POST["idtable"] == 'User') {
        $link = "getDropdownUsers.php";
    }

    $rand = $_POST['rand'] ?? mt_rand();

    $field_id = Html::cleanId("dropdown_" . $_POST["name"] . $rand);

    $displaywith = ['otherserial', 'serial'];
    $p = [
        'value'               => 0,
        'valuename'           => Dropdown::EMPTY_VALUE,
        'itemtype'            => $_POST["idtable"],
        'display_emptychoice' => true,
        'displaywith'         => $displaywith,
    ];
    $idor_params = [
        'displaywith' => $displaywith,
    ];
    if (isset($_POST['value'])) {
        $p['value'] = $_POST['value'];
    }
    if (isset($_POST['entity_restrict'])) {
        $p['entity_restrict']           = $_POST['entity_restrict'];
        $idor_params['entity_restrict'] = $_POST['entity_restrict'];
    }
    if (isset($_POST['condition'])) {
        $p['condition']           = $_POST['condition'];
        $idor_params['condition'] = $_POST['condition'];
    }
    if (isset($_POST['used'])) {
        $_POST['used'] = Toolbox::jsonDecode($_POST['used'], true);
    }
    if (isset($_POST['used'][$_POST['idtable']])) {
        $p['used'] = $_POST['used'][$_POST['idtable']];
    }
    if (isset($_POST['width'])) {
        $p['width'] = $_POST['width'];
    }
    $p['_idor_token'] = Session::getNewIDORToken($_POST["idtable"], $idor_params);

    echo  Html::jsAjaxDropdown(
        $_POST["name"],
        $field_id,
        $CFG_GLPI['root_doc'] . "/ajax/" . $link,
        $p
    );

    if (!empty($_POST['showItemSpecificity'])) {
        $params = ['items_id' => '__VALUE__',
            'itemtype' => $_POST["idtable"]
        ];
        if (isset($_POST['entity_restrict'])) {
            $params['entity_restrict'] = $_POST['entity_restrict'];
        }

        Ajax::updateItemOnSelectEvent(
            $field_id,
            "showItemSpecificity_" . $_POST["name"] . "$rand",
            $_POST['showItemSpecificity'],
            $params
        );

        echo "<br><span id='showItemSpecificity_" . $_POST["name"] . "$rand'>&nbsp;</span>\n";
    }
}
