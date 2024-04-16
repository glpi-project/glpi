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

namespace tests\units\Glpi\Asset\Capacity;

use DbTestCase;
use Entity;
use Glpi\Asset\Asset;
use Item_SoftwareLicense;
use Item_SoftwareVersion;
use Log;
use Software;
use SoftwareLicense;
use SoftwareVersion;

class HasSoftwaresCapacity extends DbTestCase
{
    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasSoftwaresCapacity::class,
                \Glpi\Asset\Capacity\HasNotepadCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasSoftwaresCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $classname_3  = $definition_3->getAssetClassName();

        $has_capacity_mapping = [
            $classname_1 => true,
            $classname_2 => false,
            $classname_3 => true,
        ];

        foreach ($has_capacity_mapping as $classname => $has_capacity) {
            // Check that the class is globally registered
            if ($has_capacity) {
                $this->array($CFG_GLPI['software_types'])->contains($classname);
            } else {
                $this->array($CFG_GLPI['software_types'])->notContains($classname);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->array($item->defineAllTabs())->hasKey('Item_SoftwareVersion$1');
            } else {
                $this->array($item->defineAllTabs())->notHasKey('Item_SoftwareVersion$1');
            }

            // No SO to check
        }
    }

    public function testCapacityDeactivation(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasSoftwaresCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasSoftwaresCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
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

        $software = $this->createItem(
            Software::class,
            [
                'name' => 'Software',
                'entities_id' => $root_entity_id,
            ]
        );
        $software_version = $this->createItem(
            SoftwareVersion::class,
            [
                'name' => 'V1.0',
                'entities_id' => $root_entity_id,
                'softwares_id' => $software->getID(),
            ]
        );
        $item_software_1 = $this->createItem(
            Item_SoftwareVersion::class,
            [
                'entities_id' => $root_entity_id,
                'softwareversions_id' => $software_version->getID(),
                'itemtype' => $item_1->getType(),
                'items_id' => $item_1->getID(),
            ]
        );
        $item_software_2 = $this->createItem(
            Item_SoftwareVersion::class,
            [
                'entities_id' => $root_entity_id,
                'softwareversions_id' => $software_version->getID(),
                'itemtype' => $item_2->getType(),
                'items_id' => $item_2->getID(),
            ]
        );

        $item_1_logs_criteria = [
            'itemtype_link' => $classname_1,
        ];
        $item_2_logs_criteria = [
            'itemtype_link' => $classname_2,
        ];

        // Ensure relation and logs exists, and class is registered to global config
        $this->object(Item_SoftwareVersion::getById($item_software_1->getID()))->isInstanceOf(Item_SoftwareVersion::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(1);
        $this->object(Item_SoftwareVersion::getById($item_software_2->getID()))->isInstanceOf(Item_SoftwareVersion::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(1);
        $this->array($CFG_GLPI['software_types'])->contains($classname_1);
        $this->array($CFG_GLPI['software_types'])->contains($classname_2);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->boolean($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]))->isTrue();
        $this->boolean(Item_SoftwareVersion::getById($item_software_1->getID()))->isFalse();
        $this->integer(countElementsInTable(Log::getTable(), $item_1_logs_criteria))->isEqualTo(0);
        $this->array($CFG_GLPI['software_types'])->notContains($classname_1);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->object(Item_SoftwareVersion::getById($item_software_2->getID()))->isInstanceOf(Item_SoftwareVersion::class);
        $this->integer(countElementsInTable(Log::getTable(), $item_2_logs_criteria))->isEqualTo(1);
        $this->array($CFG_GLPI['software_types'])->contains($classname_2);
    }

    public function testCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [\Glpi\Asset\Capacity\HasSoftwaresCapacity::class]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        /** @var Asset $asset */
        $asset = $this->createItem($class, [
            'name'        => 'Test asset',
            'entities_id' => $entity,
        ]);

        $software = $this->createItem(
            Software::class,
            [
                'name' => 'Software',
                'entities_id' => $entity,
            ]
        );
        $software_version = $this->createItem(
            SoftwareVersion::class,
            [
                'name' => 'V1.0',
                'entities_id' => $entity,
                'softwares_id' => $software->getID(),
            ]
        );
        $this->createItem(
            Item_SoftwareVersion::class,
            [
                'entities_id' => $entity,
                'softwareversions_id' => $software_version->getID(),
                'itemtype' => $asset->getType(),
                'items_id' => $asset->getID(),
            ]
        );
        $software_license = $this->createItem(
            SoftwareLicense::class,
            [
                'name' => 'pro license',
                'entities_id' => $entity,
                'softwares_id' => $software->getID(),
            ]
        );
        $this->createItem(
            Item_SoftwareLicense::class,
            [
                'softwarelicenses_id' => $software_license->getID(),
                'itemtype' => $asset->getType(),
                'items_id' => $asset->getID(),
            ]
        );

        $this->integer($clone_id = $asset->clone())->isGreaterThan(0);
        $this->array(getAllDataFromTable(Item_SoftwareVersion::getTable(), [
            'softwareversions_id' => $software_version->getID(),
            'itemtype' => $asset::getType(),
            'items_id' => $clone_id,
        ]))->hasSize(1);
        $this->array(getAllDataFromTable(Item_SoftwareLicense::getTable(), [
            'softwarelicenses_id' => $software_license->getID(),
            'itemtype' => $asset::getType(),
            'items_id' => $clone_id,
        ]))->hasSize(1);
    }

    public function testIsUsed(): void
    {
        $definition = $this->initAssetDefinition(
            capacities: [\Glpi\Asset\Capacity\HasSoftwaresCapacity::class]
        );
        $class    = $definition->getAssetClassName();
        $capacity = new \Glpi\Asset\Capacity\HasSoftwaresCapacity();
        $entity   = $this->getTestRootEntity(true);

        /** @var Asset $asset */
        $asset = $this->createItem($class, [
            'name'        => 'Test asset',
            'entities_id' => $entity,
        ]);

        $software = $this->createItem(
            Software::class,
            [
                'name' => 'Software',
                'entities_id' => $entity,
            ]
        );
        $software_version = $this->createItem(
            SoftwareVersion::class,
            [
                'name' => 'V1.0',
                'entities_id' => $entity,
                'softwares_id' => $software->getID(),
            ]
        );
        $software_license = $this->createItem(
            SoftwareLicense::class,
            [
                'name' => 'pro license',
                'entities_id' => $entity,
                'softwares_id' => $software->getID(),
            ]
        );

        $this->boolean($capacity->isUsed($class))->isFalse();

        // IS used if linked to a version
        $version_relation = $this->createItem(
            Item_SoftwareVersion::class,
            [
                'entities_id' => $entity,
                'softwareversions_id' => $software_version->getID(),
                'itemtype' => $asset->getType(),
                'items_id' => $asset->getID(),
            ]
        );
        $this->boolean($capacity->isUsed($class))->isTrue();

        // IS NOT used if relation with version is deleted
        $this->deleteItem(Item_SoftwareVersion::class, $version_relation->getID());
        $this->boolean($capacity->isUsed($class))->isFalse();

        // IS used if linked to a license
        $license_relation = $this->createItem(
            Item_SoftwareLicense::class,
            [
                'softwarelicenses_id' => $software_license->getID(),
                'itemtype' => $asset->getType(),
                'items_id' => $asset->getID(),
            ]
        );
        $this->boolean($capacity->isUsed($class))->isTrue();

        // IS NOT used if relation with license is deleted
        $this->deleteItem(Item_SoftwareLicense::class, $license_relation->getID());
        $this->boolean($capacity->isUsed($class))->isFalse();
    }

    public function testGetCapacityUsageDescription(): void
    {
        $definition = $this->initAssetDefinition(
            capacities: [\Glpi\Asset\Capacity\HasSoftwaresCapacity::class]
        );
        $class    = $definition->getAssetClassName();
        $capacity = new \Glpi\Asset\Capacity\HasSoftwaresCapacity();
        $entity   = $this->getTestRootEntity(true);

        $software1 = $this->createItem(
            Software::class,
            [
                'name' => 'Software 1',
                'entities_id' => $entity,
            ]
        );
        $software1_version1 = $this->createItem(
            SoftwareVersion::class,
            [
                'name' => 'V1.0',
                'entities_id' => $entity,
                'softwares_id' => $software1->getID(),
            ]
        );
        $software1_version2 = $this->createItem(
            SoftwareVersion::class,
            [
                'name' => 'V1.0',
                'entities_id' => $entity,
                'softwares_id' => $software1->getID(),
            ]
        );

        $software2 = $this->createItem(
            Software::class,
            [
                'name' => 'Software 2',
                'entities_id' => $entity,
            ]
        );
        $software2_version = $this->createItem(
            SoftwareVersion::class,
            [
                'name' => 'V2.1',
                'entities_id' => $entity,
                'softwares_id' => $software2->getID(),
            ]
        );
        $software2_license = $this->createItem(
            SoftwareLicense::class,
            [
                'name' => 'team license',
                'entities_id' => $entity,
                'softwares_id' => $software2->getID(),
            ]
        );

        $software3 = $this->createItem(
            Software::class,
            [
                'name' => 'Software 3',
                'entities_id' => $entity,
            ]
        );
        $software3_license1 = $this->createItem(
            SoftwareLicense::class,
            [
                'name' => 'user license',
                'entities_id' => $entity,
                'softwares_id' => $software3->getID(),
            ]
        );
        $software3_license2 = $this->createItem(
            SoftwareLicense::class,
            [
                'name' => 'expert license',
                'entities_id' => $entity,
                'softwares_id' => $software3->getID(),
            ]
        );

        $assets_count   = 0;
        $software_count = 0;
        $this->string($capacity->getCapacityUsageDescription($class))
            ->isEqualTo('0 software(s) attached to 0 assets');

        $software_count += 1; // 2 versions of the same software counts only once
        for ($i = 0; $i < 3; $i++) {
            $asset = $this->createItem($class, [
                'name'        => 'Test asset ' . $assets_count,
                'entities_id' => $entity,
            ]);

            $this->createItems(
                Item_SoftwareVersion::class,
                [
                    [
                        'entities_id' => $entity,
                        'softwareversions_id' => $software1_version1->getID(),
                        'itemtype' => $asset->getType(),
                        'items_id' => $asset->getID(),
                    ],
                    [
                        'entities_id' => $entity,
                        'softwareversions_id' => $software1_version2->getID(),
                        'itemtype' => $asset->getType(),
                        'items_id' => $asset->getID(),
                    ]
                ]
            );
            $assets_count++;

            $this->string($capacity->getCapacityUsageDescription($class))
                ->isEqualTo(sprintf('%d software(s) attached to %d assets', $software_count, $assets_count));
        }

        $software_count += 1; // 1 version + 1 license of the same software counts only once
        for ($i = 0; $i < 3; $i++) {
            $asset = $this->createItem($class, [
                'name'        => 'Test asset' . $assets_count,
                'entities_id' => $entity,
            ]);

            $this->createItem(
                Item_SoftwareVersion::class,
                [
                    'entities_id' => $entity,
                    'softwareversions_id' => $software2_version->getID(),
                    'itemtype' => $asset->getType(),
                    'items_id' => $asset->getID(),
                ]
            );
            $this->createItem(
                Item_SoftwareLicense::class,
                [
                    'softwarelicenses_id' => $software2_license->getID(),
                    'itemtype' => $asset->getType(),
                    'items_id' => $asset->getID(),
                ]
            );
            $assets_count++;

            $this->string($capacity->getCapacityUsageDescription($class))
                ->isEqualTo(sprintf('%d software(s) attached to %d assets', $software_count, $assets_count));
        }

        $software_count += 1; // 2 licenses of the same software counts only once
        for ($i = 0; $i < 3; $i++) {
            $asset = $this->createItem($class, [
                'name'        => 'Test asset' . $assets_count,
                'entities_id' => $entity,
            ]);

            $this->createItems(
                Item_SoftwareLicense::class,
                [
                    [
                        'softwarelicenses_id' => $software3_license1->getID(),
                        'itemtype' => $asset->getType(),
                        'items_id' => $asset->getID(),
                    ],
                    [
                        'softwarelicenses_id' => $software3_license2->getID(),
                        'itemtype' => $asset->getType(),
                        'items_id' => $asset->getID(),
                    ]
                ]
            );
            $assets_count++;

            $this->string($capacity->getCapacityUsageDescription($class))
                ->isEqualTo(sprintf('%d software(s) attached to %d assets', $software_count, $assets_count));
        }
    }
}
