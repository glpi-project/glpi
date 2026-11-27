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
 * @var DBmysql $DB
 */

// can_support_pagesize has not been needed since GLPI stopped supporting PHP versions older than 5.4
$migration->dropField('glpi_authldaps', 'can_support_pagesize');

// pagesize is always available now (at least with an automatic fallback), so we can set it to 1000 for all existing LDAP authentication servers where it is set to 0 or null
$iterator = $DB->request([
    'SELECT' => ['id'],
    'FROM'   => 'glpi_authldaps',
    'WHERE'  => [
        'pagesize' => ['IN', [0, null]],
    ],
]);
foreach ($iterator as $data) {
    $DB->update('glpi_authldaps', ['pagesize' => 1000], ['id' => $data['id']]);
}
