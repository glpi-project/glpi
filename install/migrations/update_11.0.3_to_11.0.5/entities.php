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
 * @var DBmysql $DB
 * @var Migration $migration
 */
// see #18814
$migration->changeField('glpi_entities', 'inquest_URL', 'inquest_URL', 'text');
$migration->changeField('glpi_entities', 'inquest_URL_change', 'inquest_URL_change', 'text');

// Fix glpi_entities.id column to use AUTO_INCREMENT instead of DEFAULT 0
// This is required for concurrent entity creation to work properly
// see #22625
$DB->doQuery(
    "ALTER TABLE `glpi_entities`
     MODIFY `id` INT unsigned NOT NULL AUTO_INCREMENT"
);

// Reset AUTO_INCREMENT to continue from the highest existing ID
$max_id = $DB->request([
    'SELECT' => ['MAX' => 'id AS max_id'],
    'FROM'   => 'glpi_entities',
])->current()['max_id'] ?? 0;
$DB->doQuery("ALTER TABLE `glpi_entities` AUTO_INCREMENT = " . ($max_id + 1));
