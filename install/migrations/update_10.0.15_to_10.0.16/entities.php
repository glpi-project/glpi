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
// Fix default value for `autopurge_delay`
$migration->changeField('glpi_entities', 'autopurge_delay', 'autopurge_delay', "int NOT NULL DEFAULT '-2'");

// Fix root entity config value if its value is inherited (-2)
$root_defaults = [
    'use_domains_alert' => 0,
    'send_domains_alert_close_expiries_delay' => 30,
    'send_domains_alert_expired_delay' => 1,
];
foreach ($root_defaults as $key => $default) {
    $current_value = $DB->request(['SELECT' => $key, 'FROM' => 'glpi_entities', 'WHERE' => ['id' => 0]])->current()[$key];
    if ($current_value === -2) {
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_entities',
                [
                    $key => $default,
                ],
                [
                    'id' => 0,
                ]
            )
        );
    }
}
