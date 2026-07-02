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

use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Api\HL\Router;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use Project;
use ProjectTask;

class GraphQLControllerTest extends HLAPITestCase
{
    public function testGraphQLListSchemas()
    {
        $this->login();

        $this->graphql->call('query { __schema { types { name } } }', function ($call) {
            $call->response
                ->isOK()
                ->data('__schema', function ($schema) {
                    $types = $schema['types'];
                    $this->assertGreaterThan(150, $types);
                    $some_expected = ['Computer', 'Ticket', 'User', 'PrinterModel', 'FirmwareType'];
                    $found = [];
                    foreach ($types as $type) {
                        $this->assertArrayNotHasKey('description', $type);
                        $this->assertArrayNotHasKey('fields', $type);
                        $this->assertNotEmpty($type['name']);
                        if (in_array($type['name'], $some_expected, true)) {
                            $found[] = $type['name'];
                        }
                    }
                    $this->assertCount(count($some_expected), $found);
                });
        });

        $this->graphql->call('query { __schema { types { name description } } }', function ($call) {
            $call->response
                ->isOK()
                ->data('__schema', function ($schema) {
                    foreach ($schema['types'] as $type) {
                        $this->assertArrayHasKey('name', $type);
                        $this->assertArrayHasKey('description', $type);
                        $this->assertArrayNotHasKey('fields', $type);
                    }
                });
        });

        $this->graphql->call('query { __schema { types { name fields { type { name } } } } }', function ($call) {
            $call->response
                ->isOK()
                ->data('__schema', function ($schema) {
                    foreach ($schema['types'] as $type) {
                        $this->assertArrayHasKey('name', $type);
                        $this->assertArrayNotHasKey('description', $type);
                        $this->assertArrayHasKey('fields', $type);
                    }
                });
        });
    }

    public function testGetComputer()
    {
        $this->login();

        $this->graphql->call('query { Computer(id: 1) { id name } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Computer', function ($computers) {
                    $this->assertCount(1, $computers);
                    $this->assertEquals(1, $computers[0]['id']);
                    $this->assertEquals('_test_pc01', $computers[0]['name']);
                });
        });
    }

    public function testGetComputers()
    {
        $this->login();

        $this->graphql->call('query { Computer { id name } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Computer', function ($computers) {
                    $this->assertGreaterThan(2, count($computers));
                });
        });
    }

    public function testGetComputersWithFilter()
    {
        $this->login();

        $this->graphql->call('query { Computer(filter: "name=like=\'*_test_pc*\'") { id name } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Computer', function ($computers) {
                    $this->assertCount(9, $computers);
                });
        });
    }

    public function testFullSchemaReplacement()
    {
        $this->login();
        $this->loginWeb();

        // Create some data just to ensure there is something to return
        $printer_model = new \PrinterModel();
        $this->assertGreaterThan(0, $printer_model->add([
            'name' => '_test_printer_model',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]));
        $this->assertGreaterThan(0, (new \CartridgeItem_PrinterModel())->add([
            'cartridgeitems_id' => \getItemByTypeName(\CartridgeItem::class, '_test_cartridgeitem01', true),
            'printermodels_id'  => $printer_model->getID(),
        ]));

        // product_number is not available this way via the REST API, but should be available here as the partial schema gets replaced by the full schema
        $this->graphql->call('query { CartridgeItem { id name printer_models { name product_number } } }', function ($call) {
            $call->response
                ->isOK()
                ->data('CartridgeItem', function ($items) {
                    $this->assertArrayHasKey('id', $items[0]);
                    $this->assertArrayHasKey('name', $items[0]);
                    $this->assertGreaterThan(0, count($items[0]['printer_models']));
                    $this->assertArrayHasKey('name', $items[0]['printer_models'][0]);
                    $this->assertArrayHasKey('product_number', $items[0]['printer_models'][0]);
                });
        });
    }

    /**
     * Tests a case where there are scalar joins inside an already-joined field (status in this case).
     * @return void
     */
    public function testGetStateVisibilities()
    {
        $state = new \State();
        $this->assertGreaterThan(0, $states_id = $state->add([
            'name' => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'is_visible_computer' => 1,
            'is_visible_monitor' => 0,
        ]));
        $computer = new \Computer();
        $this->assertGreaterThan(0, $computers_id = $computer->add([
            'name' => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'states_id' => $states_id,
        ]));

        $this->login();

        $this->graphql->call("query { Computer(id: $computers_id) { id name status { name visibilities { computer monitor } } } }", function ($call) {
            $call->response
                ->isOK()
                ->data('Computer', function ($computers) {
                    $this->assertCount(1, $computers);
                    $this->assertArrayHasKey('id', $computers[0]);
                    $this->assertArrayHasKey('name', $computers[0]);
                    $this->assertArrayHasKey('status', $computers[0]);
                    $this->assertArrayHasKey('name', $computers[0]['status']);
                    $this->assertTrue($computers[0]['status']['visibilities']['computer']);
                    $this->assertFalse($computers[0]['status']['visibilities']['monitor']);
                });
        });
    }

    public function testGetDirectlyWithoutRight()
    {
        global $DB;

        $this->assertTrue($DB->insert('glpi_tickets', [
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]));
        $tickets_id = $DB->insertId();

        $this->loginWeb();
        $this->graphql->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        $_SESSION['glpi_use_mode'] = 2;

        // Can see no tickets
        $_SESSION['glpiactiveprofile']['ticket'] = 0;
        $this->graphql->call('query { Ticket { id name } }', function ($call) {
            $call->response->isCompletelyError();
        });

        // Can only see my own tickets
        $_SESSION['glpiactiveprofile']['ticket'] = READ;

        $this->graphql->call('query { Ticket { id name } }', function ($call) use ($tickets_id) {
            $call->response
                ->isOK()
                ->data('Ticket', function ($tickets) use ($tickets_id) {
                    $this->assertNotContains($tickets_id, array_column($tickets, 'id'));
                });
        });
        $this->graphql->call('query { Ticket(id: ' . $tickets_id . ') { id name } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Ticket', function ($tickets) {
                    $this->assertEmpty($tickets);
                });
        });
    }

    public function testGetTicketIndirectlyWithoutRight()
    {
        global $DB;

        $this->assertTrue($DB->insert('glpi_tickets', [
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]));
        $this->assertTrue($DB->update('glpi_entities', [
            'comment' => 'Should not be visible',
        ], [
            'id' => $this->getTestRootEntity(true),
        ]));

        $this->loginWeb();
        $this->graphql->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        $_SESSION['glpi_use_mode'] = 2;

        // Can see no entities
        $_SESSION['glpiactiveprofile']['entity'] = 0;
        $this->graphql->call('query { Ticket { id name entity { id name comment } } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Ticket', function ($tickets) {
                    foreach ($tickets as $ticket) {
                        // The name is part of the partial schema, so it is always visible
                        $this->assertNotNull($ticket['entity']['name']);
                        // The comment comes from the full schema which the user has no right to see
                        $this->assertNull($ticket['entity']['comment']);
                    }
                });
        });
    }

    public function testGraphQLScope()
    {
        $this->login(api_options: ['scope' => 'api']);
        $request = new Request('POST', '/GraphQL', [], 'query { Ticket { id name } }');
        $this->api->call($request, function ($call) {
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->login(api_options: ['scope' => 'graphql']);
        $this->graphql->call('query { Ticket { id name } }', function ($call) {
            $call->response->isOK();
        });
    }

    public function testExtractQueryFromBodyWithApplicationGraphQLContentType()
    {
        $this->login();

        $query = 'query { Computer(id: 1) { id name } }';
        $request = new Request('POST', '/GraphQL', ['Content-Type' => 'application/graphql'], $query);
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content['data']);
                    $this->assertCount(1, $content['data']['Computer']);
                    $this->assertEquals(1, $content['data']['Computer'][0]['id']);
                    $this->assertEquals('_test_pc01', $content['data']['Computer'][0]['name']);
                });
        });
    }

    public function testExtractQueryFromBodyWithApplicationJsonContentType()
    {
        $this->login();

        $query = 'query { Computer(id: 1) { id name } }';
        $body = json_encode(['query' => $query]);
        $request = new Request('POST', '/GraphQL', ['Content-Type' => 'application/json'], $body);
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content['data']);
                    $this->assertCount(1, $content['data']['Computer']);
                    $this->assertEquals(1, $content['data']['Computer'][0]['id']);
                    $this->assertEquals('_test_pc01', $content['data']['Computer'][0]['name']);
                });
        });
    }

    public function testExtractQueryFromBodyWithOtherContentTypeFallback()
    {
        $this->login();

        $query = 'query { Computer(id: 1) { id name } }';
        $request = new Request('POST', '/GraphQL', ['Content-Type' => 'text/plain'], $query);
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content['data']);
                    $this->assertCount(1, $content['data']['Computer']);
                    $this->assertEquals(1, $content['data']['Computer'][0]['id']);
                    $this->assertEquals('_test_pc01', $content['data']['Computer'][0]['name']);
                });
        });
    }

    private function getCompleteFieldsRequestForSchema(array $properties): string
    {
        $fields_query_part = '';
        // Convert OpenAPI properties to GraphQL fields request
        foreach ($properties as $property_name => $property_info) {
            if ($property_info['type'] === 'object' && !empty($property_info['properties'])) {
                // Nested object, recurse
                $fields_query_part .= $property_name . ' { ' . $this->getCompleteFieldsRequestForSchema($property_info['properties']) . ' } ';
            } elseif ($property_info['type'] === 'array' && !empty($property_info['items']['properties'])) {
                // Array of objects, recurse
                $fields_query_part .= $property_name . ' { ' . $this->getCompleteFieldsRequestForSchema($property_info['items']['properties']) . ' } ';
            } else {
                // Scalar field
                $fields_query_part .= $property_name . ' ';
            }
        }

        return $fields_query_part;
    }

    /**
     * Test getting every field of every schema to ensure field validity.
     * In some cases, we may miss testing a schema completely at the REST API level (or it may not even have an endpoint yet).
     * This makes sure that all fields are valid at least to the point they do not trigger an error like a SQL Unknown Column error.
     * @return void
     */
    public function testSchemaFieldValidity()
    {
        $this->login();
        $router = Router::getInstance();
        $controllers = $router->getControllers();

        $schemas_errors = [];
        foreach ($controllers as $controller) {
            $schemas = $controller::getKnownSchemas(null);
            foreach ($schemas as $schema_name => $schema) {
                if (!isset($schema['x-itemtype']) || str_starts_with($schema_name, '_')) {
                    continue;
                }
                $query = "query { $schema_name(limit: 1) { " . $this->getCompleteFieldsRequestForSchema($schema['properties']) . '} }';
                $request = new Request('POST', '/GraphQL', [
                    'X-Debug-Mode' => '1', // Debug mode allows seeing more information in errors
                ], $query);
                $this->api->call($request, function ($call) use ($schema_name, &$schemas_errors) {
                    $call->response
                        ->isOK()
                        ->jsonContent(function ($content) use ($schema_name, &$schemas_errors) {
                            if (isset($content['errors'])) {
                                $schemas_errors[] = "Schema $schema_name has errors: " . json_encode($content['errors']);
                            }
                        });
                });
            }
        }

        //TODO v3 of the API should fix the user "name"/"username" properties conflict. The full schema uses "username" while the partial schema uses "name".
        //Ignore them for now.
        $schemas_errors = array_filter($schemas_errors, function ($error) {
            return !str_contains($error, 'Cannot query field \"name\" on type \"User\"');
        });
        // An assertion to force the fix of the above in case it is forgotten when the API hits v3
        if (version_compare(Router::API_VERSION, '3.0.0', '>=')) {
            $this->fail('The User "name"/"username" conflict should be fixed in API v3');
        }

        $this->assertEmpty($schemas_errors, "Schemas with errors when querying with GraphQL: \n" . implode("\n", $schemas_errors));
    }

    /**
     * Tests a GraphQL query with filters that reference properties not directly in the main schema.
     * @return void
     */
    public function testIndirectRSQLFilters(): void
    {
        $this->login();

        $this->graphql->call('query { Project(filter: "name==_project01;entity.level==2") { id name } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Project', function ($projects) {
                    $this->assertCount(1, $projects);
                    $this->assertEquals('_project01', $projects[0]['name']);
                });
        });

        $projecttask = new ProjectTask();
        $parent_task = $projecttask->add([
            'name' => 'ParentTask',
            'projects_id' => getItemByTypeName(Project::class, '_project01', true),
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $projecttask->add([
            'name' => 'ChildTask',
            'projects_id' => getItemByTypeName(Project::class, '_project01', true),
            'projecttasks_id' => $parent_task,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);

        $this->graphql->call('query { Project(filter: "name==_project01;tasks.parent_task.name==test") { id name } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Project', function ($projects) {
                    $this->assertCount(0, $projects);
                });
        });

        $this->graphql->call('query { Project(filter: "name==_project01;tasks.parent_task.name==ParentTask") { id name } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Project', function ($projects) {
                    $this->assertCount(1, $projects);
                    $this->assertEquals('_project01', $projects[0]['name']);
                });
        });
    }
}
