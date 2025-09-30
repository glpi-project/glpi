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

namespace tests\units\Glpi\Asset\Capacity;

use DbTestCase;
use DisplayPreference;
use Entity;
use Glpi\Asset\Asset;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Tests\Glpi\Asset\CapacityUsageTestTrait;
use Log;
use Notepad;
use Profile;

class HasNotepadCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return HasNotepadCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        // Capacity needs specific rights
        $superadmin_p_id = getItemByTypeName(Profile::class, 'Super-Admin', true);
        $profiles = [
            $superadmin_p_id => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
        ];

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
                new Capacity(name: HasNotepadCapacity::class),
            ],
            profiles: $profiles
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDocumentsCapacity::class),
            ],
            profiles: $profiles
        );
        $classname_2  = $definition_2->getAssetClassName();
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDocumentsCapacity::class),
                new Capacity(name: HasNotepadCapacity::class),
            ],
            profiles: $profiles
        );
        $classname_3  = $definition_3->getAssetClassName();

        $has_notepad_mapping = [
            $classname_1 => true,
            $classname_2 => false,
            $classname_3 => true,
        ];

        foreach ($has_notepad_mapping as $classname => $has_notepad) {
            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_notepad) {
                $this->assertArrayHasKey('Notepad$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('Notepad$1', $item->defineAllTabs());
            }

            // Check that the related search options are available
            $so_keys = [
                200, // Content
                201, // Creation date
                202, // Writer
                203, // Last update
                204, // Last updater
            ];
            $options = $item->getOptions();
            foreach ($so_keys as $so_key) {
                if ($has_notepad) {
                    $this->assertArrayHasKey($so_key, $options);
                } else {
                    $this->assertArrayNotHasKey($so_key, $options);
                }
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
                new Capacity(name: HasNotepadCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
                new Capacity(name: HasNotepadCapacity::class),
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();

        $item_1          = $this->createItem(
            $classname_1,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );
        $item_2          = $this->createItem(
            $classname_2,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );

        $notepad_1 = $this->createItem(
            Notepad::class,
            [
                'itemtype' => $item_1::getType(),
                'items_id' => $item_1->getID(),
                'content'  => 'A note related to asset 1',
            ]
        );
        $notepad_2 = $this->createItem(
            Notepad::class,
            [
                'itemtype' => $item_2::getType(),
                'items_id' => $item_2->getID(),
                'content'  => 'A note related to asset 2',
            ]
        );
        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'num'      => '200', // Notepad: content
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'num'      => '200', // Notepad: content
                'users_id' => 0,
            ]
        );

        $item_1_logs_criteria = [
            'itemtype' => $classname_1,
        ];
        $item_2_logs_criteria = [
            'itemtype' => $classname_2,
        ];

        // Ensure infocom relation, display preferences and logs exists
        $this->assertInstanceOf(Notepad::class, Notepad::getById($notepad_1->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); // creation + 1 note
        $this->assertInstanceOf(Notepad::class, Notepad::getById($notepad_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); // creation + 1 note

        // Disable capacity and check that infocoms relations have been cleaned
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(Notepad::getById($notepad_1->getID()));
        $this->assertFalse(DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));

        // Ensure infocom relations and logs are preserved for other definition
        $this->assertInstanceOf(Notepad::class, Notepad::getById($notepad_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
    }

    public function testSpecificRights(): void
    {
        $root_entity_id  = getItemByTypeName(Entity::class, '_test_root_entity', true);
        $superadmin_p_id = getItemByTypeName(Profile::class, 'Super-Admin', true);

        $definition = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasNotepadCapacity::class),
            ]
        );
        $classname  = $definition->getAssetClassName();

        $item = $this->createItem(
            $classname,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );

        $notepad_1 = $this->createItem(
            Notepad::class,
            [
                'itemtype' => $item::getType(),
                'items_id' => $item->getID(),
                'content'  => 'A note related to the asset',
            ]
        );

        $new_notepad_input = [
            'itemtype' => $classname,
            'items_id' => $item->getID(),
            'content'  => 'A new note',
        ];

        // By default, no rights are enabled on Notepad
        $this->login();
        $this->assertArrayNotHasKey('Notepad$1', $item->defineAllTabs());
        $this->assertFalse((new Notepad())->can(-1, CREATE, $new_notepad_input));
        $this->assertFalse((new Notepad())->can($notepad_1->getID(), UPDATE));

        // Check READNOTE right
        $updated = $definition->update([
            'id' => $definition->getID(),
            'profiles' => [
                $superadmin_p_id => ALLSTANDARDRIGHT | READNOTE,
            ],
        ]);
        $this->assertTrue($updated);
        $this->login();
        $this->assertArrayHasKey('Notepad$1', $item->defineAllTabs());
        $this->assertFalse((new Notepad())->can(-1, CREATE, $new_notepad_input));
        $this->assertFalse((new Notepad())->can($notepad_1->getID(), UPDATE));

        // Check UPDATENOTE right
        $updated = $definition->update([
            'id' => $definition->getID(),
            'profiles' => [
                $superadmin_p_id => ALLSTANDARDRIGHT | UPDATENOTE,
            ],
        ]);
        $this->assertTrue($updated);
        $this->login();
        $this->assertArrayNotHasKey('Notepad$1', $item->defineAllTabs());
        $this->assertTrue((new Notepad())->can(-1, CREATE, $new_notepad_input));
        $this->assertTrue((new Notepad())->can($notepad_1->getID(), UPDATE));
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [new Capacity(name: HasNotepadCapacity::class)]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        /** @var Asset $asset */
        $asset = $this->createItem($class, [
            'name'        => 'Test asset',
            'entities_id' => $entity,
        ]);

        $this->createItem(Notepad::class, [
            'itemtype' => $asset::getType(),
            'items_id' => $asset->getID(),
            'content'  => 'A note related to the asset',
        ]);

        $this->assertGreaterThan(0, $clone_id = $asset->clone());
        $this->assertCount(
            1,
            getAllDataFromTable(Notepad::getTable(), [
                'itemtype' => $asset::getType(),
                'items_id' => $clone_id,
                'content'  => 'A note related to the asset',
            ])
        );
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => Notepad::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Notepad::class,
            'expected' => '%d notes attached to %d assets',
        ];
    }
}
