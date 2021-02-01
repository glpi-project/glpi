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

// Remove the `NOT NULL` flag of comment fields and fix collation
$tables = [
   'glpi_apiclients',
   'glpi_applianceenvironments',
   'glpi_appliances',
   'glpi_appliancetypes',
   'glpi_devicesimcards',
   'glpi_knowbaseitems_comments',
   'glpi_lines',
   'glpi_rulerightparameters',
   'glpi_ssovariables',
   'glpi_virtualmachinestates',
   'glpi_virtualmachinesystems',
   'glpi_virtualmachinetypes',
];
foreach ($tables as $table) {
   $migration->changeField($table, 'comment', 'comment', 'text');
}

// Add `DEFAULT CURRENT_TIMESTAMP` to some date fields
$tables = [
   'glpi_alerts',
   'glpi_crontasklogs',
   'glpi_notimportedemails',
];
foreach ($tables as $table) {
   $migration->changeField($table, 'date', 'date', 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP');
}

// Fix charset for glpi_notimportedemails table
$migration->addPreQuery(
   sprintf(
      'ALTER TABLE %s CONVERT TO CHARACTER SET %s COLLATE %s',
      $DB->quoteName('glpi_notimportedemails'),
      DBConnection::getDefaultCharset(),
      DBConnection::getDefaultCollation()
   )
);
// Put back `subject` type to text (charset convertion changed it from text to mediumtext)
$migration->changeField('glpi_notimportedemails', 'subject', 'subject', 'text', ['nodefault' => true]);
