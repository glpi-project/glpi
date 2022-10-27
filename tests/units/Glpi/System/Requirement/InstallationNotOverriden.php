<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
    public function testCheckWithRealVersionDir()
    {
        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(true);
        $this->array($this->testedInstance->getValidationMessages())
            ->isEqualTo(
                [
                    'No files from previous GLPI version detected.',
                ]
            );
    }

    public function testCheckWithoutVersionDir()
    {
        vfsStream::setup(
            'root',
            null,
            []
        );

        $this->newTestedInstance(vfsStream::url('root/not-exists'));
        $this->boolean($this->testedInstance->isOutOfContext())->isEqualTo(true);
    }

    protected function versionDirectoryProvider(): iterable
    {
        // Empty .version directory
        yield [
            'files'          => [],
            'validated'      => false,
            'messages'       => [],
            'out_of_context' => true,
        ];

        // Unique version file that does not match current version
        yield [
            'files'          => [
                '10.0.3' => '',
            ],
            'validated'      => false,
            'messages'       => [
                'We detected files of previous versions of GLPI.',
                'Please update GLPI by following the procedure described in the installation documentation.',
            ],
        ];

        // Multiple version files
        yield [
            'files'          => [
                '10.0.3' => '',
                '10.0.4' => '',
            ],
            'validated'      => false,
            'messages'       => [
                'We detected files of previous versions of GLPI.',
                'Please update GLPI by following the procedure described in the installation documentation.',
            ],
        ];

        // Unique version file that does not matches current version
        $current_version = VersionParser::getNormalizedVersion(GLPI_VERSION, false);
        yield [
            'files'          => [
                $current_version => '',
            ],
            'validated'      => true,
            'messages'       => [
                'No files from previous GLPI version detected.',
            ],
        ];
    }

    /**
     * @dataProvider versionDirectoryProvider
     */
    public function testCheck(array $files, bool $validated, array $messages, bool $out_of_context = false)
    {
        vfsStream::setup(
            'root',
            null,
            [
                '.version' => $files,
            ]
        );

        $this->newTestedInstance(vfsStream::url('root/.version'));
        $this->boolean($this->testedInstance->isOutOfContext())->isEqualTo($out_of_context);
        $this->boolean($this->testedInstance->isValidated())->isEqualTo($validated, print_r($files, true));
        $this->array($this->testedInstance->getValidationMessages())->isEqualTo($messages);
    }
}
