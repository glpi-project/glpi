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
use Glpi\Asset\AssetDefinitionManager;
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
                    'The following field has an incorrect value: &quot;Capacities&quot;.',
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
                    'The following field has an incorrect value: &quot;Capacities&quot;.',
                ],
            ],
        ];

        // Profiles input
        $self_service_p_id = getItemByTypeName(Profile::class, 'Self-Service', true);
        $admin_p_id        = getItemByTypeName(Profile::class, 'Admin', true);
        $valid_profiles_input = [
            $self_service_p_id => READ,
            $admin_p_id => READ | CREATE | UPDATE | DELETE,
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
                    999999999 => READ, // invalid profile ID
                ],
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: &quot;Profiles&quot;.',
                ],
            ],
        ];

        yield [
            'input'    => [
                'profiles' => [
                    $self_service_p_id => 'a', // invalid right value
                ],
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: &quot;Profiles&quot;.',
                ],
            ],
        ];

        // Translations input
        $valid_translations_input = [
            'en_US' => [
                'one'   => 'Computer',
                'other' => 'Computers',
            ],
            'fr_FR' => [
                'one'   => 'Ordinateur',
                'many'  => 'Ordinateurs',
                'other' => 'Ordinateurs',
            ],
        ];
        yield [
            'input'    => [
                'translations' => $valid_translations_input,
            ],
            'output'   => [
                'translations' => json_encode($valid_translations_input),
            ],
            'messages' => [],
        ];
        yield [
            'input'    => [
                'translations' => [
                    'invalid_lang' => [ // invalid language
                        'one'   => 'Computer',
                        'other' => 'Computers',
                    ],
                ],
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: &quot;Translations&quot;.',
                ],
            ],
        ];
        yield [
            'input'    => [
                'translations' => [
                    'en_US' => [
                        'one'       => 'Computer',
                        'very-much' => 'Computers', // invalid category
                    ],
                ],
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: &quot;Translations&quot;.',
                ],
            ],
        ];
        yield [
            'input'    => [
                'translations' => [
                    'en_US' => [
                        'one'   => 'Computer',
                        'other' => 15, // invalid value
                    ],
                ],
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: &quot;Translations&quot;.',
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
                        'system_name'  => $system_name,
                        'capacities'   => '[]',
                        'profiles'     => '[]',
                        'translations' => '[]',
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
                            'The following field has an incorrect value: &quot;System name&quot;.',
                        ],
                    ],
                ];
            }
        }

        foreach (AssetDefinitionManager::getInstance()->getReservedAssetsSystemNames() as $system_name) {
            // System name must not be a reserved name
            yield [
                'input'    => [
                    'system_name' => $system_name,
                ],
                'output'   => false,
                'messages' => [
                    ERROR => [
                        sprintf('The system name must not be the reserved word &quot;%s&quot;.', $system_name),
                    ],
                ],
            ];
            // but can start with it
            yield [
                'input'    => [
                    'system_name' => 'My' . $system_name,
                ],
                'output'   => [
                    'system_name'  => 'My' . $system_name,
                    'capacities'   => '[]',
                    'profiles'     => '[]',
                    'translations' => '[]',
                ],
                'messages' => [],
            ];
            // or end with it
            yield [
                'input'    => [
                    'system_name' => $system_name . 'NG',
                ],
                'output'   => [
                    'system_name'  => $system_name . 'NG',
                    'capacities'   => '[]',
                    'profiles'     => '[]',
                    'translations' => '[]',
                ],
                'messages' => [],
            ];
        }

        // System name must not end with `Model` suffix
        yield [
            'input'    => [
                'system_name' => 'TestAssetModel',
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The system name must not end with the word &quot;Model&quot; or the word &quot;Type&quot;.',
                ],
            ],
        ];
        // but system name can contains `Model`
        yield [
            'input'    => [
                'system_name' => 'TestAssetModeling',
            ],
            'output'   => [
                'system_name'  => 'TestAssetModeling',
                'capacities'   => '[]',
                'profiles'     => '[]',
                'translations' => '[]',
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
                    'The system name must not end with the word &quot;Model&quot; or the word &quot;Type&quot;.',
                ],
            ],
        ];
        // but system name can contains `Type`
        yield [
            'input'    => [
                'system_name' => 'TestAssetTyped',
            ],
            'output'   => [
                'system_name'  => 'TestAssetTyped',
                'capacities'   => '[]',
                'profiles'     => '[]',
                'translations' => '[]',
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
            if (is_array($data['output']) && !array_key_exists('translations', $data['output'])) {
                // default value for `translations`
                $data['output']['translations'] = '[]';
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
                'entities_id' => $this->getTestRootEntity(true),
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
        $technician_id  = getItemByTypeName(Profile::class, 'Technician', true);
        $super_admin_id = getItemByTypeName(Profile::class, 'Super-Admin', true);

        $definition = $this->initAssetDefinition(
            'test',
            profiles: [$super_admin_id => ALLSTANDARDRIGHT]
        );

        $rightname = $definition->getAssetRightname();

        // Validate that rights are properly defined at creation
        $this->checkProfileRights(
            $rightname,
            [
                $technician_id  => 0,
                $super_admin_id => ALLSTANDARDRIGHT,
            ]
        );

        // Update rightsfrom definition and ensure that rights are correctly created in the profilerights table
        $definition->setProfileRights($technician_id, READ | CREATE | UPDATE);

        $this->checkProfileRights(
            $rightname,
            [
                $technician_id  => READ | CREATE | UPDATE,
                $super_admin_id => ALLSTANDARDRIGHT,
            ]
        );

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

        $this->checkProfileRights(
            $rightname,
            [
                $technician_id  => READ | CREATE | UPDATE,
                $super_admin_id => ALLSTANDARDRIGHT,
            ]
        );
    }

    /**
     * Check that actual profile rights matches expected ones.
     *
     * @param string $rightname
     * @param array $expected_profilerights
     * @return void
     */
    private function checkProfileRights(string $rightname, array $expected_profilerights): void
    {
        $actual_profilerights = getAllDataFromTable('glpi_profilerights', ['name' => $rightname]);

        $get_actual_profileright_for_profile = static function (int $profile_id) use ($actual_profilerights): ?array {
            foreach ($actual_profilerights as $actual_profileright) {
                if ($profile_id === $actual_profileright['profiles_id']) {
                    unset($actual_profileright['id']);
                    return $actual_profileright;
                }
            }
            return null;
        };

        foreach ($expected_profilerights as $profile_id => $rights) {
            $actual_profileright = $get_actual_profileright_for_profile($profile_id);
            $this->array($actual_profileright)->isEqualTo(
                [
                    'profiles_id' => $profile_id,
                    'name'        => $rightname,
                    'rights'      => $rights,
                ]
            );
        }
    }


    public function testUpdateTranslations()
    {
        $definition = $this->createItem(
            \Glpi\Asset\AssetDefinition::class,
            [
                'system_name' => 'test',
            ]
        );

        $this->boolean($definition->update([
            'id' => $definition->getID(),
            '_save_translation' => true,
            'language' => 'en_US',
            'plurals' => [
                'one' => 'Test',
                'other' => 'Tests',
            ]
        ]))->isTrue();
        $this->boolean($definition->update([
            'id' => $definition->getID(),
            '_save_translation' => true,
            'language' => 'fr_FR',
            'plurals' => [
                'one' => 'Test FR',
                'other' => 'Tests FR',
            ]
        ]))->isTrue();

        $definition->getFromDB($definition->getID());
        $this->array(json_decode($definition->fields['translations'], true))->isEqualTo([
            'en_US' => [
                'one' => 'Test',
                'other' => 'Tests',
            ],
            'fr_FR' => [
                'one' => 'Test FR',
                'other' => 'Tests FR',
            ],
        ]);

        $this->boolean($definition->update([
            'id' => $definition->getID(),
            '_delete_translation' => true,
            'language' => 'en_US',

        ]))->isTrue();

        $definition->getFromDB($definition->getID());
        $this->array(json_decode($definition->fields['translations'], true))->isEqualTo([
            'fr_FR' => [
                'one' => 'Test FR',
                'other' => 'Tests FR',
            ],
        ]);
    }


    public function testGetTranslatedName()
    {
        /** @var \Glpi\Asset\AssetDefinition $definition */
        $definition = $this->createItem(
            \Glpi\Asset\AssetDefinition::class,
            [
                'system_name' => 'test',
                'translations' => [
                    'en_US' => [
                        'one' => 'Test',
                        'other' => 'Tests',
                    ],
                    'fr_FR' => [
                        'one' => 'Test FR',
                        'other' => 'Tests FR',
                    ],
                ],
            ],
            ['translations']
        );

        $_SESSION['glpilanguage'] = 'en_US';
        $this->string($definition->getTranslatedName(1))->isEqualTo('Test');
        $this->string($definition->getTranslatedName(10))->isEqualTo('Tests');

        $_SESSION['glpilanguage'] = 'fr_FR';
        $this->string($definition->getTranslatedName(1))->isEqualTo('Test FR');
        $this->string($definition->getTranslatedName(10))->isEqualTo('Tests FR');

        // untranslated language
        $_SESSION['glpilanguage'] = 'es_ES';
        $this->string($definition->getTranslatedName(1))->isEqualTo("test");
        $this->string($definition->getTranslatedName(10))->isEqualTo('test');
    }

    protected function pluralFormProvider(): iterable
    {
        yield [
            'language' => 'not a valid language',
            'expected' => [
            ]
        ];

        yield [
            'language' => 'en_US',
            'expected' => [
                ["id" => "one", "formula" => "n == 1", "examples" => "1"],
                ["id" => "other", "formula" => null, "examples" => "0, 2~16, 100, 1000, 10000, 100000, 1000000, …"]
            ]
        ];

        yield [
            'language' => 'fr_FR',
            'expected' => [
                ["id" => "one", "formula" => "(n == 0 || n == 1)", "examples" => "0, 1"],
                ["id" => "many", "formula" => "n != 0 && n % 1000000 == 0", "examples" => "1000000, 1c6, 2c6, 3c6, 4c6, 5c6, 6c6, …"],
                ["id" => "other", "formula" => null, "examples" => "2~17, 100, 1000, 10000, 100000, 1c3, 2c3, 3c3, 4c3, 5c3, 6c3, …"],
            ]
        ];
    }

    /**
     * @dataProvider pluralFormProvider
     */
    public function testGetPluralFormsForLanguage(string $language, array $expected)
    {
        $result = \Glpi\Asset\AssetDefinition::getPluralFormsForLanguage($language);
        $this->array($result)->hasSize(count($expected));
        foreach ($result as $index => $category) {
            $this->object($category)->isInstanceOf(\Gettext\Languages\Category::class);
            $this->variable($category->id)->isEqualTo($expected[$index]['id']);
            $this->variable($category->formula)->isEqualTo($expected[$index]['formula']);
            $this->variable($category->examples)->isEqualTo($expected[$index]['examples']);
        }
    }
}
