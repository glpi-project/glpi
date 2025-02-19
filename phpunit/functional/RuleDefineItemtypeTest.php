<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units;

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/ruleimportcomputer.class.php */

class RuleDefineItemtypeTest extends DbTestCase
{
    /**
     * Adds a new rule
     *
     * @param string $name     New rule name
     * @param array  $criteria Rule criteria
     * @param array  $action   Rule action
     *
     * @return int
     */
    protected function addRule(string $name, array $criteria, array $action): int
    {
        global $DB;

        $rule = new \RuleDefineItemtype();
        $rulecriteria = new \RuleCriteria();

        $input = [
            'is_active' => 1,
            'name'      => $name,
            'match'     => 'AND',
            'sub_type'  => 'RuleDefineItemtype',
        ];

        $this->assertTrue(
            $DB->update(
                'glpi_rules',
                [
                    'ranking' => new \Glpi\DBAL\QueryExpression($DB->quoteName('ranking') . ' + 2')
                ],
                [
                    'sub_type'  => 'RuleDefineItemtype'
                ]
            )
        );
        $input['ranking'] = 1;
        $rules_id = $rule->add($input);
        $this->assertGreaterThan(0, $rules_id);

        // Add criteria
        foreach ($criteria as $crit) {
            $input = [
                'rules_id'  => $rules_id,
                'criteria'  => $crit['criteria'],
                'pattern'   => $crit['pattern'],
                'condition' => $crit['condition'],
            ];
            $this->assertGreaterThan(0, (int)$rulecriteria->add($input));
        }

        // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => $action['action_type'],
            'field'       => $action['field'],
            'value'       => $action['value'],
        ];
        $this->assertGreaterThan(0, (int)$ruleaction->add($input));

        return $rules_id;
    }

    public function testNoRule()
    {
        $input = [
            'itemtype' => \Phone::class,
            'name'     => 'Test asset'
        ];

        $ruleCollection = new \RuleDefineItemtypeCollection();
        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(['_no_rule_matches' => true], $data);
    }

    public function testChangeItemtype()
    {
        $input = [
            'itemtype' => \Phone::class,
            'name'     => 'Test asset'
        ];

        $ruleCollection = new \RuleDefineItemtypeCollection();
        $rules_id = $this->addRule(
            'Change itemtype',
            [
                [
                    'condition' => \RuleDefineItemtype::PATTERN_IS,
                    'criteria'  => 'itemtype',
                    'pattern'   => \Phone::class
                ]
            ],
            [
                'action_type' => 'assign',
                'field'       => '_assign',
                'value'       => \Computer::class,
            ]
        );

        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(
            [
                'new_itemtype' => \Computer::class,
                '_ruleid' => $rules_id
            ],
            $data
        );
    }

    public function testDefineItemtypeFromItemname()
    {
        $ruleCollection = new \RuleDefineItemtypeCollection();
        $rules_id = $this->addRule(
            'Change itemtype',
            [
                [
                    'condition' => \RuleDefineItemtype::REGEX_MATCH,
                    'criteria'  => 'name',
                    'pattern'   => '/.*phone.*/i'
                ]
            ],
            [
                'action_type' => 'assign',
                'field'       => '_assign',
                'value'       => \Phone::class,
            ]
        );

        $input = [
            'itemtype' => \Computer::class,
            'name'     => 'A Phone that des not know what it is!'
        ];
        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(
            [
                'new_itemtype' => \Phone::class,
                '_ruleid' => $rules_id
            ],
            $data
        );

        $input = [
            'itemtype' => \Computer::class,
            'name'     => 'A Computer, not anything else.'
        ];
        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(
            [
                '_no_rule_matches' => true,
                '_rule_process' => false
            ],
            $data
        );
    }

    public function testDefineItemtypeFromItemtypeAndMac()
    {
        $input = [
            'itemtype' => \Phone::class,
            'name'     => 'A test'
        ];

        $ruleCollection = new \RuleDefineItemtypeCollection();
        $rules_id = $this->addRule(
            'Change itemtype',
            [
                [
                    'condition' => \RuleDefineItemtype::PATTERN_IS,
                    'criteria'  => 'itemtype',
                    'pattern'   => \Phone::class
                ],
                [
                    'condition' => \RuleDefineItemtype::PATTERN_EXISTS,
                    'criteria' => 'mac',
                    'pattern' => 1
                ]
            ],
            [
                'action_type' => 'assign',
                'field'       => '_assign',
                'value'       => \Computer::class,
            ]
        );

        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(
            [
                '_no_rule_matches' => true,
                '_rule_process' => false
            ],
            $data
        );

        $input['mac'] = '00:00:00:00:00:00';
        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(
            [
                'new_itemtype' => \Computer::class,
                '_ruleid' => $rules_id
            ],
            $data
        );
    }

    public function testDefineItemtypeFromMacAndIfnumber()
    {
        $input = [
            'itemtype' => \Computer::class,
            'name'     => 'A test',
            'mac'      => '00:00:00:00:00:00'
        ];

        $ruleCollection = new \RuleDefineItemtypeCollection();
        $rules_id = $this->addRule(
            'Change itemtype',
            [
                [
                    'condition' => \RuleDefineItemtype::PATTERN_EXISTS,
                    'criteria'  => 'mac',
                    'pattern'   => 1
                ],
                [
                    'condition' => \RuleDefineItemtype::PATTERN_EXISTS,
                    'criteria' => 'ifnumber',
                    'pattern' => 1
                ]
            ],
            [
                'action_type' => 'assign',
                'field'       => '_assign',
                'value'       => \NetworkEquipment::class,
            ]
        );

        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(
            [
                '_no_rule_matches' => true,
                '_rule_process' => false
            ],
            $data
        );

        $input['ifnumber'] = '15';
        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(
            [
                'new_itemtype' => \NetworkEquipment::class,
                '_ruleid' => $rules_id
            ],
            $data
        );
    }

    public function testGetTitle()
    {
        $instance = new \RuleDefineItemtype();
        $this->assertSame('Rules to define inventoried itemtype', $instance->getTitle());
    }

    public function testMaxActionsCount()
    {
        $instance = new \RuleDefineItemtype();
        $this->assertSame(1, $instance->maxActionsCount());
    }

    public function testGetCriteria()
    {
        $instance = new \RuleDefineItemtype();
        $ruleimportinstance = new \RuleImportAsset();
        $this->assertSame(count($ruleimportinstance->getCriterias()), count($instance->getCriterias()));
    }

    public function testGetActions()
    {
        $instance = new \RuleDefineItemtype();
        $this->assertSame(1, count($instance->getActions()));
    }
}
