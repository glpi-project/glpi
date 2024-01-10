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

Session::checkRight("reports", READ);

Html::header(Report::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "report");

Report::title();

// Titre

echo "<form name='form' method='post' action='report.contract.list.php'>";

echo "<table class='tab_cadre_fixe' >";
echo "<tr><th colspan='4'>" . __('Hardware under contract') . " </th></tr>";

// 3. Selection d'affichage pour generer la liste
echo "<tr class='tab_bg_1'>";
echo "<td class='center' width='20%'>" . __('Item type') . "</td>";
echo "<td width='30%'>";
$values = [0 => __('All')];
foreach ($CFG_GLPI["contract_types"] as $itemtype) {
    if ($item = getItemForItemtype($itemtype)) {
        $values[$itemtype] = $item->getTypeName();
    }
}
Dropdown::showFromArray('item_type', $values, ['value'    => 0,
    'multiple' => true
]);
echo "</td> ";

echo "<td class='center' width='20%'>" . _n('Date', 'Dates', 1) . "</td>";
echo "<td width='30%'>";
$y      = date("Y");
$values = [ 0 => __('All')];
for ($i = ($y - 10); $i < ($y + 10); $i++) {
    $values[$i] = $i;
}
Dropdown::showFromArray('year', $values, ['value'    => $y,
    'multiple' => true
]);

echo "</td></tr>";

echo "<tr><td class='tab_bg_1 center' colspan='4'>";
echo "<input type='submit' value=\"" . __s('Display report') . "\" class='btn btn-primary'></td></tr>";

echo "</table>";
Html::closeForm();

Html::footer();
