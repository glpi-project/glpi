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
// CleanSoftwareCron cron task
$migration->addCrontask(
    'CleanSoftwareCron',
    'cleansoftware',
    MONTH_TIMESTAMP,
    param: 1000,
    options: [
        'state' => 0, // CronTask::STATE_DISABLE
        'logs_lifetime' => 300,
    ]
);
// /CleanSoftwareCron cron task

// Add architecture to software versions
if (!$DB->fieldExists('glpi_softwareversions', 'arch', false)) {
    $migration->addField(
        'glpi_softwareversions',
        'arch',
        'string',
        [
            'after' => 'name',
        ]
    );
    $migration->addKey('glpi_softwareversions', 'arch');
}
// /Add architecture to software versions
