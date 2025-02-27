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
use Notepad;
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

    public function testNotepadTab()
    {
        foreach ($this->getAssets() + $this->getComponents() as $asset_class) {
            $reflected_class = new ReflectionClass($asset_class);
            $asset = new $asset_class();
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
