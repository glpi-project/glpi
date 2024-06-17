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
 * @var array $ADDTODISPLAYPREF
 * @var \DBmysql $DB
 * @var \Migration $migration
 */

// Delete the "Clean expired sessions" crontask
$session_crontask_req = [
    'FROM' => 'glpi_crontasks',
    'WHERE' => [
        'itemtype'  => 'CronTask',
        'name'      => 'session',
    ]
];
$session_crontask_id = $DB->request($session_crontask_req)->current()['id'] ?? null;
if ($session_crontask_id !== null) {
    $migration->addPostQuery($DB->buildDelete('glpi_crontasklogs', ['crontasks_id' => $session_crontask_id]));
    $migration->addPostQuery($DB->buildDelete('glpi_crontasks', ['id' => $session_crontask_id]));
}

// Add new display preferences
$ADDTODISPLAYPREF['CronTask'] = [5, 6, 17, 18];
