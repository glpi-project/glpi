<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use DBmysql;

final class DatabaseTablesEngine extends AbstractRequirement
{
    /**
     * DB instance.
     *
     * @var DBmysql
     */
    private $db;

    public function __construct(DBmysql $db)
    {
        parent::__construct(
            __('Database tables engine')
        );

        $this->db = $db;
    }

    protected function check(): void
    {
        $this->validated = true;
        $tables_count = count($this->db->getMyIsamTables());

        // Fail if at least one MyIsam table is found
        if ($tables_count > 0) {
            $this->validated = false;
            $this->validation_messages[] = sprintf(
                __('The database contains %1$d table(s) using the unsupported MyISAM engine. Please run the "%2$s" command to migrate them to the InnoDB engine.'),
                $tables_count,
                'php bin/console migration:myisam_to_innodb'
            );
        }
    }
}
