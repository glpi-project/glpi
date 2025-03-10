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
use Consumable;
use DbTestCase;
use CartridgeItem;
use Computer;
use Monitor;
use Software;
use Peripheral;
use NetworkEquipment;
use Printer;
use Phone;
use PDU;
use Rack;
use Enclosure;
use Cable;
use Cluster;
use Webhook;
use DatabaseInstance;
use PassiveDCEquipment;
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
use ReflectionClass;
use Notepad;

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
            Cable::class,
            Cluster::class,
            Webhook::class,
            DatabaseInstance::class,
        ];
    }

    public function getComponents()
    {
        return [
            DeviceBattery::class,
            DeviceCamera::class,
            DeviceCase::class,
            DeviceControl::class,
            DeviceDrive::class,
            DeviceFirmware::class,
            DeviceGeneric::class,
            DeviceGraphicCard::class,
            DeviceHardDrive::class,
            DeviceMemory::class,
            DeviceNetworkCard::class,
            DevicePci::class,
            DevicePowerSupply::class,
            DeviceProcessor::class,
            DeviceSensor::class,
            DeviceSimcard::class,
            DeviceSoundCard::class,
            DeviceMotherboard::class,
        ];
    }

    public function itemProvider()
    {
        foreach ($this->getAssets() as $itemtype) {
            switch ($itemtype) {
                case Cartridge::class:
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
                    break;
                case Consumable::class:
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
                    break;
                default:
                    yield [
                        'itemtype' => $itemtype,
                        'item' => $this->createItem($itemtype, [
                            'name' => 'test',
                            'entities_id' => 0,
                        ]),
                    ];
                    break;
            }
        }

        foreach ($this->getComponents() as $itemtype) {
            $component = new $itemtype();
            switch ($itemtype) {
                case DeviceBattery::class:
                    yield [
                        'itemtype' => 'Item_' . $itemtype,
                        'item' => $this->createItem('Item_' . $itemtype, [
                            'entities_id' => 0,
                            'devicebatteries_id' => $component->add([
                                'entities_id' => 0,
                            ]),
                        ])
                    ];
                    break;
                case DeviceMemory::class:
                    yield [
                        'itemtype' => 'Item_' . $itemtype,
                        'item' => $this->createItem('Item_' . $itemtype, [
                            'entities_id' => 0,
                            'devicememories_id' => $component->add([
                                'entities_id' => 0,
                            ]),
                        ])
                    ];
                    break;
                case DevicePowerSupply::class:
                    yield [
                        'itemtype' => 'Item_' . $itemtype,
                        'item' => $this->createItem('Item_' . $itemtype, [
                            'entities_id' => 0,
                            'devicepowersupplies_id' => $component->add([
                                'entities_id' => 0,
                            ]),
                        ])
                    ];
                    break;
                default:
                    yield [
                        'itemtype' => 'Item_' . $itemtype,
                        'item' => $this->createItem('Item_' . $itemtype, [
                            'entities_id' => 0,
                            'itemtype' => $itemtype,
                            strtolower($itemtype . 's_id') => $component->add([
                                'entities_id' => 0,
                            ]),
                        ])
                    ];
                    break;
            }
        }
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

    public function testGetCloneRelationsNotepad()
    {
        foreach ($this->itemProvider() as $item_provider) {
            $reflected_class = new ReflectionClass($item_provider['itemtype']);
            $asset = $item_provider['item'];
            if ($reflected_class->hasProperty('usenotepad')) {
                $tabs = $asset->getCloneRelations();
                $key = array_search('Notepad', $tabs);
                if ($reflected_class->getProperty('usenotepad')) {
                    $this->assertNotFalse($key);
                } else {
                    $this->assertFalse($key);
                }
            }
        }
    }

    public function testRawSearchOptionsNotepad()
    {
        foreach ($this->itemProvider() as $item_provider) {
            $reflected_class = new ReflectionClass($item_provider['itemtype']);
            $asset = $item_provider['item'];
            if ($reflected_class->hasProperty('usenotepad')) {
                $so_notepad = Notepad::rawSearchOptionsToAdd();
                $so_notepad_ids = array_column($so_notepad, 'id');
                $item_search_options = $asset->rawSearchOptions();
                $item_search_options_ids = array_column($item_search_options, 'id');
                foreach ($so_notepad_ids as $so_id) {
                    $key = array_search($so_id, $item_search_options_ids);
                    if ($reflected_class->getProperty('usenotepad')) {
                        $this->assertNotFalse($key);
                    } else {
                        $this->assertFalse($key);
                    }
                }
            }
        }
    }
}
