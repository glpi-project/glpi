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

namespace tests\units\Glpi\Asset;

use Computer;
use DbTestCase;
use Gettext\Languages\Category;
use Glpi\Asset\AssetDefinition;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Asset\Capacity\HasInfocomCapacity;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile;

class AssetDefinitionTest extends DbTestCase
{
    public static function updateInputProvider(): iterable
    {
        // Capacities inputs
        yield [
            'input'    => [
                'capacities' => [
                    [
                        'name' => HasDocumentsCapacity::class,
                    ],
                    [
                        'name' => HasInfocomCapacity::class,
                    ],
                ],
            ],
            'output'   => [
                'capacities' => json_encode([
                    new Capacity(name: HasDocumentsCapacity::class),
                    new Capacity(name: HasInfocomCapacity::class),
                ]),
            ],
            'messages' => [],
        ];

        yield [
            'input'    => [
                'capacities' => [
                    [
                        'name' => Computer::class, // not a capacity
                    ],
                    [
                        'name' => HasInfocomCapacity::class,
                    ],
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

    #[DataProvider('updateInputProvider')]
    public function testPrepareInputForUpdate(array $input, array|false $output, array $messages): void
    {
        $definition = new AssetDefinition();

        $this->assertEquals($output, $definition->prepareInputForUpdate($input));

        foreach ($messages as $level => $level_messages) {
            $this->hasSessionMessages($level, $level_messages);
        }
    }

    public static function addInputProvider(): iterable
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

        yield [
            'input'    => [
                'system_name' => 'TestAsset',
                'label'       => 'Test Asset',
            ],
            'output'   => [
                'system_name'  => 'TestAsset',
                'label'        => 'Test Asset',
                'capacities'   => '[]',
                'profiles'     => '[]',
                'translations' => '[]',
                'fields_display' => '[]',
            ],
            'messages' => [],
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
                || ($char >= "0" && $char <= "9") // 0 -> 9
            ) {
                yield [
                    'input'    => [
                        'system_name' => $system_name,
                    ],
                    'output'   => [
                        'system_name'  => $system_name,
                        'label'        => $system_name,
                        'capacities'   => '[]',
                        'profiles'     => '[]',
                        'translations' => '[]',
                        'fields_display' => '[]',
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

        $reserved_names = [
            'Computer',
            'Monitor',
            'Software',
            'NetworkEquipment',
            'Peripheral',
            'Printer',
            'Cartridge',
            'Consumable',
            'Phone',
            'Rack',
            'Enclosure',
            'PDU',
            'PassiveDCEquipment',
            'Unmanaged',
            'Cable',
        ];
        foreach ($reserved_names as $system_name) {
            // System name must not be a reserved name
            yield [
                'input'    => [
                    'system_name' => $system_name,
                ],
                'output'   => false,
                'messages' => [
                    ERROR => [
                        'The system name is a reserved name.',
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
                    'label'        => 'My' . $system_name,
                    'capacities'   => '[]',
                    'profiles'     => '[]',
                    'translations' => '[]',
                    'fields_display' => '[]',
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
                    'label'        => $system_name . 'NG',
                    'capacities'   => '[]',
                    'profiles'     => '[]',
                    'translations' => '[]',
                    'fields_display' => '[]',
                ],
                'messages' => [],
            ];
        }

        // System name must not start with a number
        yield [
            'input'    => [
                'system_name' => '3DPrinter',
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: &quot;System name&quot;.',
                ],
            ],
        ];

        // System name can contain an underscore
        yield [
            'input'    => [
                'system_name' => 'Custom_Item',
            ],
            'output'   => [
                'system_name'  => 'Custom_Item',
                'label'        => 'Custom_Item',
                'capacities'   => '[]',
                'profiles'     => '[]',
                'translations' => '[]',
                'fields_display' => '[]',
            ],
            'messages' => [],
        ];

        // System name must not end with an underscore
        yield [
            'input'    => [
                'system_name' => 'CustomItem_',
            ],
            'output'   => false,
            'messages' => [
                ERROR => [
                    'The following field has an incorrect value: &quot;System name&quot;.',
                ],
            ],
        ];

        // System name can ends with `Model` suffix
        yield [
            'input'    => [
                'system_name' => 'TestAssetModel',
            ],
            'output'   => [
                'system_name'  => 'TestAssetModel',
                'label'        => 'TestAssetModel',
                'capacities'   => '[]',
                'profiles'     => '[]',
                'translations' => '[]',
                'fields_display' => '[]',
            ],
            'messages' => [],
        ];
        // and can contains `Model`
        yield [
            'input'    => [
                'system_name' => 'TestAssetModeling',
            ],
            'output'   => [
                'system_name'  => 'TestAssetModeling',
                'label'        => 'TestAssetModeling',
                'capacities'   => '[]',
                'profiles'     => '[]',
                'translations' => '[]',
                'fields_display' => '[]',
            ],
            'messages' => [],
        ];

        // System name can ends with `Type` suffix
        yield [
            'input'    => [
                'system_name' => 'TestAssetType',
            ],
            'output'   => [
                'system_name'  => 'TestAssetType',
                'label'        => 'TestAssetType',
                'capacities'   => '[]',
                'profiles'     => '[]',
                'translations' => '[]',
                'fields_display' => '[]',
            ],
            'messages' => [],
        ];
        // and can contains `Type`
        yield [
            'input'    => [
                'system_name' => 'TestAssetTyped',
            ],
            'output'   => [
                'system_name'  => 'TestAssetTyped',
                'label'        => 'TestAssetTyped',
                'capacities'   => '[]',
                'profiles'     => '[]',
                'translations' => '[]',
                'fields_display' => '[]',
            ],
            'messages' => [],
        ];

        foreach (self::updateInputProvider() as $data) {
            if (!array_key_exists('system_name', $data['input'])) {
                // `system_name` is mandatory on add
                $data['input']['system_name'] = __FUNCTION__;
                if (is_array($data['output'])) {
                    $data['output']['system_name'] = __FUNCTION__;
                    $data['output']['label'] = __FUNCTION__;
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
            if (is_array($data['output']) && !array_key_exists('fields_display', $data['output'])) {
                $data['output']['fields_display'] = '[]';
            }
            yield $data;
        }
    }

    #[DataProvider('addInputProvider')]
    public function testPrepareInputForAdd(array $input, array|false $output, array $messages): void
    {
        $definition = new AssetDefinition();

        $this->assertEquals($output, $definition->prepareInputForAdd($input));

        foreach ($messages as $level => $level_messages) {
            $this->hasSessionMessages($level, $level_messages);
        }
    }

    public function testUniqueSystemName(): void
    {
        $definition = new AssetDefinition();
        $this->assertGreaterThan(
            0,
            $definition->add([
                'system_name' => 'test',
                'label' => 'Test',
            ])
        );
        $definition = new AssetDefinition();
        $this->assertFalse($definition->add([
            'system_name' => 'test',
            'label' => 'Test',
        ]));
        $this->hasSessionMessages(ERROR, ['The system name must be unique.']);
    }

    public function testSystemNameUpdate(): void
    {
        $definition = $this->createItem(
            AssetDefinition::class,
            [
                'system_name' => 'test',
            ]
        );

        $updated = $definition->update([
            'id' => $definition->getID(),
            'system_name' => 'changed',
        ]);
        $this->assertFalse($updated);
        $this->hasSessionMessages(ERROR, ['The system name cannot be changed.']);
    }

    public function testDelete()
    {
        // Create the definition
        $definition = $this->initAssetDefinition('test');

        $concrete_classnames = [
            $definition->getAssetClassName() => true,
            $definition->getAssetModelClassName() => false,
            $definition->getAssetTypeClassName() => false,
        ];

        // Validate that there are display preferences that will have to be deleted
        foreach ($concrete_classnames as $classname) {
            $this->assertGreaterThan(
                0,
                getAllDataFromTable('glpi_displaypreferences', ['itemtype' => $classname])
            );
        }

        // Create some items
        foreach ($concrete_classnames as $classname => $has_entity) {
            $input = [
                'name' => 'test ' . $classname,
            ];
            if ($has_entity) {
                $input['entities_id'] = $this->getTestRootEntity(true);
            }
            $this->createItem($classname, $input);
        }

        // Delete the definition
        $this->assertTrue($definition->delete([
            'id' => $definition->getID(),
        ]));

        foreach (array_keys($concrete_classnames) as $classname) {
            // Items are deleted
            $this->assertCount(
                0,
                getAllDataFromTable($classname::getTable(), ['assets_assetdefinitions_id' => $definition->getID()])
            );

            // Display preferences are deleted
            $this->assertCount(
                0,
                getAllDataFromTable('glpi_displaypreferences', ['itemtype' => $classname])
            );
        }
    }

    public function testUpdateRights()
    {
        $technician_id  = getItemByTypeName(Profile::class, 'Technician', true);
        $super_admin_id = getItemByTypeName(Profile::class, 'Super-Admin', true);

        $definition = $this->initAssetDefinition(
            'test',
            profiles: [$super_admin_id => ALLSTANDARDRIGHT]
        );

        $rightname = $definition->getCustomObjectRightname();

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

        $this->assertCount(
            0,
            getAllDataFromTable(
                'glpi_profilerights',
                [
                    'profiles_id' => $super_admin_id,
                    'name' => $definition->getCustomObjectRightname(),
                ]
            )
        );

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
            $this->assertEquals(
                [
                    'profiles_id' => $profile_id,
                    'name'        => $rightname,
                    'rights'      => $rights,
                ],
                $actual_profileright
            );
        }
    }


    public function testUpdateTranslations()
    {
        $definition = $this->createItem(
            AssetDefinition::class,
            [
                'system_name' => 'test',
            ]
        );

        $this->assertTrue($definition->update([
            'id' => $definition->getID(),
            '_save_translation' => true,
            'language' => 'en_US',
            'plurals' => [
                'one' => 'Test',
                'other' => 'Tests',
            ],
        ]));
        $this->assertTrue($definition->update([
            'id' => $definition->getID(),
            '_save_translation' => true,
            'language' => 'fr_FR',
            'plurals' => [
                'one' => 'Test FR',
                'other' => 'Tests FR',
            ],
        ]));

        $definition->getFromDB($definition->getID());
        $this->assertEquals(
            [
                'en_US' => [
                    'one' => 'Test',
                    'other' => 'Tests',
                ],
                'fr_FR' => [
                    'one' => 'Test FR',
                    'other' => 'Tests FR',
                ],
            ],
            json_decode($definition->fields['translations'], true)
        );

        $this->assertTrue($definition->update([
            'id' => $definition->getID(),
            '_delete_translation' => true,
            'language' => 'en_US',

        ]));

        $definition->getFromDB($definition->getID());
        $this->assertEquals(
            [
                'fr_FR' => [
                    'one' => 'Test FR',
                    'other' => 'Tests FR',
                ],
            ],
            json_decode($definition->fields['translations'], true)
        );
    }


    public function testGetTranslatedName()
    {
        /** @var AssetDefinition $definition */
        $definition = $this->createItem(
            AssetDefinition::class,
            [
                'system_name' => 'test',
                'label' => 'Test',
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
        $this->assertEquals('Test', $definition->getTranslatedName(1));
        $this->assertEquals('Tests', $definition->getTranslatedName(10));

        $_SESSION['glpilanguage'] = 'fr_FR';
        $this->assertEquals('Test FR', $definition->getTranslatedName(1));
        $this->assertEquals('Tests FR', $definition->getTranslatedName(10));

        // untranslated language
        $_SESSION['glpilanguage'] = 'es_ES';
        $this->assertEquals('Test', $definition->getTranslatedName(1));
        $this->assertEquals('Test', $definition->getTranslatedName(10));
    }

    public static function pluralFormProvider(): iterable
    {
        yield [
            'language' => 'not a valid language',
            'expected' => [
            ],
        ];

        yield [
            'language' => 'en_US',
            'expected' => [
                ["id" => "one", "formula" => "n == 1", "examples" => "1"],
                ["id" => "other", "formula" => null, "examples" => "0, 2~16, 100, 1000, 10000, 100000, 1000000, …"],
            ],
        ];

        yield [
            'language' => 'fr_FR',
            'expected' => [
                ["id" => "one", "formula" => "(n == 0 || n == 1)", "examples" => "0, 1"],
                ["id" => "many", "formula" => "n != 0 && n % 1000000 == 0", "examples" => "1000000, 1c6, 2c6, 3c6, 4c6, 5c6, 6c6, …"],
                ["id" => "other", "formula" => null, "examples" => "2~17, 100, 1000, 10000, 100000, 1c3, 2c3, 3c3, 4c3, 5c3, 6c3, …"],
            ],
        ];
    }

    #[DataProvider('pluralFormProvider')]
    public function testGetPluralFormsForLanguage(string $language, array $expected): void
    {
        $result = AssetDefinition::getPluralFormsForLanguage($language);
        $this->assertCount(count($expected), $result);
        foreach ($result as $index => $category) {
            $this->assertInstanceOf(Category::class, $category);
            $this->assertEquals($expected[$index]['id'], $category->id);
            $this->assertEquals($expected[$index]['formula'], $category->formula);
            $this->assertEquals($expected[$index]['examples'], $category->examples);
        }
    }

    public function testSyncExtraProfileFields()
    {
        global $DB;

        $this->login();

        $definition = $this->createItem(
            AssetDefinition::class,
            ['system_name' => 'test', 'is_active' => 1]
        );

        $it = iterator_to_array($DB->request([
            'SELECT' => ['helpdesk_item_type'],
            'FROM' => 'glpi_profiles',
        ]), false);
        $this->assertEmpty(
            array_filter(
                array_column($it, 'helpdesk_item_type'),
                static fn($v) => in_array($definition->getAssetClassName(), json_decode($v ?? '[]'), true)
            )
        );

        $this->assertTrue($definition->update([
            'id' => $definition->getID(),
            '_profiles_extra' => [
                'helpdesk_item_type' => [
                    getItemByTypeName('Profile', 'Super-Admin', true),
                    getItemByTypeName('Profile', 'Technician', true),
                ],
            ],
        ]));
        $it = iterator_to_array($DB->request([
            'SELECT' => ['helpdesk_item_type'],
            'FROM' => 'glpi_profiles',
        ]), false);
        $this->assertCount(
            2,
            array_filter(
                array_column($it, 'helpdesk_item_type'),
                static fn($v) => in_array($definition->getAssetClassName(), json_decode($v ?? '[]', true), true)
            )
        );

        $this->assertTrue($definition->update([
            'id' => $definition->getID(),
            '_profiles_extra' => [
                'helpdesk_item_type' => [
                    getItemByTypeName('Profile', 'Super-Admin', true),
                ],
            ],
        ]));
        $it = iterator_to_array($DB->request([
            'SELECT' => ['helpdesk_item_type'],
            'FROM' => 'glpi_profiles',
        ]), false);
        $this->assertCount(
            1,
            array_filter(
                $it,
                static fn($v) => in_array($definition->getAssetClassName(), json_decode($v['helpdesk_item_type'] ?? '[]', true), true)
            )
        );
    }
}
