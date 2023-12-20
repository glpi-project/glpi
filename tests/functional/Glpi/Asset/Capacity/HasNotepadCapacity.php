<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace tests\units\Glpi\Asset\Capacity;

use DbTestCase;
use DisplayPreference;
use Entity;
use Log;
use Notepad;
use Profile;

class HasNotepadCapacity extends DbTestCase
{
    public function testCapacityActivation(): void
    {
        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        // Capacity needs specific rights
        $superadmin_p_id = getItemByTypeName(Profile::class, 'Super-Admin', true);
        $profiles_matrix = [
            $superadmin_p_id => [
                READNOTE   => 1,
                UPDATENOTE => 1,
            ],
        ];

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
                \Glpi\Asset\Capacity\HasNotepadCapacity::class,
            ],
            profiles: $profiles_matrix
        );
        $classname_1  = $definition_1->getConcreteClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasDocumentsCapacity::class,
            ]
        );
        $classname_2  = $definition_2->getConcreteClassName();
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasDocumentsCapacity::class,
                \Glpi\Asset\Capacity\HasNotepadCapacity::class,
            ],
            profiles: $profiles_matrix
        );
        $classname_3  = $definition_3->getConcreteClassName();

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
                $this->array($item->defineAllTabs())->hasKey('Notepad$1');
            } else {
                $this->array($item->defineAllTabs())->notHasKey('Notepad$1');
            }

            // Check that the releated search options are available
            $so_keys = [
                200, // Content
                201, // Creation date
                202, // Writer
                203, // Last update
                204, // Last updater
            ];
            if ($has_notepad) {
                $this->array($item->getOptions())->hasKeys($so_keys);
            } else {
                $this->array($item->getOptions())->notHasKeys($so_keys);
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
                \Glpi\Asset\Capacity\HasNotepadCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getConcreteClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
                \Glpi\Asset\Capacity\HasNotepadCapacity::class,
            ]
        );
        $classname_2  = $definition_2->getConcreteClassName();

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
                'itemtype' => $item_1->getType(),
                'items_id' => $item_1->getID(),
                'content'  => 'A note related to asset 1',
            ]
        );
        $notepad_2 = $this->createItem(
            Notepad::class,
            [
                'itemtype' => $item_2->getType(),
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
        $this->object(Notepad::getById($notepad_1->getID()))->isInstanceOf(Notepad::class);
        $this->object(DisplayPreference::getById($displaypref_1->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(2); // creation + 1 note
        $this->object(Notepad::getById($notepad_2->getID()))->isInstanceOf(Notepad::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(2); // creation + 1 note

        // Disable capacity and check that infocoms relations have been cleaned
        $this->boolean($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]))->isTrue();
        $this->boolean(Notepad::getById($notepad_1->getID()))->isFalse();
        $this->boolean(DisplayPreference::getById($displaypref_1->getID()))->isFalse();
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(0);

        // Ensure infocom relations and logs are preserved for other definition
        $this->object(Notepad::getById($notepad_2->getID()))->isInstanceOf(Notepad::class);
        $this->object(DisplayPreference::getById($displaypref_2->getID()))->isInstanceOf(DisplayPreference::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(2);
    }

    public function testSpecificRights(): void
    {
        $root_entity_id  = getItemByTypeName(Entity::class, '_test_root_entity', true);
        $superadmin_p_id = getItemByTypeName(Profile::class, 'Super-Admin', true);

        $definition = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasNotepadCapacity::class,
            ]
        );
        $classname  = $definition->getConcreteClassName();

        $item = $this->createItem(
            $classname,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id
            ]
        );

        $notepad_1 = $this->createItem(
            Notepad::class,
            [
                'itemtype' => $item->getType(),
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
        $this->array($item->defineAllTabs())->notHasKey('Notepad$1');
        $this->boolean((new Notepad())->can(-1, CREATE, $new_notepad_input))->isFalse();
        $this->boolean((new Notepad())->can($notepad_1->getID(), UPDATE))->isFalse();

        // Check READNOTE right
        $updated = $definition->update([
            'id' => $definition->getID(),
            'profiles' => [
                $superadmin_p_id => [
                    READNOTE   => 1,
                    UPDATENOTE => 0,
                ],
            ],
        ]);
        $this->boolean($updated)->isTrue();
        $this->login();
        $this->array($item->defineAllTabs())->hasKey('Notepad$1');
        $this->boolean((new Notepad())->can(-1, CREATE, $new_notepad_input))->isFalse();
        $this->boolean((new Notepad())->can($notepad_1->getID(), UPDATE))->isFalse();

        // Check UPDATENOTE right
        $updated = $definition->update([
            'id' => $definition->getID(),
            'profiles' => [
                $superadmin_p_id => [
                    READNOTE   => 0,
                    UPDATENOTE => 1,
                ],
            ],
        ]);
        $this->boolean($updated)->isTrue();
        $this->login();
        $this->array($item->defineAllTabs())->notHasKey('Notepad$1');
        $this->boolean((new Notepad())->can(-1, CREATE, $new_notepad_input))->isTrue();
        $this->boolean((new Notepad())->can($notepad_1->getID(), UPDATE))->isTrue();
    }
}
