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

/**
 * @var \DBmysql $DB
 * @var \Migration $migration
 */

// The size field seems to be unsused and rather a copy-paste from the glpi_databases table
$migration->dropField('glpi_databaseinstances', 'size');

// Block update if any database instance is currently unlinked
$unlinked_it = $DB->request([
    'SELECT' => ['id'],
    'FROM'   => 'glpi_databaseinstances',
    'WHERE' => [
        'OR' => [
            'itemtype' => '',
            'items_id' => 0
        ]
    ]
]);

if (count($unlinked_it)) {
    $unlinked_id_string = implode(', ', array_column(iterator_to_array($unlinked_it), 'id'));
    throw new \RuntimeException("Some database instances are not linked to an item: {$unlinked_id_string}");
}
