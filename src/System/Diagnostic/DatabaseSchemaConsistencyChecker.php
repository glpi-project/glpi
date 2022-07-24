<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\System\Diagnostic;

/**
 * @since 10.0.0
 */
class DatabaseSchemaConsistencyChecker extends AbstractDatabaseChecker
{
    /**
     * Get list of missing fields, basing detection on other fields.
     *
     * @param string $table_name
     *
     * @return array
     */
    public function getMissingFields(string $table_name): array
    {
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
