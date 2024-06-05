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

use Glpi\Toolbox\VersionParser;
use org\bovigo\vfs\vfsStream;

class InstallationNotOverriden extends \GLPITestCase
{
    protected function versionDirectoryProvider(): iterable
    {
        // Missing version directory
        // -> out of context
        yield [
            'files'            => null,
            'previous_version' => null,
            'validated'        => false,
            'messages'         => [],
            'out_of_context'   => true,
        ];

        // Empty version directory
        // -> out of context
        yield [
            'files'            => [],
            'previous_version' => null,
            'validated'        => false,
            'messages'         => [],
            'out_of_context'   => true,
        ];

        // Unique version file that matches current version during update from < GLPI 10.0.6
        // -> out of context
        $current_version = VersionParser::getNormalizedVersion(GLPI_VERSION, false);
        foreach ([null, '9.1', '9.5.9', '10.0.0-dev', '10.0.3', '10.0.5'] as $previous_version) {
            yield [
                'files'            => [
                    $current_version => '',
                ],
                'previous_version' => $previous_version,
                'validated'        => false,
                'messages'         => [],
                'out_of_context'   => true,
            ];
        }

        // Unique version file that does not match current version
        // -> invalidated
        yield [
            'files'            => [
                '10.0.3' => '',
            ],
            'previous_version' => null,
            'validated'        => false,
            'messages'         => [
                'We detected files of previous versions of GLPI.',
                'Please update GLPI by following the procedure described in the installation documentation.',
            ],
        ];

        // Multiple version files
        // -> invalidated
        yield [
            'files'            => [
                '10.0.3' => '',
                '10.0.4' => '',
            ],
            'previous_version' => null,
            'validated'        => false,
            'messages'         => [
                'We detected files of previous versions of GLPI.',
                'Please update GLPI by following the procedure described in the installation documentation.',
            ],
        ];

        // Unique version file that matches current version during update from >= GLPI 10.0.4
        // -> validated
        $current_version = VersionParser::getNormalizedVersion(GLPI_VERSION, false);
        foreach (['10.0.6', '10.0.7', '10.1.0-dev', '11.3.4'] as $previous_version) {
            yield [
                'files'            => [
                    $current_version => '',
                ],
                'previous_version' => $previous_version,
                'validated'        => true,
                'messages'         => [
                    'No files from previous GLPI version detected.',
                ],
                'out_of_context'   => false,
            ];
        }
    }

    /**
     * @dataProvider versionDirectoryProvider
     */
    public function testCheck(?array $files, ?string $previous_version, bool $validated, array $messages, bool $out_of_context = false)
    {
        vfsStream::setup('root', null, $files !== null ? ['version' => $files] : []);

        $this->mockGenerator->orphanize('__construct');
        $db = new \mock\DB();
        $this->calling($db)->tableExists = true;
        $this->calling($db)->fieldExists = true;
        $this->calling($db)->request = function ($query) use ($previous_version) {
            return new \ArrayIterator(
                [
                    [
                        'context' => 'core',
                        'name'    => 'version',
                        'value'   => $previous_version,
                    ]
                ]
            );
        };

        $this->newTestedInstance($db, vfsStream::url('root/version'));
        $this->boolean($this->testedInstance->isOutOfContext())->isEqualTo($out_of_context);
        $this->boolean($this->testedInstance->isValidated())->isEqualTo($validated);
        $this->array($this->testedInstance->getValidationMessages())->isEqualTo($messages);
    }
}
