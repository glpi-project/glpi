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
use Glpi\Tests\Asset\CapacityUsageTestTrait;
use ImpactItem;
use Log;

class HasImpactCapacity extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\HasImpactCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasImpactCapacity::class,
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
                \Glpi\Asset\Capacity\HasImpactCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $classname_3  = $definition_3->getAssetClassName();

        $has_impact_mapping = [
            $classname_1 => true,
            $classname_2 => false,
            $classname_3 => true,
        ];

        $enabled_impact_types = json_decode(\Config::getConfigurationValue('core', \Impact::CONF_ENABLED), true) ?? [];
        foreach ($has_impact_mapping as $classname => $has_impact) {
            // Check that the class is globally registered
            if ($has_impact) {
                $this->array($CFG_GLPI['impact_asset_types'])->hasKey($classname);
                $this->array($enabled_impact_types)->contains($classname);
            } else {
                $this->array($CFG_GLPI['impact_asset_types'])->notHasKey($classname);
                $this->array($enabled_impact_types)->notContains($classname);
            }

            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_impact) {
                $this->array($item->defineAllTabs())->hasKey('Impact$1');
            } else {
                $this->array($item->defineAllTabs())->notHasKey('Impact$1');
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
                \Glpi\Asset\Capacity\HasImpactCapacity::class,
                \Glpi\Asset\Capacity\HasHistoryCapacity::class,
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                \Glpi\Asset\Capacity\HasImpactCapacity::class,
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

        $impact_item_1 = $this->createItem(
            \ImpactRelation::class,
            [
                'itemtype_source'           => $item_1::class,
                'items_id_source'           => $item_1->getID(),
                'itemtype_impacted'         => 'Computer',
                'items_id_impacted'         => getItemByTypeName('Computer', '_test_pc01', true),
            ]
        );
        $impact_item_2 = $this->createItem(
            \ImpactRelation::class,
            [
                'itemtype_source'             => 'Computer',
                'items_id_source'             => getItemByTypeName('Computer', '_test_pc01', true),
                'itemtype_impacted'           => $item_2::class,
                'items_id_impacted'           => $item_2->getID(),
            ]
        );

        $this->object(\ImpactRelation::getById($impact_item_1->getID()))->isInstanceOf(\ImpactRelation::class);
        $this->object(\ImpactRelation::getById($impact_item_2->getID()))->isInstanceOf(\ImpactRelation::class);
        $this->array($CFG_GLPI['impact_asset_types'])->hasKey($classname_1);
        $this->array($CFG_GLPI['impact_asset_types'])->hasKey($classname_2);

        $this->boolean($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]))->isTrue();
        $this->boolean(\ImpactRelation::getById($impact_item_1->getID()))->isFalse();
        $this->array($CFG_GLPI['impact_asset_types'])->notHasKey($classname_1);

        $this->object(\ImpactRelation::getById($impact_item_2->getID()))->isInstanceOf(\ImpactRelation::class);
        $this->array($CFG_GLPI['impact_asset_types'])->hasKey($classname_2);
    }

    public function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => \ImpactRelation::class,
        ];
    }

    public function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => \ImpactRelation::class,
            'expected' => '%d impact relations involving %d assets'
        ];
    }
}
