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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Http\Request;

class ProjectController extends \HLAPITestCase
{
    public function testCreateGetUpdateDelete()
    {
        $this->login();
        $func_name = __FUNCTION__;

        // Create
        $request = new Request('POST', '/Project');
        $request->setParameter('name', $func_name);
        $request->setParameter('content', 'test');
        $request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
        $new_item_location = null;
        $this->api->call($request, function ($call) use (&$new_item_location) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_item_location) {
                    $this->array($headers)->hasKey('Location');
                    $this->string($headers['Location'])->isNotEmpty();
                    $new_item_location = $headers['Location'];
                });
        });

        // Get
        $this->api->call(new Request('GET', $new_item_location), function ($call) use ($func_name) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($func_name) {
                    $this->string($content['name'])->isIdenticalTo($func_name);
                    $this->string($content['content'])->isIdenticalTo('test');
                });
        });

        // Update
        $request = new Request('PATCH', $new_item_location);
        $request->setParameter('name', $func_name . '2');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Verify update
        $this->api->call(new Request('GET', $new_item_location), function ($call) use ($func_name) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($func_name) {
                    $this->string($content['name'])->isIdenticalTo($func_name . '2');
                    $this->string($content['content'])->isIdenticalTo('test');
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
                    $this->array($headers)->hasKey('Location');
                    $this->string($headers['Location'])->isNotEmpty();
                    $this->string($headers['Location'])->contains('/Project');
                })
                ->jsonContent(function ($content) use (&$projects_id) {
                    $this->array($content)->hasKey('id');
                    $this->integer($content['id'])->isGreaterThan(0);
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
                    $this->array($headers)->hasKey('Location');
                    $this->string($headers['Location'])->isNotEmpty();
                    $this->string($headers['Location'])->contains("/Project/Task");
                    $new_item_location = $headers['Location'];
                });
        });

        // Get
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->string($content['content'])->isIdenticalTo('test');
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
                    $this->string($content['content'])->isIdenticalTo('test2');
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
