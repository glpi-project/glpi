<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace tests\units\Glpi\DBAL;

use Glpi\DBAL\TableBuilder;
use Glpi\System\Diagnostic\DatabaseSchemaIntegrityChecker;

class DB extends \DbTestCase
{
    public function testEmptySQL()
    {
        global $DB;
        /** @var TableBuilder[] $tables */
        $tables = require_once(GLPI_ROOT . '/install/schemas/empty.php');

        $schema_sql = '';
        foreach ($tables as $table) {
            $schema_sql .= $table->getSQL() . ";\n";
        }

        $checker = new DatabaseSchemaIntegrityChecker($DB, false);
        $differences = $checker->checkCompleteSchema($schema_sql);
        $different_tables = [];
        foreach ($differences as $difference) {
            if ($difference['type'] === DatabaseSchemaIntegrityChecker::RESULT_TYPE_ALTERED_TABLE) {
                $different_tables[] = $difference['table'];
            }
        }
        $this->assertEmpty($different_tables);
    }
}
