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

namespace Glpi\System\Requirement;

/**
 * @since 9.5.0
 */
class DbTimezones extends AbstractRequirement
{
    /**
     * DB instance.
     *
     * @var \DBmysql
     */
    private $db;

    public function __construct(\DBmysql $db)
    {
        $this->title = __('DB timezone data');
        $this->description = __('Enable usage of timezones.');
        $this->db = $db;
        $this->optional = true;
    }

    protected function check()
    {
        $mysql_db_res = $this->db->request('SHOW DATABASES LIKE ' . $this->db->quoteValue('mysql'));
        if ($mysql_db_res->count() === 0) {
            $this->validated = false;
            $this->validation_messages[] = __('Access to timezone database (mysql) is not allowed.');
            return;
        }

        $tz_table_res = $this->db->request(
            'SHOW TABLES FROM '
            . $this->db->quoteName('mysql')
            . ' LIKE '
            . $this->db->quoteValue('time_zone_name')
        );
        if ($tz_table_res->count() === 0) {
            $this->validated = false;
            $this->validation_messages[] = __('Access to timezone table (mysql.time_zone_name) is not allowed.');
            return;
        }

        $iterator = $this->db->request(
            [
                'COUNT'  => 'cpt',
                'FROM'   => 'mysql.time_zone_name',
            ]
        );
        $result = $iterator->current();
        if ($result['cpt'] === 0) {
            $this->validated = false;
            $this->validation_messages[] = __('Timezones seems not loaded, see https://glpi-install.readthedocs.io/en/latest/timezones.html.');
            return;
        }

        $this->validated = true;
        $this->validation_messages[] = __('Timezones seems loaded in database.');
    }
}
