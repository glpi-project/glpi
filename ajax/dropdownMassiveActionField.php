<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (!isset($_POST["itemtype"]) || !($item = getItemForItemtype($_POST['itemtype']))) {
    exit();
}

if (Infocom::canApplyOn($_POST["itemtype"])) {
    Session::checkSeveralRightsOr([$_POST["itemtype"] => UPDATE,
        "infocom"          => UPDATE
    ]);
} else {
    $item->checkGlobal(UPDATE);
}

$inline = false;
if (isset($_POST['inline']) && $_POST['inline']) {
    $inline = true;
}
$submitname = _sx('button', 'Post');
if (isset($_POST['submitname']) && $_POST['submitname']) {
    $submitname = stripslashes($_POST['submitname']);
}


if (
    isset($_POST["itemtype"])
    && isset($_POST["id_field"]) && $_POST["id_field"]
) {
    $search = Search::getOptions($_POST["itemtype"]);
    if (!isset($search[$_POST["id_field"]])) {
        exit();
    }

    $search            = $search[$_POST["id_field"]];

    echo "<table class='tab_glpi w-100'><tr><td>";

    $plugdisplay = false;
   // Specific plugin Type case
    if (
        ($plug = isPluginItemType($_POST["itemtype"]))
        // Specific for plugin which add link to core object
        || ($plug = isPluginItemType(getItemTypeForTable($search['table'])))
    ) {
        $plugdisplay = Plugin::doOneHook(
            $plug['plugin'],
            'MassiveActionsFieldsDisplay',
            ['itemtype' => $_POST["itemtype"],
                'options'  => $search
            ]
        );
    }

    $fieldname = '';

    if (
        empty($search["linkfield"])
        || ($search['table'] == 'glpi_infocoms')
    ) {
        $fieldname = $search["field"];
    } else {
        $fieldname = $search["linkfield"];
    }
    if (!$plugdisplay) {
        $options = [];
        $values  = [];
       // For ticket template or aditional options of massive actions
        if (isset($_POST['options'])) {
            $options = $_POST['options'];
        }
        if (isset($_POST['additionalvalues'])) {
            $values = $_POST['additionalvalues'];
        }
        $values[$search["field"]] = '';
        echo $item->getValueToSelect($search, $fieldname, $values, $options);
    }

    echo "<input type='hidden' name='field' value='$fieldname'>";
    echo "</td>";
    if ($inline) {
        echo "<td><input type='submit' name='massiveaction' class='btn btn-primary' value='$submitname'></td>";
    }
    echo "</tr></table>";

    if (!$inline) {
        echo "<br><input type='submit' name='massiveaction' class='btn btn-primary' value='$submitname'>";
    }
}
