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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use HLAPICallAsserter;
use Project;
use User;

use function Safe\json_encode;

class KanbanControllerTest extends HLAPITestCase
{
    public function testKanbanState(): void
    {
        global $DB;

        $this->login();
        $users_id = getItemByTypeName(User::class, TU_USER, true);

        $DB->insert('glpi_items_kanbans', [
            'itemtype' => Project::class,
            'items_id' => 0,
            'users_id' => $users_id,
            'date_creation' => date('Y-m-d H:i:s'),
            'date_mod' => date('Y-m-d H:i:s'),
            'state' => json_encode([
                [
                    'column' => "0",
                    'folded' => "false",
                    'visible' => "true",
                    'cards' => ['Project-3', 'ProjectTask-5'],
                ],
                [
                    'column' => "1",
                    'folded' => "true",
                    'visible' => "true",
                    'cards' => ['Project-4', 'ProjectTask-6', 'Project-8'],
                ],
                [
                    'column' => "2",
                    'folded' => "false",
                    'visible' => "false",
                    'cards' => ['Project-5', 'ProjectTask-7'],
                ],
            ]),
        ]);

        $this->api->call(new Request('GET', '/Kanban/Project/0/View/' . $users_id), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(3, $content);
                    $this->assertEquals('0', $content[0]['column']);
                    $this->assertFalse($content[0]['folded']);
                    $this->assertTrue($content[0]['visible']);
                    $this->assertEquals('1', $content[1]['column']);
                    $this->assertTrue($content[1]['folded']);
                    $this->assertTrue($content[1]['visible']);
                    $this->assertEquals('2', $content[2]['column']);
                    $this->assertFalse($content[2]['folded']);
                    $this->assertFalse($content[2]['visible']);
                });
        });

        // Move column 1 and change its visibility and folded states
        $update_col_request = new Request(
            'PATCH',
            '/Kanban/Project/0/View/' . $users_id . '/Column/1',
            ['Content-Type' => 'application/json'],
            json_encode([
                'folded' => false,
                'visible' => false,
                "position" => 2,
            ])
        );
        $this->api->call($update_col_request, function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isOK();
        });

        $this->api->call(new Request('GET', '/Kanban/Project/0/View/' . $users_id), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(3, $content);
                    $this->assertEquals('0', $content[0]['column']);
                    $this->assertFalse($content[0]['folded']);
                    $this->assertTrue($content[0]['visible']);
                    $this->assertEquals('2', $content[1]['column']);
                    $this->assertFalse($content[1]['folded']);
                    $this->assertFalse($content[1]['visible']);
                    $this->assertEquals('1', $content[2]['column']);
                    $this->assertFalse($content[2]['folded']);
                    $this->assertFalse($content[2]['visible']);
                });
        });

        // Reorder cards in column 1
        $reorder_cards_request = new Request(
            'PATCH',
            '/Kanban/Project/0/View/' . $users_id . '/Column/1/Cards',
            ['Content-Type' => 'application/json'],
            json_encode([
                [
                    'itemtype' => 'ProjectTask',
                    'items_id' => 6,
                    'position' => 0,
                ],
            ])
        );
        $this->api->call($reorder_cards_request, function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isOK();
        });
        $this->api->call(new Request('GET', '/Kanban/Project/0/View/' . $users_id), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('1', $content[2]['column']);
                    $this->assertFalse($content[2]['folded']);
                    $this->assertFalse($content[2]['visible']);
                    $this->assertEquals(
                        ['ProjectTask-6', 'Project-4', 'Project-8'],
                        array_map(static fn($card) => $card['itemtype'] . '-' . $card['items_id'], $content[2]['cards'])
                    );
                });
        });

        // Replace cards in column 1
        $replace_cards_request = new Request(
            'PUT',
            '/Kanban/Project/0/View/' . $users_id . '/Column/1/Cards',
            ['Content-Type' => 'application/json'],
            json_encode([
                [
                    'itemtype' => 'ProjectTask',
                    'items_id' => 65,
                    'position' => 4,
                ],
                [
                    'itemtype' => 'ProjectTask',
                    'items_id' => 64,
                    'position' => 1,
                ],
                [
                    'itemtype' => 'ProjectTask',
                    'items_id' => 7,
                    'position' => 2,
                ],
                [
                    'itemtype' => 'Ticket',
                    'items_id' => 1,
                    'position' => 3,
                ],
            ])
        );
        $this->api->call($replace_cards_request, function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isOK();
        });
        $this->api->call(new Request('GET', '/Kanban/Project/0/View/' . $users_id), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('1', $content[2]['column']);
                    $this->assertFalse($content[2]['folded']);
                    $this->assertFalse($content[2]['visible']);
                    // Ticket card ignored because it isn't valid for a project Kanban
                    $this->assertEquals(
                        ['ProjectTask-64', 'ProjectTask-7', 'ProjectTask-65'],
                        array_map(static fn($card) => $card['itemtype'] . '-' . $card['items_id'], $content[2]['cards'])
                    );
                    // ProjectTask-7 should not be in column 2 anymore
                    $this->assertNotContains('ProjectTask-7', array_map(static fn($card) => $card['itemtype'] . '-' . $card['items_id'], $content[1]['cards']));
                });
        });

        // Delete view
        $this->api->call(new Request('DELETE', '/Kanban/Project/0/View/' . $users_id), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Check removed
        $this->api->call(new Request('GET', '/Kanban/Project/0/View/' . $users_id), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }
}
