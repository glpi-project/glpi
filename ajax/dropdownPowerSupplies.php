<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

header('Content-Type: text/html; charset=UTF-8');
Html::header_nocache();

Session::checkCentralAccess();

$itemtype = $_POST['itemtype'] ?? '';
$items_id = (int) ($_POST['items_id'] ?? 0);
$rand = (int) ($_POST['rand'] ?? mt_rand());
$choices = [];

if (is_string($itemtype) && is_a($itemtype, CommonDBTM::class, true)) {
    $asset = new $itemtype();
    if ($items_id > 0 && $asset->getFromDB($items_id) && $asset->can($items_id, READ)) {
        $choices = Plug::getPowerSupplyChoices($itemtype, $items_id);
    }
}

Dropdown::showFromArray(Plug::POWER_SUPPLY_FIELD, $choices, [
    'value'               => 0,
    'display_emptychoice' => true,
    'required'            => $choices !== [],
    'rand'                => $rand,
    'width'               => '100%',
]);
