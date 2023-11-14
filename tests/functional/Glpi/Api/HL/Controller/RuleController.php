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

class RuleController extends \HLAPITestCase
{
    protected function getRuleCollections()
    {
        return [
            'Ticket', 'Change', 'Problem', 'Asset', 'ImportAsset', 'ImportEntity', 'Right',
            'Location', 'SoftwareCategory'
        ];
    }

    public function testAccess()
    {
        $this->login();
        $this->loginWeb();

        // Remove ticket rule permission
        $rule_ticket_right = $_SESSION['glpiactiveprofile']['rule_ticket'];
        $_SESSION['glpiactiveprofile']['rule_ticket'] = 0;

        $this->api->call(new Request('GET', '/Rule/Collection'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->isNotEmpty();
                    $has_ticket_rule = false;
                    foreach ($content as $collection) {
                        if ($collection['rule_type'] === 'Ticket') {
                            $has_ticket_rule = true;
                            break;
                        }
                    }
                    $this->boolean($has_ticket_rule)->isFalse();
                });
        }, false); // false here avoids initializing a new temporary session and instead uses the InternalAuthMiddleware

        // Restore ticket rule permission
        $_SESSION['glpiactiveprofile']['rule_ticket'] = $rule_ticket_right;

        $this->api->call(new Request('GET', '/Rule/Collection'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->isNotEmpty();
                    $has_ticket_rule = false;
                    foreach ($content as $collection) {
                        if ($collection['rule_type'] === 'Ticket') {
                            $has_ticket_rule = true;
                            break;
                        }
                    }
                    $this->boolean($has_ticket_rule)->isTrue();
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
                    $this->array($types)->containsValues($this->getRuleCollections());
                });
        });
    }

    protected function listRuleCriteriaConditionsProvider()
    {
        return [
            [
                'type' => 'Asset',
                'conditions' => [
                    0 => [
                        'description' => 'is',
                        'fields' => ['_auto', 'comment', 'states_id']
                    ],
                    11 => [
                        'description' => 'under',
                        'fields' => ['locations_id', 'states_id']
                    ],
                ],
            ],
            [
                'type' => 'Ticket',
                'conditions' => [
                    0 => [
                        'description' => 'is',
                        'fields' => ['name', '_mailgate', '_x-priority']
                    ],
                    6 => [
                        'description' => 'regular expression matches',
                        'fields' => ['name', '_mailgate', '_x-priority']
                    ],
                    11 => [
                        'description' => 'under',
                        'fields' => ['_groups_id_of_requester', 'itilcategories_id']
                    ],
                ],
            ],
            [
                'type' => 'ImportAsset',
                'conditions' => [
                    333 => [
                        'description' => 'is CIDR',
                        'fields' => ['ip']
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider listRuleCriteriaConditionsProvider
     */
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
                                $this->string($item['description'])->isIdenticalTo($condition['description']);
                                $this->array($item['fields'])->containsValues($condition['fields']);
                                $tested[] = $id;
                            }
                        }
                    }
                    $this->array($tested)->hasSize(count($conditions));
                });
        });
    }

    protected function listRuleCriteriaCriteriaProvider()
    {
        return [
            [
                'type' => 'Asset',
                'criteria' => [
                    '_auto' => 'Automatic inventory',
                    '_itemtype' => 'Item type',
                    'comment' => 'Comments',
                    '_tag' => 'Agent > Inventory tag',
                    'users_id' => 'User'
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
                    'link_criteria_port' => 'General > Restrict criteria to same network port'
                ],
            ],
        ];
    }

    /**
     * @dataProvider listRuleCriteriaCriteriaProvider
     */
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
                                $this->string($item['name'])->isIdenticalTo($name);
                                $tested[] = $id;
                            }
                        }
                    }
                    $this->array($tested)->hasSize(count($criteria));
                });
        });
    }

//    protected function listRuleActionTypesProvider()
//    {
//        return [
//            [
//                'type' => 'Asset',
//                'action_types' => [
//                    'assign' => 'Assign',
//                    'regex_result' => 'Assign the value from regular expression',
//                    'fromuser' => 'Copy from user',
//                    'compute' => 'Recalculate'
//                ],
//            ],
//            [
//                'type' => 'Ticket',
//                'action_types' => [
//                    'assign' => 'Assign',
//                    'regex_result' => 'Assign the value from regular expression',
//                    'fromitem' => 'Copy from item',
//                    'compute' => 'Recalculate'
//                ],
//            ],
//            [
//                'type' => 'ImportAsset',
//                'action_types' => [
//                    'entities_id' => 'Target entity for the asset',
//                    'mac' => 'Asset > Network port > MAC',
//                    'link_criteria_port' => 'General > Restrict criteria to same network port'
//                ],
//            ],
//        ];
//    }
//
//    public function testListActionTypes($type, $action_types)
//    {
//        $this->login();
//
//        $this->api->call(new Request('GET', "/Rule/Collection/{$type}/ActionType"), function ($call) use ($action_types) {
//            /** @var \HLAPICallAsserter $call */
//            $call->response
//                ->isOK()
//                ->jsonContent(function ($content) use ($action_types) {
//                    $tested = [];
//                    foreach ($action_types as $id => $name) {
//                        foreach ($content as $item) {
//                            if ($item['id'] === $id) {
//                                $this->string($action_types['name'])->isIdenticalTo($name);
//                                $tested[] = $id;
//                            }
//                        }
//                    }
//                    $this->array($tested)->hasSize(count($action_types));
//                });
//        });
//    }

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
                        $this->array($headers)->hasKey('Location');
                        $this->string($headers['Location'])->startWith("/Rule/Collection/{$collection}/Rule");
                        $new_url = $headers['Location'];
                    });
            });

            // Get
            $this->api->call(new Request('GET', $new_url), function ($call) use ($collection) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use ($collection) {
                        $this->array($content)->hasKeys(['name']);
                        $this->string($content['name'])->isEqualTo("testCRUDRules{$collection}");
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
                        $this->array($content)->hasKeys(['name']);
                        $this->string($content['name'])->isEqualTo("testCRUDRules{$collection}2");
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
}
