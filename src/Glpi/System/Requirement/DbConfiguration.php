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

/**
 * @since 10.0.0
 */
class DbConfiguration extends AbstractRequirement
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
            __('DB configuration')
        );

        $this->db = $db;
    }

    protected function check()
    {
        $query = 'SELECT @@GLOBAL.' . $this->db->quoteName('innodb_page_size as innodb_page_size');

        if (($db_config_res = $this->db->doQuery($query)) === false) {
            $this->validated = false;
            $this->validation_messages[] = __('Unable to validate database configuration variables.');
        }

        $db_config = $db_config_res->fetch_assoc();

        $incompatibilities = [];
        if ((int) $db_config['innodb_page_size'] < 8192) {
            $incompatibilities[] = '"innodb_page_size" must be >= 8KB.';
        }

        if (count($incompatibilities) > 0) {
            $this->validation_messages = $incompatibilities;
            $this->validated = false;
        } else {
            $this->validation_messages[] = __('Database configuration is OK.');
            $this->validated = true;
        }
    }
}
