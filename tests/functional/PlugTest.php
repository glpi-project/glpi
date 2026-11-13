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

use CommonDBTM;
use Computer;
use DevicePowerSupply;
use Glpi\Asset\AssetDefinition;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDevicesCapacity;
use Glpi\Asset\Capacity\HasPlugCapacity;
use Glpi\Debug\Profile;
use Glpi\Features\Clonable;
use Glpi\Tests\DbTestCase;
use Item_DevicePowerSupply;
use PDU;
use Plug;
use Psr\Log\LogLevel;
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

    private function getPlugMainItem(?AssetDefinition $definition = null): CommonDBTM
    {
        $definition ??= $this->initAssetDefinition(capacities: [new Capacity(name: HasPlugCapacity::class)]);
        return $this->createItem(
            $definition->getAssetClassName(),
            $this->getMinimalCreationInput($definition->getAssetClassName())
        );
    }

    private function getPlugBaseInput(CommonDBTM $main_item, string $name = 'Plug name'): array
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

        $this->assertTrue($plug->update([
            'id' => $plug_id,
            'name' => 'Renamed outlet A1',
            'itemtype_asset' => Computer::class,
            'items_id_asset' => $computer->getID(),
        ]));
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

    public function testPurgingPowerSupplyPreservesPlugAssetConnection(): void
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
        $this->assertSame(Computer::class, $plug->fields['itemtype_asset']);
        $this->assertSame($computer->getID(), (int) $plug->fields['items_id_asset']);

        // Inventory can recreate the component without breaking the existing asset connection.
        $this->createPowerSupply($computer, 'PSU replacement', '');
        $this->assertTrue($plug->update(['id' => $plug->getID(), 'custom_name' => 'After inventory']));
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

        $connected_plug = $this->createItem(Plug::class, [
            'name'                          => 'Outlet PSU',
            'itemtype_main'                 => PDU::class,
            'items_id_main'                 => $pdu->getID(),
            'itemtype_asset'                => Computer::class,
            'items_id_asset'                => $computer->getID(),
            Plug::POWER_SUPPLY_FIELD        => $power_supply_a->getID(),
            'entities_id'                   => $pdu->getEntityID(),
        ]);
        $deleted_plug = $this->createItem(Plug::class, [
            'name'                          => 'Outlet redundant PSU',
            'itemtype_main'                 => PDU::class,
            'items_id_main'                 => $pdu->getID(),
            'itemtype_asset'                => Computer::class,
            'items_id_asset'                => $computer->getID(),
            Plug::POWER_SUPPLY_FIELD        => $power_supply_b->getID(),
            'entities_id'                   => $pdu->getEntityID(),
        ]);

        ob_start();
        try {
            $this->assertTrue($connected_plug->showForm($connected_plug->getID()));
            $form = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $this->assertStringContainsString(Plug::POWER_SUPPLY_FIELD, $form);
        $this->assertStringContainsString('PSU reverse connection A', $form);

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

        $this->assertTrue($deleted_plug->delete(['id' => $deleted_plug->getID()]));
        $tab_name = strip_tags((new Plug())->getTabNameForItem($computer));
        $this->assertStringContainsString('1', $tab_name);

        ob_start();
        try {
            $this->assertTrue(Plug::showPowerConnections($computer));
            $output = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $this->assertStringContainsString('Outlet PSU', $output);
        $this->assertStringNotContainsString('Outlet redundant PSU', $output);
    }

    public function testLegacyAssetConnectionCanBeUpdatedAfterInstallingPowerSupply(): void
    {
        $pdu = $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class));
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $plug = $this->createItem(Plug::class, $this->getPlugBaseInput($pdu) + [
            'itemtype_asset' => Computer::class,
            'items_id_asset' => $computer->getID(),
            'is_dynamic' => 1,
        ]);
        $power_supply = $this->createPowerSupply($computer, 'Newly installed PSU', '');

        $this->assertTrue($plug->update([
            'id' => $plug->getID(),
            'comment' => 'Updated through the API',
            'custom_name' => 'Legacy outlet',
        ]));
        $this->assertTrue($plug->getFromDB($plug->getID()));
        $this->assertSame('Legacy outlet', $plug->fields['custom_name']);
        $this->assertSame('Updated through the API', $plug->fields['comment']);
        $this->assertSame(Computer::class, $plug->fields['itemtype_asset']);
        $this->assertSame($computer->getID(), (int) $plug->fields['items_id_asset']);
        $this->assertSame(0, (int) $plug->fields[Plug::POWER_SUPPLY_FIELD]);

        // Explicitly setting the asset still requires choosing its installed PSU.
        $this->assertFalse($plug->update([
            'id' => $plug->getID(),
            'itemtype_asset' => Computer::class,
            'items_id_asset' => $computer->getID(),
        ]));
        $this->hasSessionMessages(ERROR, ['A specific power supply must be selected for this asset']);
        $this->assertTrue($plug->update([
            'id' => $plug->getID(),
            Plug::POWER_SUPPLY_FIELD => $power_supply->getID(),
        ]));
    }

    public function testTrashedPlugReleasesPowerSupplyAndCannotRestoreOccupiedConnection(): void
    {
        $pdu = $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class));
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $power_supply = $this->createPowerSupply($computer, 'Reusable PSU', '');
        $input = $this->getPlugBaseInput($pdu) + [
            'itemtype_asset' => Computer::class,
            'items_id_asset' => $computer->getID(),
            Plug::POWER_SUPPLY_FIELD => $power_supply->getID(),
        ];
        $first_plug = $this->createItem(Plug::class, $input);
        $this->assertTrue($first_plug->delete(['id' => $first_plug->getID()]));
        $second_plug = $this->createItem(Plug::class, array_replace($input, ['name' => 'Replacement outlet']));

        $this->assertTrue($first_plug->update([
            'id' => $first_plug->getID(),
            'custom_name' => 'Still in trash',
            Plug::POWER_SUPPLY_FIELD => $power_supply->getID(),
        ]));
        $this->assertFalse($first_plug->restore(['id' => $first_plug->getID()]));
        $this->hasSessionMessages(ERROR, ['The selected power supply is already connected to another plug']);
        $this->assertFalse($first_plug->update(['id' => $first_plug->getID(), 'is_deleted' => 0]));
        $this->hasSessionMessages(ERROR, ['The selected power supply is already connected to another plug']);
        $this->assertTrue($first_plug->getFromDB($first_plug->getID()));
        $this->assertSame(1, (int) $first_plug->fields['is_deleted']);

        $this->assertTrue($second_plug->delete(['id' => $second_plug->getID()]));
        // Each form action uses a fresh object; a rejected update leaves its input set to false.
        $first_plug_id = $first_plug->getID();
        $first_plug = new Plug();
        $this->assertTrue($first_plug->restore(['id' => $first_plug_id]));
        $this->assertTrue($first_plug->getFromDB($first_plug->getID()));
        $this->assertSame(0, (int) $first_plug->fields['is_deleted']);
        $this->assertSame($power_supply->getID(), (int) $first_plug->fields[Plug::POWER_SUPPLY_FIELD]);
    }

    public function testPowerSupplyAssetsHaveReverseConnectionTab(): void
    {
        global $CFG_GLPI;

        $this->login();
        $this->initAssetDefinition(capacities: [new Capacity(name: HasDevicesCapacity::class)]);
        foreach ($CFG_GLPI['itemdevicepowersupply_types'] as $itemtype) {
            $item = $this->createItem($itemtype, $this->getMinimalCreationInput($itemtype));
            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Plug$1', $tabs, $itemtype);
            $this->assertStringContainsString('Power connections', $tabs['Plug$1'], $itemtype);
        }
    }

    public function testCustomAssetHasSeparateHostAndPowerConnectionTabs(): void
    {
        $this->login();
        foreach ([
            [new Capacity(name: HasDevicesCapacity::class), new Capacity(name: HasPlugCapacity::class)],
            [new Capacity(name: HasPlugCapacity::class), new Capacity(name: HasDevicesCapacity::class)],
        ] as $capacities) {
            $definition = $this->initAssetDefinition(capacities: $capacities);
            $asset = $this->getPlugMainItem($definition);
            $this->login(); // Reload rights for the newly defined asset type.
            $pdu = $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class));
            $power_supply = $this->createPowerSupply($asset, 'Custom asset PSU', 'CUSTOM-PSU');
            $this->createItem(Plug::class, $this->getPlugBaseInput($pdu, 'Incoming outlet') + [
                'itemtype_asset' => $asset::class,
                'items_id_asset' => $asset->getID(),
                Plug::POWER_SUPPLY_FIELD => $power_supply->getID(),
            ]);
            $this->createItem(Plug::class, $this->getPlugBaseInput($asset, 'Hosted outlet'));

            $tabs = $asset->defineAllTabs();
            $this->assertArrayHasKey('Plug$1', $tabs);
            $this->assertArrayHasKey('Plug$2', $tabs);
            $this->assertStringContainsString('Plugs', $tabs['Plug$1']);
            $this->assertStringContainsString('Power connections', $tabs['Plug$2']);

            foreach ([1 => 'Hosted outlet', 2 => 'Incoming outlet'] as $tabnum => $expected_name) {
                ob_start();
                try {
                    $this->assertTrue(Plug::displayTabContentForItem($asset, $tabnum));
                    $output = (string) ob_get_contents();
                } finally {
                    ob_end_clean();
                }
                $this->assertStringContainsString($expected_name, $output);
                $this->assertStringNotContainsString($tabnum === 1 ? 'Incoming outlet' : 'Hosted outlet', $output);
            }
        }
    }

    public function testCloningPlugHostsClearsPhysicalConnections(): void
    {
        global $DB;

        $definition = $this->initAssetDefinition(capacities: [
            new Capacity(name: HasPlugCapacity::class),
            new Capacity(name: HasDevicesCapacity::class),
        ]);
        $custom_host = $this->getPlugMainItem($definition);
        $upstream = $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class));
        $incoming_psu = $this->createPowerSupply($custom_host, 'Incoming PSU', 'INCOMING-SERIAL');
        $incoming = $this->createItem(Plug::class, $this->getPlugBaseInput($upstream, 'Incoming feed') + [
            'itemtype_asset' => $custom_host::class,
            'items_id_asset' => $custom_host->getID(),
            Plug::POWER_SUPPLY_FIELD => $incoming_psu->getID(),
        ]);
        $hosts = [
            $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class)),
            $custom_host,
        ];
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        foreach ($hosts as $host) {
            $power_supply = $this->createPowerSupply($computer, 'Clone PSU', 'CLONE-SERIAL');
            $original = $this->createItem(Plug::class, $this->getPlugBaseInput($host) + [
                'itemtype_asset' => Computer::class,
                'items_id_asset' => $computer->getID(),
                Plug::POWER_SUPPLY_FIELD => $power_supply->getID(),
                'custom_name' => 'Physical outlet',
            ]);
            $clone_id = $host->clone();
            $this->assertGreaterThan(0, $clone_id);
            $cloned_plugs = iterator_to_array($DB->request([
                'FROM' => Plug::getTable(),
                'WHERE' => ['itemtype_main' => $host::class, 'items_id_main' => $clone_id],
            ]), false);
            $this->assertCount(1, $cloned_plugs);
            $this->assertSame('', $cloned_plugs[0]['itemtype_asset']);
            $this->assertSame(0, (int) $cloned_plugs[0]['items_id_asset']);
            $this->assertSame(0, (int) $cloned_plugs[0][Plug::POWER_SUPPLY_FIELD]);
            $this->assertSame('Physical outlet', $cloned_plugs[0]['custom_name']);
            $this->assertTrue($original->getFromDB($original->getID()));
            $this->assertSame($power_supply->getID(), (int) $original->fields[Plug::POWER_SUPPLY_FIELD]);
        }
        $this->assertTrue($incoming->getFromDB($incoming->getID()));
        $this->assertSame($custom_host->getID(), (int) $incoming->fields['items_id_asset']);
        $this->assertSame($incoming_psu->getID(), (int) $incoming->fields[Plug::POWER_SUPPLY_FIELD]);
    }

    public function testConnectionListsHideMovedPowerSupplyDetails(): void
    {
        $this->login();
        $pdu = $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class));
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $other = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $power_supply = $this->createPowerSupply($computer, 'Moved PSU', 'ORIGINAL-SERIAL');
        $this->createItem(Plug::class, $this->getPlugBaseInput($pdu) + [
            'itemtype_asset' => Computer::class,
            'items_id_asset' => $computer->getID(),
            Plug::POWER_SUPPLY_FIELD => $power_supply->getID(),
        ]);
        $this->assertTrue($power_supply->update([
            'id' => $power_supply->getID(),
            'items_id' => $other->getID(),
            'serial' => 'NEW-OWNER-SECRET-SERIAL',
        ]));
        foreach (['showItems' => $pdu, 'showPowerConnections' => $computer] as $method => $item) {
            ob_start();
            try {
                $this->assertTrue(Plug::$method($item));
                $output = (string) ob_get_contents();
            } finally {
                ob_end_clean();
            }
            $this->assertStringNotContainsString('NEW-OWNER-SECRET-SERIAL', $output);
        }
    }

    public function testRestoringPlugWithUnavailablePowerSupplyExplainsRecovery(): void
    {
        $pdu = $this->createItem(PDU::class, $this->getMinimalCreationInput(PDU::class));
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        $other_computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        foreach (['deleted', 'moved'] as $state) {
            $power_supply = $this->createPowerSupply($computer, 'Unavailable PSU', $state);
            $plug = $this->createItem(Plug::class, $this->getPlugBaseInput($pdu) + [
                'itemtype_asset' => Computer::class,
                'items_id_asset' => $computer->getID(),
                Plug::POWER_SUPPLY_FIELD => $power_supply->getID(),
            ]);
            $this->assertTrue($plug->delete(['id' => $plug->getID()]));
            $this->assertTrue($power_supply->update(['id' => $power_supply->getID()] + (
                $state === 'deleted' ? ['is_deleted' => 1] : ['items_id' => $other_computer->getID()]
            )));
            $this->assertFalse($plug->restore(['id' => $plug->getID()]));
            $this->hasSessionMessages(ERROR, [
                'The selected power supply is no longer available for this asset. Clear or replace the power supply connection and save the plug before restoring it.',
            ]);
            $this->assertTrue($plug->getFromDB($plug->getID()));
            $this->assertSame(1, (int) $plug->fields['is_deleted']);
            $this->assertTrue($plug->update(['id' => $plug->getID(), Plug::POWER_SUPPLY_FIELD => 0]));
            $this->assertTrue($plug->restore(['id' => $plug->getID()]));
            $this->assertTrue($plug->getFromDB($plug->getID()));
            $this->assertSame(0, (int) $plug->fields['is_deleted']);
            $this->assertSame(0, (int) $plug->fields[Plug::POWER_SUPPLY_FIELD]);
            $this->assertSame($computer->getID(), (int) $plug->fields['items_id_asset']);
        }
    }

    public function testPowerConnectionListsBatchRelatedRecords(): void
    {
        $this->login();
        $pdu = $this->createItem(PDU::class, ['name' => 'Batch PDU'] + $this->getMinimalCreationInput(PDU::class));
        $computer = $this->createItem(Computer::class, $this->getMinimalCreationInput(Computer::class));
        for ($i = 0; $i < 24; $i++) {
            $power_supply = $this->createPowerSupply($computer, "Batch PSU $i", "BATCH-SERIAL-$i");
            $this->createItem(Plug::class, $this->getPlugBaseInput($pdu, "Batch outlet $i") + [
                'itemtype_asset' => Computer::class,
                'items_id_asset' => $computer->getID(),
                Plug::POWER_SUPPLY_FIELD => $power_supply->getID(),
            ]);
        }

        $previous_mode = $_SESSION['glpi_use_mode'];
        $_SESSION['glpi_use_mode'] = \Session::DEBUG_MODE;
        try {
            $profile = Profile::getCurrent();
            $before = count($profile->getDebugInfo()['sql']['queries']);
            $choices = Plug::getPowerSupplyChoices(Computer::class, $computer->getID());
            $queries = array_slice($profile->getDebugInfo()['sql']['queries'], $before);
            $this->assertCount(24, $choices);
            $this->assertCount(1, $queries, 'All PSU choices must be loaded by one joined query.');

            foreach (['showItems' => $pdu, 'showPowerConnections' => $computer] as $method => $item) {
                $before = count($profile->getDebugInfo()['sql']['queries']);
                ob_start();
                try {
                    $this->assertTrue(Plug::$method($item));
                    $output = (string) ob_get_contents();
                } finally {
                    ob_end_clean();
                }
                $queries = array_slice($profile->getDebugInfo()['sql']['queries'], $before);
                $psu_queries = array_filter($queries, static fn(array $query): bool => str_contains(
                    $query['query'],
                    '`' . Item_DevicePowerSupply::getTable() . '`'
                ));
                $this->assertCount(1, $psu_queries, "$method must batch PSU labels.");
                $related_table = $method === 'showItems' ? Computer::getTable() : PDU::getTable();
                $related_queries = array_filter($queries, static fn(array $query): bool => str_contains(
                    $query['query'],
                    '`' . $related_table . '`'
                ));
                $this->assertCount(1, $related_queries, "$method must batch related assets.");
                $this->assertStringContainsString('BATCH-SERIAL-23', $output);
                if ($method === 'showPowerConnections') {
                    $this->assertStringContainsString('Batch PDU', $output);
                }
            }
        } finally {
            $_SESSION['glpi_use_mode'] = $previous_mode;
        }

        // Preloading a host must not make its link visible without read rights.
        $previous_right = $_SESSION['glpiactiveprofile'][PDU::$rightname];
        $_SESSION['glpiactiveprofile'][PDU::$rightname] = 0;
        ob_start();
        try {
            $this->assertTrue(Plug::showPowerConnections($computer));
            $output = (string) ob_get_contents();
        } finally {
            ob_end_clean();
            $_SESSION['glpiactiveprofile'][PDU::$rightname] = $previous_right;
        }
        $this->assertStringNotContainsString('Batch PDU', $output);

        // Component serials must follow the associated asset's read permission.
        $previous_right = $_SESSION['glpiactiveprofile'][Computer::$rightname];
        $_SESSION['glpiactiveprofile'][Computer::$rightname] = 0;
        ob_start();
        try {
            $this->assertTrue(Plug::showItems($pdu));
            $output = (string) ob_get_contents();
        } finally {
            ob_end_clean();
            $_SESSION['glpiactiveprofile'][Computer::$rightname] = $previous_right;
        }
        $this->assertStringNotContainsString('BATCH-SERIAL-', $output);
    }

    public function testPowerSupplyDropdownRejectsAbstractItemtypes(): void
    {
        $this->login();
        $previous_post = $_POST;
        try {
            foreach ([\CommonITILObject::class, \CommonDropdown::class] as $itemtype) {
                $_POST = ['itemtype' => $itemtype, 'items_id' => 1];
                ob_start();
                try {
                    include GLPI_ROOT . '/ajax/dropdownPowerSupplies.php';
                    $output = (string) ob_get_contents();
                } finally {
                    ob_end_clean();
                }
                $this->assertStringContainsString(Plug::POWER_SUPPLY_FIELD, $output);
                $this->hasPhpLogRecordThatContains(
                    sprintf('Cannot instanciate "%s" as it is an abstract class.', $itemtype),
                    LogLevel::WARNING
                );
            }
        } finally {
            $_POST = $previous_post;
        }
    }

    private function createPowerSupply(CommonDBTM $computer, string $designation, string $serial): Item_DevicePowerSupply
    {
        $device = $this->createItem(DevicePowerSupply::class, [
            'designation' => $designation,
            'entities_id' => $computer->getEntityID(),
        ]);

        return $this->createItem(Item_DevicePowerSupply::class, [
            'itemtype'                => $computer::class,
            'items_id'                => $computer->getID(),
            'devicepowersupplies_id'  => $device->getID(),
            'entities_id'             => $computer->getEntityID(),
            'serial'                  => $serial,
        ]);
    }
}
