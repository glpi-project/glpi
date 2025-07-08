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

namespace Glpi\Tests;

use DbTestCase;
use ITILTemplatePredefinedField;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractITILTemplatePredefinedFieldTest extends DbTestCase
{
    abstract public function getConcreteClass(): ITILTemplatePredefinedField;

    public static function getAssociatedItemsInputs(): iterable
    {
        yield [
            'input' => [
                'num'          => 13,
                'value'        => "Computer_1",
                'add_items_id' => 1,
                'entities_id'  => 0,
                'is_recursive' => false,
            ],
            'expected_result' => true,
        ];
        yield [
            'input' => [
                'num'          => 13,
                'value'        => 0,
                'entities_id'  => 0,
                'is_recursive' => false,
            ],
            'expected_result' => false,
            'expected_errors' => ["You must select an associated item"],
        ];
        yield [
            'input' => [
                'num'          => 13,
                'value'        => "Computer_0",
                'add_items_id' => 0,
                'entities_id'  => 0,
                'is_recursive' => false,
            ],
            'expected_result' => false,
            'expected_errors' => ["You must select an associated item"],
        ];
    }

    #[DataProvider('getAssociatedItemsInputs')]
    public function testValidAssociatedItemsInput(
        array $input,
        bool $expected_result,
        array $expected_errors = []
    ): void {
        // Arrange: create a valid template and insert it into the input
        $tpl_class = $this->getConcreteClass()::$itemtype;
        $tpl = $this->createItem(
            $tpl_class,
            ['name' => 'my template']
        );
        $input[$tpl_class::getForeignKeyField()] = $tpl->getID();

        // Act: login and prepare input
        $this->login('glpi', 'glpi');
        $result = $this->getConcreteClass()->prepareInputForAdd($input);

        // Assert: check validity and error message
        $this->assertEquals($expected_result, (bool) $result);
        if ($expected_errors) {
            $this->hasSessionMessages(ERROR, $expected_errors);
        }
    }
}
