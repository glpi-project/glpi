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

$validation_tables = ['glpi_ticketvalidations', 'glpi_changevalidations'];

$needed_migration = false;

/** @var CommonITILValidation $validation_type */
foreach ($validation_tables as $validation_table) {
    if (!$DB->fieldExists($validation_table, 'itemtype_target')) {
        $migration->addField($validation_table, 'itemtype_target', 'varchar(255) NOT NULL', [
            'after'     => 'users_id_validate',
            'update'    => "'User'"
        ]);
        $needed_migration = true;
    }
    if (!$DB->fieldExists($validation_table, 'items_id_target')) {
        $migration->addField($validation_table, 'items_id_target', "int {$default_key_sign} NOT NULL DEFAULT '0'", [
            'after'     => 'itemtype_target',
            'update'    => $DB->quoteName($validation_table . '.users_id_validate'),
        ]);
        $needed_migration = true;
    }
    $migration->addKey($validation_table, ['itemtype_target', 'items_id_target'], 'item_target');
}

// Update notification template targets to replace VALIDATION_APPROVER (14) with VALIDATION_TARGET (40) to match previous behavior as close as possible

// Use the fact fields had changed as an indication this one-time migration hasn't been run yet
if ($needed_migration) {
    $DB->updateOrDie('glpi_notificationtargets', [
        'items_id'  => Notification::VALIDATION_TARGET,
    ], [
        'items_id'  => Notification::VALIDATION_APPROVER,
    ]);
}
