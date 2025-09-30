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

namespace tests\units\Glpi\Toolbox;

use Glpi\Toolbox\VersionParser;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test class for src/Glpi/Toolbox/versionparser.class.php
 */
class VersionParserTest extends \GLPITestCase
{
    public static function versionsProvider()
    {
        return [
            [
                'version'             => '',
                'keep_stability_flag' => false,
                'normalized'          => '',
                'major'               => '',
                'intermediate'        => '',
                'stable'              => true,
                'dev'                 => false,
            ],
            [
                'version'             => '9.5+2.0',
                'keep_stability_flag' => false,
                'normalized'          => '9.5+2.0', // not semver compatible, cannot be normalized
                'major'               => '9',
                'intermediate'        => '9.5',
                'stable'              => true,
                'dev'                 => false,
            ],
            [
                'version'             => '0.89',
                'keep_stability_flag' => false,
                'normalized'          => '0.89.0',
                'major'               => '0',
                'intermediate'        => '0.89',
                'stable'              => true,
                'dev'                 => false,
            ],
            [
                'version'             => '9.2',
                'keep_stability_flag' => false,
                'normalized'          => '9.2.0',
                'major'               => '9',
                'intermediate'        => '9.2',
                'stable'              => true,
                'dev'                 => false,
            ],
            [
                'version'             => '9.2',
                'keep_stability_flag' => true, // should have no effect
                'normalized'          => '9.2.0',
                'major'               => '9',
                'intermediate'        => '9.2',
                'stable'              => true,
                'dev'                 => false,
            ],
            [
                'version'             => '9.4.1.1',
                'keep_stability_flag' => false,
                'normalized'          => '9.4.1',
                'major'               => '9',
                'intermediate'        => '9.4',
                'stable'              => true,
                'dev'                 => false,
            ],
            [
                'version'             => '10.0.0-dev',
                'keep_stability_flag' => false,
                'normalized'          => '10.0.0',
                'major'               => '10',
                'intermediate'        => '10.0',
                'stable'              => false,
                'dev'                 => true,
            ],
            [
                'version'             => '10.0.0-dev',
                'keep_stability_flag' => true,
                'normalized'          => '10.0.0-dev',
                'major'               => '10',
                'intermediate'        => '10.0',
                'stable'              => false,
                'dev'                 => true,
            ],
            [
                'version'             => '10.0.0-alpha',
                'keep_stability_flag' => false,
                'normalized'          => '10.0.0',
                'major'               => '10',
                'intermediate'        => '10.0',
                'stable'              => false,
                'dev'                 => false,
            ],
            [
                'version'             => '10.0.0-alpha2',
                'keep_stability_flag' => true,
                'normalized'          => '10.0.0-alpha2',
                'major'               => '10',
                'intermediate'        => '10.0',
                'stable'              => false,
                'dev'                 => false,
            ],
            [
                'version'             => '10.0.0-beta1',
                'keep_stability_flag' => false,
                'normalized'          => '10.0.0',
                'major'               => '10',
                'intermediate'        => '10.0',
                'stable'              => false,
                'dev'                 => false,
            ],
            [
                'version'             => '10.0.0-beta1',
                'keep_stability_flag' => true,
                'normalized'          => '10.0.0-beta1',
                'major'               => '10',
                'intermediate'        => '10.0',
                'stable'              => false,
                'dev'                 => false,
            ],
            [
                'version'             => '10.0.0-rc3',
                'keep_stability_flag' => false,
                'normalized'          => '10.0.0',
                'major'               => '10',
                'intermediate'        => '10.0',
                'stable'              => false,
                'dev'                 => false,
            ],
            [
                'version'             => '10.0.0-rc',
                'keep_stability_flag' => true,
                'normalized'          => '10.0.0-rc',
                'major'               => '10',
                'intermediate'        => '10.0',
                'stable'              => false,
                'dev'                 => false,
            ],
            [
                'version'             => '10.0.3',
                'keep_stability_flag' => true,
                'normalized'          => '10.0.3',
                'major'               => '10',
                'intermediate'        => '10.0',
                'stable'              => true,
                'dev'                 => false,
            ],
        ];
    }

    #[DataProvider('versionsProvider')]
    public function testGetNormalizeVersion(string $version, bool $keep_stability_flag, string $normalized, string $major, string $intermediate, bool $stable, bool $dev): void
    {
        $version_parser = new VersionParser();
        $this->assertEquals($normalized, $version_parser->getNormalizedVersion($version, $keep_stability_flag));
    }

    #[DataProvider('versionsProvider')]
    public function testGetMajorVersion(string $version, bool $keep_stability_flag, string $normalized, string $major, string $intermediate, bool $stable, bool $dev): void
    {
        $version_parser = new VersionParser();
        $this->assertEquals($major, $version_parser->getMajorVersion($version));
    }

    #[DataProvider('versionsProvider')]
    public function testGetIntermediateVersion(string $version, bool $keep_stability_flag, string $normalized, string $major, string $intermediate, bool $stable, bool $dev): void
    {
        $version_parser = new VersionParser();
        $this->assertEquals($intermediate, $version_parser->getIntermediateVersion($version));
    }


    #[DataProvider('versionsProvider')]
    public function testIsStableRelease(string $version, bool $keep_stability_flag, string $normalized, string $major, string $intermediate, bool $stable, bool $dev): void
    {
        $version_parser = new VersionParser();
        $this->assertSame($stable, $version_parser->isStableRelease($version));
    }

    #[DataProvider('versionsProvider')]
    public function testIsDevVersion(string $version, bool $keep_stability_flag, string $normalized, string $major, string $intermediate, bool $stable, bool $dev): void
    {
        $version_parser = new VersionParser();
        $this->assertSame($dev, $version_parser->isDevVersion($version));
    }
}
