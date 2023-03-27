<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
}
