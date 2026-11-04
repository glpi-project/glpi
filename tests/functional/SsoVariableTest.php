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
use PHPUnit\Framework\Attributes\DataProvider;
use SsoVariable;

class SsoVariableTest extends DbTestCase
{
    public static function serverVariableProvider(): iterable
    {
        // base data
        yield [
            'name' => 'REMOTE_USER',
            'safe' => true,
        ];
        yield [
            'name' => 'USERNAME',
            'safe' => true,
        ];
        yield [
            'name' => 'REDIRECT_REMOTE_USER',
            'safe' => true,
        ];

        // unsafe headers
        yield [
            'name' => 'PHP_AUTH_USER',
            'safe' => false,
        ];
        yield [
            'name' => 'HTTP_AUTH_USER',
            'safe' => false,
        ];
        yield [
            'name' => 'HTTP_REMOTE_USER',
            'safe' => false,
        ];
        yield [
            'name' => 'HTTP_WHATEVER',
            'safe' => false,
        ];

        // custom variables
        yield [
            'name' => 'INTERNAL_AUTH',
            'safe' => true,
        ];
        yield [
            'name' => 'HTTPSOMETHING', // do not match `HTTP_*`
            'safe' => true,
        ];
    }

    #[DataProvider('serverVariableProvider')]
    public function testIsSafe(
        string $name,
        bool $safe,
    ): void {
        global $DB;

        // Be sure to have one and only one variable with the given name.
        $DB->delete(SsoVariable::getTable(), ['name' => $name]);
        $DB->insert(SsoVariable::getTable(), ['name' => $name]);

        $sso_variable = \getItemByTypeName(SsoVariable::class, $name);

        $this->assertEquals($safe, $sso_variable->isSafe());
    }

    #[DataProvider('serverVariableProvider')]
    public function testIsServerVariableSafe(
        string $name,
        bool $safe,
    ): void {
        $instance = new SsoVariable();

        $this->assertEquals(
            $safe,
            $this->callPrivateMethod($instance, 'isServerVariableSafe', $name)
        );
    }

    public static function inputProvider(): iterable
    {
        yield [
            'input'  => [],
            'result' => [],
            'errors' => [],
        ];

        yield [
            'input'  => [
                'name'   => null,
            ],
            'result' => [
                'name'   => null,
            ],
            'errors' => [],
        ];

        foreach (self::serverVariableProvider() as $case) {
            if ($case['safe']) {
                yield [
                    'input'  => [
                        'name' => $case['name'],
                    ],
                    'result' => [
                        'name' => $case['name'],
                    ],
                    'errors' => [],
                ];
            } else {
                yield [
                    'input'  => [
                        'name' => $case['name'],
                    ],
                    'result' => false,
                    'errors' => [
                        sprintf('The %s variable is unsafe. It should not be used for authentication.', $case['name']),
                    ],
                ];
            }
        }
    }

    #[DataProvider('inputProvider')]
    public function testPrepareInputForAdd(
        array $input,
        mixed $result,
        array $errors
    ): void {
        $sso_variable = new SsoVariable();
        $this->assertEquals($result, $sso_variable->prepareInputForAdd($input));

        if (count($errors) > 0) {
            $this->hasSessionMessages(ERROR, $errors);
        }
    }

    #[DataProvider('inputProvider')]
    public function testPrepareInputForUpdate(
        array $input,
        mixed $result,
        array $errors
    ): void {
        $sso_variable = new SsoVariable();
        $this->assertEquals($result, $sso_variable->prepareInputForUpdate($input));

        if (count($errors) > 0) {
            $this->hasSessionMessages(ERROR, $errors);
        }
    }
}
