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

/* Test for inc/inventory/asset/controller.class.php */

class VirtualMachine extends AbstractInventoryAsset {

   protected function assetProvider() :array {
      return [
         [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <VIRTUALMACHINES>
      <MEMORY>1048</MEMORY>
      <NAME>centos7.0</NAME>
      <STATUS>off</STATUS>
      <SUBSYSTEM>kvm</SUBSYSTEM>
      <UUID>c37f7ce8-af95-4676-b454-0959f2c5e162</UUID>
      <VCPU>1</VCPU>
      <VMTYPE>libvirt</VMTYPE>
    </VIRTUALMACHINES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"memory": 1048, "name": "centos7.0", "status": "off", "subsystem": "kvm", "uuid": "c37f7ce8-af95-4676-b454-0959f2c5e162", "vcpu": 1, "vmtype": "libvirt", "ram": 1048, "virtualmachinetypes_id": "libvirt", "virtualmachinesystems_id": "kvm", "virtualmachinestates_id": "off"}'
         ], [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <VIRTUALMACHINES>
      <MEMORY>2097</MEMORY>
      <NAME>fedora23</NAME>
      <STATUS>off</STATUS>
      <SUBSYSTEM>kvm</SUBSYSTEM>
      <UUID>358f16bf-6794-4f63-8947-150b807a2294</UUID>
      <VCPU>1</VCPU>
      <VMTYPE>libvirt</VMTYPE>
    </VIRTUALMACHINES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"memory": 2097, "name": "fedora23", "status": "off", "subsystem": "kvm", "uuid": "358f16bf-6794-4f63-8947-150b807a2294", "vcpu": 1, "vmtype": "libvirt", "ram": 2097, "virtualmachinetypes_id": "libvirt", "virtualmachinesystems_id": "kvm", "virtualmachinestates_id": "off"}'
         ], [
            'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <VIRTUALMACHINES>
      <MEMORY>4194</MEMORY>
      <NAME>win8.1</NAME>
      <STATUS>off</STATUS>
      <SUBSYSTEM>kvm</SUBSYSTEM>
      <UUID>fcb505ed-0ffa-419e-a5a0-fd20bed80f1e</UUID>
      <VCPU>2</VCPU>
      <VMTYPE>libvirt</VMTYPE>
    </VIRTUALMACHINES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
            'expected'  => '{"memory": 4194, "name": "win8.1", "status": "off", "subsystem": "kvm", "uuid": "fcb505ed-0ffa-419e-a5a0-fd20bed80f1e", "vcpu": 2, "vmtype": "libvirt", "ram": 4194, "virtualmachinetypes_id": "libvirt", "virtualmachinesystems_id": "kvm", "virtualmachinestates_id": "off"}'
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
      $asset = new \Glpi\Inventory\Asset\VirtualMachine($computer, $json->content->virtualmachines);
      $asset->setExtraData((array)$json->content);
      $conf = new \Glpi\Inventory\Conf();
      $this->boolean(
         $asset->checkConf($conf)
      )->isTrue();
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected));
   }

   public function testHandle() {
      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no vms linked to this computer
      $cvm = new \ComputerVirtualMachine();
      $this->boolean($cvm->getFromDbByCrit(['computers_id' => $computer->fields['id']]))
           ->isFalse('A virtual machine is already linked to computer!');

      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($expected['xml']);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\VirtualMachine($computer, $json->content->virtualmachines);
      $asset->setExtraData((array)$json->content);
      $conf = new \Glpi\Inventory\Conf();
      $this->boolean(
         $asset->checkConf($conf)
      )->isTrue();
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

      $agent = new \Agent();
      $agent->getEmpty();
      $asset->setAgent($agent);

      $conf = new \Glpi\Inventory\Conf();
      $this->boolean(
         $asset->checkConf($conf)
      )->isTrue();

      //handle
      $asset->handleLinks();
      $asset->handle();
      $this->boolean($cvm->getFromDbByCrit(['computers_id' => $computer->fields['id']]))
           ->isTrue('Virtual machine has not been linked to computer :(');
   }
}
