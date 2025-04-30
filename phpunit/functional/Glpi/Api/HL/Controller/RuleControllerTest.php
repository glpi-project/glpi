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
use PHPUnit\Framework\Attributes\DataProvider;

class RuleControllerTest extends \HLAPITestCase
{
    protected function getRuleCollections()
    {
        return [
            'Ticket', 'Change', 'Problem', 'Asset', 'ImportAsset', 'ImportEntity', 'Right',
            'Location', 'SoftwareCategory',
        ];
    }

    public function testAccess()
    {
        $this->login();
        $this->loginWeb();

        // Remove ticket rule permission
        $rule_ticket_right = $_SESSION['glpiactiveprofile']['rule_ticket'];
        $_SESSION['glpiactiveprofile']['rule_ticket'] = 0;

        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $this->api->call(new Request('GET', '/Rule/Collection'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertNotEmpty($content);
                    $has_ticket_rule = false;
                    foreach ($content as $collection) {
                        if ($collection['rule_type'] === 'Ticket') {
                            $has_ticket_rule = true;
                            break;
                        }
                    }
                    $this->assertFalse($has_ticket_rule);
                });
        }, false); // false here avoids initializing a new temporary session and instead uses the InternalAuthMiddleware

        // Restore ticket rule permission
        $_SESSION['glpiactiveprofile']['rule_ticket'] = $rule_ticket_right;

        $this->api->call(new Request('GET', '/Rule/Collection'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertNotEmpty($content);
                    $has_ticket_rule = false;
                    foreach ($content as $collection) {
                        if ($collection['rule_type'] === 'Ticket') {
                            $has_ticket_rule = true;
                            break;
                        }
                    }
                    $this->assertTrue($has_ticket_rule);
                });
        }, false); // false here avoids initializing a new temporary session and instead uses the InternalAuthMiddleware
    }

    public function testListCollections()
    {
        $this->login();

        $this->api->call(new Request('GET', '/Rule/Collection'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $types = array_column($content, 'rule_type');
                    $rule_collections = $this->getRuleCollections();
                    $this->assertCount(count($rule_collections), array_intersect($rule_collections, $types));
                });
        });
    }

    public static function listRuleCriteriaConditionsProvider()
    {
        return [
            [
                'type' => 'Asset',
                'conditions' => [
                    0 => [
                        'description' => 'is',
                        'fields' => ['_auto', 'comment', 'states_id'],
                    ],
                    11 => [
                        'description' => 'under',
                        'fields' => ['locations_id', 'states_id'],
                    ],
                ],
            ],
            [
                'type' => 'Ticket',
                'conditions' => [
                    0 => [
                        'description' => 'is',
                        'fields' => ['name', '_mailgate', '_x-priority'],
                    ],
                    6 => [
                        'description' => 'regular expression matches',
                        'fields' => ['name', '_mailgate', '_x-priority'],
                    ],
                    11 => [
                        'description' => 'under',
                        'fields' => ['_groups_id_of_requester', 'itilcategories_id'],
                    ],
                ],
            ],
            [
                'type' => 'ImportAsset',
                'conditions' => [
                    333 => [
                        'description' => 'is CIDR',
                        'fields' => ['ip'],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('listRuleCriteriaConditionsProvider')]
    public function testListRuleCriteriaConditions($type, $conditions)
    {
        $this->login();

        $this->api->call(new Request('GET', "/Rule/Collection/{$type}/CriteriaCondition"), function ($call) use ($conditions) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($conditions) {
                    $tested = [];
                    foreach ($conditions as $id => $condition) {
                        foreach ($content as $item) {
                            if ($item['id'] === $id) {
                                $this->assertEquals($condition['description'], $item['description']);
                                $this->assertCount(count($condition['fields']), array_intersect($condition['fields'], $item['fields']));
                                $tested[] = $id;
                            }
                        }
                    }
                    $this->assertCount(count($conditions), $tested);
                });
        });
    }

    public static function listRuleCriteriaCriteriaProvider()
    {
        return [
            [
                'type' => 'Asset',
                'criteria' => [
                    '_auto' => 'Automatic inventory',
                    '_itemtype' => 'Item type',
                    'comment' => 'Comments',
                    '_tag' => 'Agent > Inventory tag',
                    'users_id' => 'User',
                ],
            ],
            [
                'type' => 'Ticket',
                'criteria' => [
                    'name' => 'Title',
                    'itilcategories_id_code' => 'Code representing the ITIL category',
                    '_mailgate' => 'Mails receiver',
                    '_x-priority' => 'X-Priority email header',
                ],
            ],
            [
                'type' => 'ImportAsset',
                'criteria' => [
                    'entities_id' => 'Target entity for the asset',
                    'mac' => 'Asset > Network port > MAC',
                    'link_criteria_port' => 'General > Restrict criteria to same network port',
                ],
            ],
        ];
    }

    #[DataProvider('listRuleCriteriaCriteriaProvider')]
    public function testListRuleCriteriaCriteria($type, $criteria)
    {
        $this->login();

        $this->api->call(new Request('GET', "/Rule/Collection/{$type}/CriteriaCriteria"), function ($call) use ($criteria) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($criteria) {
                    $tested = [];
                    foreach ($criteria as $id => $name) {
                        foreach ($content as $item) {
                            if ($item['id'] === $id) {
                                $this->assertEquals($name, $item['name']);
                                $tested[] = $id;
                            }
                        }
                    }
                    $this->assertCount(count($criteria), $tested);
                });
        });
    }

    public static function listRuleActionTypesProvider()
    {
        return [
            [
                'type' => 'Asset',
                'action_types' => [
                    'assign' => 'Assign',
                    'regex_result' => 'Assign the value from regular expression',
                    'fromuser' => 'Copy from user',
                ],
            ],
            [
                'type' => 'Ticket',
                'action_types' => [
                    'assign' => 'Assign',
                    'regex_result' => 'Assign the value from regular expression',
                    'fromitem' => 'Copy from item',
                ],
            ],
            [
                'type' => 'ImportAsset',
                'action_types' => [
                    'assign' => 'Assign',
                ],
            ],
        ];
    }

    #[DataProvider('listRuleActionTypesProvider')]
    public function testListActionTypes($type, $action_types)
    {
        $this->login();

        $this->api->call(new Request('GET', "/Rule/Collection/{$type}/ActionType"), function ($call) use ($action_types) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($action_types) {
                    $tested = [];
                    foreach ($action_types as $id => $name) {
                        foreach ($content as $item) {
                            if ($item['id'] === $id) {
                                $this->assertEquals($name, $item['name']);
                                $this->assertNotEmpty($item['fields']);
                                $tested[] = $id;
                            }
                        }
                    }
                    $this->assertCount(count($action_types), $tested);
                });
        });
    }

    public static function listRuleActionFieldsProvider()
    {
        return [
            [
                'type' => 'Asset',
                'fields' => [
                    '_stop_rules_processing' => 'Skip remaining rules',
                    '_affect_user_by_regex' => 'User based contact information',
                    'otherserial' => 'Inventory number',
                ],
            ],
            [
                'type' => 'Ticket',
                'fields' => [
                    '_stop_rules_processing' => 'Skip remaining rules',
                    '_itilcategories_id_by_completename' => 'Category (by completename)',
                    'responsible_id_validate' => 'Send an approval request - Supervisor of the requester',
                    'status' => 'Status',
                ],
            ],
            [
                'type' => 'ImportAsset',
                'fields' => [
                    '_inventory' => 'Inventory link',
                    '_ignore_import' => 'Refuse import',
                ],
            ],
        ];
    }

    #[DataProvider('listRuleActionFieldsProvider')]
    public function testListActionFields($type, $fields)
    {
        $this->login();

        $this->api->call(new Request('GET', "/Rule/Collection/{$type}/ActionField"), function ($call) use ($fields) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($fields) {
                    $tested = [];
                    foreach ($fields as $id => $name) {
                        foreach ($content as $item) {
                            if ($item['id'] === $id) {
                                $this->assertEquals($name, $item['name']);
                                $this->assertNotEmpty($item['action_types']);
                                $tested[] = $id;
                            }
                        }
                    }
                    $this->assertCount(count($fields), $tested);
                });
        });
    }

    public function testCRUDRules()
    {
        $this->login();

        $collections = $this->getRuleCollections();
        foreach ($collections as $collection) {
            $request = new Request('POST', "/Rule/Collection/{$collection}/Rule");
            $request->setParameter('name', "testCRUDRules{$collection}");
            $request->setParameter('entity', $this->getTestRootEntity(true));
            $new_url = null;

            // Create
            $this->api->call($request, function ($call) use ($collection, &$new_url) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->headers(function ($headers) use ($collection, &$new_url) {
                        $this->assertStringStartsWith("/Rule/Collection/{$collection}/Rule", $headers['Location']);
                        $new_url = $headers['Location'];
                    });
            });

            // Get
            $this->api->call(new Request('GET', $new_url), function ($call) use ($collection) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use ($collection) {
                        $this->assertEquals("testCRUDRules{$collection}", $content['name']);
                    });
            });

            // Update
            $request = new Request('PATCH', $new_url);
            $request->setParameter('name', "testCRUDRules{$collection}2");
            $this->api->call($request, function ($call) use ($collection) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use ($collection) {
                        $this->assertEquals("testCRUDRules{$collection}2", $content['name']);
                    });
            });

            // Delete
            $this->api->call(new Request('DELETE', $new_url), function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response->isOK();
            });

            // Verify delete
            $this->api->call(new Request('GET', $new_url), function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isNotFoundError();
            });
        }
    }

    public function testCRUDRuleCriteria()
    {
        $this->login();

        $rule = new \Rule();
        $this->assertGreaterThan(0, $rules_id = $rule->add([
            'entities_id' => $this->getTestRootEntity(true),
            'name' => 'testCRUDRuleCriteria',
            'sub_type' => 'RuleTicket',
        ]));

        // Create
        $request = new Request('POST', "/Rule/Collection/Ticket/Rule/{$rules_id}/Criteria");
        $request->setParameter('criteria', 'name');
        $request->setParameter('condition', 0); //is
        $request->setParameter('pattern', 'testCRUDRuleCriteria');
        $new_url = null;
        $this->api->call($request, function ($call) use ($rules_id, &$new_url) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use ($rules_id, &$new_url) {
                    $this->assertStringStartsWith("/Rule/Collection/Ticket/Rule/{$rules_id}/Criteria", $headers['Location']);
                    $new_url = $headers['Location'];
                });
        });

        // Get
        $this->api->call(new Request('GET', $new_url), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('name', $content['criteria']);
                    $this->assertEquals(0, $content['condition']);
                    $this->assertEquals('testCRUDRuleCriteria', $content['pattern']);
                });
        });

        // Update
        $request = new Request('PATCH', $new_url);
        $request->setParameter('pattern', 'testCRUDRuleCriteria2');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('name', $content['criteria']);
                    $this->assertEquals(0, $content['condition']);
                    $this->assertEquals('testCRUDRuleCriteria2', $content['pattern']);
                });
        });

        // Delete
        $this->api->call(new Request('DELETE', $new_url), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Verify delete
        $this->api->call(new Request('GET', $new_url), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isNotFoundError();
        });
    }

    public function testCRUDRuleAction()
    {
        $this->login();

        $rule = new \Rule();
        $this->assertGreaterThan(0, $rules_id = $rule->add([
            'entities_id' => $this->getTestRootEntity(true),
            'name' => 'testCRUDRuleAction',
            'sub_type' => 'RuleTicket',
        ]));

        // Create
        $request = new Request('POST', "/Rule/Collection/Ticket/Rule/{$rules_id}/Action");
        $request->setParameter('field', 'name');
        $request->setParameter('action_type', 'assign');
        $request->setParameter('value', 'testCRUDRuleAction');
        $new_url = null;
        $this->api->call($request, function ($call) use ($rules_id, &$new_url) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use ($rules_id, &$new_url) {
                    $this->assertStringStartsWith("/Rule/Collection/Ticket/Rule/{$rules_id}/Action", $headers['Location']);
                    $new_url = $headers['Location'];
                });
        });

        // Get
        $this->api->call(new Request('GET', $new_url), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('name', $content['field']);
                    $this->assertEquals('assign', $content['action_type']);
                    $this->assertEquals('testCRUDRuleAction', $content['value']);
                });
        });

        // Update
        $request = new Request('PATCH', $new_url);
        $request->setParameter('value', 'testCRUDRuleAction2');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('name', $content['field']);
                    $this->assertEquals('assign', $content['action_type']);
                    $this->assertEquals('testCRUDRuleAction2', $content['value']);
                });
        });

        // Delete
        $this->api->call(new Request('DELETE', $new_url), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Verify delete
        $this->api->call(new Request('GET', $new_url), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isNotFoundError();
        });
    }

    public function testAddRuleSpecificRanking()
    {
        $this->login();
        $request = new Request('POST', "/Rule/Collection/Ticket/Rule");
        $request->setParameter('name', "testCRUDRulesRuleTicket");
        $request->setParameter('entity', $this->getTestRootEntity(true));
        $request->setParameter('ranking', 1);
        $new_url = null;

        // Create
        $this->api->call($request, function ($call) use (&$new_url) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_url) {
                    $this->assertStringStartsWith("/Rule/Collection/Ticket/Rule", $headers['Location']);
                    $new_url = $headers['Location'];
                });
        });
        // Get and check ranking
        $this->api->call(new Request('GET', $new_url), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals("testCRUDRulesRuleTicket", $content['name']);
                    $this->assertEquals(1, $content['ranking']);
                });
        });
    }

    public function testAddRuleInvalidRanking()
    {
        $this->login();
        $request = new Request('POST', "/Rule/Collection/Ticket/Rule");
        $request->setParameter('name', "testCRUDRulesRuleTicket");
        $request->setParameter('entity', $this->getTestRootEntity(true));
        $request->setParameter('ranking', -1);
        $new_url = null;

        // Create
        $this->api->call($request, function ($call) use (&$new_url) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_url) {
                    $this->assertStringStartsWith("/Rule/Collection/Ticket/Rule", $headers['Location']);
                    $new_url = $headers['Location'];
                });
        });

        // Get and check ranking
        $this->api->call(new Request('GET', $new_url), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals("testCRUDRulesRuleTicket", $content['name']);
                    $this->assertGreaterThanOrEqual(0, $content['ranking']);
                });
        });
    }

    public function testUpdateRuleSpecificRanking()
    {
        $this->login();
        $request = new Request('POST', "/Rule/Collection/Ticket/Rule");
        $request->setParameter('name', "testCRUDRulesRuleTicket");
        $request->setParameter('entity', $this->getTestRootEntity(true));
        $new_url = null;

        // Create
        $this->api->call($request, function ($call) use (&$new_url) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_url) {
                    $this->assertStringStartsWith("/Rule/Collection/Ticket/Rule", $headers['Location']);
                    $new_url = $headers['Location'];
                });
        });

        // Update ranking
        $request = new Request('PATCH', $new_url);
        $request->setParameter('ranking', 0);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals("testCRUDRulesRuleTicket", $content['name']);
                    $this->assertEquals(0, $content['ranking']);
                });
        });
    }
}
