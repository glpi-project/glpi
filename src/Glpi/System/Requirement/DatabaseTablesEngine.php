<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\System\Requirement;

final class DatabaseTablesEngine extends AbstractRequirement
{
    /**
     * DB instance.
     *
     * @var \DBmysql
     */
    private $db;

    public function __construct(\DBmysql $db)
    {
        parent::__construct(
            __('DB tables engine')
        );

        $this->db = $db;
    }

    protected function check(): void
    {
        $this->validated = true;
        $tables = $this->db->getMyIsamTables();

        // Fail if at least one MyIsam table is found
        if (count($tables)) {
            $this->validated = false;
            $this->validation_messages[] = sprintf(
                __('Please run the "%1$s" command.'),
                'php bin/console migration:myisam_to_innodb'
            );
        }

        // List each invalid table
        foreach ($tables as $table) {
            $this->validation_messages[] = sprintf(
                __('The "%1$s" table does not have the required InnoDB engine.'),
                $table['TABLE_NAME']
            );
        }
    }
}
