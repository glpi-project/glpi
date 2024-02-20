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

namespace tests\units\Glpi\Inventory\Asset;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/volume.class.php */

class Volume extends AbstractInventoryAsset
{
    protected function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DRIVES>
      <ENCRYPT_ALGO>aes-xts-plain64</ENCRYPT_ALGO>
      <ENCRYPT_NAME>LUKS1</ENCRYPT_NAME>
      <ENCRYPT_STATUS>Yes</ENCRYPT_STATUS>
      <FILESYSTEM>ext4</FILESYSTEM>
      <FREE>3632</FREE>
      <SERIAL>e2d02a40-829a-44ce-b863-cf765ac2c9eb</SERIAL>
      <TOTAL>30109</TOTAL>
      <TYPE>/</TYPE>
      <VOLUMN>/dev/mapper/xps-root</VOLUMN>
    </DRIVES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"encrypt_algo": "aes-xts-plain64", "encrypt_name": "LUKS1", "encrypt_status": "Yes", "filesystem": "ext4", "free": 3632, "serial": "e2d02a40-829a-44ce-b863-cf765ac2c9eb", "total": 30109, "type": "/", "volumn": "/dev/mapper/xps-root", "device": "/dev/mapper/xps-root", "filesystems_id": "ext4", "totalsize": 30109, "freesize": 3632, "encryption_tool": "LUKS1", "encryption_algorithm": "aes-xts-plain64", "encryption_status": 1, "name": "/", "mountpoint": "/", "is_dynamic": 1}'
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DRIVES>
      <FILESYSTEM>ext4</FILESYSTEM>
      <FREE>736</FREE>
      <SERIAL>dca65bdb-c073-4bcb-bd0d-210031a532c9</SERIAL>
      <TOTAL>975</TOTAL>
      <TYPE>/boot</TYPE>
      <VOLUMN>/dev/nvme0n1p2</VOLUMN>
    </DRIVES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"filesystem": "ext4", "free": 736, "serial": "dca65bdb-c073-4bcb-bd0d-210031a532c9", "total": 975, "type": "/boot", "volumn": "/dev/nvme0n1p2", "device": "/dev/nvme0n1p2", "filesystems_id": "ext4", "totalsize": 975, "freesize": 736, "name": "/boot", "mountpoint": "/boot", "is_dynamic": 1}'
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DRIVES>
      <FILESYSTEM>vfat</FILESYSTEM>
      <FREE>191</FREE>
      <SERIAL>A710-491B</SERIAL>
      <TOTAL>199</TOTAL>
      <TYPE>/boot/efi</TYPE>
      <VOLUMN>/dev/nvme0n1p1</VOLUMN>
    </DRIVES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"filesystem": "vfat", "free": 191, "serial": "A710-491B", "total": 199, "type": "/boot/efi", "volumn": "/dev/nvme0n1p1", "device": "/dev/nvme0n1p1", "filesystems_id": "vfat", "totalsize": 199, "freesize": 191, "name": "/boot/efi", "mountpoint": "/boot/efi", "is_dynamic": 1}'
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DRIVES>
      <FILESYSTEM>ext4</FILESYSTEM>
      <FREE>18455</FREE>
      <SERIAL>b61d4fbf-32da-4c7a-8b15-45ae81b946b2</SERIAL>
      <TOTAL>100280</TOTAL>
      <TYPE>/home</TYPE>
      <VOLUMN>/dev/mapper/xps-home</VOLUMN>
    </DRIVES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"filesystem": "ext4", "free": 18455, "serial": "b61d4fbf-32da-4c7a-8b15-45ae81b946b2", "total": 100280, "type": "/home", "volumn": "/dev/mapper/xps-home", "device": "/dev/mapper/xps-home", "filesystems_id": "ext4", "totalsize": 100280, "freesize": 18455, "name": "/home", "mountpoint": "/home", "is_dynamic": 1}'
            ], [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DRIVES>
      <FILESYSTEM>ext4</FILESYSTEM>
      <FREE>10009</FREE>
      <SERIAL>79d60190-518f-4de4-8ed5-74146414b890</SERIAL>
      <TOTAL>20030</TOTAL>
      <TYPE>/var/lib/mysql</TYPE>
      <VOLUMN>/dev/mapper/xps-maria</VOLUMN>
    </DRIVES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"filesystem": "ext4", "free": 10009, "serial": "79d60190-518f-4de4-8ed5-74146414b890", "total": 20030, "type": "/var/lib/mysql", "volumn": "/dev/mapper/xps-maria", "device": "/dev/mapper/xps-maria", "filesystems_id": "ext4", "totalsize": 20030, "freesize": 10009, "name": "/var/lib/mysql", "mountpoint": "/var/lib/mysql", "is_dynamic": 1}'
            ], [ //network drive
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DRIVES>
      <FILESYSTEM>afpfs</FILESYSTEM>
      <FREE>2143720</FREE>
      <TOTAL>4194304</TOTAL>
      <TYPE>/Volumes/timemachine</TYPE>
      <VOLUMN>//timemachine@timemachine.glpi-project.org/timemachine</VOLUMN>
    </DRIVES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"filesystem": "afpfs", "free": 2143720, "total": 4194304, "type": "/Volumes/timemachine", "volumn": "//timemachine@timemachine.glpi-project.org/timemachine", "device": "//timemachine@timemachine.glpi-project.org/timemachine", "filesystems_id": "afpfs", "totalsize": 4194304, "freesize": 2143720, "name": "/Volumes/timemachine", "mountpoint": "/Volumes/timemachine", "is_dynamic": 1}'
            ], [ //removable drive
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DRIVES>
      <DESCRIPTION>Disque amovible</DESCRIPTION>
      <FILESYSTEM>FAT32</FILESYSTEM>
      <FREE>3267</FREE>
      <LABEL>USB2</LABEL>
      <LETTER>E:</LETTER>
      <SERIAL>7C4A2931</SERIAL>
      <TOTAL>7632</TOTAL>
      <TYPE>Removable Disk</TYPE>
      <VOLUMN>USB2</VOLUMN>
    </DRIVES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"description": "Disque amovible", "filesystem": "FAT32", "free": 3267, "label": "USB2", "letter": "E:", "serial": "7C4A2931", "total": 7632, "type": "Removable Disk", "volumn": "USB2", "device": "USB2", "filesystems_id": "FAT32", "totalsize": 7632, "freesize": 3267, "name": "USB2", "mountpoint": "E:", "is_dynamic": 1}'
            ]
        ];
    }

    /**
     * @dataProvider assetProvider
     */
    public function testPrepare($xml, $expected)
    {
        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Volume($computer, $json->content->drives);
        $asset->setExtraData((array)$json->content);

        $conf = new \Glpi\Inventory\Conf();
        $this->boolean($asset->checkConf($conf))->isTrue();

        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected));
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no volume linked to this computer
        $idd = new \Item_Disk();
        $this->boolean($idd->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A volume is already linked to computer!');

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Volume($computer, $json->content->drives);
        $asset->setExtraData((array)$json->content);

        $conf = new \Glpi\Inventory\Conf();
        $this->boolean($asset->checkConf($conf))->isTrue();

        $result = $asset->prepare();
        $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->boolean($idd->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('Volume has not been linked to computer :(');
    }

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $item_disk = new \Item_Disk();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <ACCOUNTINFO>
      <KEYNAME>TAG</KEYNAME>
      <KEYVALUE>3923</KEYVALUE>
    </ACCOUNTINFO>
    <DRIVES>
      <FREE>259327</FREE>
      <LETTER>C:</LETTER>
      <TOTAL>290143</TOTAL>
    </DRIVES>
    <DRIVES>
      <LETTER>Z:</LETTER>
    </DRIVES>
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

       //create manually a computer, with 3 disks
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $cdisk_id = $item_disk->add([
            "items_id"     => $computers_id,
            "itemtype"     => 'Computer',
            "name"         => "C:",
            "mountpoint"   => "C:",
            "entities_id"  => 0
        ]);
        $this->integer($cdisk_id)->isGreaterThan(0);

        $ddisk_id = $item_disk->add([
            "items_id"     => $computers_id,
            "itemtype"     => 'Computer',
            "name"         => "D:",
            "mountpoint"   => "D:",
            "entities_id"  => 0
        ]);
        $this->integer($ddisk_id)->isGreaterThan(0);

        $zdisk_id = $item_disk->add([
            "items_id"     => $computers_id,
            "itemtype"     => 'Computer',
            "name"         => "Z:",
            "mountpoint"   => "Z:",
            "entities_id"  => 0
        ]);
        $this->integer($zdisk_id)->isGreaterThan(0);

        $disks = $item_disk->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($disks))->isIdenticalTo(3);
        foreach ($disks as $disk) {
            $this->variable($disk['is_dynamic'])->isEqualTo(0);
        }

       //computer inventory knows only disks C: and Z:
        $this->doInventory($xml_source, true);

       //we still have 3 disks linked to the computer
        $disks = $item_disk->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($disks))->isIdenticalTo(3);

       //disks present in the inventory source are now dynamic
        $disks = $item_disk->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($disks))->isIdenticalTo(2);

        $this->boolean($item_disk->getFromDB($cdisk_id))->isTrue();
        $this->integer($item_disk->fields['is_dynamic'])->isIdenticalTo(1);

        $this->boolean($item_disk->getFromDB($zdisk_id))->isTrue();
        $this->integer($item_disk->fields['is_dynamic'])->isIdenticalTo(1);

       //disk not present in the inventory is still not dynamic
        $disks = $item_disk->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($disks))->isIdenticalTo(1);

        $this->boolean($item_disk->getFromDB($ddisk_id))->isTrue();
        $this->integer($item_disk->fields['is_dynamic'])->isIdenticalTo(0);

       //Redo inventory, but with removed disk Z:
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <ACCOUNTINFO>
      <KEYNAME>TAG</KEYNAME>
      <KEYVALUE>3923</KEYVALUE>
    </ACCOUNTINFO>
    <DRIVES>
      <FREE>259327</FREE>
      <LETTER>C:</LETTER>
      <TOTAL>290143</TOTAL>
    </DRIVES>
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

       //we now have 2 disks only
        $disks = $item_disk->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($disks))->isIdenticalTo(2);

       //disks present in the inventory source are still dynamic
        $disks = $item_disk->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($disks))->isIdenticalTo(1);

        $this->boolean($item_disk->getFromDB($cdisk_id))->isTrue();
        $this->integer($item_disk->fields['is_dynamic'])->isIdenticalTo(1);

       //Z: has been removed
        $this->boolean($item_disk->getFromDB($zdisk_id))->isFalse();

       //disk not present in the inventory is still not dynamic
        $disks = $item_disk->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($disks))->isIdenticalTo(1);

        $this->boolean($item_disk->getFromDB($ddisk_id))->isTrue();
        $this->integer($item_disk->fields['is_dynamic'])->isIdenticalTo(0);
    }

    public function testInventoryImportOrNot()
    {
        $item_disk = new \Item_Disk();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <DRIVES>
      <FREE>259327</FREE>
      <LETTER>C:</LETTER>
      <TOTAL>290143</TOTAL>
    </DRIVES>
    <DRIVES>
      <LETTER>Z:</LETTER>
    </DRIVES>
    <DRIVES>
      <FILESYSTEM>afpfs</FILESYSTEM>
      <FREE>2143720</FREE>
      <TOTAL>4194304</TOTAL>
      <TYPE>/Volumes/timemachine</TYPE>
      <VOLUMN>//timemachine@timemachine.glpi-project.org/timemachine</VOLUMN>
    </DRIVES>
    <DRIVES>
      <DESCRIPTION>Disque amovible</DESCRIPTION>
      <FILESYSTEM>FAT32</FILESYSTEM>
      <FREE>3267</FREE>
      <LABEL>USB2</LABEL>
      <LETTER>E:</LETTER>
      <SERIAL>7C4A2931</SERIAL>
      <TOTAL>7632</TOTAL>
      <TYPE>Removable Disk</TYPE>
      <VOLUMN>USB2</VOLUMN>
    </DRIVES>
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

        //per default, configuration allows all volumes import. change that.
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->boolean(
            $conf->saveConf([
                'import_volume' => 0,
                'component_networkdrive' => 0,
                'component_removablemedia' => 0
            ])
        )->isTrue();
        $this->logout();

        //first inventory should import no disk.
        $inventory = $this->doInventory($xml_source, true);

        $this->login();
        $this->boolean(
            $conf->saveConf([
                'import_volume' => 1,
                'component_networkdrive' => 1,
                'component_removablemedia' => 1
            ])
        )->isTrue();
        $this->logOut();

        $computer = $inventory->getItem();
        $computers_id = $computer->fields['id'];

        //no disks linked to the computer
        $disks = $item_disk->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($disks))->isIdenticalTo(0);

        //set config to inventory disks, but no network nor removable
        $this->login();
        $this->boolean(
            $conf->saveConf([
                'import_volume' => 1,
                'component_networkdrive' => 0,
                'component_removablemedia' => 0
            ])
        )->isTrue();
        $this->logOut();

        //first inventory should import 2 disks (C: and Z:).
        $inventory = $this->doInventory($xml_source, true);

        $this->login();
        $this->boolean(
            $conf->saveConf([
                'import_volume' => 1,
                'component_networkdrive' => 1,
                'component_removablemedia' => 1
            ])
        )->isTrue();
        $this->logOut();

        $computer = $inventory->getItem();
        $computers_id = $computer->fields['id'];

        //C: and Z: has been linked to the computer
        $disks = $item_disk->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($disks))->isIdenticalTo(2);

        //set config to inventory disks, network and removable (the default)
        $this->login();
        $this->boolean(
            $conf->saveConf([
                'import_volume' => 1,
                'component_networkdrive' => 1,
                'component_removablemedia' => 1
            ])
        )->isTrue();
        $this->logout();

        //inventory should import all 4 disks.
        $inventory = $this->doInventory($xml_source, true);

        $computer = $inventory->getItem();
        $computers_id = $computer->fields['id'];

        //all disks has been linked to the computer
        $disks = $item_disk->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($disks))->isIdenticalTo(4);

        $removables_id = null;
        foreach ($disks as $disk) {
            if ($disk['name'] == 'USB2') {
                $removables_id = $disk['id'];
                break;
            }
        }
        $this->boolean($item_disk->getFromDB($removables_id))->isTrue();

        //set config to inventory disks and network, but no removable
        $this->login();
        $this->boolean(
            $conf->saveConf([
                'import_volume' => 1,
                'component_networkdrive' => 1,
                'component_removablemedia' => 0
            ])
        )->isTrue();
        $this->logout();

        $this->doInventory($xml_source, true);

        $this->login();
        $this->boolean(
            $conf->saveConf([
                'import_volume' => 1,
                'component_networkdrive' => 1,
                'component_removablemedia' => 1
            ])
        )->isTrue();
        $this->logout();

        //3 disks are now been linked to the computer
        $disks = $item_disk->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($disks))->isIdenticalTo(3);

        //ensure removable has been removed!
        $this->boolean($item_disk->getFromDB($removables_id))->isFalse();
    }
}
