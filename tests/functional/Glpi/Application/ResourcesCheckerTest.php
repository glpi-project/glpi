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

namespace tests\units\Glpi\Application;

use Glpi\Application\ResourcesChecker;
use GLPITestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;

class ResourcesCheckerTest extends GLPITestCase
{
    public static function versionVfsStructureProvider(): iterable
    {
        yield 'no version dir' => [
            'structure' => [
            ],
            'expected'  => false,
        ];

        yield 'no version file' => [
            'structure' => [
                'version' => [],
            ],
            'expected'  => false,
        ];

        yield 'one version file' => [
            'structure' => [
                'version' => [
                    '11.0.0' => '',
                ],
            ],
            'expected'  => false,
        ];

        yield 'multiple version files' => [
            'structure' => [
                'version' => [
                    '10.0.19' => '',
                    '11.0.0' => '',
                ],
            ],
            'expected'  => true,
        ];
    }

    #[DataProvider('versionVfsStructureProvider')]
    public function testIsSourceCodeMixedOfMultipleVersions(array $structure, bool $expected): void
    {
        $root_dir = vfsStream::setup(structure: $structure);

        $resources_checker = new ResourcesChecker($root_dir->url());
        $this->assertEquals($expected, $this->callPrivateMethod($resources_checker, 'isSourceCodeMixedOfMultipleVersions'));
    }

    public static function dependenciesVfsStructureProvider(): iterable
    {
        // Compiled public dir + autoload without lock files -> assume dependencies are installed
        yield [
            'structure' => [
                'public' => [
                    'lib' => [
                    ],
                ],
                'vendor' => [
                    'autoload.php' => '...',
                ],
            ],
            'expected'  => true,
        ];

        // No `vendor/autoload.php` -> need dependencies install
        yield [
            'structure' => [
                'public' => [
                    'lib' => [
                    ],
                ],
            ],
            'expected'  => false,
        ];

        // No `public/lib` -> need dependencies install
        yield [
            'structure' => [
                'vendor' => [
                    'autoload.php' => '...',
                ],
            ],
            'expected'  => false,
        ];

        $composer_lock = <<<JSON
        {
            "_readme": [
                "This file locks the dependencies of your project to a known state",
                "Read more about it at https://getcomposer.org/doc/01-basic-usage.md#installing-dependencies",
                "This file is @generated automatically"
            ],
            "packages": [...],
            ...
        }
        JSON;
        $composer_hash = \sha1($composer_lock);

        $package_lock = <<<JSON
        {
            "name": "@glpi/glpi",
            "lockfileVersion": 3,
            "requires": true,
            "packages": [...],
            ...
        }
        JSON;
        $package_hash = \sha1($package_lock);

        // Synchronized lockfiles -> OK
        yield [
            'structure' => [
                'public' => [
                    'lib' => [
                    ],
                ],
                'vendor' => [
                    'autoload.php' => '...',
                ],
                '.composer.hash'    => $composer_hash,
                '.package.hash'     => $package_hash,
                'composer.lock'     => $composer_lock,
                'package-lock.json' => $package_lock,
            ],
            'expected'  => true,
        ];

        // Unsychronized `.composer.hash` -> need dependencies install
        yield [
            'structure' => [
                'public' => [
                    'lib' => [
                    ],
                ],
                'vendor' => [
                    'autoload.php' => '...',
                ],
                '.composer.hash'    => 'abcdefg',
                '.package.hash'     => $package_hash,
                'composer.lock'     => $composer_lock,
                'package-lock.json' => $package_lock,
            ],
            'expected'  => false,
        ];

        // No `.composer.hash` -> need dependencies install
        yield [
            'structure' => [
                'public' => [
                    'lib' => [
                    ],
                ],
                'vendor' => [
                    'autoload.php' => '...',
                ],
                '.package.hash'     => $package_hash,
                'composer.lock'     => $composer_lock,
                'package-lock.json' => $package_lock,
            ],
            'expected'  => false,
        ];

        // Unsychronized `.package.hash` -> need dependencies install
        yield [
            'structure' => [
                'public' => [
                    'lib' => [
                    ],
                ],
                'vendor' => [
                    'autoload.php' => '...',
                ],
                '.composer.hash'    => $composer_hash,
                '.package.hash'     => 'abcdefg',
                'composer.lock'     => $composer_lock,
                'package-lock.json' => $package_lock,
            ],
            'expected'  => false,
        ];

        // No `.package.hash` -> need dependencies install
        yield [
            'structure' => [
                'public' => [
                    'lib' => [
                    ],
                ],
                'vendor' => [
                    'autoload.php' => '...',
                ],
                '.composer.hash'    => $composer_hash,
                'composer.lock'     => $composer_lock,
                'package-lock.json' => $package_lock,
            ],
            'expected'  => false,
        ];
    }

    #[DataProvider('dependenciesVfsStructureProvider')]
    public function testAreDependenciesUpToDate(array $structure, bool $expected): void
    {
        $root_dir = vfsStream::setup(structure: $structure);

        $resources_checker = new ResourcesChecker($root_dir->url());
        $this->assertEquals($expected, $this->callPrivateMethod($resources_checker, 'areDependenciesUpToDate'));
    }
}
