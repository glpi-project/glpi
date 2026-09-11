<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use mysqli_result;
use Toolbox;

/**
 * @since 10.0.0
 */
class DbConfiguration extends AbstractRequirement
{
    /**
     * DB instance.
     *
     */
    private DBmysql $db;

    public function __construct(DBmysql $db)
    {
        parent::__construct(
            __('DB configuration')
        );

        $this->db = $db;
    }

    /**
     * Minimum `max_allowed_packet` value (bytes) required to import the default data.
     * A lower value makes the installation fail with a "MySQL server has gone away" error.
     */
    private const MIN_MAX_ALLOWED_PACKET = 16 * 1024 * 1024;

    protected function check()
    {
        $query = 'SELECT @@GLOBAL.' . $this->db->quoteName('innodb_page_size as innodb_page_size')
            . ', @@GLOBAL.' . $this->db->quoteName('max_allowed_packet as max_allowed_packet');

        /** @var mysqli_result $db_config_res */
        $db_config_res = $this->db->doQuery($query);
        $db_config = $db_config_res->fetch_assoc();

        if (!is_array($db_config)) {
            $this->validated = false;
            $this->validation_messages[] = __('Unable to read the database configuration.');
            return;
        }

        $incompatibilities = [];
        if ((int) $db_config['innodb_page_size'] < 8192) {
            $incompatibilities[] = '"innodb_page_size" must be >= 8KB.';
        }
        if ((int) $db_config['max_allowed_packet'] < self::MIN_MAX_ALLOWED_PACKET) {
            $incompatibilities[] = sprintf(
                '"max_allowed_packet" must be >= %1$s (current value is %2$s).',
                Toolbox::getSize(self::MIN_MAX_ALLOWED_PACKET),
                Toolbox::getSize((int) $db_config['max_allowed_packet'])
            );
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
