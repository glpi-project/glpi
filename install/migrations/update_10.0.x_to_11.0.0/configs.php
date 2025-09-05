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
 * @var Migration $migration
 */
$migration->addConfig([
    'password_init_token_delay'     => '86400',
    'toast_location'                => 'bottom-right',
    'set_followup_tech'             => '0',
    'set_solution_tech'             => '0',
    'is_demo_dashboards'            => '0',
    'planned_task_state'            => '1',
    'plugins_execution_mode'        => 'on', // Plugin::EXECUTION_MODE_ON
    'allow_unauthenticated_uploads' => '0',
]);
$migration->addField('glpi_users', 'toast_location', 'string');

$migration->addField('glpi_users', 'set_followup_tech', 'tinyint DEFAULT NULL');
$migration->addField('glpi_users', 'set_solution_tech', 'tinyint DEFAULT NULL');
$migration->addField(
    'glpi_users',
    'planned_task_state',
    'int DEFAULT NULL'
);

$migration->removeConfig(['url_base_api']);

// Add config entries that were missing from the default installation data since GLPI 9.5.
$migration->addConfig([
    'glpinetwork_registration_key' => null,
    'impact_assets_list' => '[]',
    'timezone' => null,
]);

$migration->addConfig([
    'enable_hlapi' => '0',
]);
