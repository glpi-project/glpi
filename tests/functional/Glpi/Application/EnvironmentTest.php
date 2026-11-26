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

namespace tests\units\Glpi\Application;

use Glpi\Application\Environment;
use Glpi\Tests\GLPITestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;

class EnvironmentTest extends GLPITestCase
{
    /**
     * @return iterable<string, array{structure: array<string, mixed>, expected: array<string, bool>}>
     */
    public static function shouldExpectResourcesToChangeProvider(): iterable
    {
        yield 'release archive' => [
            'structure' => [],
            'expected'  => [
                'production'  => false,
                'staging'     => false,
                'testing'     => true,
                'e2e_testing' => true,
                'development' => true,
            ],
        ];

        yield 'git checkout with a readable revision' => [
            'structure' => [
                '.git' => ['HEAD' => "0123456789abcdef0123456789abcdef01234567\n"],
            ],
            'expected'  => [
                'production'  => false,
                'staging'     => false,
                'testing'     => true,
                'e2e_testing' => true,
                'development' => true,
            ],
        ];

        // e.g. a repository using the reftable refs storage
        yield 'git checkout with an unreadable revision' => [
            'structure' => [
                '.git' => ['HEAD' => "ref: refs/heads/.invalid\n"],
            ],
            'expected'  => [
                'production'  => true,
                'staging'     => true,
                'testing'     => true,
                'e2e_testing' => true,
                'development' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $structure
     * @param array<string, bool> $expected
     */
    #[DataProvider('shouldExpectResourcesToChangeProvider')]
    public function testShouldExpectResourcesToChange(array $structure, array $expected): void
    {
        vfsStream::setup('glpi', null, $structure);

        foreach (Environment::cases() as $environment) {
            $this->assertSame(
                $expected[$environment->value],
                $environment->shouldExpectResourcesToChange(vfsStream::url('glpi')),
                $environment->value
            );
        }
    }
}
