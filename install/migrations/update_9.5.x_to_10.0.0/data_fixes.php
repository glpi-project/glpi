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

/** Drop unused config entry 'use_timezones' */
$migration->addPostQuery(
    $DB->buildDelete(
        'glpi_configs',
        [
            'context'   => 'core',
            'name'      => 'use_timezones',
        ]
    )
);
/** /Drop unused config entry 'use_timezones' */

/** Fix olaticket crontask frequency */
$migration->addPostQuery(
    $DB->buildUpdate(
        'glpi_crontasks',
        ['frequency' => '300'],
        ['itemtype' => 'OlaLevel_Ticket', 'name' => 'olaticket']
    )
);
/** /Fix olaticket crontask frequency */

/** Fix mixed classes case in DB */
$mixed_case_classes = [
    'DeviceMotherBoardModel' => 'DeviceMotherboardModel',
];
foreach ($mixed_case_classes as $bad_case_classname => $classname) {
    $migration->renameItemtype($bad_case_classname, $classname, false);
}
/** /Fix mixed classes case in DB */
