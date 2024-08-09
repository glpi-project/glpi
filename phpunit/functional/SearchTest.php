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

namespace tests\units;

use DbTestCase;

class SearchTest extends DbTestCase
{
    private function doSearch($itemtype, $params, array $forcedisplay = [])
    {
        global $CFG_GLPI;

        // check param itemtype exists (to avoid search errors)
        if ($itemtype !== 'AllAssets') {
            $this->assertTrue(is_subclass_of($itemtype, 'CommonDBTM'));
        }

        // login to glpi if needed
        if (!isset($_SESSION['glpiname'])) {
            $this->login();
        }

        // force item lock
        if (in_array($itemtype, $CFG_GLPI['lock_lockable_objects'])) {
            $CFG_GLPI["lock_use_lock_item"] = 1;
            $CFG_GLPI["lock_item_list"] = [$itemtype];
        }

        // don't compute last request from session
        $params['reset'] = 'reset';

        // do search
        $params = \Search::manageParams($itemtype, $params);
        $data = \Search::getDatas($itemtype, $params, $forcedisplay);

        // do not store this search from session
        \Search::resetSaveSearch();

        $this->checkSearchResult($data);

        return $data;
    }

    /**
     * Check that search result is valid.
     *
     * @param array $result
     *
     * @return void
     */
    private function checkSearchResult($result)
    {
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('count', $result['data']);
        $this->assertArrayHasKey('begin', $result['data']);
        $this->assertArrayHasKey('end', $result['data']);
        $this->assertArrayHasKey('totalcount', $result['data']);
        $this->assertArrayHasKey('cols', $result['data']);
        $this->assertArrayHasKey('rows', $result['data']);
        $this->assertArrayHasKey('items', $result['data']);
        $this->assertIsInt($result['data']['count']);
        $this->assertIsInt($result['data']['begin']);
        $this->assertIsInt($result['data']['end']);
        $this->assertIsInt($result['data']['totalcount']);
        $this->assertIsArray($result['data']['cols']);
        $this->assertIsArray($result['data']['rows']);
        $this->assertIsArray($result['data']['items']);

        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('search', $result['sql']);
        $this->assertIsString($result['sql']['search']);
    }

    public function testCommonITILSatisfactionEndDate()
    {
        global $DB, $GLPI_CACHE;
        $entity_root_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $entity_child_1_id = getItemByTypeName('Entity', '_test_child_1', true);
        $entity_child_2_id = getItemByTypeName('Entity', '_test_child_2', true);
        $user_id = getItemByTypeName('User', TU_USER, true);

        $this->login();

        $DB->update(
            \Entity::getTable(),
            [
                'inquest_duration' => '0',
            ],
            ['id' => $entity_root_id]
        );
        $DB->update(
            \Entity::getTable(),
            [
                'inquest_duration' => '2',
            ],
            ['id' => $entity_child_1_id]
        );
        $DB->update(
            \Entity::getTable(),
            [
                'inquest_duration' => '4',
            ],
            ['id' => $entity_child_2_id]
        );
        $GLPI_CACHE->clear();

        // Create a closed ticket
        $ticket = new \Ticket();
        $ticket1_id = (int) $ticket->add([
            'entity_id' => $entity_root_id,
            'name' => __FUNCTION__ . ' 1',
            'content' => __FUNCTION__ . ' 1 content',
            'solvedate' => $_SESSION['glpi_currenttime'],
            'status' => \CommonITILObject::CLOSED,
            'users_id_recipient' => $user_id,
        ]);
        $this->assertTrue($ticket->getFromDB($ticket1_id));
        $this->assertTrue($ticket->isClosed());

        $ticket2_id = (int) $ticket->add([
            'entity_id' => $entity_child_1_id,
            'name' => __FUNCTION__ . ' 2',
            'content' => __FUNCTION__ . ' 2 content',
            'solvedate' => $_SESSION['glpi_currenttime'],
            'status' => \CommonITILObject::CLOSED,
            'users_id_recipient' => $user_id,
        ]);
        $this->assertTrue($ticket->getFromDB($ticket2_id));
        $this->assertTrue($ticket->isClosed());

        $ticket3_id = (int) $ticket->add([
            'entity_id' => $entity_child_2_id,
            'name' => __FUNCTION__ . ' 3',
            'content' => __FUNCTION__ . ' 3 content',
            'solvedate' => $_SESSION['glpi_currenttime'],
            'status' => \CommonITILObject::CLOSED,
            'users_id_recipient' => $user_id,
        ]);
        $this->assertTrue($ticket->getFromDB($ticket3_id));
        $this->assertTrue($ticket->isClosed());

        // Create satisfaction
        $satisfaction = new \TicketSatisfaction();
        $satisfaction->add([
            'tickets_id' => $ticket1_id,
            'type' => \CommonITILSatisfaction::TYPE_INTERNAL,
        ]);
        $this->assertTrue($satisfaction->getFromDB($satisfaction->getID()));

        $satisfaction->add([
            'tickets_id' => $ticket2_id,
            'type' => \CommonITILSatisfaction::TYPE_INTERNAL,
        ]);
        $this->assertTrue($satisfaction->getFromDB($satisfaction->getID()));

        $satisfaction->add([
            'tickets_id' => $ticket3_id,
            'type' => \CommonITILSatisfaction::TYPE_INTERNAL,
        ]);
        $this->assertTrue($satisfaction->getFromDB($satisfaction->getID()));

        $search_params = [
            'is_deleted' => 0,
            'start' => 0,
            'criteria' => [
                [
                    'field' => 1, // name
                    'searchtype' => 'contains',
                    'value' => __FUNCTION__,
                ],
                [
                    'field' => 72, // satisfaction end date
                    'searchtype' => 'contains',
                    'value' => '',
                ],
            ],
            'order' => 1,
        ];

        $data = $this->doSearch(\Ticket::class, $search_params);

        $items = [];
        foreach ($data['data']['rows'] as $row) {
            $items[] = [
                $row['raw']['ITEM_Ticket_2'],
                $row['raw']['ITEM_Ticket_72'],
            ];
        }
        $expected = [
            [
                $ticket1_id,
                '',
            ],
            [
                $ticket2_id,
                date('Y-m-d H:i', strtotime('+2 days', strtotime($_SESSION['glpi_currenttime']))),
            ],
            [
                $ticket3_id,
                date('Y-m-d H:i', strtotime('+4 days', strtotime($_SESSION['glpi_currenttime']))),
            ],
        ];
        $this->assertEquals($expected, $items);
    }
}
