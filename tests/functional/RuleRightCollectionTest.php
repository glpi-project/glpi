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

namespace tests\units;

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RuleRightCollectionTest extends DbTestCase
{
    public static function prepateInputDataForProcessProvider()
    {
        return [
            [
                [],
                ['type' => \Auth::DB_GLPI, 'login' => 'glpi'],
                ['TYPE' => \Auth::DB_GLPI, 'LOGIN' => 'glpi'],
            ],
            [
                [],
                ['TYPE' => \Auth::DB_GLPI, 'loGin' => 'glpi'],
                ['TYPE' => \Auth::DB_GLPI, 'LOGIN' => 'glpi'],
            ],
            [
                [],
                ['type' => \Auth::MAIL, 'login' => 'glpi', 'mail_server' => 'mail.example.com'],
                ['TYPE' => \Auth::MAIL, 'LOGIN' => 'glpi', 'MAIL_SERVER' => 'mail.example.com'],
            ],
            [
                [],
                ['type' => \Auth::MAIL, 'login' => 'glpi', 'MAIL_server' => 'mail.example.com'],
                ['TYPE' => \Auth::MAIL, 'LOGIN' => 'glpi', 'MAIL_SERVER' => 'mail.example.com'],
            ],
        ];
    }

    #[DataProvider('prepateInputDataForProcessProvider')]
    public function testPrepareInputDataForProcess($input, $params, $expected)
    {
        $collection = new \RuleRightCollection();

        // Expect the result to have at least the key/values from the $expected array
        $result = $collection->prepareInputDataForProcess($input, $params);
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $result);
            $this->assertEquals($value, $result[$key]);
        }
    }

    public static function exportXMLProvider()
    {
        yield [
            'rule_data' => [
                'criteria' => [
                    'field' => 'type', 'condition' => \Rule::PATTERN_IS, 'value' => '1',
                ],
            ],
            'itemtype_data' => null,
        ];

        yield [
            'rule_data' => [
                'criteria' => [
                    'field' => 'mail_server', 'condition' => \Rule::PATTERN_IS,
                ],
            ],
            'itemtype_data' => [
                'itemtype' => \AuthMail::class,
                'input' => [
                    'connect_string' => '{mail.example.com}',
                    'name' => 'Test Mail Auth',
                ],
            ],
        ];

        yield [
            'rule_data' => [
                'criteria' => [
                    'field' => 'ldap_server', 'condition' => \Rule::PATTERN_IS,
                ],
            ],
            'itemtype_data' => [
                'itemtype' => \AuthLDAP::class,
                'input' => [
                    'name' => 'Test LDAP Auth',
                ],
            ],
        ];

        yield [
            'rule_data' => [
                'criteria' => [
                    'field' => 'mail_email', 'condition' => \Rule::PATTERN_IS, 'value' => 'mail.example.com',
                ],
            ],
            'itemtype_data' => null,
        ];

        yield [
            'rule_data' => [
                'criteria' => [
                    'field' => 'login', 'condition' => \Rule::PATTERN_IS, 'value' => 'login',
                ],
            ],
            'itemtype_data' => null,
        ];

        yield [
            'rule_data' => [
                'criteria' => [
                    'field' => 'groups', 'condition' => \Rule::PATTERN_IS,
                ],
            ],
            'itemtype_data' => [
                'itemtype' => \Group::class,
                'input' => [
                    'name' => 'Test Group',
                ],
            ],
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => 'entities_id', 'action_type' => 'assign',
                ],
            ],
            'itemtype_data' => [
                'itemtype' => \Entity::class,
                'input' => [
                    'name' => 'Test Entity',
                    'entities_id' => 0,
                ],
            ],
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => '_affect_entity_by_dn', 'action_type' => 'regex_result', 'value' => 'entity',
                ],
            ],
            'itemtype_data' => null,
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => '_affect_entity_by_tag', 'action_type' => 'regex_result', 'value' => 'entity',
                ],
            ],
            'itemtype_data' => null,
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => '_affect_entity_by_domain', 'action_type' => 'regex_result', 'value' => 'entity',
                ],
            ],
            'itemtype_data' => null,
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => '_affect_entity_by_completename', 'action_type' => 'regex_result', 'value' => 'entity',
                ],
            ],
            'itemtype_data' => null,
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => 'profiles_id', 'action_type' => 'assign',
                ],
            ],
            'itemtype_data' => [
                'itemtype' => \Profile::class,
                'input' => [
                    'name' => 'Test Profile',
                ],
            ],
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => 'is_recursive', 'action_type' => 'assign', 'value' => '1',
                ],
            ],
            'itemtype_data' => null,
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => 'is_active', 'action_type' => 'assign', 'value' => '1',
                ],
            ],
            'itemtype_data' => null,
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => '_entities_id_default', 'action_type' => 'assign',
                ],
            ],
            'itemtype_data' => [
                'itemtype' => \Entity::class,
                'input' => [
                    'name' => 'Test Entity',
                    'entities_id' => 0,
                ],
            ],
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => 'specific_groups_id', 'action_type' => 'assign',
                ],
            ],
            'itemtype_data' => [
                'itemtype' => \Group::class,
                'input' => [
                    'name' => 'Test Group',
                ],
            ],
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => 'groups_id', 'action_type' => 'assign',
                ],
            ],
            'itemtype_data' => [
                'itemtype' => \Group::class,
                'input' => [
                    'name' => 'Test Group',
                ],
            ],
        ];

        yield [
            'rule_data' => [
                'action' => [
                    'field' => '_profiles_id_default', 'action_type' => 'assign',
                ],
            ],
            'itemtype_data' => [
                'itemtype' => \Profile::class,
                'input' => [
                    'name' => 'Test Profile',
                ],
            ],
        ];
    }

    #[DataProvider('exportXMLProvider')]
    public function testExportXML(array $rule_data, ?array $itemtype_data)
    {
        $this->login();

        $collection = new \RuleRightCollection();

        //create a rule right
        $rule = $this->createItem(
            \Rule::class,
            [
                'entities_id' => 0,
                'sub_type' => \RuleRight::class,
                'name' => 'Test Rule Right',
                'is_active' => 1,
                'is_recursive' => 1,
                'match' => 'AND',
                'condition' => 0,
            ]
        );

        $itemtype = null;

        // If rule use itemtype data, create the itemtype
        if (is_array($itemtype_data)) {
            $itemtype = $this->createItem(
                $itemtype_data['itemtype'],
                $itemtype_data['input']
            );
        }

        // If rule has criteria or action, create them
        if (isset($rule_data['criteria'])) {
            $this->createItem(
                \RuleCriteria::class,
                [
                    'rules_id' => $rule->getID(),
                    'criteria' => $rule_data['criteria']['field'],
                    'condition' => $rule_data['criteria']['condition'],
                    'pattern' => $itemtype ? $itemtype->getID() : $rule_data['criteria']['value'],
                ]
            );
        }

        if (isset($rule_data['action'])) {
            $this->createItem(
                \RuleAction::class,
                [
                    'rules_id' => $rule->getID(),
                    'action_type' => $rule_data['action']['action_type'],
                    'field' => $rule_data['action']['field'],
                    'value' => $itemtype ? $itemtype->getID() : $rule_data['action']['value'],
                ]
            );
        }

        // Export the rule to XML
        $xml = $collection->getRulesXMLFile([$rule->getID()]);

        // Create expected XML structure
        $xmlE = new \SimpleXMLElement('<rules/>');
        $xmlERule = $xmlE->addChild('rule');
        $xmlERule->entities_id = 'Root entity';
        $xmlERule->sub_type = \RuleRight::class;
        $xmlERule->ranking = $rule->fields['ranking'];
        $xmlERule->name = 'Test Rule Right';
        $xmlERule->description = '';
        $xmlERule->match = 'AND';
        $xmlERule->is_active = '1';
        $xmlERule->comment = '';
        $xmlERule->is_recursive = '1';
        $xmlERule->uuid = $rule->fields['uuid'];
        $xmlERule->condition = 0;
        $xmlERule->date_creation = $rule->fields['date_creation'];
        if (isset($rule_data['criteria'])) {
            $xmlERuleCriteria = $xmlERule->addChild('rulecriteria');
            $xmlERuleCriteria->criteria = $rule_data['criteria']['field'];
            $xmlERuleCriteria->condition = $rule_data['criteria']['condition'];
            $xmlERuleCriteria->pattern = $itemtype ? $itemtype->getID() : $rule_data['criteria']['value'];
        }
        if (isset($rule_data['action'])) {
            $xmlERuleCriteria = $xmlERule->addChild('ruleaction');
            $xmlERuleCriteria->action_type = $rule_data['action']['action_type'];
            $xmlERuleCriteria->field = $rule_data['action']['field'];
            if (str_contains($rule_data['action']['field'], 'entities_id')) {
                $xmlERuleCriteria->value = $itemtype->fields['completename'];
            } else {
                $xmlERuleCriteria->value = $itemtype ? $itemtype->fields['name'] : $rule_data['action']['value'];
            }
        }

        $expected = $xmlE->asXML();

        // Compare the generated XML with the expected XML
        $this->assertEquals($expected, $xml);
    }
}
