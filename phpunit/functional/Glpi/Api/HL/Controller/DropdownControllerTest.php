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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Http\Request;

class DropdownControllerTest extends \HLAPITestCase
{
    public function testIndex()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Dropdowns'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertGreaterThanOrEqual(4, count($content));
                    foreach ($content as $asset) {
                        $this->assertNotEmpty($asset['itemtype']);
                        $this->assertNotEmpty($asset['name']);
                        $this->assertEquals('/Dropdowns/' . $asset['itemtype'], $asset['href']);
                    }
                });
        });
    }

    public function testAutoSearch()
    {
        $this->login();
        $entity = $this->getTestRootEntity(true);
        $dataset = [
            [
                'name' => 'testAutoSearch_1',
                'entity' => $entity,
            ],
            [
                'name' => 'testAutoSearch_2',
                'entity' => $entity,
            ],
            [
                'name' => 'testAutoSearch_3',
                'entity' => $entity,
            ],
        ];
        $this->api->call(new Request('GET', '/Dropdowns'), function ($call) use ($dataset) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($dataset) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $dropdown) {
                        $this->api->autoTestSearch('/Dropdowns/' . $dropdown['itemtype'], $dataset);
                    }
                });
        });
    }

    public function testAutoCRUD()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Dropdowns'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $dropdown) {
                        $this->api->autoTestCRUD('/Dropdowns/' . $dropdown['itemtype']);
                    }
                });
        });
    }

    public function testCRUDNoRights()
    {
        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        $this->api->call(new Request('GET', '/Dropdowns'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $dropdown) {
                        $create_request = new Request('POST', $dropdown['href']);
                        $create_request->setParameter('name', 'testCRUDNoRights' . random_int(0, 10000));
                        $create_request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
                        $new_location = null;
                        $new_items_id = null;
                        $this->api->call($create_request, function ($call) use (&$new_location, &$new_items_id) {
                            /** @var \HLAPICallAsserter $call */
                            $call->response
                                ->isOK()
                                ->headers(function ($headers) use (&$new_location) {
                                    $new_location = $headers['Location'];
                                })
                                ->jsonContent(function ($content) use (&$new_items_id) {
                                    $new_items_id = $content['id'];
                                });
                        });
                        $this->api->autoTestCRUDNoRights(
                            endpoint: $dropdown['href'],
                            itemtype: $dropdown['itemtype'],
                            items_id: (int) $new_items_id
                        );
                    }
                });
        });
    }
}
