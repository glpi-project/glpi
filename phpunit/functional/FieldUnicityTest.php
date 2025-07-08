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

use Computer;
use DbTestCase;
use FieldUnicity;
use PHPUnit\Framework\Attributes\DataProvider;

class FieldUnicityTest extends DbTestCase
{
    public static function inputProvider(): iterable
    {
        yield [
            'input'  => [
                'itemtype' => Computer::class,
            ],
            'result' => false,
            'errors' => ['It&#039;s mandatory to select a type and at least one field'],
        ];

        yield [
            'input'  => [
                'fields'   => '',
            ],
            'result' => false,
            'errors' => ['It&#039;s mandatory to select a type and at least one field'],
        ];

        yield [
            'input'  => [
                '_fields'  => [],
            ],
            'result' => false,
            'errors' => ['It&#039;s mandatory to select a type and at least one field'],
        ];

        yield [
            'input'  => [
                'itemtype' => Computer::class,
                'fields'   => '',
            ],
            'result' => false,
            'errors' => ['It&#039;s mandatory to select a type and at least one field'],
        ];

        yield [
            'input'  => [
                'itemtype' => Computer::class,
                'fields'   => 'name',
            ],
            'result' => [
                'itemtype' => Computer::class,
                'fields'   => 'name',
            ],
            'errors' => [],
        ];

        yield [
            'input'  => [
                'itemtype' => Computer::class,
                '_fields'  => ['name', 'serial'],
            ],
            'result' => [
                'itemtype' => Computer::class,
                'fields'   => 'name,serial',
            ],
            'errors' => [],
        ];
    }

    public static function addInputProvider(): iterable
    {
        yield [
            'input'  => [],
            'result' => false,
            'errors' => ['It&#039;s mandatory to select a type and at least one field'],
        ];

        yield from self::inputProvider();
    }

    #[DataProvider('addInputProvider')]
    public function testPrepareInputForAdd(
        array $input,
        mixed $result,
        array $errors
    ): void {
        $this->login();

        $fieldunicity = new FieldUnicity();
        $this->assertEquals($result, $fieldunicity->prepareInputForAdd($input));

        if (count($errors) > 0) {
            $this->hasSessionMessages(ERROR, $errors);
        }
    }

    public static function updateInputProvider(): iterable
    {
        yield from self::inputProvider();
    }

    #[DataProvider('updateInputProvider')]
    public function testPrepareInputForUpdate(
        array $input,
        mixed $result,
        array $errors
    ): void {
        $this->login();

        $fieldunicity = new FieldUnicity();
        $this->assertEquals($result, $fieldunicity->prepareInputForUpdate($input));

        if (count($errors) > 0) {
            $this->hasSessionMessages(ERROR, $errors);
        }
    }
}
