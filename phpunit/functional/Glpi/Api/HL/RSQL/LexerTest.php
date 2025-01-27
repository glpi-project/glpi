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

namespace tests\units\Glpi\Api\HL\RSQL;

use Glpi\Api\HL\RSQL\Lexer;
use Glpi\Api\HL\RSQL\RSQLException;
use GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LexerTest extends GLPITestCase
{
    public static function tokenizeProvider()
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

    #[DataProvider('tokenizeProvider')]
    public function testTokenize(string $query, array $expected)
    {
        $tokens = Lexer::tokenize($query);
        $this->assertEquals($expected, $tokens);
    }

    public function testMissingOperator()
    {
        $this->expectException(RSQLException::class);
        $this->expectExceptionMessage('RSQL query is missing an operator in filter for property "id"');
        Lexer::tokenize('id');
    }

    public static function incompleteOperatorProvider()
    {
        return [
            ['id='],
            ['id=l']
        ];
    }

    #[DataProvider('incompleteOperatorProvider')]
    public function testIncompleteOperator(string $query)
    {
        $this->expectException(RSQLException::class);
        $this->expectExceptionMessage('RSQL query has an incomplete operator in filter for property "id"');
        Lexer::tokenize($query);
    }

    public function testMissingValue()
    {
        $this->expectException(RSQLException::class);
        $this->expectExceptionMessage('RSQL query is missing a value in filter for property "id"');
        Lexer::tokenize('id=like=');
    }

    public static function unclosedGroupProvider()
    {
        return [
            ['(id=like=1'],
            ['name==Test1,((id=like=1),(name==Test2)']
        ];
    }

    #[DataProvider('unclosedGroupProvider')]
    public function testUnclosedGroup(string $query)
    {
        $this->expectException(RSQLException::class);
        $this->expectExceptionMessage('RSQL query has one or more unclosed groups');
        Lexer::tokenize($query);
    }
}
