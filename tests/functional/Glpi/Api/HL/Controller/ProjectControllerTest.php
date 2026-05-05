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

namespace tests\units\Glpi\Api\HL\Controller;

use Computer;
use Entity;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use Project;
use ProjectTask;
use Session;

class ProjectControllerTest extends HLAPITestCase
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

    public function testCRUDNoRights()
    {
        $this->api->autoTestCRUDNoRights(
            endpoint: '/Project',
            itemtype: Project::class,
            items_id: getItemByTypeName(Project::class, strtolower('_project01'), true)
        );
    }

    public function testRestrictedProjectRead()
    {
        global $DB;

        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $project = getItemByTypeName(Project::class, strtolower('_project01'));
        $request = new Request('GET', '/Project/' . $project->getID());
        $_SESSION['glpiactiveprofile'][Project::$rightname] = 0;

        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        });

        $_SESSION['glpiactiveprofile'][Project::$rightname] = Project::READMY;
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        $this->assertTrue($project->update([
            'id' => $project->getID(),
            'users_id' => $_SESSION['glpiID'] + 1, // Created by another user
        ]));
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });

        $DB->insert('glpi_projectteams', [
            'projects_id' => $project->getID(),
            'itemtype'   => 'User',
            'items_id'   => $_SESSION['glpiID'],
        ]);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        $this->assertTrue($DB->update(Project::getTable(), [
            'entities_id' => 0, // Out of user's entities
            'is_recursive' => 0,
        ], ['id' => $project->getID()]));
        $project->getFromDB($project->getID());
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }

    public function testRestrictedProjectTaskRead()
    {
        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $project = getItemByTypeName(Project::class, strtolower('_project01'));
        $project_task = $this->createItem(ProjectTask::class, [
            'projects_id' => $project->getID(),
            'name'     => __FUNCTION__,
            'entities_id' => getItemByTypeName(Entity::class, '_test_root_entity', true),
        ]);
        $this->api->call(new Request('GET', '/Project/' . $project->getID() . '/Task'), function ($call) use ($project_task) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(static fn($c) => count($c) === 1 && $c[0]['id'] === $project_task->getID());
        });

        $_SESSION['glpiactiveprofile'][Project::$rightname] = 0;
        $this->api->call(new Request('GET', '/Project/' . $project->getID() . '/Task'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(static fn($c) => empty($c));
        });
        $this->api->call(new Request('GET', '/Project/Task/' . $project_task->getID()), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }

    public function testCRUDProjectCost()
    {
        $projects_id = getItemByTypeName(Project::class, '_project01', true);

        $this->api->autoTestCRUD('/Project/Project/' . $projects_id . '/Cost', [
            'name' => __FUNCTION__,
            'cost' => 100,
        ], [
            'name' => __FUNCTION__ . '2',
            'cost' => 150,
        ]);
    }

    public function testCRUDItemProjectLink()
    {
        $computers_id = getItemByTypeName(Computer::class, '_test_pc01', true);
        $projects_id = getItemByTypeName(Project::class, '_project01', true);

        $this->api->autoTestCRUD('/Assets/Computer/' . $computers_id . '/Project', [
            'project' => $projects_id,
        ], [
            'project' => $projects_id,
        ]);
    }

    public function testCRUDProjectTeamMember()
    {
        $this->loginWeb();
        $this->login();

        $project = $this->createItem(Project::class, [
            'name' => __FUNCTION__,
            'entities_id' => getItemByTypeName(Entity::class, '_test_root_entity', true),
            'content' => 'test',
        ]);

        $teammember_endpoint = "/Project/{$project->getID()}/TeamMember";

        $this->api->call(new Request('GET', $teammember_endpoint), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEmpty($content);
                });
        });

        $user_id = Session::getLoginUserID();
        $request = new Request('POST', $teammember_endpoint);
        $request->setParameter('itemtype', 'User');
        $request->setParameter('items_id', $user_id);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });

        $teammember_id = null;
        $this->api->call(new Request('GET', $teammember_endpoint), function ($call) use ($user_id, &$teammember_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($user_id, &$teammember_id) {
                    $this->assertCount(1, $content);
                    $this->assertEquals('User', $content[0]['itemtype']);
                    $this->assertEquals($user_id, $content[0]['items_id']);
                    $teammember_id = $content[0]['id'];
                });
        });

        $request = new Request('DELETE', $teammember_endpoint . '/' . $teammember_id);
        $request->setParameter('itemtype', 'User');
        $request->setParameter('items_id', $user_id);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });

        $this->api->call(new Request('GET', $teammember_endpoint), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEmpty($content);
                });
        });
    }

    public function testCRUDProjectTaskTeamMember()
    {
        $this->loginWeb();
        $this->login();

        $project = $this->createItem(Project::class, [
            'name' => __FUNCTION__,
            'entities_id' => getItemByTypeName(Entity::class, '_test_root_entity', true),
            'content' => 'test',
        ]);

        $task = $this->createItem(ProjectTask::class, [
            'projects_id' => $project->getID(),
            'name'     => __FUNCTION__ . '_task',
            'entities_id' => getItemByTypeName(Entity::class, '_test_root_entity', true),
        ]);

        $teammember_endpoint = "/Project/Task/{$task->getID()}/TeamMember";

        $user_id = Session::getLoginUserID();
        $request = new Request('POST', $teammember_endpoint);
        $request->setParameter('itemtype', 'User');
        $request->setParameter('items_id', $user_id);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });

        $teammember_id = null;
        $this->api->call(new Request('GET', $teammember_endpoint), function ($call) use ($user_id, &$teammember_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($user_id, &$teammember_id) {
                    $this->assertCount(1, $content);
                    $this->assertEquals('User', $content[0]['itemtype']);
                    $this->assertEquals($user_id, $content[0]['items_id']);
                    $teammember_id = $content[0]['id'];
                });
        });

        $request = new Request('DELETE', $teammember_endpoint . '/' . $teammember_id);
        $request->setParameter('itemtype', 'User');
        $request->setParameter('items_id', $user_id);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });

        $this->api->call(new Request('GET', $teammember_endpoint), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEmpty($content);
                });
        });
    }
}
