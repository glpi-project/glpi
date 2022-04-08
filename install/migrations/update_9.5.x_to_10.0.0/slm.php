<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * @var DB $DB
 * @var Migration $migration
 */

// Replace usage of negative values in `calendars_id` fields
foreach (['glpi_slms', 'glpi_olas', 'glpi_slas'] as $table) {
    if (!$DB->fieldExists($table, 'use_ticket_calendar')) {
        $migration->addField($table, 'use_ticket_calendar', 'bool');
        $migration->addPostQuery(
            $DB->buildUpdate(
                $table,
                [
                    'use_ticket_calendar' => 1,
                    'calendars_id'        => 0,
                ],
                [
                    'calendars_id'        => -1,
                ]
            )
        );
    }
}

// Copy calendar settings from SLM to children
foreach (['glpi_olas', 'glpi_slas'] as $table) {
    $migration->addPostQuery(
        $DB->buildUpdate(
            $table,
            [
                $table . '.use_ticket_calendar' => new QueryExpression($DB->quoteName('glpi_slms.use_ticket_calendar')),
                $table . '.calendars_id'        => new QueryExpression($DB->quoteName('glpi_slms.calendars_id')),
            ],
            [
                true
            ],
            [
                'INNER JOIN' => [
                    'glpi_slms' => [
                        'FKEY' => [
                            $table      => 'slms_id',
                            'glpi_slms' => 'id'
                        ],
                    ],
                ],
            ]
        )
    );
}
