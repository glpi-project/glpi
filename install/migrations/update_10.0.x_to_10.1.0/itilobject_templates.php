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

/**
 * @var DB $DB
 * @var Migration $migration
 */

$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

// Add itilobject_templates_id fields to all ITIL Objects
$itil_type_tables = ['glpi_tickets', 'glpi_changes', 'glpi_problems'];

foreach ($itil_type_tables as $table) {
    if (!$DB->fieldExists($table, 'itilobject_templates_id')) {
        $migration->addField($table, 'itilobject_templates_id', "int {$default_key_sign} NOT NULL DEFAULT '0'");
        $migration->addKey($table, 'itilobject_templates_id');
    }
}

// Add status_allowed field to all ITIL Object template tables
$itiltemplate_tables = [
    'glpi_tickettemplates'  => [1, 2, 3, 4, 5, 6],
    'glpi_changetemplates'  => [1, 9, 10, 7, 4, 11, 12, 5, 8, 6, 14, 13],
    'glpi_problemtemplates' => [1, 7, 2, 3, 4, 5, 8, 6],
];

/**
 * @var CommonITILObject $itil_type
 * @var string $table
 */
foreach ($itiltemplate_tables as $table => $all_statuses) {
    if (!$DB->fieldExists($table, 'status_limit')) {
        $default_value = exportArrayToDB($all_statuses);
        $migration->addField($table, 'status_limit', 'string', [
            'null'  => false,
            'value' => $default_value
        ]);
    }
}
