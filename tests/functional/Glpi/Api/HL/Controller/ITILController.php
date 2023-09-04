<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

class ITILController extends \HLAPITestCase
{
    public function testCreateGetUpdateDelete()
    {
        $this->login();
        $itil_types = ['Ticket', 'Change', 'Problem'];
        $func_name = __FUNCTION__;

        foreach ($itil_types as $itil_type) {
            // Create
            $request = new Request('POST', '/Assistance/' . $itil_type);
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
                        $this->array($headers)->hasKey('Location');
                        $this->string($headers['Location'])->isNotEmpty();
                        $this->string($headers['Location'])->contains('/Assistance/' . $itil_type);
                        $itil_base_path = $headers['Location'];
                    });
            });

            // Create
            $request = new Request('POST', $itil_base_path . '/Timeline/Followup');
            $request->setParameter('content', 'test');
            $new_item_location = null;
            $this->api->call($request, function ($call) use ($itil_base_path, &$new_item_location) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->headers(function ($headers) use ($itil_base_path, &$new_item_location) {
                        $this->array($headers)->hasKey('Location');
                        $this->string($headers['Location'])->isNotEmpty();
                        $this->string($headers['Location'])->contains($itil_base_path . '/Timeline/Followup/');
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

            // Delete
            $this->api->call(new Request('DELETE', $new_item_location), function ($call) {
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
                        $this->array($headers)->hasKey('Location');
                        $this->string($headers['Location'])->isNotEmpty();
                        $this->string($headers['Location'])->contains('/Assistance/' . $itil_type);
                        $itil_base_path = $headers['Location'];
                    });
            });

            // Create
            $request = new Request('POST', $itil_base_path . '/Timeline/Task');
            $request->setParameter('content', 'test');
            $new_item_location = null;
            $this->api->call($request, function ($call) use ($itil_base_path, &$new_item_location) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->headers(function ($headers) use ($itil_base_path, &$new_item_location) {
                        $this->array($headers)->hasKey('Location');
                        $this->string($headers['Location'])->isNotEmpty();
                        $this->string($headers['Location'])->contains($itil_base_path . '/Timeline/Task/');
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

            // Delete
            $this->api->call(new Request('DELETE', $new_item_location), function ($call) {
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
                        $this->array($headers)->hasKey('Location');
                        $this->string($headers['Location'])->isNotEmpty();
                        $this->string($headers['Location'])->contains('/Assistance/' . $itil_type);
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
                        $this->array($content)->size->isGreaterThanOrEqualTo(4);
                        // Ensure there are 2 items with type=Task and 2 items with type=Followup
                        $remaining_matches = [
                            'Task' => ['test0' => 1, 'test1' => 1],
                            'Followup' => ['test0' => 1, 'test1' => 1],
                        ];
                        $tasks = array_filter($content, static function ($item) {
                            return $item['type'] === 'Task';
                        });
                        $this->array($tasks)->size->isIdenticalTo(2);
                        foreach ($tasks as $task) {
                            unset($remaining_matches['Task'][$task['item']['content']]);
                        }

                        $fups = array_filter($content, static function ($item) {
                            return $item['type'] === 'Followup';
                        });
                        $this->array($fups)->size->isIdenticalTo(2);
                        foreach ($fups as $fup) {
                            unset($remaining_matches['Followup'][$fup['item']['content']]);
                        }

                        $this->array($remaining_matches['Task'])->isEmpty();
                        $this->array($remaining_matches['Followup'])->isEmpty();
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
            $this->integer($templates_id = $template->add([
                'name' => __FUNCTION__,
                'content' => 'test',
                'is_recursive' => 1,
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]))->isGreaterThan(0);

            // Create a recurring ITIL item
            $request = new Request('POST', '/Assistance/Recurring' . $itil_type);
            $request->setParameter('name', $func_name);
            $request->setParameter('template', $templates_id);
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
                    });
            });

            // Delete
            $this->api->call(new Request('DELETE', $new_item_location), function ($call) {
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

    /**
     * Make sure users cannot change the parent of a timeline subitem
     * @return void
     */
    public function testBlockOverridingParentItem()
    {
        $ticket = new \Ticket();
        $this->integer($tickets_id = $ticket->add([
            'name' => __FUNCTION__,
            'content' => 'test',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]))->isGreaterThan(0);

        $fup = new \ITILFollowup();
        $task = new \TicketTask();
        $solution = new \ITILSolution();
        $validation = new \TicketValidation();
        $document = new \Document();
        $document_item = new \Document_Item();

        // Create a followup
        $this->integer($fup_id = $fup->add([
            'name' => __FUNCTION__,
            'content' => 'test',
            'itemtype' => 'Ticket',
            'items_id' => $tickets_id,
        ]))->isGreaterThan(0);

        // Create a task
        $this->integer($task_id = $task->add([
            'name' => __FUNCTION__,
            'content' => 'test',
            'tickets_id' => $tickets_id,
        ]))->isGreaterThan(0);

        // Create a solution
        $this->integer($solution_id = $solution->add([
            'name' => __FUNCTION__,
            'content' => 'test',
            'itemtype' => 'Ticket',
            'items_id' => $tickets_id,
        ]))->isGreaterThan(0);

        // Create a validation
        $this->integer($validation_id = $validation->add([
            'name' => __FUNCTION__,
            'content' => 'test',
            'tickets_id' => $tickets_id,
            'itemtype_target' => 'User',
            'items_id_target' => 2
        ]))->isGreaterThan(0);

        // Create a document
        $this->integer($document_id = $document->add([
            'name' => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]))->isGreaterThan(0);

        // Link the document to the ticket
        $this->integer($document_item_id = $document_item->add([
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'documents_id' => $document_id,
            'itemtype' => 'Ticket',
            'items_id' => $tickets_id,
        ]))->isGreaterThan(0);

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
                $this->integer($content['items_id'])->isIdenticalTo($tickets_id);
                $this->string($content['itemtype'])->isIdenticalTo('Ticket');
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
                $this->integer($content['tickets_id'])->isIdenticalTo($tickets_id);
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
                $this->integer($content['items_id'])->isIdenticalTo($tickets_id);
                $this->string($content['itemtype'])->isIdenticalTo('Ticket');
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
                $this->integer($content['tickets_id'])->isIdenticalTo($tickets_id);
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
                $this->integer($content['items_id'])->isIdenticalTo($tickets_id);
                $this->string($content['itemtype'])->isIdenticalTo('Ticket');
            });
        });
    }
}
