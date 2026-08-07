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

namespace tests\units;

use Glpi\Tests\DbTestCase;
use Glpi\Tests\RuleBuilder;

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

        $rule_builder = new RuleBuilder(__FUNCTION__, \RuleDictionnaryPrinterModel::class);
        $rule_builder->setEntity(0)
            ->addCriteria('name', \Rule::PATTERN_IS, 'Model to ignore')
            ->addAction('assign', '_ignore_import', '1');
        $rule = $this->createRule($rule_builder);

        // --- act + assert ---
        // Test matching rule
        $input = [
            'name' => 'Model to ignore',
        ];
        $result = $collection->processAllRules($input);
        $expected = ['_ignore_import' => '1', '_ruleid' => $rule->getID()];
        $this->assertSame($expected, $result);

        // Test non-matching rule
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

        $rule_builder = new RuleBuilder(__FUNCTION__, \RuleDictionnaryPrinterModel::class);
        $rule_builder->setEntity(0)
            ->addCriteria('name', \Rule::PATTERN_IS, 'WrongOne')
            ->addAction('assign', 'name', 'GoodOne');
        $rule = $this->createRule($rule_builder);

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

        $rule_builder = new RuleBuilder(__FUNCTION__, \RuleDictionnaryPrinterModel::class);
        $rule_builder->setEntity(0)
            ->addCriteria('name', \Rule::PATTERN_IS, 'WrongOne')
            ->addAction('assign', 'name', 'GoodOne');
        $rule = $this->createRule($rule_builder);

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
