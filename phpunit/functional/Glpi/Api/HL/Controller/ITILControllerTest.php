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

use Change;
use ChangeValidation;
use CommonITILObject;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Http\Request;
use Problem;
use Ticket;
use TicketValidation;

class ITILControllerTest extends \HLAPITestCase
{
    public function testCreateGetUpdateDelete()
    {
        $this->login();
        $itil_types = ['Ticket', 'Change', 'Problem'];
        $func_name = __FUNCTION__;

        foreach ($itil_types as $itil_type) {
            $this->api->autoTestCRUD('/Assistance/' . $itil_type, [
                'name' => $func_name,
                'content' => 'test',
                'entity' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]);
        }
    }

    public function testCreateGetUpdateDeleteFollowup()
    {
        $this->login();
        $itil_types = ['Ticket', 'Change', 'Problem'];

        foreach ($itil_types as $itil_type) {
            // Create ITIL Object
            $request = new Request('POST', '/Assistance/' . $itil_type);
            $request->setParameter('name', __FUNCTION__);
            $request->setParameter('content', 'test');
            $request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
            $itil_base_path = null;
            $this->api->call($request, function ($call) use ($itil_type, &$itil_base_path) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->headers(function ($headers) use ($itil_type, &$itil_base_path) {
                        $this->assertStringContainsString('/Assistance/' . $itil_type, $headers['Location']);
                        $itil_base_path = $headers['Location'];
                    });
            });

            $this->api->autoTestCRUD($itil_base_path . '/Timeline/Followup', [
                'content' => 'test',
            ]);
        }
    }

    public function testCreateGetUpdateDeleteTask()
    {
        $this->login();
        $itil_types = ['Ticket', 'Change', 'Problem'];

        foreach ($itil_types as $itil_type) {
            // Create ITIL Object
            $request = new Request('POST', '/Assistance/' . $itil_type);
            $request->setParameter('name', __FUNCTION__);
            $request->setParameter('content', 'test');
            $request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
            $itil_base_path = null;
            $this->api->call($request, function ($call) use ($itil_type, &$itil_base_path) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->headers(function ($headers) use ($itil_type, &$itil_base_path) {
                        $this->assertStringContainsString('/Assistance/' . $itil_type, $headers['Location']);
                        $itil_base_path = $headers['Location'];
                    });
            });

            $this->api->autoTestCRUD($itil_base_path . '/Timeline/Task', [
                'content' => 'test',
            ]);
        }
    }

    public function testGetTimeline()
    {
        $this->login();
        $itil_types = ['Ticket', 'Change', 'Problem'];

        foreach ($itil_types as $itil_type) {
            // Create ITIL Object
            $request = new Request('POST', '/Assistance/' . $itil_type);
            $request->setParameter('name', __FUNCTION__);
            $request->setParameter('content', 'test');
            $request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
            $itil_base_path = null;
            $this->api->call($request, function ($call) use ($itil_type, &$itil_base_path) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->headers(function ($headers) use ($itil_type, &$itil_base_path) {
                        $this->assertStringContainsString('/Assistance/' . $itil_type, $headers['Location']);
                        $itil_base_path = $headers['Location'];
                    });
            });

            // Create 2 followups and tasks
            $subtypes = ['Followup', 'Task'];
            foreach ($subtypes as $subtype) {
                for ($i = 0; $i < 2; $i++) {
                    $request = new Request('POST', $itil_base_path . '/Timeline/' . $subtype);
                    $request->setParameter('content', 'test' . $i);
                    $this->api->call($request, function ($call) {
                        /** @var \HLAPICallAsserter $call */
                        $call->response->isOK();
                    });
                }
            }

            // Get timeline
            $this->api->call(new Request('GET', $itil_base_path . '/Timeline'), function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) {
                        $this->assertGreaterThanOrEqual(4, count($content));
                        // Ensure there are 2 items with type=Task and 2 items with type=Followup
                        $remaining_matches = [
                            'Task' => ['test0' => 1, 'test1' => 1],
                            'Followup' => ['test0' => 1, 'test1' => 1],
                        ];
                        $tasks = array_filter($content, static function ($item) {
                            return $item['type'] === 'Task';
                        });
                        $this->assertCount(2, $tasks);
                        foreach ($tasks as $task) {
                            unset($remaining_matches['Task'][$task['item']['content']]);
                        }

                        $fups = array_filter($content, static function ($item) {
                            return $item['type'] === 'Followup';
                        });
                        $this->assertCount(2, $fups);
                        foreach ($fups as $fup) {
                            unset($remaining_matches['Followup'][$fup['item']['content']]);
                        }

                        $this->assertEmpty($remaining_matches['Task']);
                        $this->assertEmpty($remaining_matches['Followup']);
                    });
            });
        }
    }

    public function getSpecificTimelineType()
    {
        $this->login();
        $itil_types = ['Ticket', 'Change', 'Problem'];
        $subtypes = ['Followup', 'Task'];

        foreach ($itil_types as $itil_type) {
            // Create ITIL Object
            $request = new Request('POST', '/Assistance/' . $itil_type);
            $request->setParameter('name', __FUNCTION__);
            $request->setParameter('content', 'test');
            $request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
            $itil_base_path = null;
            $this->api->call($request, function ($call) use ($itil_type, &$itil_base_path) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->headers(function ($headers) use ($itil_type, &$itil_base_path) {
                        $this->array($headers)->hasKey('Location');
                        $this->string($headers['Location'])->isNotEmpty();
                        $this->string($headers['Location'])->contains('/Assistance/' . $itil_type);
                        $itil_base_path = $headers['Location'];
                    });
            });

            // Create 2 of the specific subtypes
            foreach ($subtypes as $subtype) {
                for ($i = 0; $i < 2; $i++) {
                    $request = new Request('POST', $itil_base_path . '/Timeline/' . $subtype);
                    $request->setParameter('content', 'test' . $i);
                    $this->api->call($request, function ($call) {
                        /** @var \HLAPICallAsserter $call */
                        $call->response->isOK();
                    });
                }
            }

            // Get the specific subtype
            foreach ($subtypes as $subtype) {
                $this->api->call(new Request('GET', $itil_base_path . '/Timeline/' . $subtype), function ($call) use ($subtype) {
                    /** @var \HLAPICallAsserter $call */
                    $call->response
                        ->isOK()
                        ->jsonContent(function ($content) use ($subtype) {
                            $this->array($content)->hasSize(2);
                            $this->array(array_column($content, '_itemtype'))->isEqualTo([$subtype, $subtype]);
                        });
                });
            }
        }
    }

    public function testCRUDRecurringITIL()
    {
        $this->login();
        $func_name = __FUNCTION__;

        foreach (['Ticket', 'Change'] as $itil_type) {
            // Create a ITIL template
            $template_class = $itil_type . 'Template';
            $template = new $template_class();
            $this->assertGreaterThan(0, $templates_id = $template->add([
                'name' => __FUNCTION__,
                'content' => 'test',
                'is_recursive' => 1,
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]));

            $this->api->autoTestCRUD('/Assistance/Recurring' . $itil_type, [
                'name' => $func_name,
                'template' => $templates_id,
            ]);
        }
    }

    /**
     * Make sure users cannot change the parent of a timeline subitem
     * @return void
     */
    public function testBlockOverridingParentItem()
    {
        $ticket = new Ticket();
        $this->assertGreaterThan(0, $tickets_id = $ticket->add([
            'name' => __FUNCTION__,
            'content' => 'test',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]));

        $fup = new \ITILFollowup();
        $task = new \TicketTask();
        $solution = new \ITILSolution();
        $validation = new TicketValidation();
        $document = new \Document();
        $document_item = new \Document_Item();

        // Create a followup
        $this->assertGreaterThan(0, $fup_id = $fup->add([
            'name' => __FUNCTION__,
            'content' => 'test',
            'itemtype' => 'Ticket',
            'items_id' => $tickets_id,
        ]));

        // Create a task
        $this->assertGreaterThan(0, $task_id = $task->add([
            'name' => __FUNCTION__,
            'content' => 'test',
            'tickets_id' => $tickets_id,
        ]));

        // Create a solution
        $this->assertGreaterThan(0, $solution_id = $solution->add([
            'name' => __FUNCTION__,
            'content' => 'test',
            'itemtype' => 'Ticket',
            'items_id' => $tickets_id,
        ]));

        // Create a validation
        $this->assertGreaterThan(0, $validation_id = $validation->add([
            'name' => __FUNCTION__,
            'content' => 'test',
            'tickets_id' => $tickets_id,
            'itemtype_target' => 'User',
            'items_id_target' => 2,
        ]));

        // Create a document
        $this->assertGreaterThan(0, $document_id = $document->add([
            'name' => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]));

        // Link the document to the ticket
        $this->assertGreaterThan(0, $document_item_id = $document_item->add([
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'documents_id' => $document_id,
            'itemtype' => 'Ticket',
            'items_id' => $tickets_id,
        ]));

        // Need to login to use the API
        $this->login('glpi', 'glpi');

        // Try to change the parent of the followup
        $request = new Request('PATCH', "/Assistance/Ticket/$tickets_id/Timeline/Followup/$fup_id");
        $request->setParameter('itemtype', 'Change');
        $request->setParameter('items_id', $tickets_id + 1);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });
        // verify the parent was not changed
        $this->api->call(new Request('GET', "/Assistance/Ticket/$tickets_id/Timeline/Followup/$fup_id"), function ($call) use ($tickets_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
            $call->response->jsonContent(function ($content) use ($tickets_id) {
                $this->assertEquals($tickets_id, $content['items_id']);
                $this->assertEquals('Ticket', $content['itemtype']);
            });
        });

        // Try to change the parent of the task
        $request = new Request('PATCH', "/Assistance/Ticket/$tickets_id/Timeline/Task/$task_id");
        $request->setParameter('tickets_id', $tickets_id + 1);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });
        // verify the parent was not changed
        $this->api->call(new Request('GET', "/Assistance/Ticket/$tickets_id/Timeline/Task/$task_id"), function ($call) use ($tickets_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
            $call->response->jsonContent(function ($content) use ($tickets_id) {
                $this->assertEquals($tickets_id, $content['tickets_id']);
            });
        });

        // Try to change the parent of the solution
        $request = new Request('PATCH', "/Assistance/Ticket/$tickets_id/Timeline/Solution/$solution_id");
        $request->setParameter('itemtype', 'Change');
        $request->setParameter('items_id', $tickets_id + 1);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });
        // verify the parent was not changed
        $this->api->call(new Request('GET', "/Assistance/Ticket/$tickets_id/Timeline/Solution/$solution_id"), function ($call) use ($tickets_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
            $call->response->jsonContent(function ($content) use ($tickets_id) {
                $this->assertEquals($tickets_id, $content['items_id']);
                $this->assertEquals('Ticket', $content['itemtype']);
            });
        });

        // Try to change the parent of the validation
        $request = new Request('PATCH', "/Assistance/Ticket/$tickets_id/Timeline/Validation/$validation_id");
        $request->setParameter('tickets_id', $tickets_id + 1);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });
        // verify the parent was not changed
        $this->api->call(new Request('GET', "/Assistance/Ticket/$tickets_id/Timeline/Validation/$validation_id"), function ($call) use ($tickets_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
            $call->response->jsonContent(function ($content) use ($tickets_id) {
                $this->assertEquals($tickets_id, $content['tickets_id']);
            });
        });

        // Try to change the parent of the document
        $request = new Request('PATCH', "/Assistance/Ticket/$tickets_id/Timeline/Document/$document_item_id");
        $request->setParameter('itemtype', 'Change');
        $request->setParameter('items_id', $tickets_id + 1);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });
        // verify the parent was not changed
        $this->api->call(new Request('GET', "/Assistance/Ticket/$tickets_id/Timeline/Document/$document_item_id"), function ($call) use ($tickets_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
            $call->response->jsonContent(function ($content) use ($tickets_id) {
                $this->assertEquals($tickets_id, $content['items_id']);
                $this->assertEquals('Ticket', $content['itemtype']);
            });
        });
    }

    public function testCRUDNoRights()
    {
        /** @var array<class-string<CommonITILObject>, string> $itil_types */
        $itil_types = [
            Ticket::class => 'Ticket',
            Change::class => 'Change',
            Problem::class => 'Problem',
        ];

        foreach ($itil_types as $itil_type => $schema_name) {
            $this->api->autoTestCRUDNoRights(
                endpoint: '/Assistance/' . $schema_name,
                itemtype: $itil_type,
                items_id: getItemByTypeName($itil_type, strtolower('_' . $itil_type . '01'), true),
                deny_read: static function () use ($itil_type) {
                    // Access on validations affects `canView` of ITIL objects
                    $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
                    $_SESSION['glpiactiveprofile'][ChangeValidation::$rightname] = 0;
                    $_SESSION['glpiactiveprofile'][$itil_type::$rightname] = 0;
                }
            );
        }
    }

    public function testRestrictedRead()
    {
        $func_name = __FUNCTION__;
        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $_SESSION['glpiactiveprofile'][TicketValidation::$rightname] = 0;
        $_SESSION['glpiactiveprofile'][ChangeValidation::$rightname] = 0;

        /** @var class-string<CommonITILObject> $itil_type */
        foreach ([Change::class, Problem::class, Ticket::class] as $itil_type) {
            $this->createItems($itil_type, [
                [
                    'name' => $func_name . '1',
                    'content' => 'test',
                    'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                ],
                [
                    'name' => $func_name . '2',
                    'content' => 'test',
                    'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                    'users_id_recipient' => 1, // Some other user
                    '_users_id_requester' => 1, // Some other user
                    '_skip_auto_assign' => 1,
                ],
                [
                    'name' => $func_name . '3',
                    'content' => 'test',
                    'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                    'users_id_recipient' => 1, // Some other user,
                    '_users_id_requester' => $_SESSION['glpiID'],
                    '_skip_auto_assign' => 1,
                ],
                [
                    'name' => $func_name . '4',
                    'content' => 'test',
                    'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                    'users_id_recipient' => 1, // Some other user,
                    '_groups_id_requester' => getItemByTypeName('Group', '_test_group_1', true),
                    '_skip_auto_assign' => 1,
                ],
                [
                    'name' => $func_name . '5',
                    'content' => 'test',
                    'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                    'users_id_recipient' => 1, // Some other user,
                    '_groups_id_requester' => getItemByTypeName('Group', '_test_group_2', true), // Some other group,
                    '_skip_auto_assign' => 1,
                ],
                [
                    'name' => $func_name . '6',
                    'content' => 'test',
                    'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                    'users_id_recipient' => 1, // Some other user
                    '_users_id_requester' => 1,
                    '_users_id_assign' => $_SESSION['glpiID'],
                    '_skip_auto_assign' => 1,
                ],
                [
                    'name' => $func_name . '7',
                    'content' => 'test',
                    'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                    'users_id_recipient' => 1, // Some other user
                    '_users_id_requester' => 1,
                    '_groups_id_assign' => getItemByTypeName('Group', '_test_group_1', true),
                    '_skip_auto_assign' => 1,
                ],
                [
                    'name' => $func_name . '8',
                    'content' => 'test',
                    'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                    'status' => CommonITILObject::ASSIGNED,
                    'users_id_recipient' => 1, // Some other user
                    '_users_id_requester' => 1, // Some other user
                    '_skip_auto_assign' => 1,
                ],
            ]);
            $_SESSION['glpigroups'][] = getItemByTypeName('Group', '_test_group_1', true); // Group of the ticket
            $request = new Request('GET', '/Assistance/' . $itil_type);
            $request->setParameter('filter', ['name=ilike=' . $func_name . '*']);

            if ($itil_type !== Ticket::class) {
                $_SESSION['glpiactiveprofile'][$itil_type::$rightname] = CommonITILObject::READMY;
                $this->api->call($request, function ($call) use ($func_name) {
                    /** @var \HLAPICallAsserter $call */
                    $call->response
                        ->isOK()
                        ->jsonContent(function ($content) use ($func_name) {
                            $readable_names = array_column($content, 'name');
                            $this->assertCount(5, $readable_names);
                            $this->assertContains($func_name . '1', $readable_names);
                            $this->assertContains($func_name . '3', $readable_names);
                            $this->assertContains($func_name . '4', $readable_names);
                            $this->assertContains($func_name . '6', $readable_names);
                            $this->assertContains($func_name . '7', $readable_names);
                        });
                });
            } else {
                $_SESSION['glpiactiveprofile'][$itil_type::$rightname] = CommonITILObject::READMY;
                $this->api->call($request, function ($call) use ($func_name) {
                    /** @var \HLAPICallAsserter $call */
                    $call->response
                        ->isOK()
                        ->jsonContent(function ($content) use ($func_name) {
                            $readable_names = array_column($content, 'name');
                            $this->assertCount(2, $readable_names);
                            $this->assertContains($func_name . '1', $readable_names);
                            $this->assertContains($func_name . '3', $readable_names);
                        });
                });

                $_SESSION['glpiactiveprofile'][$itil_type::$rightname] = Ticket::READGROUP;
                $this->api->call($request, function ($call) use ($func_name) {
                    /** @var \HLAPICallAsserter $call */
                    $call->response
                        ->isOK()
                        ->jsonContent(function ($content) use ($func_name) {
                            $readable_names = array_column($content, 'name');
                            $this->assertContains($func_name . '4', $readable_names);
                        });
                });

                $_SESSION['glpiactiveprofile'][$itil_type::$rightname] = Ticket::OWN;
                $this->api->call($request, function ($call) use ($func_name) {
                    /** @var \HLAPICallAsserter $call */
                    $call->response
                        ->isOK()
                        ->jsonContent(function ($content) use ($func_name) {
                            $readable_names = array_column($content, 'name');
                            $this->assertCount(1, $readable_names);
                            $this->assertContains($func_name . '6', $readable_names);
                        });
                });

                $_SESSION['glpiactiveprofile'][$itil_type::$rightname] = Ticket::READASSIGN;
                $this->api->call($request, function ($call) use ($func_name) {
                    /** @var \HLAPICallAsserter $call */
                    $call->response
                        ->isOK()
                        ->jsonContent(function ($content) use ($func_name) {
                            $readable_names = array_column($content, 'name');
                            $this->assertCount(2, $readable_names);
                            $this->assertContains($func_name . '6', $readable_names);
                            $this->assertContains($func_name . '7', $readable_names);
                        });
                });

                $_SESSION['glpiactiveprofile'][$itil_type::$rightname] = Ticket::READNEWTICKET;
                $this->api->call($request, function ($call) use ($func_name) {
                    /** @var \HLAPICallAsserter $call */
                    $call->response
                        ->isOK()
                        ->jsonContent(function ($content) use ($func_name) {
                            $readable_names = array_column($content, 'name');
                            $this->assertCount(5, $readable_names);
                            $this->assertContains($func_name . '1', $readable_names);
                            $this->assertContains($func_name . '2', $readable_names);
                            $this->assertContains($func_name . '3', $readable_names);
                            $this->assertContains($func_name . '4', $readable_names);
                            $this->assertContains($func_name . '5', $readable_names);
                        });
                });
            }
        }
    }
}
