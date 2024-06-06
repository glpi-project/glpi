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

class DbEngine extends \GLPITestCase
{
    protected function versionProvider()
    {
        return [
            [
                'version'   => '5.5.38-0ubuntu0.14.04.1',
                'validated' => false,
                'messages'  => ['Database engine version (5.5.38) is not supported. Minimum required version is MySQL 5.7.'],
            ],
            [
                'version'   => '5.6.46-log',
                'validated' => false,
                'messages'  => ['Database engine version (5.6.46) is not supported. Minimum required version is MySQL 5.7.'],
            ],
            [
                'version'   => '5.7.50-log',
                'validated' => true,
                'messages'  => ['Database engine version (5.7.50) is supported.'],
            ],
            [
                'version'   => '8.0.23-standard',
                'validated' => true,
                'messages'  => ['Database engine version (8.0.23) is supported.'],
            ],
            [
                'version'   => '10.1.48-MariaDB',
                'validated' => false,
                'messages'  => ['Database engine version (10.1.48) is not supported. Minimum required version is MariaDB 10.2.'],
            ],
            [
                'version'   => '10.2.36-MariaDB',
                'validated' => true,
                'messages'  => ['Database engine version (10.2.36) is supported.'],
            ],
            [
                'version'   => '10.3.28-MariaDB',
                'validated' => true,
                'messages'  => ['Database engine version (10.3.28) is supported.'],
            ],
            [
                'version'   => '10.4.8-MariaDB-1:10.4.8+maria~bionic',
                'validated' => true,
                'messages'  => ['Database engine version (10.4.8) is supported.'],
            ],
            [
                'version'   => '10.5.9-MariaDB',
                'validated' => true,
                'messages'  => ['Database engine version (10.5.9) is supported.'],
            ],
        ];
    }

    /**
     * @dataProvider versionProvider
     */
    public function testCheck(string $version, bool $validated, array $messages)
    {

        $this->mockGenerator->orphanize('__construct');
        $db = new \mock\DB();
        $this->calling($db)->getVersion = $version;

        $this->newTestedInstance($db);
        $this->boolean($this->testedInstance->isValidated())->isEqualTo($validated);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo($messages);
    }
}
