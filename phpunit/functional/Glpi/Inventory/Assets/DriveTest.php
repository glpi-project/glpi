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

namespace tests\units\Glpi\Inventory\Asset;

use Glpi\Inventory\Asset\Drive;
use Glpi\Inventory\Conf;
use Glpi\Inventory\Converter;
use PHPUnit\Framework\Attributes\DataProvider;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/drive.class.php and inc/inventory/asset/harddrive.class.php */

class DriveTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
        return [
            [ //hdd
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <STORAGES>
      <DESCRIPTION>PCI</DESCRIPTION>
      <DISKSIZE>250059</DISKSIZE>
      <FIRMWARE>BXV77D0Q</FIRMWARE>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MODEL>PM951NVMe SAMSUNG 256GB</MODEL>
      <NAME>nvme0n1</NAME>
      <SERIALNUMBER>S29NNXAH146409</SERIALNUMBER>
      <TYPE>disk</TYPE>
    </STORAGES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "PCI", "disksize": 250059, "firmware": "BXV77D0Q", "manufacturer": "Samsung", "model": "PM951NVMe SAMSUNG 256GB", "name": "nvme0n1", "type": "disk", "serial": "S29NNXAH146409", "capacity": 250059, "deviceharddrivetypes_id": "disk", "manufacturers_id": "Samsung", "designation": "PM951NVMe SAMSUNG 256GB", "serial": "S29NNXAH146409", "is_dynamic": 1}',
            ], [ //cdrom
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <STORAGES>
      <DESCRIPTION>Lecteur de CD-ROM</DESCRIPTION>
      <MANUFACTURER>(Lecteurs de CD-ROM standard)</MANUFACTURER>
      <MODEL>VBOX CD-ROM ATA Device</MODEL>
      <NAME>VBOX CD-ROM ATA Device</NAME>
      <SCSI_COID>1</SCSI_COID>
      <SCSI_LUN>0</SCSI_LUN>
      <SCSI_UNID>0</SCSI_UNID>
      <SERIALNUMBER />
      <TYPE>UNKNOWN</TYPE>
    </STORAGES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "Lecteur de CD-ROM", "manufacturer": "(Lecteurs de CD-ROM standard)", "model": "VBOX CD-ROM ATA Device", "name": "VBOX CD-ROM ATA Device", "scsi_coid": "1", "scsi_lun": "0", "scsi_unid": "0", "type": "UNKNOWN", "designation": "Lecteur de CD-ROM", "interfacetypes_id": "UNKNOWN", "manufacturers_id": "(Lecteurs de CD-ROM standard)", "is_dynamic": 1}',
            ],
        ];
    }

    #[DataProvider('assetProvider')]
    public function testPrepare($xml, $expected)
    {
        $converter = new Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new Drive($computer, $json->content->storages);
        $asset->setExtraData((array) $json->content);
        $this->assertTrue($asset->checkConf(new Conf()));
        $result = $asset->prepare();

        if (!$asset->isDrive($json->content->storages[0])) {
            //work with a HDD
            $this->assertIsArray($result);
            $this->assertEmpty($result);
            $result = $asset->getPreparedHarddrives();
        }
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no controller linked to this computer
        $idd = new \Item_DeviceDrive();
        $idh = new \Item_DeviceHardDrive();
        $this->assertFalse(
            $idd->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'A drive is already linked to computer!'
        );
        $this->assertFalse(
            $idh->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'A hard drive is already linked to computer!'
        );

        //convert data
        $expected = $this->assetProvider()[0];

        $converter = new Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new Drive($computer, $json->content->storages);
        $asset->setExtraData((array) $json->content);
        $this->assertTrue($asset->checkConf(new Conf()));
        $result = $asset->prepare();
        //is a harddrive
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $result = $asset->getPreparedHarddrives();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $idh->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'Hard disk has not been linked to computer :('
        );
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $device_drive = new \DeviceDrive();
        $item_drive = new \Item_DeviceDrive();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <STORAGES>
      <DESCRIPTION>CD-ROM drive</DESCRIPTION>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MODEL>hp CDDVDW SU-208FB ATA Device</MODEL>
      <NAME>hp CDDVDW SU-208FB ATA Device</NAME>
      <SCSI_COID>1</SCSI_COID>
      <SCSI_LUN>0</SCSI_LUN>
      <SCSI_UNID>0</SCSI_UNID>
      <TYPE>CDROM</TYPE>
    </STORAGES>
    <STORAGES>
      <DESCRIPTION>Lecteur de CD-ROM</DESCRIPTION>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MODEL>LITE-ON DVD SOHD-16P9S</MODEL>
      <NAME>LITE-ON DVD SOHD-16P9S</NAME>
      <SCSI_COID>4</SCSI_COID>
      <SCSI_LUN>0</SCSI_LUN>
      <SCSI_UNID>0</SCSI_UNID>
      <TYPE>CD-ROM</TYPE>
    </STORAGES>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        //create manually a computer, with 3 drives
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Samsung',
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $interface = new \InterfaceType();
        $interfacetypes_id = $interface->add([
            'name' => 'CDROM',
        ]);
        $this->assertGreaterThan(0, $interfacetypes_id);

        $drive_1_id = $device_drive->add([
            'designation' => 'CD-ROM drive',
            'manufacturers_id' => $manufacturers_id,
            'interfacetypes_id' => $interfacetypes_id,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $drive_1_id);

        $item_drive_1_id = $item_drive->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicedrives_id' => $drive_1_id,
        ]);
        $this->assertGreaterThan(0, $item_drive_1_id);

        $interfacetypes_id = $interface->add([
            'name' => 'CD-ROM',
        ]);
        $this->assertGreaterThan(0, $interfacetypes_id);

        $drive_2_id = $device_drive->add([
            'designation' => 'Lecteur de CD-ROM',
            'manufacturers_id' => $manufacturers_id,
            'interfacetypes_id' => $interfacetypes_id,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $drive_2_id);

        $item_drive_2_id = $item_drive->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicedrives_id' => $drive_2_id,
        ]);
        $this->assertGreaterThan(0, $item_drive_2_id);

        $interfacetypes_id = $interface->add([
            'name' => 'DVD Writer',
        ]);

        $drive_3_id = $device_drive->add([
            'designation' => 'My Drive',
            'manufacturers_id' => $manufacturers_id,
            'interfacetypes_id' => $interfacetypes_id,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $drive_3_id);

        $item_drive_3_id = $item_drive->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'devicedrives_id' => $drive_3_id,
        ]);
        $this->assertGreaterThan(0, $item_drive_3_id);

        $drives = $item_drive->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $drives);
        foreach ($drives as $drive) {
            $this->assertEquals(0, $drive['is_dynamic']);
        }

        //computer inventory knows only "hp" and "lite-on" drives
        $this->doInventory($xml_source, true);

        //we still have 3 drives
        $drives = $device_drive->find();
        $this->assertCount(3, $drives);

        //we still have 3 drives items linked to the computer
        $drives = $item_drive->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $drives);

        //drives present in the inventory source are now dynamic
        $drives = $item_drive->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $drives);

        //drive not present in the inventory is still not dynamic
        $drives = $item_drive->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $drives);

        //Redo inventory, but with removed "lite-on" drive
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <STORAGES>
      <DESCRIPTION>CD-ROM drive</DESCRIPTION>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MODEL>hp CDDVDW SU-208FB ATA Device</MODEL>
      <NAME>hp CDDVDW SU-208FB ATA Device</NAME>
      <SCSI_COID>1</SCSI_COID>
      <SCSI_LUN>0</SCSI_LUN>
      <SCSI_UNID>0</SCSI_UNID>
      <TYPE>CDROM</TYPE>
    </STORAGES>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        //we still have 3 drives
        $drives = $device_drive->find();
        $this->assertCount(3, $drives);

        //we now have 2 drives linked to computer only
        $drives = $item_drive->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $drives);

        //drive present in the inventory source is still dynamic
        $drives = $item_drive->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $drives);

        //drive not present in the inventory is still not dynamic
        $drives = $item_drive->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $drives);
    }

    public function testInventoryUpdateHardDrive()
    {
        $computer = new \Computer();
        $device_hdd = new \DeviceHardDrive();
        $item_hdd = new \Item_DeviceHardDrive();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <STORAGES>
      <DESCRIPTION>PCI</DESCRIPTION>
      <DISKSIZE>256060</DISKSIZE>
      <FIRMWARE>BXV77D0Q</FIRMWARE>
      <INTERFACE>IDE</INTERFACE>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MODEL>PM951 NVMe SAMSUNG 256GB</MODEL>
      <NAME>nvme0n1</NAME>
      <SERIALNUMBER>S29NNXAH146764</SERIALNUMBER>
      <TYPE>disk</TYPE>
    </STORAGES>
    <STORAGES>
      <DESCRIPTION>Lecteur de disque</DESCRIPTION>
      <DISKSIZE>320072</DISKSIZE>
      <FIRMWARE>GHBOA900</FIRMWARE>
      <INTERFACE>IDE</INTERFACE>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MODEL>HGST HTS725032A7E630</MODEL>
      <NAME>\\.\PHYSICALDRIVE0</NAME>
      <SCSI_COID>0</SCSI_COID>
      <SCSI_LUN>0</SCSI_LUN>
      <SCSI_UNID>0</SCSI_UNID>
      <SERIAL>131005TF0401Y11K4NNN</SERIAL>
      <SERIALNUMBER>131005TF0401Y11K4NNN</SERIALNUMBER>
      <TYPE>disk</TYPE>
    </STORAGES>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        //create manually a computer, with 3 harddrives
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $manufacturer = new \Manufacturer();
        $manufacturers_id = $manufacturer->add([
            'name' => 'Samsung',
        ]);
        $this->assertGreaterThan(0, $manufacturers_id);

        $interface = new \InterfaceType();
        $this->assertTrue(
            $interface->getFromDBByCrit(['name' => 'IDE'])
        );
        $interfacetypes_id = $interface->fields['id'];
        $this->assertGreaterThan(0, $interfacetypes_id);

        $hdd_type = new \DeviceHardDriveType();
        $hddt_id = $hdd_type->add([
            'name' => 'disk',
        ]);
        $this->assertGreaterThan(0, $hddt_id);

        $harddrive_1_id = $device_hdd->add([
            'designation' => 'PM951 NVMe SAMSUNG 256GB',
            'manufacturers_id' => $manufacturers_id,
            'interfacetypes_id' => $interfacetypes_id,
            'entities_id'  => 0,
            'deviceharddrivetypes_id' => $hddt_id,
        ]);
        $this->assertGreaterThan(0, $harddrive_1_id);

        $item_harddrive_1_id = $item_hdd->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'deviceharddrives_id' => $harddrive_1_id,
            'serial'       => 'S29NNXAH146764',
        ]);
        $this->assertGreaterThan(0, $item_harddrive_1_id);

        $harddrive_2_id = $device_hdd->add([
            'designation' => 'HGST HTS725032A7E630',
            'manufacturers_id' => $manufacturers_id,
            'interfacetypes_id' => $interfacetypes_id,
            'entities_id'  => 0,
            'deviceharddrivetypes_id' => $hddt_id,
        ]);
        $this->assertGreaterThan(0, $harddrive_2_id);

        $item_harddrive_2_id = $item_hdd->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'deviceharddrives_id' => $harddrive_2_id,
            'serial'       => '131005TF0401Y11K4NNN',
        ]);
        $this->assertGreaterThan(0, $item_harddrive_2_id);

        $harddrive_3_id = $device_hdd->add([
            'designation' => 'My Hard Drive',
            'manufacturers_id' => $manufacturers_id,
            'interfacetypes_id' => $interfacetypes_id,
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $harddrive_3_id);

        $item_harddrive_3_id = $item_hdd->add([
            'items_id'     => $computers_id,
            'itemtype'     => 'Computer',
            'deviceharddrives_id' => $harddrive_3_id,
        ]);
        $this->assertGreaterThan(0, $item_harddrive_3_id);

        $harddrives = $item_hdd->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $harddrives);
        foreach ($harddrives as $harddrive) {
            $this->assertEquals(0, $harddrive['is_dynamic']);
        }

        //computer inventory knows only "PM951 NVMe SAMSUNG 256GB" and "HGST HTS725032A7E630" harddrives
        $this->doInventory($xml_source, true);

        //we still have 3 harddrives
        $harddrives = $device_hdd->find();
        $this->assertCount(3, $harddrives);

        //we still have 3 harddrives items linked to the computer
        $harddrives = $item_hdd->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(3, $harddrives);

        //harddrives present in the inventory source are now dynamic
        $harddrives = $item_hdd->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $harddrives);

        //harddrive not present in the inventory is still not dynamic
        $harddrives = $item_hdd->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $harddrives);

        //Redo inventory, but with removed "HGST HTS725032A7E630" harddrive
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <STORAGES>
      <DESCRIPTION>PCI</DESCRIPTION>
      <DISKSIZE>256060</DISKSIZE>
      <FIRMWARE>BXV77D0Q</FIRMWARE>
      <INTERFACE>IDE</INTERFACE>
      <MANUFACTURER>Samsung</MANUFACTURER>
      <MODEL>PM951 NVMe SAMSUNG 256GB</MODEL>
      <NAME>nvme0n1</NAME>
      <SERIALNUMBER>S29NNXAH146764</SERIALNUMBER>
      <TYPE>disk</TYPE>
    </STORAGES>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        //we still have 3 harddrives
        $harddrives = $device_hdd->find();
        $this->assertCount(3, $harddrives);

        //we now have 2 harddrives linked to computer only
        $harddrives = $item_hdd->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $harddrives);

        //harddrive present in the inventory source is still dynamic
        $harddrives = $item_hdd->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $harddrives);

        //harddrive not present in the inventory is still not dynamic
        $harddrives = $item_hdd->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->assertCount(1, $harddrives);
    }
}
