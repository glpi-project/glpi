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

class RuleDefineItemtypeTest extends DbTestCase
{
    public function testNoRule()
    {
        $input = [
            'itemtype' => \Phone::class,
            'name'     => 'Test asset',
        ];

        $ruleCollection = new \RuleDefineItemtypeCollection();
        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(['_no_rule_matches' => true], $data);
    }

    public function testChangeItemtype()
    {
        $input = [
            'itemtype' => \Phone::class,
            'name'     => 'Test asset',
        ];

        $ruleCollection = new \RuleDefineItemtypeCollection();
        $rules_id = $this->addRule(
            \RuleDefineItemtype::class,
            'Change itemtype',
            [
                [
                    'condition' => \RuleDefineItemtype::PATTERN_IS,
                    'criteria'  => 'itemtype',
                    'pattern'   => \Phone::class,
                ],
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
                '_ruleid' => $rules_id,
            ],
            $data
        );
    }

    public function testDefineItemtypeFromItemname()
    {
        $ruleCollection = new \RuleDefineItemtypeCollection();
        $rules_id = $this->addRule(
            \RuleDefineItemtype::class,
            'Change itemtype',
            [
                [
                    'condition' => \RuleDefineItemtype::REGEX_MATCH,
                    'criteria'  => 'name',
                    'pattern'   => '/.*phone.*/i',
                ],
            ],
            [
                'action_type' => 'assign',
                'field'       => '_assign',
                'value'       => \Phone::class,
            ]
        );

        $input = [
            'itemtype' => \Computer::class,
            'name'     => 'A Phone that does not know what it is!',
        ];
        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(
            [
                'new_itemtype' => \Phone::class,
                '_ruleid' => $rules_id,
            ],
            $data
        );

        $input = [
            'itemtype' => \Computer::class,
            'name'     => 'A Computer, not anything else.',
        ];
        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(
            [
                '_no_rule_matches' => true,
                '_rule_process' => false,
            ],
            $data
        );
    }

    public function testDefineItemtypeFromItemtypeAndMac()
    {
        $input = [
            'itemtype' => \Phone::class,
            'name'     => 'A test',
        ];

        $ruleCollection = new \RuleDefineItemtypeCollection();
        $rules_id = $this->addRule(
            \RuleDefineItemtype::class,
            'Change itemtype',
            [
                [
                    'condition' => \RuleDefineItemtype::PATTERN_IS,
                    'criteria'  => 'itemtype',
                    'pattern'   => \Phone::class,
                ],
                [
                    'condition' => \RuleDefineItemtype::PATTERN_EXISTS,
                    'criteria' => 'mac',
                    'pattern' => 1,
                ],
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
                '_rule_process' => false,
            ],
            $data
        );

        $input['mac'] = '00:00:00:00:00:00';
        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(
            [
                'new_itemtype' => \Computer::class,
                '_ruleid' => $rules_id,
            ],
            $data
        );
    }

    public function testDefineItemtypeFromMacAndIfnumber()
    {
        $input = [
            'itemtype' => \Computer::class,
            'name'     => 'A test',
            'mac'      => '00:00:00:00:00:00',
        ];

        $ruleCollection = new \RuleDefineItemtypeCollection();
        $rules_id = $this->addRule(
            \RuleDefineItemtype::class,
            'Change itemtype',
            [
                [
                    'condition' => \RuleDefineItemtype::PATTERN_EXISTS,
                    'criteria'  => 'mac',
                    'pattern'   => 1,
                ],
                [
                    'condition' => \RuleDefineItemtype::PATTERN_EXISTS,
                    'criteria' => 'ifnumber',
                    'pattern' => 1,
                ],
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
                '_rule_process' => false,
            ],
            $data
        );

        $input['ifnumber'] = '15';
        $data = $ruleCollection->processAllRules($input);
        $this->assertSame(
            [
                'new_itemtype' => \NetworkEquipment::class,
                '_ruleid' => $rules_id,
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
        // remove 4 criterias not present in RuleDefineItemtype
        //  - linked_item
        //  - entityrestrict
        //  - link_criteria_port
        //  - only_these_criteria
        $this->assertSame(count($ruleimportinstance->getCriterias()) - 4, count($instance->getCriterias()));
    }

    public function testGetActions()
    {
        $instance = new \RuleDefineItemtype();
        $this->assertSame(1, count($instance->getActions()));
    }
}
