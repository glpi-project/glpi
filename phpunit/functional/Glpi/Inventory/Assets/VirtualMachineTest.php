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

use PHPUnit\Framework\Attributes\DataProvider;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/controller.class.php */

class VirtualMachineTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
    {
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
                'expected'  => '{"memory": 1048, "name": "centos7.0", "status": "off", "subsystem": "kvm", "uuid": "c37f7ce8-af95-4676-b454-0959f2c5e162", "vcpu": 1, "vmtype": "libvirt", "ram": 1048, "virtualmachinetypes_id": "libvirt", "virtualmachinesystems_id": "kvm", "virtualmachinestates_id": "off","is_deleted": 0}'
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
                'expected'  => '{"memory": 2097, "name": "fedora23", "status": "off", "subsystem": "kvm", "uuid": "358f16bf-6794-4f63-8947-150b807a2294", "vcpu": 1, "vmtype": "libvirt", "ram": 2097, "virtualmachinetypes_id": "libvirt", "virtualmachinesystems_id": "kvm", "virtualmachinestates_id": "off","is_deleted": 0}'
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
                'expected'  => '{"memory": 4194, "name": "win8.1", "status": "off", "subsystem": "kvm", "uuid": "fcb505ed-0ffa-419e-a5a0-fd20bed80f1e", "vcpu": 2, "vmtype": "libvirt", "ram": 4194, "virtualmachinetypes_id": "libvirt", "virtualmachinesystems_id": "kvm", "virtualmachinestates_id": "off","is_deleted": 0}'
            ]
        ];
    }

    #[DataProvider('assetProvider')]
    public function testPrepare($xml, $expected)
    {
        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\VirtualMachine($computer, $json->content->virtualmachines);
        $asset->setExtraData((array)$json->content);
        $conf = new \Glpi\Inventory\Conf();
        $this->assertTrue(
            $asset->checkConf($conf)
        );
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected), $result[0]);
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no vms linked to this computer
        $cvm = new \ItemVirtualMachine();
        $this->assertFalse(
            $cvm->getFromDbByCrit(['itemtype' => 'Computer', 'items_id' => $computer->fields['id']]),
            'A virtual machine is already linked to computer!'
        );

        //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\VirtualMachine($computer, $json->content->virtualmachines);
        $asset->setExtraData((array)$json->content);
        $conf = new \Glpi\Inventory\Conf();
        $this->assertTrue(
            $asset->checkConf($conf)
        );
        $result = $asset->prepare();
        $this->assertEquals(json_decode($expected['expected']), $result[0]);

        $agent = new \Agent();
        $agent->getEmpty();
        $asset->setAgent($agent);

        $conf = new \Glpi\Inventory\Conf();
        $this->assertTrue(
            $asset->checkConf($conf)
        );

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $cvm->getFromDbByCrit(['itemtype' => 'Computer', 'items_id' => $computer->fields['id']]),
            'Virtual machine has not been linked to computer :('
        );
    }

    public function testImportVirtualMachine()
    {

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
            <BIOS>
              <ASSETTAG></ASSETTAG>
              <BDATE>2018-02-08T00:00:00Z</BDATE>
              <BVERSION>1.3.7</BVERSION>
              <MSN>2YR88P2</MSN>
              <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
              <SMODEL>PowerEdge R640</SMODEL>
              <SSN>2YR88P2</SSN>
            </BIOS>
            <CPUS>
              <CORE>10</CORE>
              <MANUFACTURER>Intel</MANUFACTURER>
              <NAME>Intel(R) Xeon(R) Gold 5115 CPU @ 2.40GHz</NAME>
              <SPEED>2394</SPEED>
              <THREAD>2</THREAD>
            </CPUS>
            <HARDWARE>
              <DNS>10.100.230.2/10.100.230.4</DNS>
              <MEMORY>130625</MEMORY>
              <NAME>ESX-03-DMZ</NAME>
              <UUID>8c8c8944-0074-5632-7452-b2c04f564712</UUID>
              <VMSYSTEM>Physical</VMSYSTEM>
              <WORKGROUP>teclib.fr</WORKGROUP>
            </HARDWARE>
            <NETWORKS>
              <DESCRIPTION>vmk0</DESCRIPTION>
              <IPADDRESS>10.100.240.66</IPADDRESS>
              <IPMASK>255.255.255.192</IPMASK>
              <MACADDR>80:18:44:f0:47:33</MACADDR>
              <MTU>1500</MTU>
              <STATUS>Up</STATUS>
              <VIRTUALDEV>1</VIRTUALDEV>
            </NETWORKS>
            <OPERATINGSYSTEM>
              <BOOT_TIME>2022-08-06 15:40:56</BOOT_TIME>
              <DNS_DOMAIN>insep.fr</DNS_DOMAIN>
              <FQDN>esx-03-dmz.teclib.fr</FQDN>
              <FULL_NAME>VMware ESXi 6.7.0 build-19195723</FULL_NAME>
              <NAME>VMware ESXi</NAME>
              <TIMEZONE>
                <NAME>UTC</NAME>
                <OFFSET>+0000</OFFSET>
              </TIMEZONE>
              <VERSION>6.7.0</VERSION>
            </OPERATINGSYSTEM>
            <VERSIONCLIENT>GLPI-Agent_v1.4-1</VERSIONCLIENT>
            <VIRTUALMACHINES>
              <COMMENT>Computer VM</COMMENT>
              <IPADDRESS>10.100.240.11</IPADDRESS>
              <MAC>00:50:56:90:43:42</MAC>
              <MEMORY>1024</MEMORY>
              <NAME>SRV-DMZ-EZ</NAME>
              <OPERATINGSYSTEM>
                 <BOOT_TIME>2024-01-24T16:00:00Z</BOOT_TIME>
                 <FQDN>test.test.test</FQDN>
                 <FULL_NAME>OS Test</FULL_NAME>
              </OPERATINGSYSTEM>
              <STATUS>running</STATUS>
              <UUID>420904FE-6a92-95e8-13f9-a37fc3607c14</UUID>
              <VCPU>1</VCPU>
              <VMTYPE>VMware</VMTYPE>
            </VIRTUALMACHINES>
          </CONTENT>
          <DEVICEID>ESX-03-DMZ.insep.fr-2023-02-02-11-34-53</DEVICEID>
          <QUERY>INVENTORY</QUERY>
        </REQUEST>
        ";

          //change config to import vms as computers
          $this->login();
          $conf = new \Glpi\Inventory\Conf();
          $this->assertTrue($conf->saveConf(['vm_as_computer' => 1]));
          $this->logout();

          //computer inventory knows bios
          $inventory = $this->doInventory($xml_source, true);

          $esx_id_first = $inventory->getItem()->fields['id'];
          $this->assertGreaterThan(0, $esx_id_first);

          //always one VM
          $vm = new \ItemVirtualMachine();
          $this->assertCount(1, $vm->find());

          //get ComputervirtualMachine
          $vm_first = new \ItemVirtualMachine();
          $this->assertTrue($vm_first->getFromDBByCrit([
              'uuid' => '420904fe-6a92-95e8-13f9-a37fc3607c14',
              'itemtype' => 'Computer',
              'items_id' => $esx_id_first
          ]));


          //get Computer
          $computer_linked_first = new \Computer();
          $this->assertTrue($computer_linked_first->getFromDBByCrit([
              'uuid' => '420904fe-6a92-95e8-13f9-a37fc3607c14',
          ]));

          //get NetworkPort Computer
          $netport_computer_linked_first = new \NetworkPort();
          $this->assertTrue($netport_computer_linked_first->getFromDBByCrit([
              'items_id' => $computer_linked_first->fields['id'],
              'itemtype' => 'Computer',
          ]));

          $netpname_computer_linked_first = new \NetworkName();
          $this->assertTrue($netpname_computer_linked_first->getFromDBByCrit([
              'items_id' => $netport_computer_linked_first->fields['id'],
              'itemtype' => 'NetworkPort',
          ]));

          $ip_computer_linked_first = new \IPAddress();
          $this->assertTrue($ip_computer_linked_first->getFromDBByCrit([
              'items_id' => $netpname_computer_linked_first->fields['id'],
              'itemtype' => 'NetworkName',
              'name' => '10.100.240.11'
          ]));

          //get Operating system Computer
          $itemos_computer_linked_first = new \Item_OperatingSystem();
          $this->assertTrue($itemos_computer_linked_first->getFromDBByCrit([
              'items_id' => $computer_linked_first->fields['id'],
              'itemtype' => 'Computer',
          ]));

          $os_computer_linked_first = new \OperatingSystem();
          $this->assertTrue($os_computer_linked_first->getFromDBByCrit([
              'id' => $itemos_computer_linked_first->fields['operatingsystems_id'],
              'name' => 'OS Test'
          ]));

          $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
          <REQUEST>
            <CONTENT>
              <BIOS>
                <ASSETTAG></ASSETTAG>
                <BDATE>2018-02-08T00:00:00Z</BDATE>
                <BVERSION>1.3.7</BVERSION>
                <MSN>2YR88P2</MSN>
                <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
                <SMODEL>PowerEdge R640</SMODEL>
                <SSN>2YR88P2</SSN>
              </BIOS>
              <CPUS>
                <CORE>10</CORE>
                <MANUFACTURER>Intel</MANUFACTURER>
                <NAME>Intel(R) Xeon(R) Gold 5115 CPU @ 2.40GHz</NAME>
                <SPEED>2394</SPEED>
                <THREAD>2</THREAD>
              </CPUS>
              <HARDWARE>
                <DNS>10.100.230.2/10.100.230.4</DNS>
                <MEMORY>130625</MEMORY>
                <NAME>ESX-03-DMZ</NAME>
                <UUID>8c8c8944-0074-5632-7452-b2c04f564712</UUID>
                <VMSYSTEM>Physical</VMSYSTEM>
                <WORKGROUP>teclib.fr</WORKGROUP>
              </HARDWARE>
              <NETWORKS>
                <DESCRIPTION>vmk0</DESCRIPTION>
                <IPADDRESS>10.100.240.66</IPADDRESS>
                <IPMASK>255.255.255.192</IPMASK>
                <MACADDR>80:18:44:f0:47:33</MACADDR>
                <MTU>1500</MTU>
                <STATUS>Up</STATUS>
                <VIRTUALDEV>1</VIRTUALDEV>
              </NETWORKS>
              <OPERATINGSYSTEM>
                <BOOT_TIME>2022-08-06 15:40:56</BOOT_TIME>
                <DNS_DOMAIN>insep.fr</DNS_DOMAIN>
                <FQDN>esx-03-dmz.teclib.fr</FQDN>
                <FULL_NAME>VMware ESXi 6.7.0 build-19195723</FULL_NAME>
                <NAME>VMware ESXi</NAME>
                <TIMEZONE>
                  <NAME>UTC</NAME>
                  <OFFSET>+0000</OFFSET>
                </TIMEZONE>
                <VERSION>6.7.0</VERSION>
              </OPERATINGSYSTEM>
              <VERSIONCLIENT>GLPI-Agent_v1.4-1</VERSIONCLIENT>
              <VIRTUALMACHINES>
                <COMMENT>Computer VM</COMMENT>
                <MAC>00:50:56:90:43:42</MAC>
                <MEMORY>1024</MEMORY>
                <NAME>SRV-DMZ-EZ</NAME>
                <STATUS>running</STATUS>
                <UUID>fe040942-926a-e895-13f9-a37fc3607c14</UUID>
                <VCPU>1</VCPU>
                <VMTYPE>VMware</VMTYPE>
              </VIRTUALMACHINES>
            </CONTENT>
            <DEVICEID>ESX-03-DMZ.insep.fr-2023-02-02-11-34-53</DEVICEID>
            <QUERY>INVENTORY</QUERY>
          </REQUEST>
          ";


          //redo inventory with different formatted UUID fe040942-926a-e895-13f9-a37fc3607c14
          $inventory = $this->doInventory($xml_source, true);

          $esx_id_second = $inventory->getItem()->fields['id'];
          $this->assertGreaterThan(0, $esx_id_second);

          $this->assertSame($esx_id_second, $esx_id_first);

          //always one VM
          $vm = new \ItemVirtualMachine();
          $this->assertCount(1, $vm->find());

          //get ComputervirtualMachine
          $vm_second = new \ItemVirtualMachine();
          $this->assertTrue($vm_second->getFromDBByCrit([
              'uuid' => 'fe040942-926a-e895-13f9-a37fc3607c14',
              'itemtype' => 'Computer',
              'items_id' => $esx_id_second
          ]));

          //get Computer
          $computer_linked_second = new \Computer();
          $this->assertTrue($computer_linked_second->getFromDBByCrit([
              'uuid' => 'fe040942-926a-e895-13f9-a37fc3607c14',
          ]));

          //same VM and Computer
          $this->assertSame($vm_second->fields['id'], $vm_first->fields['id']);
          $this->assertSame($computer_linked_second->fields['id'], $computer_linked_first->fields['id']);
    }

    public function testImportVirtualMachineWithoutHistory()
    {
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
            <BIOS>
              <ASSETTAG></ASSETTAG>
              <BDATE>2018-02-08T00:00:00Z</BDATE>
              <BVERSION>1.3.7</BVERSION>
              <MSN>2YR88P2</MSN>
              <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
              <SMODEL>PowerEdge R640</SMODEL>
              <SSN>2YR88P2</SSN>
            </BIOS>
            <HARDWARE>
              <DNS>10.100.230.2/10.100.230.4</DNS>
              <MEMORY>130625</MEMORY>
              <NAME>ESX-03-DMZ</NAME>
              <UUID>8c8c8944-0074-5632-7452-b2c04f564712</UUID>
              <VMSYSTEM>Physical</VMSYSTEM>
              <WORKGROUP>teclib.fr</WORKGROUP>
            </HARDWARE>
            <VERSIONCLIENT>GLPI-Agent_v1.4-1</VERSIONCLIENT>
            <VIRTUALMACHINES>
              <COMMENT>Computer VM</COMMENT>
              <MAC>00:50:56:90:43:42</MAC>
              <MEMORY>1024</MEMORY>
              <NAME>SRV-DMZ-EZ</NAME>
              <STATUS>running</STATUS>
              <UUID>fe040942-926a-e895-13f9-a37fc3607c14</UUID>
              <VCPU>1</VCPU>
              <VMTYPE>VMware</VMTYPE>
            </VIRTUALMACHINES>
            <VIRTUALMACHINES>
            <COMMENT>Computer VM</COMMENT>
            <MAC>00:50:56:90:43:42</MAC>
            <MEMORY>1024</MEMORY>
            <NAME>SRV-DMZ-EZ</NAME>
            <STATUS>running</STATUS>
            <UUID>c37f7ce8-af95-4676-b454-0959f2c5e162</UUID>
            <VCPU>1</VCPU>
            <VMTYPE>VMware</VMTYPE>
          </VIRTUALMACHINES>
          </CONTENT>
          <DEVICEID>ESX-03-DMZ.insep.fr-2023-02-02-11-34-53</DEVICEID>
          <QUERY>INVENTORY</QUERY>
        </REQUEST>
        ";

        //change config to import vms as computers
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->assertTrue($conf->saveConf(['vm_as_computer' => 1]));
        $this->logout();

        //computer inventory knows bios
        $inventory = $this->doInventory($xml_source, true);

        $esx_id_first = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $esx_id_first);

        //get two VM
        $vm = new \ItemVirtualMachine();
        $this->assertCount(2, $vm->find());

        //get first ComputervirtualMachine -> not deleted / purged
        $firlst_vm = new \ItemVirtualMachine();
        $this->assertTrue($firlst_vm->getFromDBByCrit([
            'uuid' => 'fe040942-926a-e895-13f9-a37fc3607c14',
            'itemtype' => 'Computer',
            'items_id' => $esx_id_first,
            'is_deleted' => false
        ]));
        //get related computer with fe040942-926a-e895-13f9-a37fc3607c14 -> not deleted / purged
        $first_computer_linked = new \Computer();
        $this->assertTrue($first_computer_linked->getFromDBByCrit([
            'uuid' => 'fe040942-926a-e895-13f9-a37fc3607c14',
            'is_deleted' => false
        ]));

        //get second ComputervirtualMachine -> not deleted / purged
        $second_vm = new \ItemVirtualMachine();
        $this->assertTrue($second_vm->getFromDBByCrit([
            'uuid' => 'c37f7ce8-af95-4676-b454-0959f2c5e162',
            'itemtype' => 'Computer',
            'items_id' => $esx_id_first,
            'is_deleted' => false
        ]));
        //get computer with c37f7ce8-af95-4676-b454-0959f2c5e162 -> not deleted / purged
        $second_computer_linked = new \Computer();
        $this->assertTrue($second_computer_linked->getFromDBByCrit([
            'uuid' => 'c37f7ce8-af95-4676-b454-0959f2c5e162',
            'is_deleted' => false
        ]));

        //redi inventory without c37f7ce8-af95-4676-b454-0959f2c5e162
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
            <BIOS>
              <ASSETTAG></ASSETTAG>
              <BDATE>2018-02-08T00:00:00Z</BDATE>
              <BVERSION>1.3.7</BVERSION>
              <MSN>2YR88P2</MSN>
              <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
              <SMODEL>PowerEdge R640</SMODEL>
              <SSN>2YR88P2</SSN>
            </BIOS>
            <HARDWARE>
              <DNS>10.100.230.2/10.100.230.4</DNS>
              <MEMORY>130625</MEMORY>
              <NAME>ESX-03-DMZ</NAME>
              <UUID>8c8c8944-0074-5632-7452-b2c04f564712</UUID>
              <VMSYSTEM>Physical</VMSYSTEM>
              <WORKGROUP>teclib.fr</WORKGROUP>
            </HARDWARE>
            <VERSIONCLIENT>GLPI-Agent_v1.4-1</VERSIONCLIENT>
            <VIRTUALMACHINES>
              <COMMENT>Computer VM</COMMENT>
              <MAC>00:50:56:90:43:42</MAC>
              <MEMORY>1024</MEMORY>
              <NAME>SRV-DMZ-EZ</NAME>
              <STATUS>running</STATUS>
              <UUID>fe040942-926a-e895-13f9-a37fc3607c14</UUID>
              <VCPU>1</VCPU>
              <VMTYPE>VMware</VMTYPE>
            </VIRTUALMACHINES>
          </CONTENT>
          <DEVICEID>ESX-03-DMZ.insep.fr-2023-02-02-11-34-53</DEVICEID>
          <QUERY>INVENTORY</QUERY>
        </REQUEST>
        ";

        //redo inventory
        $inventory = $this->doInventory($xml_source, true);

        //now one VM
        $vm = new \ItemVirtualMachine();
        $this->assertCount(1, $vm->find());

        //get first ComputervirtualMachine -> not deleted / purged
        $firlst_vm = new \ItemVirtualMachine();
        $this->assertTrue($firlst_vm->getFromDBByCrit([
            'uuid' => 'fe040942-926a-e895-13f9-a37fc3607c14',
            'itemtype' => 'Computer',
            'items_id' => $esx_id_first,
            'is_deleted' => false
        ]));
        //get computer with fe040942-926a-e895-13f9-a37fc3607c14 -> not deleted / purged
        $first_computer_linked = new \Computer();
        $this->assertTrue($first_computer_linked->getFromDBByCrit([
            'uuid' => 'fe040942-926a-e895-13f9-a37fc3607c14',
            'is_deleted' => false
        ]));

        //get second ComputervirtualMachine -> purged
        $second_vm = new \ItemVirtualMachine();
        $this->assertFalse($second_vm->getFromDBByCrit([
            'uuid' => 'c37f7ce8-af95-4676-b454-0959f2c5e162',
            'itemtype' => 'Computer',
            'items_id' => $esx_id_first
        ]));
        //get computer with c37f7ce8-af95-4676-b454-0959f2c5e162 -> not deleted / purged
        $second_computer_linked = new \Computer();
        $this->assertTrue($second_computer_linked->getFromDBByCrit([
            'uuid' => 'c37f7ce8-af95-4676-b454-0959f2c5e162',
            'is_deleted' => false
        ]));
    }

    /**
     * check import of computers even if some are refused by rules
     */
    public function testImportComputerFromVirtualMachine()
    {
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
            <BIOS>
              <ASSETTAG></ASSETTAG>
              <BDATE>2018-02-08T00:00:00Z</BDATE>
              <BVERSION>1.3.7</BVERSION>
              <MSN>2YR88P2</MSN>
              <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
              <SMODEL>PowerEdge R640</SMODEL>
              <SSN>2YR88P2</SSN>
            </BIOS>
            <HARDWARE>
              <DNS>10.100.230.2/10.100.230.4</DNS>
              <MEMORY>130625</MEMORY>
              <NAME>ESX-03-DMZ</NAME>
              <UUID>8c8c8944-0074-5632-7452-b2c04f564712</UUID>
              <VMSYSTEM>Physical</VMSYSTEM>
              <WORKGROUP>teclib.fr</WORKGROUP>
            </HARDWARE>
            <VERSIONCLIENT>GLPI-Agent_v1.4-1</VERSIONCLIENT>
            <VIRTUALMACHINES>
              <COMMENT>Computer VM</COMMENT>
              <MAC>00:50:56:90:43:42</MAC>
              <MEMORY>1024</MEMORY>
              <NAME>SRV-DMZ-EZ</NAME>
              <STATUS>running</STATUS>
              <UUID>fe040942-926a-e895-13f9-a37fc3607c14</UUID>
              <VCPU>1</VCPU>
              <VMTYPE>VMware</VMTYPE>
            </VIRTUALMACHINES>
            <VIRTUALMACHINES>
              <COMMENT>Computer VM</COMMENT>
              <MAC>00:50:56:90:43:42</MAC>
              <MEMORY>1024</MEMORY>
              <NAME>SRV-REFUSED</NAME>
              <STATUS>running</STATUS>
              <UUID>c37f7ce8-af95-4676-b454-0959f2c5e162</UUID>
              <VCPU>1</VCPU>
              <VMTYPE>VMware</VMTYPE>
            </VIRTUALMACHINES>
            <VIRTUALMACHINES>
              <COMMENT>Computer VM</COMMENT>
              <MAC>00:70:64:87:14:53</MAC>
              <MEMORY>1024</MEMORY>
              <NAME>ANOTHER-SRV</NAME>
              <STATUS>running</STATUS>
              <UUID>25c1bb60-5bcb-11d9-b18f-5404a6a534c4</UUID>
              <VCPU>1</VCPU>
              <VMTYPE>VMware</VMTYPE>
            </VIRTUALMACHINES>
          </CONTENT>
          <DEVICEID>ESX-03-DMZ.insep.fr-2023-02-02-11-34-53</DEVICEID>
          <QUERY>INVENTORY</QUERY>
        </REQUEST>
        ";


        //create rule to refuse SRV-REFUSED computer
        $rule = new \Rule();
        $criteria = new \RuleCriteria();
        $action = new \RuleAction();

        $rules_id = $rule->add(['name' => 'Exclude SRV-REFUSED',
            'is_active' => 1,
            'entities_id' => 0,
            'sub_type' => 'RuleImportAsset',
            'match' => \Rule::AND_MATCHING,
            'condition' => 0,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, $rules_id);


        // Move rule to top ranking so other rules cannot interfere
        $collection = new \RuleImportAssetCollection();
        $this->assertTrue($collection->moveRule($rules_id, 0, \RuleCollection::MOVE_BEFORE));

        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id' => $rules_id,
                'criteria' => 'name',
                'condition' => \Rule::PATTERN_CONTAIN,
                'pattern' => 'REFUSED'
            ])
        );

        $this->assertGreaterThan(
            0,
            $action->add([
                'rules_id' => $rules_id,
                'action_type' => 'assign',
                'field' => '_inventory',
                'value' => '2' //import denied
            ])
        );

        //change config to import vms as computers
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->assertTrue($conf->saveConf(['vm_as_computer' => 1]));
        $this->logout();

        //computer inventory
        $inventory = $this->doInventory($xml_source, true);

        $esx_id_first = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $esx_id_first);

        //get three VM
        $vm = new \ItemVirtualMachine();
        $this->assertCount(3, $vm->find());


        //get related computer with fe040942-926a-e895-13f9-a37fc3607c14 -> exist
        $first_computer_linked = new \Computer();
        $this->assertTrue($first_computer_linked->getFromDBByCrit([
            'uuid' => 'fe040942-926a-e895-13f9-a37fc3607c14',
        ]));

        //get computer with c37f7ce8-af95-4676-b454-0959f2c5e162  -> not exist
        $second_computer_linked = new \Computer();
        $this->assertFalse($second_computer_linked->getFromDBByCrit([
            'uuid' => 'c37f7ce8-af95-4676-b454-0959f2c5e162',
        ]));

        //get computer with 25c1bb60-5bcb-11d9-b18f-5404a6a534c4  -> exist
        $third_computer_linked = new \Computer();
        $this->assertTrue($third_computer_linked->getFromDBByCrit([
            'uuid' => '25c1bb60-5bcb-11d9-b18f-5404a6a534c4',
        ]));
    }

    public function testNoMoreVirtualMachine()
    {
        //inventory
        $source = json_decode('{"content":{"hardware":{"dns":"10.100.230.2\\/10.100.230.4","memory":130625,"name":"ESX-03-DMZ","uuid":"8c8c8944-0074-5632-7452-b2c04f564712","vmsystem":"Physical","workgroup":"teclib.fr"},"versionclient":"GLPI-Agent_v1.4-1","virtualmachines":[{"comment":"Computer VM","mac":"00:50:56:90:43:42","memory":1024,"name":"SRV-DMZ-EZ","status":"running","uuid":"420904FE-6a92-95e8-13f9-a37fc3607c14","vcpu":1,"vmtype":"vmware"}]},"deviceid":"ESX-03-DMZ.insep.fr-2023-02-02-11-34-53","action":"inventory","itemtype":"Computer"}');
        $inventory = $this->doInventory($source);

        $id_first = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $id_first);

        //one VM
        $vm = new \ItemVirtualMachine();
        $this->assertCount(1, $vm->find());

        //get ComputervirtualMachine
        $vm = new \ItemVirtualMachine();
        $this->assertTrue($vm->getFromDBByCrit([
            'uuid' => '420904fe-6a92-95e8-13f9-a37fc3607c14',
            'itemtype' => 'Computer',
            'items_id' => $id_first
        ]));

        //make sure partial with no VM does not remove existing VMs
        //remove VM, but set partial
        $no_vm_source = json_decode('{"content":{"hardware":{"dns":"10.100.230.2\\/10.100.230.4","memory":130625,"name":"ESX-03-DMZ","uuid":"8c8c8944-0074-5632-7452-b2c04f564712","vmsystem":"Physical","workgroup":"teclib.fr"},"versionclient":"GLPI-Agent_v1.4-1","virtualmachines":[{"comment":"Computer VM","mac":"00:50:56:90:43:42","memory":1024,"name":"SRV-DMZ-EZ","status":"running","uuid":"420904FE-6a92-95e8-13f9-a37fc3607c14","vcpu":1,"vmtype":"vmware"}]},"deviceid":"ESX-03-DMZ.insep.fr-2023-02-02-11-34-53","action":"inventory","itemtype":"Computer"}');
        unset($no_vm_source->content->virtualmachines);
        $no_vm_source->partial = true;

        $inventory = $this->doInventory($no_vm_source);
        $id_second = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $id_second);

        $this->assertSame($id_second, $id_first);

        //VM still present
        $vm = new \ItemVirtualMachine();
        $this->assertCount(1, $vm->find());

        //get ComputervirtualMachine
        $vm = new \ItemVirtualMachine();
        $this->assertTrue($vm->getFromDBByCrit([
            'uuid' => '420904fe-6a92-95e8-13f9-a37fc3607c14',
            'itemtype' => 'Computer',
            'items_id' => $id_first
        ]));

        //remove VM, but set partial
        $no_vm_source = json_decode('{"content":{"hardware":{"dns":"10.100.230.2\\/10.100.230.4","memory":130625,"name":"ESX-03-DMZ","uuid":"8c8c8944-0074-5632-7452-b2c04f564712","vmsystem":"Physical","workgroup":"teclib.fr"},"versionclient":"GLPI-Agent_v1.4-1","virtualmachines":[{"comment":"Computer VM","mac":"00:50:56:90:43:42","memory":1024,"name":"SRV-DMZ-EZ","status":"running","uuid":"420904FE-6a92-95e8-13f9-a37fc3607c14","vcpu":1,"vmtype":"vmware"}]},"deviceid":"ESX-03-DMZ.insep.fr-2023-02-02-11-34-53","action":"inventory","itemtype":"Computer"}');
        unset($no_vm_source->content->virtualmachines);
        $inventory = $this->doInventory($no_vm_source);

        $id_second = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $id_second);

        $this->assertSame($id_second, $id_first);

        //no VM left
        $vm = new \ItemVirtualMachine();
        $this->assertCount(0, $vm->find());
    }


    public function testDefaultStatusForRelatedComputer()
    {
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
            <BIOS>
              <ASSETTAG></ASSETTAG>
              <BDATE>2018-02-08T00:00:00Z</BDATE>
              <BVERSION>1.3.7</BVERSION>
              <MSN>324DFG567</MSN>
              <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
              <SMODEL>PowerEdge R640</SMODEL>
              <SSN>324DFG567</SSN>
            </BIOS>
            <HARDWARE>
              <DNS>10.100.230.2/10.100.230.4</DNS>
              <MEMORY>130625</MEMORY>
              <NAME>ESX-03-DMZ</NAME>
              <UUID>8c8c8944-0074-5632-7452-b2c04f564712</UUID>
              <VMSYSTEM>Physical</VMSYSTEM>
              <WORKGROUP>teclib.fr</WORKGROUP>
            </HARDWARE>
            <VERSIONCLIENT>GLPI-Agent_v1.4-1</VERSIONCLIENT>
            <VIRTUALMACHINES>
              <COMMENT>Computer VM</COMMENT>
              <MAC>00:50:56:90:43:42</MAC>
              <MEMORY>1024</MEMORY>
              <NAME>SRV-DMZ-EZ</NAME>
              <STATUS>running</STATUS>
              <UUID>a1234567-89ab-cdef-0123-456789abcdef</UUID>
              <VCPU>1</VCPU>
              <VMTYPE>VMware</VMTYPE>
            </VIRTUALMACHINES>
          </CONTENT>
          <DEVICEID>ESX-03-DMZ.insep.fr-2023-02-02-11-34-53</DEVICEID>
          <QUERY>INVENTORY</QUERY>
        </REQUEST>
        ";

        //change config to import vms as computers
        $this->login();

        $state = new \State();
        $inv_states_id = $state->add([
            'name' => 'Has been inventoried'
        ]);

        $conf = new \Glpi\Inventory\Conf();
        $this->assertTrue($conf->saveConf(['vm_as_computer' => 1, 'states_id_default' => $inv_states_id]));
        $this->logout();

        //computer inventory
        $inventory = $this->doInventory($xml_source, true);

        $esx_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $esx_id);

        //get one VM
        $vm = new \ItemVirtualMachine();
        $this->assertCount(1, $vm->find());

        //get related ComputervirtualMachine
        $firlst_vm = new \ItemVirtualMachine();
        $this->assertTrue($firlst_vm->getFromDBByCrit([
            'uuid' => 'a1234567-89ab-cdef-0123-456789abcdef',
            'itemtype' => 'Computer',
            'items_id' => $esx_id,
        ]));

        //get related computer with a1234567-89ab-cdef-0123-456789abcdef with same state as default configured
        $first_computer_linked = new \Computer();
        $this->assertTrue($first_computer_linked->getFromDBByCrit([
            'uuid' => 'a1234567-89ab-cdef-0123-456789abcdef',
            'states_id' => $inv_states_id
        ]));
    }

    public function testUpdateVirtualMachineComment()
    {
        //inventory
        $source = json_decode('{"content":{"hardware":{"dns":"10.100.230.2\\/10.100.230.4","memory":130625,"name":"ESX-03-DMZ","uuid":"8c8c8944-0074-5632-7452-b2c04f564712","vmsystem":"Physical","workgroup":"teclib.fr"},"versionclient":"GLPI-Agent_v1.4-1","virtualmachines":[{"comment":"Computer VM","mac":"00:50:56:90:43:42","memory":1024,"name":"SRV-DMZ-EZ","status":"running","uuid":"420904FE-6a92-95e8-13f9-a37fc3607c14","vcpu":1,"vmtype":"vmware"}]},"deviceid":"ESX-03-DMZ.insep.fr-2023-02-02-11-34-53","action":"inventory","itemtype":"Computer"}');
        $inventory = $this->doInventory($source);

        $id_first = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $id_first);

        //one VM
        $vm = new \ItemVirtualMachine();
        $this->assertCount(1, $vm->find());

        //get ComputervirtualMachine
        $vm = new \ItemVirtualMachine();
        $this->assertTrue($vm->getFromDBByCrit([
            'uuid'     => '420904fe-6a92-95e8-13f9-a37fc3607c14',
            'itemtype' => \Computer::class,
            'items_id' => $id_first
        ]));

        $this->assertSame('Computer VM', $vm->fields['comment']);

        //change comment, do inventory again
        $source = json_decode('{"content":{"hardware":{"dns":"10.100.230.2\\/10.100.230.4","memory":130625,"name":"ESX-03-DMZ","uuid":"8c8c8944-0074-5632-7452-b2c04f564712","vmsystem":"Physical","workgroup":"teclib.fr"},"versionclient":"GLPI-Agent_v1.4-1","virtualmachines":[{"comment":"Edited Computer VM","mac":"00:50:56:90:43:42","memory":1024,"name":"SRV-DMZ-EZ","status":"running","uuid":"420904FE-6a92-95e8-13f9-a37fc3607c14","vcpu":1,"vmtype":"vmware"}]},"deviceid":"ESX-03-DMZ.insep.fr-2023-02-02-11-34-53","action":"inventory","itemtype":"Computer"}');
        $this->doInventory($source);

        //still one VM
        $this->assertCount(1, $vm->find());

        $this->assertTrue($vm->getFromDBByCrit([
            'uuid'     => '420904fe-6a92-95e8-13f9-a37fc3607c14',
            'itemtype' => \Computer::class,
            'items_id' => $id_first
        ]));
        $this->assertSame('Edited Computer VM', $vm->fields['comment']);
    }
}
