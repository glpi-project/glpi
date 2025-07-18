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
$migration->addCrontask(
    'CommonITILValidationCron',
    'approvalreminder',
    1 * WEEK_TIMESTAMP,
    options: [
        'state' => 0, // CronTask::STATE_DISABLE
    ]
);

if (!$DB->fieldExists("glpi_ticketvalidations", "last_reminder_date", false)) {
    $migration->addField('glpi_ticketvalidations', 'last_reminder_date', "timestamp NULL DEFAULT NULL", ['after' => 'timeline_position' ]);
}

if (!$DB->fieldExists("glpi_changevalidations", "last_reminder_date", false)) {
    $migration->addField('glpi_changevalidations', 'last_reminder_date', "timestamp NULL DEFAULT NULL", ['after' => 'timeline_position' ]);
}

// Add approval_reminder_repeat_interval to entity
if (!$DB->fieldExists("glpi_entities", "approval_reminder_repeat_interval")) {
    $migration->addField(
        "glpi_entities",
        "approval_reminder_repeat_interval",
        "integer",
        [
            'after'     => "agent_base_url",
            'value'     => -2,               // Inherit as default value
            'update'    => '0',              // Disabled for root entity
            'condition' => 'WHERE `id` = 0',
        ]
    );
}
