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
 * Update from 9.5.4 to 9.5.5
 *
 * @return bool for success (will die for most error)
 **/
function update954to955() {
   global $DB, $migration;

   $updateresult = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.5.5'));
   $migration->setVersion('9.5.5');

   /* Add `DEFAULT CURRENT_TIMESTAMP` to some date fields */
   $tables = [
      'glpi_alerts',
      'glpi_crontasklogs',
      'glpi_notimportedemails',
   ];
   foreach ($tables as $table) {
      $type_result = $DB->request(
         [
            'SELECT'       => ['data_type as DATA_TYPE'],
            'FROM'         => 'information_schema.columns',
            'WHERE'       => [
               'table_schema' => $DB->dbdefault,
               'table_name'   => $table,
               'column_name'  => 'date',
            ],
         ]
      );
      $type = $type_result->next()['DATA_TYPE'];
      $migration->changeField($table, 'date', 'date', $type . ' NOT NULL DEFAULT CURRENT_TIMESTAMP');
   }
   /* /Add `DEFAULT CURRENT_TIMESTAMP` to some date fields */

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
