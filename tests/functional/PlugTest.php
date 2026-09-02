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

use Computer;
use DevicePowerSupply;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasPlugCapacity;
use Glpi\Features\Clonable;
use Glpi\Tests\DbTestCase;
use Item_DevicePowerSupply;
use PDU;
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

    public function testPlugCanTargetAnInstalledPowerSupply(): void
    {
        $pdu = $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class));
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $power_supply = $this->createPowerSupply($computer, 'PSU-A', 'SERIAL-A');

        $choices = Plug::getPowerSupplyChoices(Computer::class, $computer->getID());
        $this->assertArrayHasKey($power_supply->getID(), $choices);
        $this->assertStringContainsString('PSU-A', $choices[$power_supply->getID()]);
        $this->assertStringContainsString('SERIAL-A', $choices[$power_supply->getID()]);

        $plug = new Plug();
        $plug_id = $plug->add([
            'name'                          => 'Outlet A1',
            'itemtype_main'                 => PDU::class,
            'items_id_main'                 => $pdu->getID(),
            'itemtype_asset'                => Computer::class,
            'items_id_asset'                => $computer->getID(),
            Plug::POWER_SUPPLY_FIELD        => $power_supply->getID(),
            'entities_id'                   => $pdu->getEntityID(),
        ]);

        $this->assertGreaterThan(0, $plug_id);
        $this->assertTrue($plug->getFromDB($plug_id));
        $this->assertSame($power_supply->getID(), (int) $plug->fields[Plug::POWER_SUPPLY_FIELD]);
    }

    public function testPowerSupplyMustBelongToAssociatedAsset(): void
    {
        $pdu = $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class));
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $another_computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $power_supply = $this->createPowerSupply($another_computer, 'PSU-B', 'SERIAL-B');

        $plug = new Plug();
        $this->assertFalse($plug->add([
            'name'                          => 'Outlet A2',
            'itemtype_main'                 => PDU::class,
            'items_id_main'                 => $pdu->getID(),
            'itemtype_asset'                => Computer::class,
            'items_id_asset'                => $computer->getID(),
            Plug::POWER_SUPPLY_FIELD        => $power_supply->getID(),
            'entities_id'                   => $pdu->getEntityID(),
        ]));
        $this->hasSessionMessages(ERROR, [
            'The selected power supply does not belong to the associated asset',
        ]);
    }

    public function testPowerSupplyCannotBeConnectedTwice(): void
    {
        $pdu = $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class));
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $power_supply = $this->createPowerSupply($computer, 'PSU-C', 'SERIAL-C');

        $input = [
            'itemtype_main'          => PDU::class,
            'items_id_main'          => $pdu->getID(),
            'itemtype_asset'         => Computer::class,
            'items_id_asset'         => $computer->getID(),
            Plug::POWER_SUPPLY_FIELD => $power_supply->getID(),
            'entities_id'            => $pdu->getEntityID(),
        ];

        $first_plug = new Plug();
        $this->assertGreaterThan(0, $first_plug->add(['name' => 'Outlet A3'] + $input));

        $second_plug = new Plug();
        $this->assertFalse($second_plug->add(['name' => 'Outlet A4'] + $input));
        $this->hasSessionMessages(ERROR, [
            'The selected power supply is already connected to another plug',
        ]);
    }

    public function testAssetWithInstalledPowerSuppliesRequiresSpecificPowerSupply(): void
    {
        $pdu = $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class));
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $power_supply = $this->createPowerSupply($computer, 'PSU-D', 'SERIAL-D');

        $plug = new Plug();
        $this->assertFalse($plug->add([
            'name'                          => 'Outlet A5',
            'itemtype_main'                 => PDU::class,
            'items_id_main'                 => $pdu->getID(),
            'itemtype_asset'                => Computer::class,
            'items_id_asset'                => $computer->getID(),
            'entities_id'                   => $pdu->getEntityID(),
        ]));
        $this->hasSessionMessages(ERROR, [
            'A specific power supply must be selected for this asset',
        ]);
    }

    public function testPurgingPowerSupplyDisconnectsPlugFromAsset(): void
    {
        $pdu = $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class));
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $power_supply = $this->createPowerSupply($computer, 'PSU-E', 'SERIAL-E');
        $plug = $this->createItem(Plug::class, [
            'name'                          => 'Outlet A6',
            'itemtype_main'                 => PDU::class,
            'items_id_main'                 => $pdu->getID(),
            'itemtype_asset'                => Computer::class,
            'items_id_asset'                => $computer->getID(),
            Plug::POWER_SUPPLY_FIELD        => $power_supply->getID(),
            'entities_id'                   => $pdu->getEntityID(),
        ]);

        $this->assertTrue($power_supply->delete(['id' => $power_supply->getID()], true));
        $this->assertTrue($plug->getFromDB($plug->getID()));
        $this->assertSame(0, (int) $plug->fields[Plug::POWER_SUPPLY_FIELD]);
        $this->assertSame('', $plug->fields['itemtype_asset']);
        $this->assertSame(0, (int) $plug->fields['items_id_asset']);
    }

    public function testComputerDisplaysReversePowerConnections(): void
    {
        $this->login('glpi', 'glpi');

        $pdu = $this->createItem(PDU::class, [
            'name' => 'PDU reverse connection',
            'entities_id' => 0,
        ]);
        $computer = $this->createItem(Computer::class, [
            'name' => 'Computer reverse connection',
            'entities_id' => 0,
        ]);
        $power_supply_a = $this->createPowerSupply($computer, 'PSU reverse connection A', 'SERIAL-REVERSE-A');
        $power_supply_b = $this->createPowerSupply($computer, 'PSU reverse connection B', 'SERIAL-REVERSE-B');

        $this->createItem(Plug::class, [
            'name'                          => 'Outlet PSU',
            'itemtype_main'                 => PDU::class,
            'items_id_main'                 => $pdu->getID(),
            'itemtype_asset'                => Computer::class,
            'items_id_asset'                => $computer->getID(),
            Plug::POWER_SUPPLY_FIELD        => $power_supply_a->getID(),
            'entities_id'                   => $pdu->getEntityID(),
        ]);
        $this->createItem(Plug::class, [
            'name'                          => 'Outlet redundant PSU',
            'itemtype_main'                 => PDU::class,
            'items_id_main'                 => $pdu->getID(),
            'itemtype_asset'                => Computer::class,
            'items_id_asset'                => $computer->getID(),
            Plug::POWER_SUPPLY_FIELD        => $power_supply_b->getID(),
            'entities_id'                   => $pdu->getEntityID(),
        ]);

        $tab_name = strip_tags((new Plug())->getTabNameForItem($computer));
        $this->assertStringContainsString('Power connections', $tab_name);
        $this->assertStringContainsString('2', $tab_name);

        ob_start();
        try {
            $this->assertTrue(Plug::showPowerConnections($computer));
            $output = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertStringContainsString('PDU reverse connection', $output);
        $this->assertStringContainsString('Outlet PSU', $output);
        $this->assertStringContainsString('PSU reverse connection A', $output);
        $this->assertStringContainsString('SERIAL-REVERSE-A', $output);
        $this->assertStringContainsString('Outlet redundant PSU', $output);
        $this->assertStringContainsString('PSU reverse connection B', $output);
        $this->assertStringContainsString('SERIAL-REVERSE-B', $output);
        $this->assertStringNotContainsString('Entire asset', $output);
    }

    private function createPowerSupply(Computer $computer, string $designation, string $serial): Item_DevicePowerSupply
    {
        $device = $this->createItem(DevicePowerSupply::class, [
            'designation' => $designation,
            'entities_id' => $computer->getEntityID(),
        ]);

        return $this->createItem(Item_DevicePowerSupply::class, [
            'itemtype'                => Computer::class,
            'items_id'                => $computer->getID(),
            'devicepowersupplies_id'  => $device->getID(),
            'entities_id'             => $computer->getEntityID(),
            'serial'                  => $serial,
        ]);
    }
}
