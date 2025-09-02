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
 * @since 9.5.0
 */
class DbTimezones extends AbstractRequirement
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
            __('DB timezone data'),
            __('Enable usage of timezones.'),
            true
        );

        $this->db = $db;
    }

    protected function check()
    {
        $available_timezones = $this->db->getTimezones();

        if (count($available_timezones) === 0) {
            $this->validated = false;
            $this->validation_messages[] = __('Timezones seems not loaded, see https://glpi-install.readthedocs.io/en/latest/timezones.html.');
            return;
        }

        $this->validated = true;
        $this->validation_messages[] = __('Timezones seems loaded in database.');
    }
}
