<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units\Glpi\Inventory\Asset;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/volume.class.php */

class Volume extends AbstractInventoryAsset {

   protected function assetProvider() :array {
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
            'expected'  => '{"encrypt_algo": "aes-xts-plain64", "encrypt_name": "LUKS1", "encrypt_status": "Yes", "filesystem": "ext4", "free": 3632, "serial": "e2d02a40-829a-44ce-b863-cf765ac2c9eb", "total": 30109, "type": "/", "volumn": "/dev/mapper/xps-root", "device": "/dev/mapper/xps-root", "filesystems_id": "ext4", "totalsize": 30109, "freesize": 3632, "encryption_tool": "LUKS1", "encryption_algorithm": "aes-xts-plain64", "encryption_status": 1, "name": "/", "mountpoint": "/"}'
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
            'expected'  => '{"filesystem": "ext4", "free": 736, "serial": "dca65bdb-c073-4bcb-bd0d-210031a532c9", "total": 975, "type": "/boot", "volumn": "/dev/nvme0n1p2", "device": "/dev/nvme0n1p2", "filesystems_id": "ext4", "totalsize": 975, "freesize": 736, "name": "/boot", "mountpoint": "/boot"}'
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
            'expected'  => '{"filesystem": "vfat", "free": 191, "serial": "A710-491B", "total": 199, "type": "/boot/efi", "volumn": "/dev/nvme0n1p1", "device": "/dev/nvme0n1p1", "filesystems_id": "vfat", "totalsize": 199, "freesize": 191, "name": "/boot/efi", "mountpoint": "/boot/efi"}'
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
            'expected'  => '{"filesystem": "ext4", "free": 18455, "serial": "b61d4fbf-32da-4c7a-8b15-45ae81b946b2", "total": 100280, "type": "/home", "volumn": "/dev/mapper/xps-home", "device": "/dev/mapper/xps-home", "filesystems_id": "ext4", "totalsize": 100280, "freesize": 18455, "name": "/home", "mountpoint": "/home"}'
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
            'expected'  => '{"filesystem": "ext4", "free": 10009, "serial": "79d60190-518f-4de4-8ed5-74146414b890", "total": 20030, "type": "/var/lib/mysql", "volumn": "/dev/mapper/xps-maria", "device": "/dev/mapper/xps-maria", "filesystems_id": "ext4", "totalsize": 20030, "freesize": 10009, "name": "/var/lib/mysql", "mountpoint": "/var/lib/mysql"}'

         ]
      ];
   }

   /**
    * @dataProvider assetProvider
    */
   public function testPrepare($xml, $expected) {
      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($xml);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\Volume($computer, $json->content->drives);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected));
   }

   public function testHandle() {
      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no volume linked to this computer
      $idd = new \Item_Disk();
      $this->boolean($idd->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A volume is already linked to computer!');

      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($expected['xml']);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\Volume($computer, $json->content->drives);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

      //handle
      $asset->handleLinks();
      $asset->handle();
      $this->boolean($idd->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('Volume has not been linked to computer :(');
   }
}
