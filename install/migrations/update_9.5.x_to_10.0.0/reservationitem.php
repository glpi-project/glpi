<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

$migration->displayMessage("Adding unicity key to reservationitem");
$table = 'glpi_reservationitems';

// Copy table
$tmp_table = "tmp_$table";
$migration->copyTable($table, $tmp_table, false);

// Drop is_deleted
$migration->dropKey($tmp_table, 'is_deleted');
$migration->dropField($tmp_table, "is_deleted");

// Add unicity key
$migration->addKey(
   $tmp_table,
   ['itemtype', 'items_id'],
   'unicity',
   'UNIQUE'
);

// Insert without duplicates
$quote_tmp_table = $DB->quoteName($tmp_table);
$select = $DB->request([
   'FROM' => $table
])->getSql();

// "IGNORE" keyword used to avoid duplicates
$DB->queryOrDie("INSERT IGNORE INTO $quote_tmp_table $select");

// Replace table with the new version
$migration->dropTable($table);
$migration->renameTable($tmp_table, $table);
