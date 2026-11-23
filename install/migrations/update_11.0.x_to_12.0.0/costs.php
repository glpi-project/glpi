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

/**
 * @var Migration $migration
 */

$cost_tables = ['glpi_changecosts', 'glpi_contractcosts', 'glpi_problemcosts', 'glpi_projectcosts', 'glpi_ticketcosts'];

foreach ($cost_tables as $table) {
    $migration->addField($table, 'users_id', 'fkey');
    $migration->addKey($table, 'users_id');
    $migration->addField($table, 'users_id_lastupdater', 'fkey');
    $migration->addKey($table, 'users_id_lastupdater');
    $migration->addField($table, 'date_creation', 'datetime');
    $migration->addKey($table, 'date_creation');
    $migration->addField($table, 'date_mod', 'datetime');
    $migration->addKey($table, 'date_mod');
}
