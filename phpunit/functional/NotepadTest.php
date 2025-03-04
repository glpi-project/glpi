<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units;

use Cartridge;
use Computer;
use Consumable;
use DbTestCase;
use Enclosure;
use Monitor;
use PassiveDCEquipment;
use PDU;
use Peripheral;
use Phone;
use Printer;
use Rack;
use Software;
use Cable;
use CartridgeItem;
use ConsumableItem;
use DeviceBattery;
use DeviceCamera;
use DeviceCase;
use DeviceControl;
use DeviceDrive;
use DeviceFirmware;
use DeviceGeneric;
use DeviceGraphicCard;
use DeviceHardDrive;
use DeviceMemory;
use DeviceMotherboard;
use DeviceNetworkCard;
use DevicePci;
use DevicePowerSupply;
use DeviceProcessor;
use DeviceSensor;
use DeviceSimcard;
use DeviceSoundCard;
use Item_DeviceMemory;
use Item_DeviceBattery;
use Item_DeviceCamera;
use Item_DeviceCase;
use Item_DeviceControl;
use Item_DeviceDrive;
use Item_DeviceFirmware;
use Item_DeviceGeneric;
use Item_DeviceGraphicCard;
use Item_DeviceHardDrive;
use Item_DeviceMotherboard;
use Item_DeviceNetworkCard;
use Item_DevicePci;
use Item_DevicePowerSupply;
use Item_DeviceProcessor;
use Item_DeviceSensor;
use Item_DeviceSimcard;
use Item_DeviceSoundCard;
use NetworkEquipment;
use ReflectionClass;

/**
 * @engine isolate
 */
class NotepadTest extends DbTestCase
{
    public function getAssets()
    {
        return [
            Computer::class,
            Monitor::class,
            Software::class,
            NetworkEquipment::class,
            Peripheral::class,
            Printer::class,
            Cartridge::class,
            Consumable::class,
            Phone::class,
            Rack::class,
            Enclosure::class,
            PDU::class,
            PassiveDCEquipment::class,
            Cable::class
        ];
    }

    public function getComponents()
    {
        return [
            Item_DeviceBattery::class,
            Item_DeviceCamera::class,
            Item_DeviceCase::class,
            Item_DeviceControl::class,
            Item_DeviceDrive::class,
            Item_DeviceFirmware::class,
            Item_DeviceGeneric::class,
            Item_DeviceGraphicCard::class,
            Item_DeviceHardDrive::class,
            Item_DeviceMemory::class,
            Item_DeviceNetworkCard::class,
            Item_DevicePci::class,
            Item_DevicePowerSupply::class,
            Item_DeviceProcessor::class,
            Item_DeviceSensor::class,
            Item_DeviceSimcard::class,
            Item_DeviceSoundCard::class,
            Item_DeviceMotherboard::class,
        ];
    }

    public function itemProvider()
    {
        yield [
            'itemtype' => Computer::class,
            'item' => $this->createItem(Computer::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        yield [
            'itemtype' => Monitor::class,
            'item' => $this->createItem(Monitor::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        yield [
            'itemtype' => Software::class,
            'item' => $this->createItem(Software::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        yield [
            'itemtype' => NetworkEquipment::class,
            'item' => $this->createItem(NetworkEquipment::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        yield [
            'itemtype' => Peripheral::class,
            'item' => $this->createItem(Peripheral::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        yield [
            'itemtype' => Printer::class,
            'item' => $this->createItem(Printer::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        $cartridge_item = new CartridgeItem();
        yield [
            'itemtype' => Cartridge::class,
            'item' => $this->createItem(Cartridge::class, [
                'printers_id' => 0,
                'cartridgeitems_id' => $cartridge_item->add([
                    'name' => 'test',
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $consumable_item = new ConsumableItem();
        yield [
            'itemtype' => Consumable::class,
            'item' => $this->createItem(Consumable::class, [
                'entities_id' => 0,
                'consumableitems_id' => $consumable_item->add([
                    'name' => 'test',
                    'entities_id' => 0,
                ]),
            ]),
        ];

        yield [
            'itemtype' => Phone::class,
            'item' => $this->createItem(Phone::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        yield [
            'itemtype' => Rack::class,
            'item' => $this->createItem(Rack::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        yield [
            'itemtype' => Enclosure::class,
            'item' => $this->createItem(Enclosure::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        yield [
            'itemtype' => PDU::class,
            'item' => $this->createItem(PDU::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        yield [
            'itemtype' => PassiveDCEquipment::class,
            'item' => $this->createItem(PassiveDCEquipment::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        yield [
            'itemtype' => Cable::class,
            'item' => $this->createItem(Cable::class, [
                'name' => 'test',
                'entities_id' => 0,
            ]),
        ];

        $battery = new DeviceBattery();
        yield [
            'itemtype' => Item_DeviceBattery::class,
            'item' => $this->createItem(Item_DeviceBattery::class, [
                'entities_id' => 0,
                'devicebatteries_id' => $battery->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $camera = new DeviceCamera();
        yield [
            'itemtype' => Item_DeviceCamera::class,
            'item' => $this->createItem(Item_DeviceCamera::class, [
                'entities_id' => 0,
                'devicecameras_id' => $camera->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $case = new DeviceCase();
        yield [
            'itemtype' => Item_DeviceCase::class,
            'item' => $this->createItem(Item_DeviceCase::class, [
                'entities_id' => 0,
                'devicecases_id' => $case->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $control = new DeviceControl();
        yield [
            'itemtype' => Item_DeviceControl::class,
            'item' => $this->createItem(Item_DeviceControl::class, [
                'entities_id' => 0,
                'devicecontrols_id' => $control->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $drive = new DeviceDrive();
        yield [
            'itemtype' => Item_DeviceDrive::class,
            'item' => $this->createItem(Item_DeviceDrive::class, [
                'entities_id' => 0,
                'devicedrives_id' => $drive->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $firmware = new DeviceFirmware();
        yield [
            'itemtype' => Item_DeviceFirmware::class,
            'item' => $this->createItem(Item_DeviceFirmware::class, [
                'entities_id' => 0,
                'devicefirmwares_id' => $firmware->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $generic = new DeviceGeneric();
        yield [
            'itemtype' => Item_DeviceGeneric::class,
            'item' => $this->createItem(Item_DeviceGeneric::class, [
                'entities_id' => 0,
                'devicegenerics_id' => $generic->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $graphic_card = new DeviceGraphicCard();
        yield [
            'itemtype' => Item_DeviceGraphicCard::class,
            'item' => $this->createItem(Item_DeviceGraphicCard::class, [
                'entities_id' => 0,
                'devicegraphiccards_id' => $graphic_card->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $hard_drive = new DeviceHardDrive();
        yield [
            'itemtype' => Item_DeviceHardDrive::class,
            'item' => $this->createItem(Item_DeviceHardDrive::class, [
                'entities_id' => 0,
                'deviceharddrives_id' => $hard_drive->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $memory = new DeviceMemory();
        yield [
            'itemtype' => Item_DeviceMemory::class,
            'item' => $this->createItem(Item_DeviceMemory::class, [
                'entities_id' => 0,
                'devicememories_id' => $memory->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $network_card = new DeviceNetworkCard();
        yield [
            'itemtype' => Item_DeviceNetworkCard::class,
            'item' => $this->createItem(Item_DeviceNetworkCard::class, [
                'entities_id' => 0,
                'devicenetworkcards_id' => $network_card->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $pci = new DevicePci();
        yield [
            'itemtype' => Item_DevicePci::class,
            'item' => $this->createItem(Item_DevicePci::class, [
                'entities_id' => 0,
                'devicepcis_id' => $pci->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $power_supply = new DevicePowerSupply();
        yield [
            'itemtype' => Item_DevicePowerSupply::class,
            'item' => $this->createItem(Item_DevicePowerSupply::class, [
                'entities_id' => 0,
                'devicepowersupplies_id' => $power_supply->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $processor = new DeviceProcessor();
        yield [
            'itemtype' => Item_DeviceProcessor::class,
            'item' => $this->createItem(Item_DeviceProcessor::class, [
                'entities_id' => 0,
                'deviceprocessors_id' => $processor->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $sensor = new DeviceSensor();
        yield [
            'itemtype' => Item_DeviceSensor::class,
            'item' => $this->createItem(Item_DeviceSensor::class, [
                'entities_id' => 0,
                'devicesensors_id' => $sensor->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $simcard = new DeviceSimcard();
        yield [
            'itemtype' => Item_DeviceSimcard::class,
            'item' => $this->createItem(Item_DeviceSimcard::class, [
                'entities_id' => 0,
                'itemtype' => 'DeviceSimcard',
                'devicesimcards_id' => $simcard->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $sound_card = new DeviceSoundCard();
        yield [
            'itemtype' => Item_DeviceSoundCard::class,
            'item' => $this->createItem(Item_DeviceSoundCard::class, [
                'entities_id' => 0,
                'devicesoundcards_id' => $sound_card->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];

        $motherboard = new DeviceMotherboard();
        yield [
            'itemtype' => Item_DeviceMotherboard::class,
            'item' => $this->createItem(Item_DeviceMotherboard::class, [
                'entities_id' => 0,
                'devicemotherboards_id' => $motherboard->add([
                    'entities_id' => 0,
                ]),
            ]),
        ];
    }

    public function testNotepadTab()
    {
        foreach ($this->itemProvider() as $item_provider) {
            $reflected_class = new ReflectionClass($item_provider['itemtype']);
            $asset = $item_provider['item'];
            if ($reflected_class->hasProperty('usenotepad')) {
                // reduce the right of tech profile
                // to have only the right of display their own changes (created, assign)
                $rightname = $asset::$rightname;
                \ProfileRight::updateProfileRights(getItemByTypeName('Profile', "Technician", true), [
                    "$rightname" => (READNOTE)
                ]);

                // let's use tech user
                $this->login('tech', 'tech');
                $tabs = $asset->defineTabs();
                if ($reflected_class->getProperty('usenotepad')) {
                    $this->assertArrayHasKey('Notepad$1', $tabs);
                } else {
                    $this->assertArrayNotHasKey('Notepad$1', $tabs);
                }
            }
        }
    }
}
