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

use Glpi\Http\Request;

class ProjectControllerTest extends \HLAPITestCase
{
    public function testCreateGetUpdateDelete()
    {
        $this->api->autoTestCRUD('/Project');
    }

    public function testCreateGetUpdateDeleteTask()
    {
        $this->login('glpi', 'glpi');

        // Create Project
        $request = new Request('POST', '/Project');
        $request->setParameter('name', __FUNCTION__);
        $request->setParameter('content', 'test');
        $request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));

        $projects_id = null;
        $this->api->call($request, function ($call) use (&$projects_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) {
                    $this->assertStringContainsString('/Project', $headers['Location']);
                })
                ->jsonContent(function ($content) use (&$projects_id) {
                    $this->assertGreaterThan(0, $content['id']);
                    $projects_id = $content['id'];
                });
        });

        // Create
        $request = new Request('POST', "/Project/$projects_id/Task");
        $request->setParameter('content', 'test');
        $new_item_location = null;
        $this->api->call($request, function ($call) use (&$new_item_location) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_item_location) {
                    $this->assertStringContainsString('/Project/Task', $headers['Location']);
                    $new_item_location = $headers['Location'];
                });
        });

        // Get
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('test', $content['content']);
                });
        });

        // Update
        $request = new Request('PATCH', $new_item_location);
        $request->setParameter('content', 'test2');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Verify Update
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('test2', $content['content']);
                });
        });

        // Delete (Trash)
        $this->api->call(new Request('DELETE', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Get (Trash)
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Delete (Purge)
        $request = new Request('DELETE', $new_item_location);
        $request->setParameter('force', 1);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Verify not found
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }
}
