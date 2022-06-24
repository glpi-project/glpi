<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

$inventory_config = getAllDataFromTable('glpi_configs', ['context' => 'inventory']);

if (!isset($inventory_config['agents_action'])) {
    $migrated = false;

    if ($DB->tableExists('glpi_plugin_glpiinventory_configs')) {
        $iterator = $DB->request([
            'SELECT' => ['type', 'value'],
            'FROM' => 'glpi_plugin_glpiinventory_configs',
            'WHERE' => ['type' => 'agents_action']
        ]);
        if ($iterator->count() > 0) {
            $migrated = true;
            $migration->addConfig(
                ['agents_action' => $iterator->current()['value']],
                'inventory'
            );
        }
    }

    if (!$migrated && $DB->tableExists('glpi_plugin_fusioninventory_configs')) {
        $iterator = $DB->request([
            'SELECT' => ['type', 'value'],
            'FROM' => 'glpi_plugin_fusioninventory_configs',
            'WHERE' => ['type' => 'agents_action']
        ]);
        if ($iterator->count() > 0) {
            $migrated = true;
            $migration->addConfig(
                ['agents_action' => $iterator->current()['value']],
                'inventory'
            );
        }
    }

    if (!$migrated) {
        $migration->addConfig(
            ['agents_action' => 0],
            'inventory'
        );
    }
}

CronTask::register('Agent', 'cronCleanoldagents', DAY_TIMESTAMP, [
    'comment' => 'Clean old agents',
    'state' => CronTask::STATE_WAITING,
    'mode' => CronTask::MODE_EXTERNAL,
    'logs_lifetime' => 30
]);
