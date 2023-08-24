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
 * @var DB $DB
 * @var Migration $migration
 */

foreach (['glpi_computervirtualmachines', 'glpi_networkequipments'] as $table) {
    // field has to be nullable to be able to set empty values to null
    $migration->changeField(
        $table,
        'ram',
        'ram',
        'varchar(255) DEFAULT NULL',
    );
    $migration->migrationOneTable($table);

    $iterator = $DB->request([
        'FROM'  => $table,
        'WHERE' => [
            'ram' => ['REGEXP', '[^0-9]+'],
        ],
    ]);
    foreach ($iterator as $row) {
        $DB->updateOrDie(
            $table,
            ['ram' => preg_replace('/[^0-9]+/', '', $row['ram'])],
            ['id'  => $row['id']]
        );
    }
    $DB->updateOrDie(
        $table,
        ['ram' => null],
        [
            'OR' => [
                'ram' => '',
                // We expect the `ram` value to be expressed in MiB, so if the value exceeds the maximum value of the field,
                // it is probably invalid, since it corresponds to more than 4096 TiB of RAM.
                // Setting value to null will prevent a `Out of range value for column 'ram'` SQL error.
                new QueryExpression(sprintf('CAST(%s AS UNSIGNED) >= POW(2, 32)', 'ram')),
            ],
        ]
    );
    $migration->changeField(
        $table,
        'ram',
        'ram',
        'int unsigned DEFAULT NULL',
    );
}
