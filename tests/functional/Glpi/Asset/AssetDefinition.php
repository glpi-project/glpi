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

namespace tests\units\Glpi\Asset;

use Computer;
use DbTestCase;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Asset\Capacity\HasInfocomCapacity;
use Profile;

class AssetDefinition extends DbTestCase
{
    protected function updateInputProvider(): iterable
    {
        // Capacities inputs
        yield [
            'input'    => [
                'capacities' => [
                    HasDocumentsCapacity::class,
                    HasInfocomCapacity::class,
                ],
            ],
            'output'   => [
                'capacities' => json_encode([
                    HasDocumentsCapacity::class,
                    HasInfocomCapacity::class,
                ]),
            ],
            'messages' => [],
        ];

        yield [
            'input'    => [
                'capacities' => [
                    Computer::class, // not a capacity
                    HasInfocomCapacity::class,
                ],
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: "Capacities".',
                ],
            ],
        ];

        yield [
            'input'    => [
                'capacities' => 'not a valid capacity input',
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: "Capacities".',
                ],
            ],
        ];

        // Profiles input
        $self_service_p_id = getItemByTypeName(Profile::class, 'Self-Service', true);
        $admin_p_id        = getItemByTypeName(Profile::class, 'Admin', true);
        $valid_profiles_input = [
            $self_service_p_id => [
                READ => 1,
                CREATE => 0,
                UPDATE => 0,
                DELETE => 0,
            ],
            $admin_p_id => [
                READ => 1,
                CREATE => 1,
                UPDATE => 1,
                DELETE => 1,
            ],
        ];
        yield [
            'input'    => [
                'profiles' => $valid_profiles_input,
            ],
            'output'   => [
                'profiles' => json_encode($valid_profiles_input),
            ],
            'messages' => [],
        ];

        yield [
            'input'    => [
                'profiles' => [
                    999999999 => [ // invalid profile ID
                        READ => 1,
                        CREATE => 0,
                        UPDATE => 0,
                        DELETE => 0,
                    ],
                ],
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: "Profiles".',
                ],
            ],
        ];

        yield [
            'input'    => [
                'profiles' => [
                    $self_service_p_id => [
                        'read' => 1, // invalid right value
                    ],
                ],
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: "Profiles".',
                ],
            ],
        ];

        yield [
            'input'    => [
                'profiles' => [
                    $self_service_p_id => [
                        READ => 'a', // invalid boolean value
                        UPDATE => 0,
                    ],
                ],
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: "Profiles".',
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateInputProvider
     */
    public function testPrepareInputForUpdate(array $input, array|false $output, array $messages): void
    {
        $definition = $this->newTestedInstance();

        $this->variable($definition->prepareInputForUpdate($input))->isEqualTo($output);

        foreach ($messages as $level => $level_messages) {
            $this->hasSessionMessages($level, $level_messages);
        }
    }

    protected function addInputProvider(): iterable
    {
        yield [
            'input'    => [],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The system name is mandatory.',
                ],
            ],
        ];

        // start at 32 to ignore control chars
        // stop at 8096, no need to test the whole UTF-8 charset
        for ($i = 32; $i < 8096; $i++) {
            $char = mb_chr($i);
            if ($char === false) {
                continue;
            }

            $system_name = sprintf('TestAsset%s', $char);
            if (
                ($char >= "A" && $char <= "Z") // A -> Z
                || ($char >= "a" && $char <= "z") // a -> z
            ) {
                yield [
                    'input'    => [
                        'system_name' => $system_name,
                    ],
                    'output'   => [
                        'system_name' => $system_name,
                        'capacities'  => '[]',
                        'profiles'    => '[]',
                    ],
                    'messages' => [],
                ];
            } else {
                yield [
                    'input'    => [
                        'system_name' => $system_name,
                    ],
                    'output'   => false,
                    'messages' => [
                        ERROR => [
                            'The following field has an incorrect value: "System name".',
                        ],
                    ],
                ];
            }
        }

        // System name must not end with `Model` suffix
        yield [
            'input'    => [
                'system_name' => 'TestAssetModel',
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: "System name".',
                ],
            ],
        ];
        // but system name can contains `Model`
        yield [
            'input'    => [
                'system_name' => 'TestAssetModeling',
            ],
            'output'   => [
                'system_name' => 'TestAssetModeling',
                'capacities'  => '[]',
                'profiles'    => '[]',
            ],
            'messages' => [],
        ];

        // System name must not end with `Type` suffix
        yield [
            'input'    => [
                'system_name' => 'TestAssetType',
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: "System name".',
                ],
            ],
        ];
        // but system name can contains `Type`
        yield [
            'input'    => [
                'system_name' => 'TestAssetTyped',
            ],
            'output'   => [
                'system_name' => 'TestAssetTyped',
                'capacities'  => '[]',
                'profiles'    => '[]',
            ],
            'messages' => [],
        ];

        foreach ($this->updateInputProvider() as $data) {
            if (!array_key_exists('system_name', $data['input'])) {
                // `system_name` is mandatory on add
                $data['input']['system_name'] = __FUNCTION__;
                if (is_array($data['output'])) {
                    $data['output']['system_name'] = __FUNCTION__;
                }
            }
            if (is_array($data['output']) && !array_key_exists('capacities', $data['output'])) {
                // default value for `capacities`
                $data['output']['capacities'] = '[]';
            }
            if (is_array($data['output']) && !array_key_exists('profiles', $data['output'])) {
                // default value for `profiles`
                $data['output']['profiles'] = '[]';
            }
            yield $data;
        }
    }

    /**
     * @dataProvider addInputProvider
     */
    public function testPrepareInputForAdd(array $input, array|false $output, array $messages): void
    {
        $definition = $this->newTestedInstance();

        $this->variable($definition->prepareInputForAdd($input))->isEqualTo($output);

        foreach ($messages as $level => $level_messages) {
            $this->hasSessionMessages($level, $level_messages);
        }
    }

    public function testSystemNameUpdate(): void
    {
        $definition = $this->createItem(
            \Glpi\Asset\AssetDefinition::class,
            [
                'system_name' => 'test',
            ]
        );

        $updated = $definition->update([
            'id' => $definition->getID(),
            'system_name' => 'changed',
        ]);
        $this->boolean($updated)->isFalse();
        $this->hasSessionMessages(ERROR, ['The system name cannot be changed.']);
    }

    public function testDelete()
    {
        /** @var \Glpi\Asset\AssetDefinition $definition */
        $definition = $this->initAssetDefinition('test');
        \Glpi\Asset\AssetDefinitionManager::getInstance()->boostrapAssets();

        $this->createItem(
            $definition->getAssetClassName(),
            [
                'name' => 'test',
            ]
        );

        $this->boolean($definition->delete([
            'id' => $definition->getID(),
        ]))->isTrue();
        $this->array(getAllDataFromTable('glpi_assets_assets', [
            'assets_assetdefinitions_id' => $definition->getID(),
        ]))->size->isEqualTo(0);
    }

    public function testUpdateRights()
    {
        $definition = $this->initAssetDefinition('test');
        $super_admin_id = getItemByTypeName(Profile::class, 'Super-Admin', true);
        \ProfileRight::updateProfileRights(
            profiles_id: $super_admin_id,
            rights: [
                $definition->getAssetRightname() => ALLSTANDARDRIGHT
            ]
        );
        // Refresh
        $definition->getFromDB($definition->getID());

        $this->integer(json_decode($definition->fields['profiles'], true)[$super_admin_id])->isEqualTo(ALLSTANDARDRIGHT);

        // Make the definition inactive and verify the rights are removed from the profilerights table
        $definition->update([
            'id' => $definition->getID(),
            'is_active' => 0,
        ]);
        $this->array(getAllDataFromTable('glpi_profilerights', [
            'profiles_id' => $super_admin_id,
            'name' => $definition->getAssetRightname(),
        ]))->size->isEqualTo(0);

        // Make the definition active again and verify the rights are added back to the profilerights table
        $definition->update([
            'id' => $definition->getID(),
            'is_active' => 1,
        ]);
        $this->integer(json_decode($definition->fields['profiles'], true)[$super_admin_id])->isEqualTo(ALLSTANDARDRIGHT);
    }
}
