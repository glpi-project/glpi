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

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use Update;

class UpdateTest extends \GLPITestCase
{
    public function testCurrents()
    {
        global $DB;
        $update = new Update($DB);
        $this->assertEquals([
            'dbversion' => GLPI_SCHEMA_VERSION,
            'language'  => 'en_GB',
            'version'   => GLPI_VERSION,
        ], $update->getCurrents());
    }

    public function testSetMigration()
    {
        global $DB;
        $update = new Update($DB);
        $migration = new \Migration(GLPI_VERSION);
        $this->assertInstanceOf(Update::class, $update->setMigration($migration));
    }

    public static function migrationsProvider(): iterable
    {
        $path = vfsStream::url('install/migrations');

        $migrations_910_to_921 = [
            [
                'file'           => $path . '/update_9.1.0_to_9.1.1.php',
                'function'       => 'update910to911',
                'target_version' => '9.1.1',
            ],
            [
                'file'           => $path . '/update_9.1.1_to_9.1.3.php',
                'function'       => 'update911to913',
                'target_version' => '9.1.3',
            ],
            [
                'file'           => $path . '/update_9.1.x_to_9.2.0.php',
                'function'       => 'update91xto920',
                'target_version' => '9.2.0',
            ],
            [
                'file'           => $path . '/update_9.2.0_to_9.2.1.php',
                'function'       => 'update920to921',
                'target_version' => '9.2.1',
            ],
        ];

        $migrations_921_to_941 = [
            [
                'file'           => $path . '/update_9.2.1_to_9.2.2.php',
                'function'       => 'update921to922',
                'target_version' => '9.2.2',
            ],
            [
                'file'           => $path . '/update_9.2.2_to_9.2.3.php',
                'function'       => 'update922to923',
                'target_version' => '9.2.3',
            ],
            [
                'file'           => $path . '/update_9.2.x_to_9.3.0.php',
                'function'       => 'update92xto930',
                'target_version' => '9.3.0',
            ],
            [
                'file'           => $path . '/update_9.3.0_to_9.3.1.php',
                'function'       => 'update930to931',
                'target_version' => '9.3.1',
            ],
            [
                'file'           => $path . '/update_9.3.1_to_9.3.2.php',
                'function'       => 'update931to932',
                'target_version' => '9.3.2',
            ],
            [
                'file'           => $path . '/update_9.3.x_to_9.4.0.php',
                'function'       => 'update93xto940',
                'target_version' => '9.4.0',
            ],
            [
                'file'           => $path . '/update_9.4.0_to_9.4.1.php',
                'function'       => 'update940to941',
                'target_version' => '9.4.1',
            ],
        ];

        $migrations_941_to_1006 = [
            [
                'file'           => $path . '/update_9.4.1_to_9.4.2.php',
                'function'       => 'update941to942',
                'target_version' => '9.4.2',
            ],
            [
                'file'           => $path . '/update_9.4.2_to_9.4.3.php',
                'function'       => 'update942to943',
                'target_version' => '9.4.3',
            ],
            [
                'file'           => $path . '/update_9.4.3_to_9.4.5.php',
                'function'       => 'update943to945',
                'target_version' => '9.4.5',
            ],
            [
                'file'           => $path . '/update_9.4.5_to_9.4.6.php',
                'function'       => 'update945to946',
                'target_version' => '9.4.6',
            ],
            [
                'file'           => $path . '/update_9.4.6_to_9.4.7.php',
                'function'       => 'update946to947',
                'target_version' => '9.4.7',
            ],
            [
                'file'           => $path . '/update_9.4.x_to_9.5.0.php',
                'function'       => 'update94xto950',
                'target_version' => '9.5.0',
            ],
            [
                'file'           => $path . '/update_9.5.1_to_9.5.2.php',
                'function'       => 'update951to952',
                'target_version' => '9.5.2',
            ],
            [
                'file'           => $path . '/update_9.5.2_to_9.5.3.php',
                'function'       => 'update952to953',
                'target_version' => '9.5.3',
            ],
            [
                'file'           => $path . '/update_9.5.3_to_9.5.4.php',
                'function'       => 'update953to954',
                'target_version' => '9.5.4',
            ],
            [
                'file'           => $path . '/update_9.5.4_to_9.5.5.php',
                'function'       => 'update954to955',
                'target_version' => '9.5.5',
            ],
            [
                'file'           => $path . '/update_9.5.5_to_9.5.6.php',
                'function'       => 'update955to956',
                'target_version' => '9.5.6',
            ],
            [
                'file'           => $path . '/update_9.5.6_to_9.5.7.php',
                'function'       => 'update956to957',
                'target_version' => '9.5.7',
            ],
            [
                'file'           => $path . '/update_9.5.x_to_10.0.0.php',
                'function'       => 'update95xto1000',
                'target_version' => '10.0.0',
            ],
            [
                'file'           => $path . '/update_10.0.0_to_10.0.1.php',
                'function'       => 'update1000to1001',
                'target_version' => '10.0.1',
            ],
            [
                'file'           => $path . '/update_10.0.1_to_10.0.2.php',
                'function'       => 'update1001to1002',
                'target_version' => '10.0.2',
            ],
            [
                'file'           => $path . '/update_10.0.2_to_10.0.3.php',
                'function'       => 'update1002to1003',
                'target_version' => '10.0.3',
            ],
            [
                'file'           => $path . '/update_10.0.3_to_10.0.4.php',
                'function'       => 'update1003to1004',
                'target_version' => '10.0.4',
            ],
            [
                'file'           => $path . '/update_10.0.4_to_10.0.5.php',
                'function'       => 'update1004to1005',
                'target_version' => '10.0.5',
            ],
            [
                'file'           => $path . '/update_10.0.5_to_10.0.6.php',
                'function'       => 'update1005to1006',
                'target_version' => '10.0.6',
            ],
        ];

        $path = vfsStream::url('install/migrations');

        // Validates version normalization (9.1 -> 9.1.0).
        yield [
            'current_version'     => '9.1',
            'force_latest'        => false,
            'expected_migrations' => array_merge(
                $migrations_910_to_921,
                $migrations_921_to_941,
                $migrations_941_to_1006,
            ),
        ];

        // Validate 9.2.2 specific case.
        yield [
            'current_version'     => '9.2.2',
            'force_latest'        => false,
            'expected_migrations' => array_merge(
                $migrations_921_to_941,
                $migrations_941_to_1006,
            ),
        ];

        // Validate version normalization (9.4.1.1 -> 9.4.1).
        yield [
            'current_version'     => '9.4.1.1',
            'force_latest'        => false,
            'expected_migrations' => $migrations_941_to_1006,
        ];

        // Dev versions always triggger latest migration
        foreach (['dev', 'alpha', 'alpha3', 'beta', 'beta1', 'rc', 'rc2'] as $version_suffix) {
            // "pre-release" version suffix should always trigger migration replay
            // when source version is a previous version
            yield [
                'current_version'     => sprintf('10.0.5-%s', $version_suffix),
                'force_latest'        => false,
                'expected_migrations' => [
                    [
                        'file'           => $path . '/update_10.0.4_to_10.0.5.php',
                        'function'       => 'update1004to1005',
                        'target_version' => '10.0.5',
                    ],
                    [
                        'file'           => $path . '/update_10.0.5_to_10.0.6.php',
                        'function'       => 'update1005to1006',
                        'target_version' => '10.0.6',
                    ],
                ],
            ];
            // and when source version is a latest version
            yield [
                'current_version'     => sprintf('10.0.6-%s', $version_suffix),
                'force_latest'        => false,
                'expected_migrations' => [
                    [
                        'file'           => $path . '/update_10.0.5_to_10.0.6.php',
                        'function'       => 'update1005to1006',
                        'target_version' => '10.0.6',
                    ],
                ],
            ];

            // Force latests does not duplicate latest in list
            yield [
                'current_version'     => sprintf('10.0.6-%s', $version_suffix),
                'force_latest'        => true,
                'expected_migrations' => [
                    [
                        'file'           => $path . '/update_10.0.5_to_10.0.6.php',
                        'function'       => 'update1005to1006',
                        'target_version' => '10.0.6',
                    ],
                ],
            ];
        }

        // Validate that list is empty when version matches latest version
        yield [
            'current_version'     => '10.0.6',
            'force_latest'        => false,
            'expected_migrations' => [
            ],
        ];

        // Validate force latest
        yield [
            'current_version'     => '10.0.6',
            'force_latest'        => true,
            'expected_migrations' => [
                [
                    'file'           => $path . '/update_10.0.5_to_10.0.6.php',
                    'function'       => 'update1005to1006',
                    'target_version' => '10.0.6',
                ],
            ],
        ];
    }

    #[DataProvider('migrationsProvider')]
    public function testGetMigrationsToDo(string $current_version, bool $force_latest, array $expected_migrations)
    {
        global $DB;
        vfsStream::setup(
            'install',
            null,
            [
                'migrations' => [
                    'update_0.90.x_to_9.1.0.php'  => '',
                    'update_10.0.0_to_10.0.1.php' => '',
                    'update_10.0.1_to_10.0.2.php' => '',
                    'update_10.0.2_to_10.0.3.php' => '',
                    'update_10.0.3_to_10.0.4.php' => '',
                    'update_10.0.4_to_10.0.5.php' => '',
                    'update_10.0.5_to_10.0.6.php' => '',
                    'update_9.1.0_to_9.1.1.php'   => '',
                    'update_9.1.1_to_9.1.3.php'   => '',
                    'update_9.1.x_to_9.2.0.php'   => '',
                    'update_9.2.0_to_9.2.1.php'   => '',
                    'update_9.2.1_to_9.2.2.php'   => '',
                    'update_9.2.2_to_9.2.3.php'   => '',
                    'update_9.2.x_to_9.3.0.php'   => '',
                    'update_9.3.0_to_9.3.1.php'   => '',
                    'update_9.3.1_to_9.3.2.php'   => '',
                    'update_9.3.x_to_9.4.0.php'   => '',
                    'update_9.4.0_to_9.4.1.php'   => '',
                    'update_9.4.1_to_9.4.2.php'   => '',
                    'update_9.4.2_to_9.4.3.php'   => '',
                    'update_9.4.3_to_9.4.5.php'   => '',
                    'update_9.4.5_to_9.4.6.php'   => '',
                    'update_9.4.6_to_9.4.7.php'   => '',
                    'update_9.4.x_to_9.5.0.php'   => '',
                    'update_9.5.1_to_9.5.2.php'   => '',
                    'update_9.5.2_to_9.5.3.php'   => '',
                    'update_9.5.3_to_9.5.4.php'   => '',
                    'update_9.5.4_to_9.5.5.php'   => '',
                    'update_9.5.5_to_9.5.6.php'   => '',
                    'update_9.5.6_to_9.5.7.php'   => '',
                    'update_9.5.x_to_10.0.0.php'  => '',
                ],
            ]
        );
        $update = new Update($DB, vfsStream::url('install/migrations'));
        $this->assertEquals($expected_migrations, $this->callPrivateMethod($update, 'getMigrationsToDo', $current_version, $force_latest));
    }
}
