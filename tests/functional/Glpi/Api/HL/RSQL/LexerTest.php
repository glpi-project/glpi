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

namespace tests\units\Glpi\Api\HL\RSQL;

use Glpi\Api\HL\RSQL\Lexer;
use Glpi\Api\HL\RSQL\RSQLException;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LexerTest extends GLPITestCase
{
    public static function tokenizeProvider()
    {
        return [
            [
                'id==20',
                [[Lexer::T_PROPERTY, 'id'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, '20']],
            ],
            [
                '((model.name=in=(A2696,A2757,A2777);name=like=*Staff*),(model.name=in=(A2602,A2604,A2603,A2605);name=like=*Student*)),name=in=(A2436,A2764,A2437,A2766)',
                [
                    [Lexer::T_GROUP_OPEN, "("],[Lexer::T_GROUP_OPEN, "("], [Lexer::T_PROPERTY,"model.name"], [Lexer::T_OPERATOR, "=in="], [Lexer::T_VALUE, "(A2696,A2757,A2777)"],
                    [Lexer::T_AND, ";"], [Lexer::T_PROPERTY, "name"], [Lexer::T_OPERATOR, "=like="], [Lexer::T_VALUE, "*Staff*"],
                    [Lexer::T_GROUP_CLOSE, ")"], [Lexer::T_OR, ","], [Lexer::T_GROUP_OPEN, "("],
                    [Lexer::T_PROPERTY, "model.name"], [Lexer::T_OPERATOR, "=in="], [Lexer::T_VALUE, "(A2602,A2604,A2603,A2605)"],
                    [Lexer::T_AND, ";"], [Lexer::T_PROPERTY, "name"], [Lexer::T_OPERATOR, "=like="], [Lexer::T_VALUE, "*Student*"],
                    [Lexer::T_GROUP_CLOSE, ")"], [Lexer::T_GROUP_CLOSE, ")"], [Lexer::T_OR, ","],
                    [Lexer::T_PROPERTY, "name"],[Lexer::T_OPERATOR, "=in="], [Lexer::T_VALUE, "(A2436,A2764,A2437,A2766)"],
                ],
            ],
            [
                'name==(test', // In this case, "(test" is a valid value
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, '(test']],
            ],
            [
                'name!=test', // Only operator that doesn't start with '='
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '!='], [Lexer::T_VALUE, 'test']],
            ],
            [
                'name=in=test',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=in='], [Lexer::T_VALUE, 'test']],
            ],
            [
                'name=out=test',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=out='], [Lexer::T_VALUE, 'test']],
            ],
            [
                'name=lt=test',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=lt='], [Lexer::T_VALUE, 'test']],
            ],
            [
                'name=le=test',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=le='], [Lexer::T_VALUE, 'test']],
            ],
            [
                'name=gt=test',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=gt='], [Lexer::T_VALUE, 'test']],
            ],
            [
                'name=ge=test',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=ge='], [Lexer::T_VALUE, 'test']],
            ],
            [
                'name=like=test',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=like='], [Lexer::T_VALUE, 'test']],
            ],
            [
                'name=ilike=test',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=ilike='], [Lexer::T_VALUE, 'test']],
            ],
            [
                'name=isnull=',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=isnull='], [Lexer::T_UNSPECIFIED_VALUE, '']],
            ],
            [
                'name=notnull=',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notnull='], [Lexer::T_UNSPECIFIED_VALUE, '']],
            ],
            [
                'name=empty=',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=empty='], [Lexer::T_UNSPECIFIED_VALUE, '']],
            ],
            [
                'name=notempty=',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notempty='], [Lexer::T_UNSPECIFIED_VALUE, '']],
            ],
            [
                // Multibyte test
                'name==テスト',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, 'テスト']],
            ],
            [
                'is_deleted==0',
                [[Lexer::T_PROPERTY, 'is_deleted'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, '0']],
            ],
            [
                // No-value filter not at end of query
                'name=empty=;id==10',
                [
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=empty='], [Lexer::T_UNSPECIFIED_VALUE, ''], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'id'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, '10'],
                ],
            ],
            [
                'name=notlike=Test*',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notlike='], [Lexer::T_VALUE, 'Test*']],
            ],
            [
                'name=notilike=Test*',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, 'Test*']],
            ],
            [
                // Complex filter
                //https://github.com/glpi-project/glpi/issues/23936
                "is_deleted==False;(name=notilike='*(*',name=notilike='*)*',name=notilike='* *',name=notilike='.*',name=notilike='*.');location.id=out=(12,34);location.completename=notilike='Storage >*';name=ilike='*example.org';location.name=notnull=;location.name=notempty=",
                [
                    [Lexer::T_PROPERTY, 'is_deleted'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, 'False'], [Lexer::T_AND, ';'],
                    [Lexer::T_GROUP_OPEN, '('],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'*(*'"], [Lexer::T_OR, ','],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'*)*'"], [Lexer::T_OR, ','],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'* *'"], [Lexer::T_OR, ','],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'.*'"], [Lexer::T_OR, ','],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'*.'"],
                    [Lexer::T_GROUP_CLOSE, ')'], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'location.id'], [Lexer::T_OPERATOR, '=out='], [Lexer::T_VALUE, '(12,34)'], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'location.completename'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'Storage >*'"], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=ilike='], [Lexer::T_VALUE, "'*example.org'"], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'location.name'], [Lexer::T_OPERATOR, '=notnull='], [Lexer::T_UNSPECIFIED_VALUE, ''], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'location.name'], [Lexer::T_OPERATOR, '=notempty='], [Lexer::T_UNSPECIFIED_VALUE, ''],
                ],
            ],
            [
                // Complex filter with the no-value operators not at the end
                //https://github.com/glpi-project/glpi/issues/23936
                "is_deleted==False;location.name=notnull=;location.name=notempty=;(name=notilike='*(*',name=notilike='*)*',name=notilike='* *',name=notilike='.*',name=notilike='*.');location.id=out=(12,34);location.completename=notilike='Storage >*';name=ilike='*example.org'",
                [
                    [Lexer::T_PROPERTY, 'is_deleted'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, 'False'], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'location.name'], [Lexer::T_OPERATOR, '=notnull='], [Lexer::T_UNSPECIFIED_VALUE, ''], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'location.name'], [Lexer::T_OPERATOR, '=notempty='], [Lexer::T_UNSPECIFIED_VALUE, ''], [Lexer::T_AND, ';'],
                    [Lexer::T_GROUP_OPEN, '('],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'*(*'"], [Lexer::T_OR, ','],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'*)*'"], [Lexer::T_OR, ','],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'* *'"], [Lexer::T_OR, ','],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'.*'"], [Lexer::T_OR, ','],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'*.'"],
                    [Lexer::T_GROUP_CLOSE, ')'], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'location.id'], [Lexer::T_OPERATOR, '=out='], [Lexer::T_VALUE, '(12,34)'], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'location.completename'], [Lexer::T_OPERATOR, '=notilike='], [Lexer::T_VALUE, "'Storage >*'"], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=ilike='], [Lexer::T_VALUE, "'*example.org'"],
                ],
            ],
            [
                'itemtype==Computer;items_id=in=(1,2,3);name=like=*Test*',
                [
                    [Lexer::T_PROPERTY, 'itemtype'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, 'Computer'], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'items_id'], [Lexer::T_OPERATOR, '=in='], [Lexer::T_VALUE, '(1,2,3)'], [Lexer::T_AND, ';'],
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=like='], [Lexer::T_VALUE, '*Test*'],
                ],
            ],
            [
                // Filter with T_AND character as value
                'name=like=\;*',
                [
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=like='], [Lexer::T_VALUE, ';*'],
                ],
            ],
            [
                'name=like=\(*',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=like='], [Lexer::T_VALUE, '(*'],],
            ],
            [
                'name=like=\)*',
                [[Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=like='], [Lexer::T_VALUE, ')*'],],
            ],
            [
                // Filter with T_AND character as value and quoted
                'name=like=";*"',
                [
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=like='], [Lexer::T_VALUE, '";*"'],
                ],
            ],
            [
                'name==\\Test',
                [
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, '\Test'],
                ],
            ],
            [
                'name==\\\Test',
                [
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, '\Test'],
                ],
            ],
            [
                'name==\\\\Test',
                [
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, '\\Test'],
                ],
            ],
            [
                'name==\\\\\\\\Test',
                [
                    [Lexer::T_PROPERTY, 'name'], [Lexer::T_OPERATOR, '=='], [Lexer::T_VALUE, '\\\\Test'],
                ],
            ],
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
            ['id=l'],
        ];
    }

    #[DataProvider('incompleteOperatorProvider')]
    public function testIncompleteOperator(string $query)
    {
        $this->expectException(RSQLException::class);
        $this->expectExceptionMessage('RSQL query has an incomplete operator in filter for property "id"');
        Lexer::tokenize($query);
    }

    public static function unclosedGroupProvider()
    {
        return [
            ['(id=like=1'],
            ['name==Test1,((id=like=1),(name==Test2)'],
        ];
    }

    #[DataProvider('unclosedGroupProvider')]
    public function testUnclosedGroup(string $query)
    {
        $this->expectException(RSQLException::class);
        $this->expectExceptionMessage('RSQL query has one or more unclosed groups');
        Lexer::tokenize($query);
    }

    public function testValueAfterUnaryOperator(): void
    {
        $this->expectException(RSQLException::class);
        $this->expectExceptionMessage('RSQL query is missing an operator in filter for property "test"');
        Lexer::tokenize('name=notnull=test');
    }
}
