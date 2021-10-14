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

namespace Glpi\System\Diagnostic;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 10.0.0
 */
class DatabaseSchemaConsistencyChecker extends AbstractDatabaseChecker {

   /**
    * Get list of missing fields, basing detection on other fields.
    *
    * @param string $table_name
    *
    * @return array
    */
   public function getMissingFields(string $table_name): array {
      $missing_columns = [];

      $columns = $this->getColumnsNames($table_name);
      foreach ($columns as $column_name) {
         switch ($column_name) {
            case 'date_creation':
               if (!in_array('date_mod', $columns)) {
                  $missing_columns[] = 'date_mod';
               }
               break;
            case 'date_mod':
               if ($table_name === 'glpi_logs') {
                  // Logs cannot be modified and their date is stored on `date_mod`.
                  // FIXME It would be more logical to have a `date` instead, but renaming it is not so simple as table
                  // can contains millions of rows.
                  break;
               }
               if (!in_array('date_creation', $columns)) {
                  $missing_columns[] = 'date_creation';
               }
               break;
         }
      }

      return $missing_columns;
   }
}
