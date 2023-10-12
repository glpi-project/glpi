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

namespace tests\units\Glpi\Api\HL\RSQL;

use GLPITestCase;

class Lexer extends GLPITestCase
{
    protected function tokenizeProvider()
    {
        return [
            [
                'id==20',
                [[5, 'id'], [6, '=='], [7, '20']],
            ],
            [
                '((model.name=in=(A2696,A2757,A2777);name=like=*Staff*),(model.name=in=(A2602,A2604,A2603,A2605);name=like=*Student*)),name=in=(A2436,A2764,A2437,A2766)',
                [
                    [3, "("] ,[3, "("], [5,"model.name"], [6, "=in="], [7, "(A2696,A2757,A2777)"], [1, ";"],
                    [5, "name"], [6, "=like="], [7, "*Staff*"], [4, ")"], [2, ","], [3, "("], [5, "model.name"],
                    [6, "=in="], [7, "(A2602,A2604,A2603,A2605)"], [1, ";"], [5, "name"], [6, "=like="], [7, "*Student*"],
                    [4, ")"], [4, ")"], [2, ","], [5, "name"],[6, "=in="], [7, "(A2436,A2764,A2437,A2766)"]
                ]
            ],
            [
                'name==(test', // In this case, "(test" is a valid value
                [[5, 'name'], [6, '=='], [7, '(test']]
            ]
        ];
    }

    /**
     * @dataProvider tokenizeProvider
     */
    public function testTokenize(string $query, array $expected)
    {
        $tokens = \Glpi\Api\HL\RSQL\Lexer::tokenize($query);
        $this->array($tokens)->isEqualTo($expected);
    }

    public function testMissingOperator()
    {
        $this->exception(function () {
            \Glpi\Api\HL\RSQL\Lexer::tokenize('id');
        })->isInstanceOf(\Glpi\Api\HL\RSQL\RSQLException::class)
            ->hasMessage('RSQL query is missing an operator in filter for property "id"');
    }

    public function testIncompleteOperator()
    {
        $this->exception(function () {
            \Glpi\Api\HL\RSQL\Lexer::tokenize('id=');
        })->isInstanceOf(\Glpi\Api\HL\RSQL\RSQLException::class)
            ->hasMessage('RSQL query has an incomplete operator in filter for property "id"');
        $this->exception(function () {
            \Glpi\Api\HL\RSQL\Lexer::tokenize('id=l');
        })->isInstanceOf(\Glpi\Api\HL\RSQL\RSQLException::class)
            ->hasMessage('RSQL query has an incomplete operator in filter for property "id"');
    }

    public function testMissingValue()
    {
        $this->exception(function () {
            \Glpi\Api\HL\RSQL\Lexer::tokenize('id=like=');
        })->isInstanceOf(\Glpi\Api\HL\RSQL\RSQLException::class)
            ->hasMessage('RSQL query is missing a value in filter for property "id"');
    }

    public function testUnclosedGroup()
    {
        $this->exception(function () {
            \Glpi\Api\HL\RSQL\Lexer::tokenize('(id=like=1');
        })->isInstanceOf(\Glpi\Api\HL\RSQL\RSQLException::class)
            ->hasMessage('RSQL query has one or more unclosed groups');

        $this->exception(function () {
            \Glpi\Api\HL\RSQL\Lexer::tokenize('name==Test1,((id=like=1),(name==Test2)');
        })->isInstanceOf(\Glpi\Api\HL\RSQL\RSQLException::class)
            ->hasMessage('RSQL query has one or more unclosed groups');
    }
}
