<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Asset\AssetDefinition;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasPlugCapacity;
use Glpi\Features\Clonable;
use Glpi\Tests\DbTestCase;
use Plug;
use Toolbox;

class PlugTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasPlugCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['plug_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Plug$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasPlugCapacity::class)]);

        foreach ($CFG_GLPI['plug_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Plug::class, $item->getCloneRelations(), $itemtype);
        }
    }

    private function getPlugMainItem(?AssetDefinition $definition = null): \CommonDBTM
    {
        $definition ??= $this->initAssetDefinition(capacities: [new Capacity(name: HasPlugCapacity::class)]);
        return $this->createItem(
            $definition->getAssetClassName(),
            $this->getMinimalCreationInput($definition->getAssetClassName())
        );
    }

    private function getPlugBaseInput(\CommonDBTM $main_item, string $name = 'Plug name'): array
    {
        return [
            'itemtype_main' => $main_item::class,
            'items_id_main' => $main_item->getID(),
            'entities_id'   => $main_item->getEntityID(),
            'is_recursive'  => $main_item->isRecursive(),
            'name'          => $name,
        ];
    }

    public function testPrepareInputForAddSetsNumberIncrementally()
    {
        $this->login();
        $main_item = $this->getPlugMainItem();

        foreach ([1, 2, 3] as $expected_number) {
            $plug = $this->createItem(Plug::class, $this->getPlugBaseInput($main_item), ['number']);
            $this->assertSame($expected_number, (int) $plug->fields['number']);
        }
    }

    public function testPrepareInputForAddNumberIsPerMainItem()
    {
        $this->login();
        $definition = $this->initAssetDefinition(capacities: [new Capacity(name: HasPlugCapacity::class)]);
        $first_main_item = $this->getPlugMainItem($definition);
        $second_main_item = $this->getPlugMainItem($definition);

        $first_plug = $this->createItem(Plug::class, $this->getPlugBaseInput($first_main_item), ['number']);
        $this->assertSame(1, (int) $first_plug->fields['number']);

        $second_plug = $this->createItem(Plug::class, $this->getPlugBaseInput($second_main_item), ['number']);
        $this->assertSame(1, (int) $second_plug->fields['number']);
    }

    public function testPrepareInputForAddIgnoresProvidedNumber()
    {
        $this->login();
        $main_item = $this->getPlugMainItem();

        $input = $this->getPlugBaseInput($main_item) + ['number' => 999];
        $plug = new Plug();
        $id = $plug->add($input);
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        $this->assertTrue($plug->getFromDB($id));

        $this->assertSame(1, (int) $plug->fields['number']);
    }

    public function testPrepareInputForAddKeepsIncrementingAfterSoftDelete()
    {
        $this->login();
        $main_item = $this->getPlugMainItem();

        $first_plug = $this->createItem(Plug::class, $this->getPlugBaseInput($main_item), ['number']);
        $this->assertSame(1, (int) $first_plug->fields['number']);


        $this->assertTrue($first_plug->update([
            'id'         => $first_plug->getID(),
            'is_deleted' => 1,
        ]));

        $second_plug = $this->createItem(Plug::class, $this->getPlugBaseInput($main_item), ['number']);
        $this->assertSame(2, (int) $second_plug->fields['number']);
    }

    public function testPrepareInputForUpdateNeverChangesNumber()
    {
        $this->login();
        $main_item = $this->getPlugMainItem();

        $plug = $this->createItem(Plug::class, $this->getPlugBaseInput($main_item), ['number']);
        $this->assertSame(1, (int) $plug->fields['number']);

        $success = $plug->update([
            'id'     => $plug->getID(),
            'number' => 50,
            'name'   => 'Renamed',
        ]);
        $this->assertTrue($success);
        $this->assertTrue($plug->getFromDB($plug->getID()));

        $this->assertSame(1, (int) $plug->fields['number']);
        $this->assertSame('Renamed', $plug->fields['name']);
    }
}
