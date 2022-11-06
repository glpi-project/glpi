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

/**
 * @since 0.84
 */

use Glpi\Toolbox\Sanitizer;

include('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

/** @global array $CFG_GLPI */

try {
    $ma = new MassiveAction($_POST, $_GET, 'initial');
} catch (\Exception $e) {
    echo "<div class='center'><img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='" .
                              __s('Warning') . "'><br><br>";
    echo "<span class='b'>" . $e->getMessage() . "</span><br>";
    echo "</div>";
    exit();
}

echo "<div class='center massiveactions'>";
Html::openMassiveActionsForm();
$params = ['action' => '__VALUE__'];
$input  = $ma->getInput();
foreach ($input as $key => $val) {
    // Value will be sanitized again when massive action form will be submitted.
    // It have to be unsanitized here to prevent double sanitization.
    $params[$key] = Sanitizer::unsanitize($val);
}

$actions = $params['actions'];

if (count($actions)) {
    if (isset($params['hidden']) && is_array($params['hidden'])) {
        foreach ($params['hidden'] as $key => $val) {
            echo Html::hidden($key, ['value' => $val]);
        }
    }
    echo _n('Action', 'Actions', 1);
    echo "&nbsp;";

    $actions = ['-1' => Dropdown::EMPTY_VALUE] + $actions;
    $rand    = Dropdown::showFromArray('massiveaction', $actions);

    echo "<br><br>";

    Ajax::updateItemOnSelectEvent(
        "dropdown_massiveaction$rand",
        "show_massiveaction$rand",
        $CFG_GLPI["root_doc"] . "/ajax/dropdownMassiveAction.php",
        $params
    );

    echo "<span id='show_massiveaction$rand'>&nbsp;</span>\n";
}

Html::closeForm();
echo "</div>";
