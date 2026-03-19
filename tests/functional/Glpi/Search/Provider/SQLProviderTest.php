<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Search\Provider\SQLProvider;
use Glpi\Tests\DbTestCase;

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

    public function testHtmlTextSearchWithSpaces()
    {
        $this->login();

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name'    => 'Test with strong text',
            'content' => '<strong>1) Option: <strong>option1',
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $ticket_loaded = new \Ticket();
        $this->assertTrue($ticket_loaded->getFromDB($tickets_id));
        $this->assertStringContainsString('1) Option:', $ticket_loaded->fields['content']);
        $this->assertStringContainsString('option1', $ticket_loaded->fields['content']);

        $data = \Search::getDatas('Ticket', [
            'reset'      => 'reset',
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => [
                0 => [
                    'field'      => 21,  // ticket content (Richtext provider)
                    'searchtype' => 'contains',
                    'value'      => '1) Option: option1',
                ],
            ],
        ]);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('totalcount', $data['data']);
        $this->assertGreaterThan(
            0,
            $data['data']['totalcount'],
        );

        $found = false;
        foreach ($data['data']['rows'] as $row) {
            if ($row['raw']['id'] == $tickets_id) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testHtmlTextSearchWithPartialWords()
    {
        $this->login();

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name'    => 'Test with red light',
            'content' => 'When the red light starts flashing, you have less than 10 seconds to cut the blue cable.',
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        $data = \Search::getDatas('Ticket', [
            'reset'      => 'reset',
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => [
                0 => [
                    'field'      => 21,
                    'searchtype' => 'contains',
                    'value'      => 'red cable',
                ],
            ],
        ]);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('totalcount', $data['data']);

        $found = false;
        foreach ($data['data']['rows'] as $row) {
            if ($row['raw']['id'] == $tickets_id) {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found);
    }
}
