<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
* @var DBmysql $DB
* @var Migration $migration
*/

/**
 * The search options for the different levels of toner and drum (1 per color)
 * have been replaced by respective unique fields.
 */
$displayPreference = new DisplayPreference();
$appliedPreferences = [];
foreach (
    $displayPreference->find([
        'itemtype' => 'Printer',
        ['num' => ['>=', 1400]],
        ['num' => ['<=', 1415]],
    ], ['num', 'users_id']) as $dpref
) {
    $num = $dpref['num'] < 1408 ? 1400 : 1401;

    $migration->addPostQuery($DB->buildDelete('glpi_displaypreferences', [
        'id' => $dpref['id'],
    ]));
    if (!isset($appliedPreferences[$dpref['users_id']][$num])) {
        $migration->addPostQuery($DB->buildInsert(
            'glpi_displaypreferences',
            [
                'users_id' => $dpref['users_id'],
                'itemtype' => 'Printer',
                'num' => $num,
                'rank' => $dpref['rank'],
            ]
        ));
        $appliedPreferences[$dpref['users_id']][$num] = true;
    }
}

// Add new 'interface' column to glpi_displaypreferences
$table = "glpi_displaypreferences";
if (!$DB->fieldExists($table, 'interface')) {
    $migration->addField($table, 'interface', 'string', [
        'value' => 'central',
        'null'  => false,
    ]);

    // The only way to update a key is to drop it then recreate it
    // We have to execute the migration after the key is dropped or the addKey
    // method will not create the ADD query because it will think the key already exists.
    $migration->dropKey($table, 'unicity');
    $migration->migrationOneTable($table);
    $migration->addKey($table, [
        'users_id',
        'itemtype',
        'num',
        'interface',
    ], 'unicity', 'UNIQUE');

    // Force the migration of this table to be executed immediately because new preferences
    // using the new column are added in the same update using $ADDTODISPLAYPREF_HELPDESK
    $migration->migrationOneTable($table);
}

$ADDTODISPLAYPREF_HELPDESK[Ticket::class] = [
    12, // Status
    19, // Last update
    15, // Opening date
];
