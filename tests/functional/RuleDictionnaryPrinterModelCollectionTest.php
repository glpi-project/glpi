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

class RuleDictionnaryPrinterModelCollectionTest extends DbTestCase
{
    public function testCleanTestOutputCriterias()
    {
        $collection = new \RuleDictionnaryPrinterModelCollection();
        $params     = ['manufacturers_id' => 1,
            '_bad'             => '_value2',
            '_ignore_import'   => '1',
        ];
        $result     = $collection->cleanTestOutputCriterias($params);
        $expected   = [
            'manufacturers_id' => 1,
        ];
        $this->assertSame($expected, $result);
    }

    public function testIgnoreImport()
    {
        $collection = new \RuleDictionnaryPrinterModelCollection();

        $rule = $this->createItem(
            \Rule::class,
            [
                'name'        => 'Ignore import',
                'is_active'   => 1,
                'entities_id' => 0,
                'sub_type'    => \RuleDictionnaryPrinterModel::class,
                'match'       => \Rule::AND_MATCHING,
                'condition'   => 0,
                'description' => '',
            ]
        );

        $this->createItem(
            \RuleCriteria::class,
            [
                'rules_id'  => $rule->getID(),
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'Model to ignore',
            ]
        );

        $this->createItem(
            \RuleAction::class,
            [
                'rules_id'    => $rule->getID(),
                'action_type' => 'assign',
                'field'       => '_ignore_import',
                'value'       => '1',
            ]
        );

        $input = [
            'name' => 'Model to ignore',

        ];
        $result = $collection->processAllRules($input);
        $expected = ['_ignore_import' => '1', '_ruleid' => $rule->getID()];
        $this->assertSame($expected, $result);

        $input = [
            'name' => 'Any other model',
        ];
        $result = $collection->processAllRules($input);
        $expected = ['_no_rule_matches' => true, '_rule_process' => false];
        $this->assertSame($expected, $result);
    }

    public function testRule(): void
    {
        $collection = new \RuleDictionnaryPrinterModelCollection();

        $rule = $this->createItem(
            \Rule::class,
            [
                'name'        => 'Adapt Model',
                'is_active'   => 1,
                'entities_id' => 0,
                'sub_type'    => \RuleDictionnaryPrinterModel::class,
                'match'       => \Rule::AND_MATCHING,
                'condition'   => 0,
                'description' => '',
            ]
        );

        $this->createItem(
            \RuleCriteria::class,
            [
                'rules_id'  => $rule->getID(),
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'WrongOne',
            ]
        );

        $this->createItem(
            \RuleAction::class,
            [
                'rules_id'    => $rule->getID(),
                'action_type' => 'assign',
                'field'       => 'name',
                'value'       => 'GoodOne',
            ]
        );

        $input = [
            'name' => 'WrongOne',
        ];

        $result = $collection->processAllRules($input);
        $expected = ['name' => 'GoodOne', '_ruleid' => $rule->getID()];
        $this->assertSame($expected, $result);
    }

    public function testReplay(): void
    {
        $collection = new \RuleDictionnaryPrinterModelCollection();

        $rule = $this->createItem(
            \Rule::class,
            [
                'name'        => 'Adapt Model',
                'is_active'   => 1,
                'entities_id' => 0,
                'sub_type'    => \RuleDictionnaryPrinterModel::class,
                'match'       => \Rule::AND_MATCHING,
                'condition'   => 0,
                'description' => '',
            ]
        );

        $this->createItem(
            \RuleCriteria::class,
            [
                'rules_id'  => $rule->getID(),
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'WrongOne',
            ]
        );

        $this->createItem(
            \RuleAction::class,
            [
                'rules_id'    => $rule->getID(),
                'action_type' => 'assign',
                'field'       => 'name',
                'value'       => 'GoodOne',
            ]
        );

        $printermodel = $this->createItem(
            \PrinterModel::class,
            [
                'name' => 'WrongOne',
            ]
        );
        $wrong_models_id = $printermodel->getID();

        //new model must not exist to reproduce issue https://github.com/glpi-project/glpi/issues/18987

        $printer = $this->createItem(
            \Printer::class,
            [
                'name' => 'My test printer',
                'printermodels_id' => $wrong_models_id,
                'entities_id' => 0,
            ]
        );

        $this->expectOutputRegex('/.*Replay rules on existing database started on.*/');
        $collection->replayRulesOnExistingDB(0, 0, [], []);

        $this->assertTrue($printer->getFromDB($printer->getID()));
        $this->assertNotEquals($wrong_models_id, $printer->fields['printermodels_id']);

        $this->assertTrue($printermodel->getFromDB($printer->fields['printermodels_id']));
        $this->assertEquals('GoodOne', $printermodel->fields['name']);
    }
}
