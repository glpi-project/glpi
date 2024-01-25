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

use Lockedfield;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/antivirus.class.php */

class Unmanaged extends AbstractInventoryAsset
{
    protected function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
<CONTENT>
    <DEVICE>
    <DNSHOSTNAME>192.168.1.20</DNSHOSTNAME>
    <ENTITY>0</ENTITY>
    <IP>192.168.1.20</IP>
    <MAC>4c:cc:6a:02:13:a9</MAC>
    <NETBIOSNAME>DESKTOP-A3J16LF</NETBIOSNAME>
    <WORKGROUP>WORKGROUP</WORKGROUP>
    </DEVICE>
    <MODULEVERSION>5.1</MODULEVERSION>
    <PROCESSNUMBER>189</PROCESSNUMBER>
</CONTENT>
<DEVICEID>asus-desktop-2022-09-20-16-43-09</DEVICEID>
<QUERY>NETDISCOVERY</QUERY>
</REQUEST>
",
                'expected'  => '
                {
                    "content": {
                        "hardware": {
                            "workgroup": "WORKGROUP"
                        },
                        "versionclient": "5.1",
                        "network_device": {
                            "type": "Unmanaged",
                            "mac": "4c:cc:6a:02:13:a9",
                            "name": "DESKTOP-A3J16LF",
                            "ips": [
                                "192.168.1.20"
                            ]
                        }
                    },
                    "deviceid": "asus-desktop-2022-09-20-16-43-09",
                    "action": "netdiscovery",
                    "jobid": 189,
                    "itemtype": "Unmanaged"
                }'
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
        $this->object($json)->isEqualTo(json_decode($expected));
    }


    /**
     * test :
     * 1 - as unmanaged
     * 2 - as computer
     * test RuleEntity
     * test LockedField
     * test unmanaged converter
     */
    public function testInventory()
    {
        global $DB;
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
            <DEVICE>
            <DNSHOSTNAME>192.168.1.20</DNSHOSTNAME>
            <ENTITY>0</ENTITY>
            <IP>192.168.1.20</IP>
            <MAC>4c:cc:6a:02:13:a9</MAC>
            <NETBIOSNAME>DESKTOP-A3J16LF</NETBIOSNAME>
            <WORKGROUP>WORKGROUP</WORKGROUP>
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>189</PROCESSNUMBER>
        </CONTENT>
        <DEVICEID>asus-desktop-2022-09-20-16-43-09</DEVICEID>
        <QUERY>NETDISCOVERY</QUERY>
        </REQUEST>
        ";

        //create agent
        //from SNMP Discovery or Inventory agent already exist
        $agent = new \Agent();
        $agent_id = $agent->add([
            'deviceid'  => 'asus-desktop-2022-09-20-16-43-09',
            'tag'       => 'sub',
            'agenttypes_id' => 1, //Core
            'itemtype' => 'Computer', //Core
            'items_id' =>  0 //Core
        ]);

        $this->integer($agent_id)->isGreaterThan(0);
        //create rule
        $this->login();
        $entity = new \Entity();
        $entities_id_a = $entity->add([
            'name'         => 'Entity A',
            'entities_id'  => 0,
            'completename' => 'Root entitiy > Entity A',
            'level'        => 2,
            'tag'          => 'entA'
        ]);
        $this->integer($entities_id_a)->isGreaterThan(0);

        $entities_id_b = $entity->add([
            'name'         => 'Entity B',
            'entities_id'  => 0,
            'completename' => 'Root entitiy > Entity B',
            'level'        => 2,
            'tag'          => 'sub'
        ]);
        $this->integer($entities_id_b)->isGreaterThan(0);

        // Add a rule for get entity tag (1)
        $rule = new \Rule();
        $input = [
            'is_active' => 1,
            'name'      => 'entity rule 1',
            'match'     => 'AND',
            'sub_type'  => 'RuleImportEntity',
            'ranking'   => 1
        ];
        $rule1_id = $rule->add($input);
        $this->integer($rule1_id)->isGreaterThan(0);

        // Add criteria
        $rulecriteria = new \RuleCriteria();
        $input = [
            'rules_id'  => $rule1_id,
            'criteria'  => "tag",
            'pattern'   => "/(.*)/",
            'condition' => \RuleImportEntity::REGEX_MATCH
        ];
        $this->integer($rulecriteria->add($input))->isGreaterThan(0);

        $input = [
            'rules_id'  => $rule1_id,
            'criteria'  => "itemtype",
            'pattern'   => "Unmanaged",
            'condition' => \RuleImportEntity::PATTERN_IS
        ];
        $this->integer($rulecriteria->add($input))->isGreaterThan(0);

        // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rule1_id,
            'action_type' => 'regex_result',
            'field'       => '_affect_entity_by_tag',
            'value'       => '#0'
        ];
        $this->integer($ruleaction->add($input))->isGreaterThan(0);

        $this->doInventory($xml, true);

        //no Agent from discovery
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);

        //check for one Unmanaged
        $unmanaged = new \Unmanaged();
        $this->boolean($unmanaged->getFromDbByCrit(['name' => 'DESKTOP-A3J16LF']))->isTrue();

        //check last_inventory_update
        $this->variable($unmanaged->fields['last_inventory_update'])->isEqualTo($_SESSION['glpi_currenttime']);

        //check entity
        $this->variable($unmanaged->fields['entities_id'])->isEqualTo($entities_id_b);

        //check for one NetworkPort
        $np = new \NetworkPort();
        $nps = $np->find(["itemtype" => \Unmanaged::class, "items_id" => $unmanaged->fields['id'], "mac" => "4c:cc:6a:02:13:a9"]);
        $this->integer(count($nps))->isIdenticalTo(1);

        //check for one IPAdress
        $ip = new \IPAddress();
        $ips = $ip->find(["mainitemtype" => \Unmanaged::class, "mainitems_id" => $unmanaged->fields['id'], "name" => "192.168.1.20"]);
        $this->integer(count($ips))->isIdenticalTo(1);

        $rm = new \RuleMatchedLog();
        //check for one RuleMatchLog
        $rms = $rm->find(["itemtype" => \Unmanaged::class, "items_id" => $unmanaged->fields['id']]);
        $this->integer(count($rms))->isIdenticalTo(1);


        //update field to create locked field
        //update computer to add lock on serial
        $this->boolean($unmanaged->update([
            'id' => $unmanaged->fields['id'],
            'users_id' => getItemByTypeName('User', 'glpi', true)
        ]))->isTrue();

        //get lockedfield field
        $lock = new \Lockedfield();
        $locks = $lock->find(["itemtype" => \Unmanaged::class, "items_id" => $unmanaged->fields['id'], "field" => "users_id"]);
        $this->integer(count($locks))->isIdenticalTo(1);


        //redo inventory
        $this->doInventory($xml, true);

        //check for always one Unmanaged
        $this->boolean($unmanaged->getFromDbByCrit(['name' => 'DESKTOP-A3J16LF']))->isTrue();

        //check last_inventory_update
        $this->variable($unmanaged->fields['last_inventory_update'])->isEqualTo($_SESSION['glpi_currenttime']);

        //check entity
        $this->variable($unmanaged->fields['entities_id'])->isEqualTo($entities_id_b);

        //check for lock
        $locks = $lock->find(["itemtype" => \Unmanaged::class, "items_id" => $unmanaged->fields['id'], "field" => "users_id"]);
        $this->integer(count($locks))->isIdenticalTo(1);


        //check for always  one NetworkPort
        $nps = $np->find(["itemtype" => \Unmanaged::class, "items_id" => $unmanaged->fields['id'], "mac" => "4c:cc:6a:02:13:a9"]);
        $this->integer(count($nps))->isIdenticalTo(1);

        //check for always one IPAddress
        $ips = $ip->find(["mainitemtype" => \Unmanaged::class, "mainitems_id" => $unmanaged->fields['id'], "name" => "192.168.1.20"]);
        $this->integer(count($ips))->isIdenticalTo(1);

        //check for 2 RuleMatchLog
        $rms = $rm->find(["itemtype" => \Unmanaged::class, "items_id" => $unmanaged->fields['id']]);
        $this->integer(count($rms))->isIdenticalTo(2);

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
            <DEVICE>
            <DNSHOSTNAME>192.168.1.22</DNSHOSTNAME>
            <ENTITY>0</ENTITY>
            <IP>192.168.1.22</IP>
            <MAC>4c:cc:6a:02:13:a9</MAC>
            <NETBIOSNAME>DESKTOP-A3J16LF</NETBIOSNAME>
            <WORKGROUP>WORKGROUP</WORKGROUP>
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>189</PROCESSNUMBER>
        </CONTENT>
        <DEVICEID>asus-desktop-2022-09-20-16-43-09</DEVICEID>
        <QUERY>NETDISCOVERY</QUERY>
        </REQUEST>
        ";

        //redo inventory but change IP
        $this->doInventory($xml, true);

        //check for always one Unmanaged
        $this->boolean($unmanaged->getFromDbByCrit(['name' => 'DESKTOP-A3J16LF']))->isTrue();

        //check last_inventory_update
        $this->variable($unmanaged->fields['last_inventory_update'])->isEqualTo($_SESSION['glpi_currenttime']);

        //check entity
        $this->variable($unmanaged->fields['entities_id'])->isEqualTo($entities_id_b);

        //check for lock
        $locks = $lock->find(["itemtype" => \Unmanaged::class, "items_id" => $unmanaged->fields['id'], "field" => "users_id"]);
        $this->integer(count($locks))->isIdenticalTo(1);

        //check for always  one NetworkPort
        $nps = $np->find(["itemtype" => \Unmanaged::class, "items_id" => $unmanaged->fields['id'], "mac" => "4c:cc:6a:02:13:a9"]);
        $this->integer(count($nps))->isIdenticalTo(1);

        //check for always one IPAddress but .22
        $ips = $ip->find(["mainitemtype" => \Unmanaged::class, "mainitems_id" => $unmanaged->fields['id'], "name" => "192.168.1.22"]);
        $this->integer(count($ips))->isIdenticalTo(1);

        //check for 3 RuleMatchLog
        $rms = $rm->find(["itemtype" => \Unmanaged::class, "items_id" => $unmanaged->fields['id']]);
        $this->integer(count($rms))->isIdenticalTo(3);

        //convert as Computer
        $unmanaged->convert($unmanaged->fields['id'], "Computer");

        //unamage device no longer exist
        $this->boolean($unmanaged->getFromDbByCrit(['name' => 'DESKTOP-A3J16LF']))->isFalse();

        //check computer exist now
        $computer = new \Computer();
        $this->boolean($computer->getFromDbByCrit(['name' => 'DESKTOP-A3J16LF']))->isTrue();

        //check last_inventory_update
        $this->variable($computer->fields['last_inventory_update'])->isEqualTo($_SESSION['glpi_currenttime']);

        //check entity
        $this->variable($computer->fields['entities_id'])->isEqualTo($entities_id_b);

        //check for lock move to computer
        $locks = $lock->find(["itemtype" => \Computer::class, "items_id" => $computer->fields['id'], "field" => "users_id"]);
        $this->integer(count($locks))->isIdenticalTo(1);

        //check for lock move to computer
        $locks = $lock->find(["itemtype" => \Unmanaged::class, "items_id" => $computer->fields['id'], "field" => "users_id"]);
        $this->integer(count($locks))->isIdenticalTo(0);


        //check for always  one NetworkPort
        $nps = $np->find(["itemtype" => \Computer::class, "items_id" => $computer->fields['id'], "mac" => "4c:cc:6a:02:13:a9"]);
        $this->integer(count($nps))->isIdenticalTo(1);

        //check for always  one IPAddress but .22
        $ips = $ip->find(["mainitemtype" => \Computer::class, "mainitems_id" => $computer->fields['id'], "name" => "192.168.1.22"]);
        $this->integer(count($ips))->isIdenticalTo(1);

        //check for 3 RuleMatchLog
        $rms = $rm->find(["itemtype" => \Computer::class, "items_id" => $computer->fields['id']]);
        $this->integer(count($rms))->isIdenticalTo(3);

        //redo inventory
        $this->doInventory($xml, true);

        //check for one computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDbByCrit(['name' => 'DESKTOP-A3J16LF']))->isTrue();

        //check last_inventory_update
        $this->variable($computer->fields['last_inventory_update'])->isEqualTo($_SESSION['glpi_currenttime']);

        //check entity
        $this->variable($computer->fields['entities_id'])->isEqualTo($entities_id_b);

        //check for always one NetworkPort
        $nps = $np->find(["itemtype" => \Computer::class, "items_id" => $computer->fields['id'], "mac" => "4c:cc:6a:02:13:a9"]);
        $this->integer(count($nps))->isIdenticalTo(1);

        //check for always one IPAddress but .22
        $ips = $ip->find(["mainitemtype" => \Computer::class, "mainitems_id" => $computer->fields['id'], "name" => "192.168.1.22"]);
        $this->integer(count($ips))->isIdenticalTo(1);

        //check for 4 RuleMatchLog
        $rms = $rm->find(["itemtype" => \Computer::class, "items_id" => $computer->fields['id']]);
        $this->integer(count($rms))->isIdenticalTo(4);

        //redo inventory with users_id
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
            <DEVICE>
                <DNSHOSTNAME>192.168.1.22</DNSHOSTNAME>
                <ENTITY>0</ENTITY>
                <IP>192.168.1.22</IP>
                <MAC>4c:cc:6a:02:13:a9</MAC>
                <NETBIOSNAME>DESKTOP-A3J16LF</NETBIOSNAME>
                <WORKGROUP>WORKGROUP</WORKGROUP>
            </DEVICE>
            <HARDWARE>
                <LASTLOGGEDUSER>tech</LASTLOGGEDUSER>
            </HARDWARE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>189</PROCESSNUMBER>
        </CONTENT>
        <DEVICEID>asus-desktop-2022-09-20-16-43-09</DEVICEID>
        <QUERY>NETDISCOVERY</QUERY>
        </REQUEST>
        ";

        //redo inventory
        $this->doInventory($xml, true);

        //check for one computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDbByCrit(['name' => 'DESKTOP-A3J16LF']))->isTrue();

        //check users_id is always glpi
        $this->variable($computer->fields['users_id'])->isEqualTo(getItemByTypeName('User', 'glpi', true));

        //release lock
        $this->boolean($lock->deleteByCriteria(["itemtype" => \Computer::class, "items_id" => $computer->fields['id'], "field" => "users_id"]))->isTrue();

        //redo inventory with another user
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
            <DEVICE>
                <DNSHOSTNAME>192.168.1.22</DNSHOSTNAME>
                <ENTITY>0</ENTITY>
                <IP>192.168.1.22</IP>
                <MAC>4c:cc:6a:02:13:a9</MAC>
                <NETBIOSNAME>DESKTOP-A3J16LF</NETBIOSNAME>
                <WORKGROUP>WORKGROUP</WORKGROUP>
            </DEVICE>
            <HARDWARE>
                <LASTLOGGEDUSER>tech</LASTLOGGEDUSER>
            </HARDWARE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>189</PROCESSNUMBER>
        </CONTENT>
        <DEVICEID>asus-desktop-2022-09-20-16-43-09</DEVICEID>
        <QUERY>NETDISCOVERY</QUERY>
        </REQUEST>
        ";

        //redo inventory
        $this->doInventory($xml, true);

        //check for one computer
        $computer = new \Computer();
        $this->boolean($computer->getFromDbByCrit(['name' => 'DESKTOP-A3J16LF']))->isTrue();

        //check users_id is changed to tech
        $this->variable($computer->fields['users_id'])->isEqualTo(getItemByTypeName('User', 'tech', true));
    }


    public function testAgentNotDeleted()
    {
        global $DB;
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
            <HARDWARE>
              <NAME>glpixps</NAME>
              <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
            </HARDWARE>
            <BIOS>
              <MSN>640HP72</MSN>
              <SSN>000</SSN>
            </BIOS>
            <NETWORKS>
              <DESCRIPTION>Carte Intel(R) PRO/1000 MT pour station de travail</DESCRIPTION>
              <IPADDRESS>192.168.1.20</IPADDRESS>
              <MACADDR>08:00:27:16:9C:60</MACADDR>
              <VIRTUALDEV>0</VIRTUALDEV>
            </NETWORKS>
            <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
          </CONTENT>
          <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
          <QUERY>INVENTORY</QUERY>
          </REQUEST>";


        $this->doInventory($xml, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps.teclib.infra-2018-10-03-08-42-36')
            ->string['itemtype']->isIdenticalTo('Computer');

        //check created computer
        $computers_id = $agent['items_id'];

        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        //do network discovery with another agent
        global $DB;
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
            <DEVICE>
            <DNSHOSTNAME>glpixps</DNSHOSTNAME>
            <ENTITY>0</ENTITY>
            <IP>192.168.1.20</IP>
            <MAC>08:00:27:16:9C:60</MAC>
            <NETBIOSNAME>DESKTOP-A3J16LF</NETBIOSNAME>
            <WORKGROUP>WORKGROUP</WORKGROUP>
            </DEVICE>
            <MODULEVERSION>5.1</MODULEVERSION>
            <PROCESSNUMBER>189</PROCESSNUMBER>
        </CONTENT>
        <DEVICEID>asus-desktop-2022-09-20-16-43-09</DEVICEID>
        <QUERY>NETDISCOVERY</QUERY>
        </REQUEST>
        ";

        //redo inventory
        $this->doInventory($xml, true);

        //check Unmanaged not exist (since an asset with the same MAC exists)
        $unmanaged = new \Unmanaged();
        $this->boolean($unmanaged->getFromDbByCrit(['name' => 'glpixps']))->isFalse();

        //reload agent
        $agent_reload = new \Agent();
        $this->boolean($agent_reload->getFromDB($agent['id']))->isTrue();

        //check is always linked to computer
        $this->string($agent_reload->fields['itemtype'])->isIdenticalTo("Computer");
        $this->integer($agent_reload->fields['items_id'])->isIdenticalTo($computers_id);
    }

    public function testState()
    {
        global $DB;

        $this->login();

        //create states to use
        $state = new \State();
        $inv_states_id = $state->add([
            'name' => 'In use'
        ]);
        $this->integer($inv_states_id)->isGreaterThan(0);

        \Config::setConfigurationValues(
            'inventory',
            [
                'states_id_default' => $inv_states_id
            ]
        );

        $json_str = '
        {
            "action": "inventory",
            "content": {
                "accesslog": {
                    "logdate": "2023-12-11 09:39:16"
                },
                "bios": {
                    "assettag": "Asset-1234567890",
                    "bdate": "2013-10-29",
                    "bmanufacturer": "American Megatrends Inc.",
                    "bversion": "1602",
                    "mmanufacturer": "ASUSTeK COMPUTER INC.",
                    "mmodel": "Z87-A",
                    "msn": "131219362301208",
                    "skunumber": "All",
                    "smanufacturer": "ASUS",
                    "smodel": "All Series"
                },
                "hardware": {
                    "chassis_type": "Desktop",
                    "datelastloggeduser": "Mon Dec 11 09:34",
                    "defaultgateway": "192.168.1.1",
                    "dns": "127.0.0.53",
                    "lastloggeduser": "teclib",
                    "memory": 32030,
                    "name": "teclib-asus-desktop",
                    "swap": 2047,
                    "uuid": "31042c80-d7da-11dd-93d0-bcee7b8de946",
                    "vmsystem": "Physical",
                    "workgroup": "home"
                },
                "networks": [
                    {
                        "description": "enp3s0",
                        "driver": "r8169",
                        "ipaddress": "192.168.1.20",
                        "ipgateway": "192.168.1.1",
                        "ipmask": "255.255.255.0",
                        "ipsubnet": "192.168.1.0",
                        "mac": "bc:ee:7b:8d:e9:46",
                        "pciid": "10EC:8168:1043:859E",
                        "pcislot": "0000:03:00.0",
                        "speed": "1000",
                        "status": "up",
                        "type": "ethernet",
                        "virtualdev": false
                    }
                ],
                "operatingsystem": {
                    "arch": "x86_64",
                    "boot_time": "2023-12-11 08:36:20",
                    "dns_domain": "home",
                    "fqdn": "teclib-asus-desktop.home",
                    "full_name": "Ubuntu 22.04.3 LTS",
                    "hostid": "007f0101",
                    "install_date": "2023-09-11 08:40:42",
                    "kernel_name": "linux",
                    "kernel_version": "5.15.0-89-generic",
                    "name": "Ubuntu",
                    "ssh_key": "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAICYWwKX1KRqEzIjEsWMQrFX5xDHjx8uTv\/aqNaZ6Xk6m",
                    "timezone": {
                        "name": "Europe\/Paris",
                        "offset": "+0100"
                    },
                    "version": "22.04.3 LTS (Jammy Jellyfish)"
                },
                "versionclient": "GLPI-Agent_v1.4-1",
                "versionprovider": {
                    "comments": [
                        "Built by Debian",
                        "Source time: 2022-07-01 09:21"
                    ],
                    "etime": 4,
                    "name": "GLPI",
                    "perl_args": "--debug --debug",
                    "perl_config": [
                        "gccversion: 11.4.0",
                        "defines: use64bitall use64bitint usedl useithreads uselanginfo uselargefiles usemallocwrap usemultiplicity usemymalloc=n usenm=false useopcode useperlio useposix useshrplib usethreads usevendorprefix usevfork=false"
                    ],
                    "perl_exe": "\/usr\/bin\/perl",
                    "perl_inc": "\/usr\/share\/glpi-agent\/lib:\/etc\/perl:\/usr\/local\/lib\/x86_64-linux-gnu\/perl\/5.34.0:\/usr\/local\/share\/perl\/5.34.0:\/usr\/lib\/x86_64-linux-gnu\/perl5\/5.34:\/usr\/share\/perl5:\/usr\/lib\/x86_64-linux-gnu\/perl-base:\/usr\/lib\/x86_64-linux-gnu\/perl\/5.34:\/usr\/share\/perl\/5.34:\/usr\/local\/lib\/site_perl",
                    "perl_module": [
                        "LWP @ 6.61",
                        "LWP::Protocol @ 6.61",
                        "IO::Socket @ 1.46",
                        "IO::Socket::SSL @ 2.074",
                        "IO::Socket::INET @ 1.46",
                        "Net::SSLeay @ 1.92",
                        "Net::SSLeay uses OpenSSL 3.0.2 15 Mar 2022",
                        "Net::HTTPS @ 6.22",
                        "HTTP::Status @ 6.36",
                        "HTTP::Response @ 6.36"
                    ],
                    "perl_version": "v5.34.0",
                    "program": "\/usr\/bin\/glpi-agent",
                    "version": "1.4-1"
                }
            },
            "deviceid": "teclib-asus-desktop-2022-09-20-16-43-09",
            "itemtype": "Computer",
            "tag": "sub"
        }';

        $json = json_decode($json_str);
        $inventory = $this->doInventory($json);

        //check created agent
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('teclib-asus-desktop-2022-09-20-16-43-09')
            ->string['name']->isIdenticalTo('teclib-asus-desktop-2022-09-20-16-43-09')
            ->string['version']->isIdenticalTo('1.4-1')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->string['tag']->isIdenticalTo('sub')
            ->integer['agenttypes_id']->isIdenticalTo($agenttype['id'])
            ->integer['items_id']->isGreaterThan(0);

        //check created computer
        $computers_id = $agent['items_id'];
        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        //check states has been set
        $this->integer($computer->fields['states_id'])->isIdenticalTo($inv_states_id);

        //run discovery
        $xml_source = '
        <?xml version="1.0" encoding="UTF-8" ?>
<REQUEST>
  <CONTENT>
    <DEVICE>
      <DNSHOSTNAME>192.168.1.20</DNSHOSTNAME>
      <ENTITY>0</ENTITY>
      <IP>192.168.1.20</IP>
      <MAC>bc:ee:7b:8d:e9:46</MAC>
    </DEVICE>
    <MODULEVERSION>5.1</MODULEVERSION>
    <PROCESSNUMBER>17</PROCESSNUMBER>
  </CONTENT>
  <DEVICEID>teclib-asus-desktop-2022-09-20-16-43-09</DEVICEID>
  <QUERY>NETDISCOVERY</QUERY>
</REQUEST>
        ';
        //do a discovery
        $inventory->setDiscovery(true);
        $inventory->doInventory($xml_source, true);

        //no Unmanaged create
        $unmanaged = new \Unmanaged();
        $found = $unmanaged->find();
        $this->array($found)->hasSize(0);

        //reload computer
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        //check that state has not change
        $this->integer($computer->fields['states_id'])->isIdenticalTo($inv_states_id);
    }
}
