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

use Blacklist;
use BlacklistedMailContent;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Dropdown\DropdownDefinition;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use Holiday;
use Location;
use NetworkPortType;
use PCIVendor;
use Session;
use State;
use USBVendor;
use ValidationStep;

class DropdownControllerTest extends HLAPITestCase
{
    public function testIndex()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Dropdowns'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertGreaterThanOrEqual(4, count($content));
                    foreach ($content as $asset) {
                        $this->assertNotEmpty($asset['itemtype']);
                        $this->assertNotEmpty($asset['name']);
                        $this->assertMatchesRegularExpression('/\/Dropdowns\/\w+/', $asset['href']);
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
                'vendorid' => 'TST',
                'date_begin' => '2024-01-01 10:00:00',
                'date_end' => '2024-12-31 18:00:00',
                'min_required_approval_percent' => 100,
                'iftype' => 9999,
            ],
            [
                'name' => 'testAutoSearch_2',
                'entity' => $entity,
                'vendorid' => 'TST',
                'date_begin' => '2024-01-01 10:00:00',
                'date_end' => '2024-12-31 18:00:00',
                'min_required_approval_percent' => 100,
                'iftype' => 9999,
            ],
            [
                'name' => 'testAutoSearch_3',
                'entity' => $entity,
                'vendorid' => 'TST',
                'date_begin' => '2024-01-01 10:00:00',
                'date_end' => '2024-12-31 18:00:00',
                'min_required_approval_percent' => 100,
                'iftype' => 9999,
            ],
        ];
        $this->api->call(new Request('GET', '/Dropdowns'), function ($call) use ($dataset) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($dataset) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $dropdown) {
                        $this->api->autoTestSearch($dropdown['href'], $dataset);
                    }
                });
        });
    }

    public function testAutoCRUD()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Dropdowns'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $dropdown) {
                        if ($dropdown['itemtype'] === Holiday::class) {
                            continue;
                        }
                        $params = [];
                        if ($dropdown['itemtype'] === USBVendor::class || $dropdown['itemtype'] === PCIVendor::class) {
                            $params['vendorid'] = 'TST';
                        } elseif ($dropdown['itemtype'] === ValidationStep::class) {
                            $params['min_required_approval_percent'] = 100;
                        } elseif ($dropdown['itemtype'] === NetworkPortType::class) {
                            $params['iftype'] = 9999;
                        }
                        $this->api->autoTestCRUD($dropdown['href'], $params);
                    }
                });
        });
    }

    public function testCRUDNoRights()
    {
        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        $this->api->call(new Request('GET', '/Dropdowns'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $dropdown) {
                        if ($dropdown['itemtype'] === BlacklistedMailContent::class || $dropdown['itemtype'] === Holiday::class) {
                            continue;
                        }
                        $create_request = new Request('POST', $dropdown['href']);
                        $create_request->setParameter('name', 'testCRUDNoRights' . random_int(0, 10000));
                        $create_request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
                        if ($dropdown['itemtype'] === USBVendor::class || $dropdown['itemtype'] === PCIVendor::class) {
                            $create_request->setParameter('vendorid', 'TST');
                        } elseif ($dropdown['itemtype'] === ValidationStep::class) {
                            $create_request->setParameter('min_required_approval_percent', 100);
                        } elseif ($dropdown['itemtype'] === NetworkPortType::class) {
                            $create_request->setParameter('iftype', 9999);
                        }
                        $new_location = null;
                        $new_items_id = null;
                        $this->api->call($create_request, function ($call) use (&$new_location, &$new_items_id) {
                            $call->response
                                ->isOK()
                                ->headers(function ($headers) use (&$new_location) {
                                    $new_location = $headers['Location'];
                                })
                                ->jsonContent(function ($content) use (&$new_items_id) {
                                    $new_items_id = $content['id'];
                                });
                        });
                        if ($dropdown['itemtype'] === Blacklist::class) {
                            $this->api->autoTestCRUDNoRights(
                                endpoint: $dropdown['href'],
                                itemtype: $dropdown['itemtype'],
                                items_id: (int) $new_items_id,
                                deny_create: static function () {
                                    $_SESSION['glpiactiveprofile'][Blacklist::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
                                },
                                deny_purge: static function () {
                                    $_SESSION['glpiactiveprofile'][Blacklist::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
                                }
                            );
                        } else {
                            $this->api->autoTestCRUDNoRights(
                                endpoint: $dropdown['href'],
                                itemtype: $dropdown['itemtype'],
                                items_id: (int) $new_items_id,
                                create_params: [
                                    'name' => __FUNCTION__,
                                    'iftype' => 0,
                                ]
                            );
                        }
                    }
                });
        });
    }

    public function testSearchParentEntityItem()
    {
        $this->loginWeb();
        $this->createItem(Location::class, [
            'name' => 'recursive_location',
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);
        $this->assertTrue(Session::changeActiveEntities(getItemByTypeName('Entity', '_test_child_1', true)));
        $this->login();
        $this->api->call(new Request('GET', '/Dropdowns/Location', [
            'GLPI-Entity' => getItemByTypeName('Entity', '_test_child_1', true),
        ]), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    $found = false;
                    foreach ($content as $dropdown) {
                        if ($dropdown['name'] === 'recursive_location') {
                            $found = true;
                            break;
                        }
                    }
                    $this->assertTrue($found, 'Item from parent entity not found in dropdown results');
                });
        });
    }

    public function testWriteStateVisibilities()
    {
        $state = $this->createItem(State::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'is_visible_computer' => 1,
            'is_visible_monitor' => 0,
            'is_visible_phone' => 1,
            'is_visible_printer' => 1,
        ]);

        $this->login();

        $this->api->call(new Request('GET', '/Dropdowns/State/' . $state->getID()), function ($call) {
            $call->response->isOK()
                ->jsonContent(function ($content) {
                    $this->assertTrue($content['visibilities']['computer']);
                    $this->assertFalse($content['visibilities']['monitor']);
                    $this->assertTrue($content['visibilities']['phone']);
                    $this->assertTrue($content['visibilities']['printer']);
                });
        });

        $update_request = new Request('PATCH', '/Dropdowns/State/' . $state->getID());
        $update_request->setParameter('visibilities', [
            'computer' => false,
            'monitor' => true,
            'phone' => false,
            'printer' => false,
        ]);
        $this->api->call($update_request, function ($call) {
            $call->response->isOK();
        });

        $this->api->call(new Request('GET', '/Dropdowns/State/' . $state->getID()), function ($call) {
            $call->response->isOK()
                ->jsonContent(function ($content) {
                    $this->assertFalse($content['visibilities']['computer']);
                    $this->assertTrue($content['visibilities']['monitor']);
                    $this->assertFalse($content['visibilities']['phone']);
                    $this->assertFalse($content['visibilities']['printer']);
                });
        });
    }

    public function testCRUDDropdownDefinition()
    {
        $create_input = [
            'system_name' => 'Color',
            'label' => 'Color',
            'icon' => 'ti-palette',
            'comment' => 'Test comment',
            'is_active' => true,
            'translations' => '{"fr_FR":{"one":"Couleur","many":"Couleurs","other":"Couleurs"}}',
        ];

        $this->login();

        // Create
        $request = new Request('POST', '/Dropdowns/CustomDefinition');
        foreach ($create_input as $key => $value) {
            $request->setParameter($key, $value);
        }
        $definition_location = null;
        $this->api->call($request, function ($call) use (&$definition_location) {
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$definition_location) {
                    $this->assertNotEmpty($headers['Location']);
                    $definition_location = $headers['Location'];
                });
        });

        $check_content = function (array $content) use ($create_input) {
            foreach ($create_input as $key => $value) {
                if ($key === 'translations') {
                    $this->assertEquals(json_decode($value, true), json_decode($content[$key], true));
                } else {
                    $this->assertEquals($value, $content[$key]);
                }
            }
        };

        // Get
        $this->api->call(new Request('GET', $definition_location), function ($call) use ($check_content) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($check_content) {
                    $check_content($content);
                });
        });

        // Search
        $request = new Request('GET', '/Dropdowns/CustomDefinition');
        $request->setParameter('filter', 'system_name==Color');
        $this->api->call($request, function ($call) use ($check_content) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($check_content) {
                    $this->assertCount(1, $content);
                    $check_content($content[0]);
                });
        });

        // Update
        $update_input = [
            'label' => 'Updated Color',
            'is_active' => false,
        ];
        $request = new Request('PATCH', $definition_location);
        foreach ($update_input as $key => $value) {
            $request->setParameter($key, $value);
        }
        $this->api->call($request, function ($call) {
            $call->response->isOK();
        });

        // Get after update
        $this->api->call(new Request('GET', $definition_location), function ($call) use ($update_input) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($update_input) {
                    $this->assertEquals($update_input['label'], $content['label']);
                    $this->assertEquals($update_input['is_active'], $content['is_active']);
                });
        });

        // Cannot change system name
        $request = new Request('PATCH', $definition_location);
        $request->setParameter('system_name', 'NewColor');
        $this->api->call($request, function ($call) {
            $call->response
                ->isNotOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('Failed to update item(s)', $content['title']);
                    $this->assertStringContainsString('The system name cannot be changed', $content['additional_messages'][0]['message']);
                });
        });

        // Delete
        $this->api->call(new Request('DELETE', $definition_location), function ($call) {
            $call->response->isOK();
        });

        // Ensure deleted
        $this->api->call(new Request('GET', $definition_location), function ($call) {
            $call->response->isNotFoundError();
        });
    }

    public function testCRUDNoRightsDropdownDefinition()
    {
        $definition = $this->initDropdownDefinition();

        $this->api->autoTestCRUDNoRights(
            endpoint: '/Dropdowns/CustomDefinition',
            itemtype: DropdownDefinition::class,
            items_id: $definition->getID(),
            deny_create: static function () {
                $_SESSION['glpiactiveprofile'][DropdownDefinition::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
            },
            deny_purge: static function () {
                $_SESSION['glpiactiveprofile'][DropdownDefinition::$rightname] = ALLSTANDARDRIGHT & ~UPDATE;
            },
            create_params: ['system_name' => 'NoRightsDefinition'],
        );
    }

    public function testDropdownDefinitionNotInIndex()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Dropdowns'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertNotContains(DropdownDefinition::class, array_column($content, 'itemtype'));
                });
        });
    }
}
