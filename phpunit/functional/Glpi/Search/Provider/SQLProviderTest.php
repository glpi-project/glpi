<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units\Glpi\Search\Provider;

use DbTestCase;
use Glpi\Search\Provider\SQLProvider;

class SQLProviderTest extends DbTestCase
{
    public function testGetLeftJoinCriteria()
    {
        global $DB;

        $already_linked = [];
        $item_item_join = SQLProvider::getLeftJoinCriteria(
            'Ticket',
            'glpi_tickets',
            $already_linked,
            'glpi_tickets_tickets',
            'tickets_tickets_id',
            false,
            0,
            ['jointype' => 'item_item'],
            'tickets_id_1'
        );
        $it = new \DBmysqlIterator($DB);
        $this->assertEquals(
            ' LEFT JOIN `glpi_tickets_tickets` ON (`glpi_tickets`.`id` = `glpi_tickets_tickets`.`tickets_id_1` OR `glpi_tickets`.`id` = `glpi_tickets_tickets`.`tickets_id_2`)',
            $it->analyseJoins($item_item_join)
        );

        $item_item_revert_join = SQLProvider::getLeftJoinCriteria(
            'Ticket_Ticket',
            'glpi_tickets_tickets',
            $already_linked,
            'glpi_tickets',
            'tickets_id',
            false,
            0,
            ['jointype' => 'item_item_revert'],
            'tickets_id'
        );
        $this->assertEquals(
            ' LEFT JOIN `glpi_tickets` ON (`glpi_tickets`.`id` = `glpi_tickets_tickets`.`tickets_id_1` OR `glpi_tickets`.`id` = `glpi_tickets_tickets`.`tickets_id_2`)',
            $it->analyseJoins($item_item_revert_join)
        );
    }
}
