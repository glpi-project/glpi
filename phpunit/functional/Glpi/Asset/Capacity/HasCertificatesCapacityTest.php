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

use Certificate;
use Certificate_Item;
use DbTestCase;
use DisplayPreference;
use Entity;
use Glpi\PHPUnit\Tests\Glpi\Asset\CapacityUsageTestTrait;
use Log;

class HasCertificatesCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\HasCertificatesCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
                \Glpi\Asset\Capacity\HasCertificatesCapacity::class,
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
                \Glpi\Asset\Capacity\HasCertificatesCapacity::class,
                \Glpi\Asset\Capacity\HasNotepadCapacity::class,
            ]
        );
        $classname_3  = $definition_3->getAssetClassName();

        $has_certificates_mapping = [
            $classname_1 => true,
            $classname_2 => false,
            $classname_3 => true,
        ];

        $this->login(); // must be logged in to have class in Certificate::getTypes()
        foreach ($has_certificates_mapping as $classname => $has_certificates) {
            // Check that the class is globally registered
            if ($has_certificates) {
                $this->assertContains($classname, $CFG_GLPI['certificate_types']);
                $this->assertContains($classname, Certificate::getTypes());
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['certificate_types']);
                $this->assertNotContains($classname, Certificate::getTypes());
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_certificates) {
                $this->assertArrayHasKey('Certificate_Item$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('Certificate_Item$1', $item->defineAllTabs());
            }

            // Check that the related search options are available
            $so_keys = [
                1300, // Name
                1301, // Serial number
                1302, // Inventory number
                1304, // Certificate type
                1305, // Comments
                1306, // Expiration
            ];
            $options = $item->getOptions();
            foreach ($so_keys as $so_key) {
                if ($has_certificates) {
                    $this->assertArrayHasKey($so_key, $options);
                } else {
                    $this->assertArrayNotHasKey($so_key, $options);
                }
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasCertificatesCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasCertificatesCapacity::class,
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
        $certificate    = $this->createItem(
            Certificate::class,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );
        $certificate_item_1 = $this->createItem(
            Certificate_Item::class,
            [
                'certificates_id' => $certificate->getID(),
                'itemtype'     => $item_1->getType(),
                'items_id'     => $item_1->getID(),
            ]
        );
        $certificate_item_2 = $this->createItem(
            Certificate_Item::class,
            [
                'certificates_id' => $certificate->getID(),
                'itemtype'     => $item_2->getType(),
                'items_id'     => $item_2->getID(),
            ]
        );
        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'users_id' => 0,
            ]
        );

        $item_1_logs_criteria = [
            'itemtype'      => Certificate::class,
            'itemtype_link' => $classname_1,
        ];
        $item_2_logs_criteria = [
            'itemtype'      => Certificate::class,
            'itemtype_link' => $classname_2,
        ];

        // Ensure relation, display preferences and logs exists, and class is registered to global config
        $this->assertInstanceOf(Certificate_Item::class, Certificate_Item::getById($certificate_item_1->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(1, countElementsInTable(Log::getTable(), $item_1_logs_criteria));
        $this->assertInstanceOf(Certificate_Item::class, Certificate_Item::getById($certificate_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(1, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
        $this->assertContains($classname_1, $CFG_GLPI['certificate_types']);
        $this->assertContains($classname_2, $CFG_GLPI['certificate_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(Certificate_Item::getById($certificate_item_1->getID()));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));
        $this->assertNotContains($classname_1, $CFG_GLPI['certificate_types']);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->assertInstanceOf(Certificate_Item::class, Certificate_Item::getById($certificate_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(1, countElementsInTable(Log::getTable(), $item_2_logs_criteria));
        $this->assertContains($classname_2, $CFG_GLPI['certificate_types']);
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => Certificate::class,
            'relation_classname' => Certificate_Item::class
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Certificate::class,
            'relation_classname' => Certificate_Item::class,
            'expected' => '%d certificates attached to %d assets'
        ];
    }
}
