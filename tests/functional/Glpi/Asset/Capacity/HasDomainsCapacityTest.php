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
use Domain;
use Domain_Item;
use DomainRelation;
use Entity;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDomainsCapacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Tests\Glpi\Asset\CapacityUsageTestTrait;
use Log;

class HasDomainsCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return HasDomainsCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDomainsCapacity::class),
                new Capacity(name: HasNotepadCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDomainsCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_3  = $definition_3->getAssetClassName();

        $has_domains_mapping = [
            $classname_1 => true,
            $classname_2 => false,
            $classname_3 => true,
        ];

        foreach ($has_domains_mapping as $classname => $has_domains) {
            // Check that the class is globally registered
            $this->login(); // must be logged in to have class in Domain::getTypes()
            if ($has_domains) {
                $this->assertContains($classname, $CFG_GLPI['domain_types']);
                $this->assertContains($classname, Domain::getTypes());
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['domain_types']);
                $this->assertNotContains($classname, Domain::getTypes());
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_domains) {
                $this->assertArrayHasKey('Domain_Item$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('Domain_Item$1', $item->defineAllTabs());
            }

            // Check that the related search options are available
            $so_keys = [
                205, // Name
                206, // Type
            ];
            $this->login(); // must be logged in to get search options
            $options = $item->getOptions();
            foreach ($so_keys as $so_key) {
                if ($has_domains) {
                    $this->assertArrayHasKey($so_key, $options);
                } else {
                    $this->assertArrayNotHasKey($so_key, $options);
                }
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDomainsCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasDomainsCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
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
        $domain = $this->createItem(
            Domain::class,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );
        $domain_item_1 = $this->createItem(
            Domain_Item::class,
            [
                'domains_id'         => $domain->getID(),
                'itemtype'           => $item_1->getType(),
                'items_id'           => $item_1->getID(),
                'domainrelations_id' => DomainRelation::BELONGS,
            ]
        );
        $this->updateItem(Domain_Item::class, $domain_item_1->getID(), ['domainrelations_id' => DomainRelation::MANAGE]);
        $domain_item_2 = $this->createItem(
            Domain_Item::class,
            [
                'domains_id'         => $domain->getID(),
                'itemtype'           => $item_2->getType(),
                'items_id'           => $item_2->getID(),
                'domainrelations_id' => DomainRelation::BELONGS,
            ]
        );
        $this->updateItem(Domain_Item::class, $domain_item_2->getID(), ['domainrelations_id' => DomainRelation::MANAGE]);
        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'num'      => 206, // Type
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'num'      => 206, // Type
                'users_id' => 0,
            ]
        );

        $item_1_logs_criteria = [
            'OR' => [
                [
                    'itemtype'      => $classname_1,
                    'itemtype_link' => ['LIKE', 'Domain%'],
                ],
                [
                    'itemtype'      => Domain::class,
                    'itemtype_link' => $classname_1,
                ],
            ],
        ];
        $item_2_logs_criteria = [
            'OR' => [
                [
                    'itemtype'      => $classname_2,
                    'itemtype_link' => ['LIKE', 'Domain%'],
                ],
                [
                    'itemtype'      => Domain::class,
                    'itemtype_link' => $classname_2,
                ],
            ],
        ];

        // Ensure relation, display preferences and logs exists, and class is registered to global config
        $this->assertInstanceOf(Domain_Item::class, Domain_Item::getById($domain_item_1->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(3, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); // both side links + update
        $this->assertInstanceOf(Domain_Item::class, Domain_Item::getById($domain_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(3, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); // both side links + update
        $this->assertContains($classname_1, $CFG_GLPI['domain_types']);
        $this->assertContains($classname_2, $CFG_GLPI['domain_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(Domain_Item::getById($domain_item_1->getID()));
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));
        $this->assertNotContains($classname_1, $CFG_GLPI['domain_types']);

        // Ensure relations, logs and global registration are preserved for other definition
        $this->assertInstanceOf(Domain_Item::class, Domain_Item::getById($domain_item_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(3, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); // both side links + update
        $this->assertContains($classname_2, $CFG_GLPI['domain_types']);
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => Domain::class,
            'relation_classname' => Domain_Item::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Domain::class,
            'relation_classname' => Domain_Item::class,
            'expected' => '%d domains attached to %d assets',
        ];
    }
}
