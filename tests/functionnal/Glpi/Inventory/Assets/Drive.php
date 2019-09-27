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

/* Test for inc/inventory/asset/drive.class.php and inc/inventory/asset/harddrive.class.php */

class Drive extends AbstractInventoryAsset {

   protected function assetProvider() :array {
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
            'expected'  => '{"description": "PCI", "disksize": 250059, "firmware": "BXV77D0Q", "manufacturer": "Samsung", "model": "PM951NVMe SAMSUNG 256GB", "name": "nvme0n1", "serialnumber": "S29NNXAH146409", "type": "disk", "capacity": 250059, "manufacturers_id": "Samsung", "designation": "PM951NVMe SAMSUNG 256GB", "serial": "S29NNXAH146409"}'
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
      <SERIALNUMBER />  <TYPE>UNKNOWN</TYPE>
    </STORAGES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"description": "Lecteur de CD-ROM", "manufacturer": "(Lecteurs de CD-ROM standard)", "model": "VBOX CD-ROM ATA Device", "name": "VBOX CD-ROM ATA Device", "scsi_coid": "1", "scsi_lun": "0", "scsi_unid": "0", "type": "UNKNOWN", "designation": "Lecteur de CD-ROM", "interfacetypes_id": "UNKNOWN", "manufacturers_id": "(Lecteurs de CD-ROM standard)"}'
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
      $asset = new \Glpi\Inventory\Asset\Drive($computer, $json->content->storages);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();

      if (!$asset->isDrive($json->content->storages[0])) {
         //work with a HDD
         $this->array($result)->isEmpty();
         $result = $asset->getPreparedHarddrives();
      }
      $this->object($result[0])->isEqualTo(json_decode($expected));
   }

   public function testHandle() {
      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no controller linked to this computer
      $idd = new \Item_DeviceDrive();
      $idh = new \Item_DevicehardDrive();
      $this->boolean($idd->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A drive is already linked to computer!');
      $this->boolean($idh->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A hard drive is already linked to computer!');

      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($expected['xml']);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\Drive($computer, $json->content->storages);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      //is a harddrive
      $this->array($result)->isEmpty();
      $result = $asset->getPreparedHarddrives();
      $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

      //handle
      $asset->handleLinks();
      $asset->handle();
      $this->boolean($idh->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('Hard disk has not been linked to computer :(');
   }
}
