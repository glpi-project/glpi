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

/**
 * The search options for the different levels of toner and drum (1 per color)
 * have been replaced by respective unique fields.
 */
$displayPreference = new \DisplayPreference();
$appliedPreferences = [];
foreach (
    $displayPreference->find([
        'itemtype' => 'Printer',
        ['num' => ['>=', 1400]],
        ['num' => ['<=', 1415]],
    ], ['num', 'users_id']) as $dpref
) {
    $num = $dpref['num'] < 1408 ? 1400 : 1401;

    \DisplayPreference::getById($dpref['id'])->deleteFromDB();
    if (!isset($appliedPreferences[$dpref['users_id']][$num])) {
        $displayPreference->add([
            'users_id' => $dpref['users_id'],
            'itemtype' => 'Printer',
            'num' => $num
        ]);
        $appliedPreferences[$dpref['users_id']][$num] = true;
    }
}
