<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\System\Requirement;

class PhpSupportedVersion extends \GLPITestCase
{
    protected function versionProvider(): iterable
    {
        yield [
            'phpversion' => '7.4.0-rc1',
            'validated'  => false,
            'messages'   => [
                'PHP 7.4 is no longer maintained by its community.',
                'Even if GLPI still supports this PHP version, an upgrade to a more recent PHP version is recommended.',
                'Indeed, this PHP version may contain unpatched security vulnerabilities.',
            ],
        ];

        yield [
            'phpversion' => '7.4.3',
            'validated'  => false,
            'messages'   => [
                'PHP 7.4 is no longer maintained by its community.',
                'Even if GLPI still supports this PHP version, an upgrade to a more recent PHP version is recommended.',
                'Indeed, this PHP version may contain unpatched security vulnerabilities.',
            ],
        ];

        yield [
            'phpversion' => '7.4.99',
            'validated'  => false,
            'messages'   => [
                'PHP 7.4 is no longer maintained by its community.',
                'Even if GLPI still supports this PHP version, an upgrade to a more recent PHP version is recommended.',
                'Indeed, this PHP version may contain unpatched security vulnerabilities.',
            ],
        ];

        yield [
            'phpversion' => '8.0.0-rc1',
            'validated'  => false,
            'messages'   => [
                'PHP 8.0 is no longer maintained by its community.',
                'Even if GLPI still supports this PHP version, an upgrade to a more recent PHP version is recommended.',
                'Indeed, this PHP version may contain unpatched security vulnerabilities.',
            ],
        ];

        yield [
            'phpversion' => '8.0.15',
            'validated'  => false,
            'messages'   => [
                'PHP 8.0 is no longer maintained by its community.',
                'Even if GLPI still supports this PHP version, an upgrade to a more recent PHP version is recommended.',
                'Indeed, this PHP version may contain unpatched security vulnerabilities.',
            ],
        ];

        yield [
            'phpversion' => '8.1.0-rc1',
            'validated'  => true,
            'messages'   => [],
        ];

        yield [
            'phpversion' => '8.1.7',
            'validated'  => true,
            'messages'   => [],
        ];

        yield [
            'phpversion' => '8.2.0-alpha3',
            'validated'  => true,
            'messages'   => [],
        ];

        yield [
            'phpversion' => '8.2.34',
            'validated'  => true,
            'messages'   => [],
        ];

        yield [
            'phpversion' => '8.3.0-dev',
            'validated'  => true,
            'messages'   => [],
        ];

        yield [
            'phpversion' => '8.3.1',
            'validated'  => true,
            'messages'   => [],
        ];
    }

    /**
     * @dataProvider versionProvider
     */
    public function testCheck(string $phpversion, bool $validated, array $messages): void
    {
        $this->function->phpversion = $phpversion;

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo($validated);
        $this->array($this->testedInstance->getValidationMessages())->isEqualTo($messages);
    }
}
