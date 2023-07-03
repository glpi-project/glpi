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

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/computer.class.php */

class Computer extends AbstractInventoryAsset
{
    const INV_FIXTURES = GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/';

    protected function assetProvider(): array
    {
        return [
            [ //both bios and hardware
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BIOS>
      <ASSETTAG />  <BDATE>06/02/2016</BDATE>
      <BMANUFACTURER>Dell Inc.</BMANUFACTURER>
      <BVERSION>1.4.3</BVERSION>
      <MMANUFACTURER>Dell Inc.</MMANUFACTURER>
      <MMODEL>07TYC2</MMODEL>
      <MSN>/640HP72/CN129636460078/</MSN>
      <SKUNUMBER>0704</SKUNUMBER>
      <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
      <SMODEL>XPS 13 9350</SMODEL>
      <SSN>640HP72</SSN>
    </BIOS>
    <HARDWARE>
      <CHASSIS_TYPE>Laptop</CHASSIS_TYPE>
      <CHECKSUM>131071</CHECKSUM>
      <DATELASTLOGGEDUSER>Wed Oct 3 06:56</DATELASTLOGGEDUSER>
      <DEFAULTGATEWAY>192.168.1.1</DEFAULTGATEWAY>
      <DNS>192.168.1.1/172.28.200.20</DNS>
      <ETIME>3</ETIME>
      <IPADDR>192.168.1.119/192.168.122.1/192.168.11.47</IPADDR>
      <LASTLOGGEDUSER>trasher</LASTLOGGEDUSER>
      <MEMORY>7822</MEMORY>
      <NAME>glpixps</NAME>
      <OSCOMMENTS>#1 SMP Thu Sep 20 02:43:23 UTC 2018</OSCOMMENTS>
      <OSNAME>Fedora 28 (Workstation Edition)</OSNAME>
      <OSVERSION>4.18.9-200.fc28.x86_64</OSVERSION>
      <PROCESSORN>1</PROCESSORN>
      <PROCESSORS>2300</PROCESSORS>
      <PROCESSORT>Intel(R) Core(TM) i5-6200U CPU @ 2.30GHz</PROCESSORT>
      <SWAP>7951</SWAP>
      <USERID>trasher</USERID>
      <UUID>4c4c4544-0034-3010-8048-b6c04f503732</UUID>
      <VMSYSTEM>Physical</VMSYSTEM>
      <WORKGROUP>teclib.infra</WORKGROUP>
    </HARDWARE>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'asset' => '{"chassis_type":"Laptop","datelastloggeduser":"Wed Oct 3 06:56","defaultgateway":"192.168.1.1","dns":"192.168.1.1\\/172.28.200.20","lastloggeduser":"trasher","memory":7822,"name":"glpixps","swap":7951,"uuid":"4c4c4544-0034-3010-8048-b6c04f503732","vmsystem":"Physical","workgroup":"teclib.infra","domains_id":"teclib.infra","users_id":0,"contact":"trasher","manufacturers_id":"Dell Inc.","computermodels_id":"XPS 13 9350","serial":"640HP72","mserial":"\\/640HP72\\/CN129636460078\\/","computertypes_id":"Laptop","autoupdatesystems_id":"GLPI Native Inventory","last_inventory_update": "DATE_NOW","is_deleted": 0}'
            ], [ //only hardware
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <CHASSIS_TYPE>Laptop</CHASSIS_TYPE>
      <CHECKSUM>131071</CHECKSUM>
      <DATELASTLOGGEDUSER>Wed Oct 3 06:56</DATELASTLOGGEDUSER>
      <DEFAULTGATEWAY>192.168.1.1</DEFAULTGATEWAY>
      <DNS>192.168.1.1/172.28.200.20</DNS>
      <ETIME>3</ETIME>
      <IPADDR>192.168.1.119/192.168.122.1/192.168.11.47</IPADDR>
      <LASTLOGGEDUSER>trasher</LASTLOGGEDUSER>
      <MEMORY>7822</MEMORY>
      <NAME>glpixps</NAME>
      <OSCOMMENTS>#1 SMP Thu Sep 20 02:43:23 UTC 2018</OSCOMMENTS>
      <OSNAME>Fedora 28 (Workstation Edition)</OSNAME>
      <OSVERSION>4.18.9-200.fc28.x86_64</OSVERSION>
      <PROCESSORN>1</PROCESSORN>
      <PROCESSORS>2300</PROCESSORS>
      <PROCESSORT>Intel(R) Core(TM) i5-6200U CPU @ 2.30GHz</PROCESSORT>
      <SWAP>7951</SWAP>
      <USERID>trasher</USERID>
      <UUID>4c4c4544-0034-3010-8048-b6c04f503732</UUID>
      <VMSYSTEM>Physical</VMSYSTEM>
      <WORKGROUP>teclib.infra</WORKGROUP>
    </HARDWARE>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'asset' => '{"chassis_type":"Laptop","datelastloggeduser":"Wed Oct 3 06:56","defaultgateway":"192.168.1.1","dns":"192.168.1.1\\/172.28.200.20","lastloggeduser":"trasher","memory":7822,"name":"glpixps","swap":7951,"uuid":"4c4c4544-0034-3010-8048-b6c04f503732","vmsystem":"Physical","workgroup":"teclib.infra","domains_id":"teclib.infra","users_id":0,"contact":"trasher","computertypes_id":"Laptop","autoupdatesystems_id":"GLPI Native Inventory","last_inventory_update": "DATE_NOW","is_deleted": 0}'
            ], [ //only bios
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BIOS>
      <ASSETTAG />  <BDATE>06/02/2016</BDATE>
      <BMANUFACTURER>Dell Inc.</BMANUFACTURER>
      <BVERSION>1.4.3</BVERSION>
      <MMANUFACTURER>Dell Inc.</MMANUFACTURER>
      <MMODEL>07TYC2</MMODEL>
      <MSN>/640HP72/CN129636460078/</MSN>
      <SKUNUMBER>0704</SKUNUMBER>
      <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
      <SMODEL>XPS 13 9350</SMODEL>
      <SSN>640HP72</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'asset' => '{"manufacturers_id":"Dell Inc.","computermodels_id":"XPS 13 9350","serial":"640HP72","mserial":"\\/640HP72\\/CN129636460078\\/","autoupdatesystems_id":"GLPI Native Inventory","last_inventory_update": "DATE_NOW","is_deleted": 0}'
            ], [ //only bios - with otherserial
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <BIOS>
      <ASSETTAG>SER1234</ASSETTAG>
      <BDATE>06/02/2016</BDATE>
      <BMANUFACTURER>Dell Inc.</BMANUFACTURER>
      <BVERSION>1.4.3</BVERSION>
      <MMANUFACTURER>Dell Inc.</MMANUFACTURER>
      <MMODEL>07TYC2</MMODEL>
      <MSN>/640HP72/CN129636460078/</MSN>
      <SKUNUMBER>0704</SKUNUMBER>
      <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
      <SMODEL>XPS 13 9350</SMODEL>
      <SSN>640HP72</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'asset' => '{"manufacturers_id":"Dell Inc.","computermodels_id":"XPS 13 9350","serial":"640HP72","mserial":"\\/640HP72\\/CN129636460078\\/","otherserial":"SER1234","autoupdatesystems_id":"GLPI Native Inventory","last_inventory_update": "DATE_NOW","is_deleted": 0}'
            ]
        ];
    }

    /**
     * @dataProvider assetProvider
     */
    public function testPrepare($xml, $asset)
    {
        $date_now = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date_now;
        $asset = str_replace('DATE_NOW', $date_now, $asset);
        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $main = new \Glpi\Inventory\Asset\Computer($computer, $json);
        $main->setExtraData((array)$json->content);
        $result = $main->prepare();
        $this->object($result[0])->isEqualTo(json_decode($asset));
    }

    public function testAutoCleanWithoutLockedField()
    {
        global $DB, $CFG_GLPI;
        $item_monitor = new \Computer_Item();

        $manual_monitor = new \Monitor();
        $manual_monitor_id = $manual_monitor->add([
            "name" => "manual monitor",
            "entities_id" => 0
        ]);
        $this->integer($manual_monitor_id)->isGreaterThan(0);

         $CFG_GLPI['is_user_autoupdate']     = 1;

        $xml =  "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>glpixps</NAME>
      <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
      <LASTLOGGEDUSER>tech</LASTLOGGEDUSER>
    </HARDWARE>
    <BIOS>
      <ASSETTAG>SER1234</ASSETTAG>
      <BDATE>06/02/2016</BDATE>
      <BMANUFACTURER>Dell Inc.</BMANUFACTURER>
      <BVERSION>1.4.3</BVERSION>
      <MMANUFACTURER>Dell Inc.</MMANUFACTURER>
      <MMODEL>07TYC2</MMODEL>
      <MSN>/640HP72/CN129636460078/</MSN>
      <SKUNUMBER>0704</SKUNUMBER>
      <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
      <SMODEL>XPS 13 9350</SMODEL>
      <SSN>640HP72</SSN>
    </BIOS>
    <MONITORS>
          <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
          <CAPTION>DJCP6</CAPTION>
          <DESCRIPTION>32/2015</DESCRIPTION>
          <MANUFACTURER>Sharp Corporation</MANUFACTURER>
          <SERIAL>AFGHHDR0</SERIAL>
        </MONITORS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $this->doInventory($json);

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
        $this->integer($computer->fields['users_id'])->isIdenticalTo(getItemByTypeName('User', 'tech', true));

        //add manual manual monitor to computer
        $this->integer($item_monitor->add([
            "itemtype" => "Monitor",
            "items_id" => $manual_monitor_id,
            "computers_id" => $computers_id
        ]))->isGreaterThan(0);

        $this->integer($computer->fields['users_id'])->isGreaterThan(0);

        //one dynamic monitor linked
        $dynamic_monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($dynamic_monitors))->isIdenticalTo(1);

        //one manual monitor linked
        $manual_monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($manual_monitors))->isIdenticalTo(1);

        //load dynamic monitor
        $dynamic_monitor = new \Monitor();
        $this->boolean($dynamic_monitor->getFromDB(reset($dynamic_monitors)['items_id']))->isTrue();
        //check same users
        $this->integer($dynamic_monitor->fields['users_id'])->isIdenticalTo($computer->fields['users_id']);
        $this->integer($dynamic_monitor->fields['users_id'])->isIdenticalTo(getItemByTypeName('User', 'tech', true));


        //load manual monitor
        $manual_monitor = new \Monitor();
        $this->boolean($manual_monitor->getFromDB(reset($manual_monitors)['items_id']))->isTrue();
        //check same users
        $this->integer($manual_monitor->fields['users_id'])->isIdenticalTo($computer->fields['users_id']);
        $this->integer($manual_monitor->fields['users_id'])->isIdenticalTo(getItemByTypeName('User', 'tech', true));


        //Enable option to clean users_id when element is disconnected
        $CFG_GLPI['is_user_autoclean']     = 1;


        //remove Computer_Item for dynamic monitor
        $item_dynamic_monitor_id = reset($dynamic_monitors)['id'];
        $this->boolean($item_monitor->getFromDB($item_dynamic_monitor_id))->isTrue();
        $this->boolean($item_monitor->delete($item_monitor->fields, 1))->isTrue();
        // reload dynamic monitor and check users_id
        $this->boolean($dynamic_monitor->getFromDB($dynamic_monitor->getID()))->isTrue();
        $this->integer($dynamic_monitor->fields['users_id'])->isIdenticalTo(0);

        //remove Computer_Item for manual monitor
        $item_manual_monitor_id = reset($manual_monitors)['id'];
        $this->boolean($item_monitor->getFromDB($item_manual_monitor_id))->isTrue();
        $this->boolean($item_monitor->delete($item_monitor->fields, 1))->isTrue();
        //reload manual monitor and check users_id
        $this->boolean($manual_monitor->getFromDB($manual_monitor->getID()))->isTrue();
        $this->integer($manual_monitor->fields['users_id'])->isIdenticalTo(0);

        $locked_field = new \Lockedfield();
        //no lock from computer
        $locks = $locked_field->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($locks))->isIdenticalTo(0);

        //no lock from dynamic monitor
        $locks = $locked_field->find(['itemtype' => 'Monitor', 'items_id' => $dynamic_monitor->getID()]);
        $this->integer(count($locks))->isIdenticalTo(0);

        //no lock from manual monitor
        $locks = $locked_field->find(['itemtype' => 'Monitor', 'items_id' => $manual_monitor->getID()]);
        $this->integer(count($locks))->isIdenticalTo(0);
    }

    public function testAutoUpdateWithoutLockedField()
    {
        global $DB, $CFG_GLPI;
        $item_monitor = new \Computer_Item();

        $manual_monitor = new \Monitor();
        $manual_monitor_id = $manual_monitor->add([
            "name" => "manual monitor",
            "entities_id" => 0
        ]);
        $this->integer($manual_monitor_id)->isGreaterThan(0);

        $xml =  "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>glpixps</NAME>
      <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
      <LASTLOGGEDUSER>glpi</LASTLOGGEDUSER>
    </HARDWARE>
    <BIOS>
      <ASSETTAG>SER1234</ASSETTAG>
      <BDATE>06/02/2016</BDATE>
      <BMANUFACTURER>Dell Inc.</BMANUFACTURER>
      <BVERSION>1.4.3</BVERSION>
      <MMANUFACTURER>Dell Inc.</MMANUFACTURER>
      <MMODEL>07TYC2</MMODEL>
      <MSN>/640HP72/CN129636460078/</MSN>
      <SKUNUMBER>0704</SKUNUMBER>
      <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
      <SMODEL>XPS 13 9350</SMODEL>
      <SSN>640HP72</SSN>
    </BIOS>
    <MONITORS>
          <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
          <CAPTION>DJCP6</CAPTION>
          <DESCRIPTION>32/2015</DESCRIPTION>
          <MANUFACTURER>Sharp Corporation</MANUFACTURER>
          <SERIAL>AFGHHDR0</SERIAL>
        </MONITORS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $this->doInventory($json);

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

        //add manual manual monitor to computer
        $this->integer($item_monitor->add([
            "itemtype" => "Monitor",
            "items_id" => $manual_monitor_id,
            "computers_id" => $computers_id
        ]))->isGreaterThan(0);

        $this->integer($computer->fields['users_id'])->isGreaterThan(0);

        //one dynamic monitor linked
        $dynamic_monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($dynamic_monitors))->isIdenticalTo(1);

        //one manual monitor linked
        $manual_monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($manual_monitors))->isIdenticalTo(1);

        //load dynamic monitor
        $dynamic_monitor = new \Monitor();
        $this->boolean($dynamic_monitor->getFromDB(reset($dynamic_monitors)['items_id']))->isTrue();
        //check same users
        $this->integer($dynamic_monitor->fields['users_id'])->isIdenticalTo($computer->fields['users_id']);

        //load manual monitor
        $manual_monitor = new \Monitor();
        $this->boolean($manual_monitor->getFromDB(reset($manual_monitors)['items_id']))->isTrue();
        //check same users
        $this->integer($manual_monitor->fields['users_id'])->isIdenticalTo($computer->fields['users_id']);

        //Enable option to propagate users_id on update to connected element
        $CFG_GLPI['is_user_autoupdate']     = 1;

        //change user from XML file
        $xml =  "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
            <HARDWARE>
              <NAME>glpixps</NAME>
              <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
              <LASTLOGGEDUSER>tech</LASTLOGGEDUSER>
            </HARDWARE>
            <BIOS>
              <ASSETTAG>SER1234</ASSETTAG>
              <BDATE>06/02/2016</BDATE>
              <BMANUFACTURER>Dell Inc.</BMANUFACTURER>
              <BVERSION>1.4.3</BVERSION>
              <MMANUFACTURER>Dell Inc.</MMANUFACTURER>
              <MMODEL>07TYC2</MMODEL>
              <MSN>/640HP72/CN129636460078/</MSN>
              <SKUNUMBER>0704</SKUNUMBER>
              <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
              <SMODEL>XPS 13 9350</SMODEL>
              <SSN>640HP72</SSN>
            </BIOS>
            <MONITORS>
                  <BASE64>AP///////wBNEEkUAAAAACAZAQSlHRF4Dt5Qo1RMmSYPUFQAAAABAQEBAQEBAQEBAQEBAQEBGjaAoHA4H0AwIDUAJqUQAAAYAAAAEAAAAAAAAAAAAAAAAAAAAAAA/gBESkNQNoBMUTEzM00xAAAAAAACQQMoABIAAAsBCiAgAGY=</BASE64>
                  <CAPTION>DJCP6</CAPTION>
                  <DESCRIPTION>32/2015</DESCRIPTION>
                  <MANUFACTURER>Sharp Corporation</MANUFACTURER>
                  <SERIAL>AFGHHDR0</SERIAL>
                </MONITORS>
            <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
          </CONTENT>
          <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
          <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $this->doInventory($json);

        //check agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps.teclib.infra-2018-10-03-08-42-36')
            ->string['itemtype']->isIdenticalTo('Computer');

        //check computer
        $computers_id = $agent['items_id'];

        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        $this->integer($computer->fields['users_id'])->isGreaterThan(0);

        //one dynamic monitor linked
        $dynamic_monitors = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($dynamic_monitors))->isIdenticalTo(1);

        //one manual monitor linked
        $monitors_manual = $item_monitor->find(['itemtype' => 'Monitor', 'computers_id' => $computers_id, 'is_dynamic' => 0]);
        $this->integer(count($monitors_manual))->isIdenticalTo(1);

        //load dynamic monitor
        $dynamic_monitor = new \Monitor();
        $dynamic_monitor_id = reset($dynamic_monitors)['items_id'];
        $this->boolean($dynamic_monitor->getFromDB($dynamic_monitor_id))->isTrue();
        //check same users
        $this->integer($dynamic_monitor->fields['users_id'])->isIdenticalTo($computer->fields['users_id']);

        //load manual monitor
        $manual_monitor = new \Monitor();
        $manual_monitor_id = reset($monitors_manual)['items_id'];
        $this->boolean($manual_monitor->getFromDB($manual_monitor_id))->isTrue();
        //check same users
        $this->integer($manual_monitor->fields['users_id'])->isIdenticalTo($computer->fields['users_id']);


        $locked_field = new \Lockedfield();
        //no lock from computer
        $locks = $locked_field->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($locks))->isIdenticalTo(0);

        //no lock from dynamic monitor
        $locks = $locked_field->find(['itemtype' => 'Monitor', 'items_id' => $dynamic_monitor_id]);
        $this->integer(count($locks))->isIdenticalTo(0);

        //no lock from manual monitor
        $locks = $locked_field->find(['itemtype' => 'Monitor', 'items_id' => $manual_monitor_id]);
        $this->integer(count($locks))->isIdenticalTo(0);
    }

    public function testHandle()
    {
        $json_str = file_get_contents(self::INV_FIXTURES . 'computer_1.json');
        $json = json_decode($json_str);

        $computer = new \Computer();

        $data = (array)$json->content;
        $inventory = new \Glpi\Inventory\Inventory();
        $this->boolean($inventory->setData($json))->isTrue();

        $agent = new \Agent();
        $this->integer($agent->handleAgent($inventory->extractMetadata()))->isGreaterThan(0);

        $main = new \Glpi\Inventory\Asset\Computer($computer, $json);
        $main->setAgent($agent)->setExtraData($data);
        $result = $main->prepare();
        $this->array($result)->hasSize(1);

        $main->handle();
        $this->boolean($main->areLinksHandled())->isTrue();
    }

    public function testHandleMserial()
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
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>";

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $this->doInventory($json);

        //check matchedlogs
        $criteria = [
            'FROM' => \RuleMatchedLog::getTable(),
            'LEFT JOIN' => [
                \Rule::getTable() => [
                    'ON' => [
                        \RuleMatchedLog::getTable() => 'rules_id',
                        \Rule::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => []
        ];
        $iterator = $DB->request($criteria);
        $this->string($iterator->current()['name'])->isIdenticalTo('Computer import (by serial + uuid)');

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

        //check serial came from "MSN" node.
        $this->string($computer->fields['serial'])->isIdenticalTo('640HP72');

        //Reimport, should not create a new computer
        $this->doInventory($json);

        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps.teclib.infra-2018-10-03-08-42-36')
            ->string['itemtype']->isIdenticalTo('Computer');

        //check created computer - the same as before
        $this->integer($agent['items_id'])->isIdenticalTo($computers_id);
        $computers_id = $agent['items_id'];

        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        //check serial came from "MSN" node.
        $this->string($computer->fields['serial'])->isIdenticalTo('640HP72');
    }

    public function testHandleMserialOnly()
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
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>";

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $this->doInventory($json);

        //check matchedlogs
        $criteria = [
            'FROM' => \RuleMatchedLog::getTable(),
            'LEFT JOIN' => [
                \Rule::getTable() => [
                    'ON' => [
                        \RuleMatchedLog::getTable() => 'rules_id',
                        \Rule::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => []
        ];
        $iterator = $DB->request($criteria);
        $this->string($iterator->current()['name'])->isIdenticalTo('Computer import (by serial + uuid)');

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

        //check serial came from "MSN" node.
        $this->string($computer->fields['serial'])->isIdenticalTo('640HP72');

        //Reimport, should not create a new computer
        $this->doInventory($json);

        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps.teclib.infra-2018-10-03-08-42-36')
            ->string['itemtype']->isIdenticalTo('Computer');

        //check created computer - the same as before
        $this->integer($agent['items_id'])->isIdenticalTo($computers_id);
        $computers_id = $agent['items_id'];

        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        //check serial came from "MSN" node.
        $this->string($computer->fields['serial'])->isIdenticalTo('640HP72');
    }

    public function testLastBoot()
    {
        global $DB;
        $json_str = file_get_contents(self::INV_FIXTURES . 'computer_1.json');
        $json = json_decode($json_str);

        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['itemtype']->isIdenticalTo('Computer');

        //check created computer
        $computers_id = $agent['items_id'];

        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        //check last_boot came from "operatingsystem->boot_time" node.
        $this->string($computer->fields['last_boot'])->isIdenticalTo('2020-06-09 07:58:08');
    }

    public function testInventoryChangeStatusOrNotFromManualInjection()
    {
        //create states
        $state = new \State();
        $state_1_id = $state->add(
            [
                'name'      => 'Manual state',
                'states_id' => 0,
                "entities_id" => 0,
                'is_visible_computer' => 1
            ]
        );
        $this->integer($state_1_id)->isGreaterThan(0);

        $state_2_id = $state->add(
            [
                'name'      => 'Inventory state',
                'states_id' => 0,
                "entities_id" => 0,
                'is_visible_computer' => 1
            ]
        );
        $this->integer($state_2_id)->isGreaterThan(0);

        //create computer manually with specific states before do an automatic inventory
        $computer = new \Computer();
        $computer->add(
            [
                "name" => "glpixps",
                "serial" => "640HP72",
                "states_id" => $state_1_id,
                "entities_id" => 0
            ]
        );


        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        //per default, do not change states_id
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->boolean(
            $conf->saveConf([
                'states_id_default' => '-1'
            ])
        )->isTrue();
        $this->logout();

        $inventory = $this->doInventory($xml_source, true);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //load printer
        $computer->getFromDB($computers_id);
        $this->integer($computer->fields['states_id'])->isEqualTo($state_1_id);

        //restore default configuration
        $this->login();
        $this->boolean(
            $conf->saveConf([
                'states_id_default' => $state_2_id
            ])
        )->isTrue();
        $this->logOut();

        //inventory again
        $this->doInventory($xml_source, true);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //load printer
        $computer->getFromDB($computers_id);
        $this->integer($computer->fields['states_id'])->isEqualTo($state_2_id);
    }

    public function testInventoryChangeStatusOrNotFromAutomaticInventory()
    {
        //create states
        $state = new \State();
        $state_1_id = $state->add(
            [
                'name'      => 'Manual state',
                'states_id' => 0,
                "entities_id" => 0,
                'is_visible_computer' => 1
            ]
        );
        $this->integer($state_1_id)->isGreaterThan(0);



        $computer = new \Computer();
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        //per default, do not change states_id
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->boolean(
            $conf->saveConf([
                'states_id_default' => '-1'
            ])
        )->isTrue();
        $this->logout();

        $inventory = $this->doInventory($xml_source, true);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //load printer
        $computer->getFromDB($computers_id);
        $this->integer($computer->fields['states_id'])->isEqualTo(0);

        //restore default configuration
        $this->login();
        $this->boolean(
            $conf->saveConf([
                'states_id_default' => $state_1_id
            ])
        )->isTrue();
        $this->logOut();

        //inventory again
        $this->doInventory($xml_source, true);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //load printer
        $computer->getFromDB($computers_id);
        $this->integer($computer->fields['states_id'])->isEqualTo($state_1_id);
    }

    public function testInventoryDefaultEntity()
    {
        //first step : run an inventory with default entities_id = 0
        $computer = new \Computer();
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $inventory = $this->doInventory($xml_source, true);
        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //load computer and check entities_id (0 by default)
        $computer->getFromDB($computers_id);
        $this->integer($computer->fields['entities_id'])->isEqualTo(0);


        //per default, use entities_id 0
        //so change entities_id to 1
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->boolean(
            $conf->saveConf([
                'entities_id_default' => 1
            ])
        )->isTrue();
        $this->logout();

        //second step : run same inventory and check that entities_id not change
        $inventory = $this->doInventory($xml_source, true);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //load computer and check entities_id is always 0
        $computer->getFromDB($computers_id);
        $this->integer($computer->fields['entities_id'])->isEqualTo(0);

        //third step : run new inventory and check that entities_id is 1
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixpsxps</NAME>
            <UUID>5404A6A534C4-25C1BB60-5BCB-11D9-B18F</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>glpixpsxps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        //inventory again
        $inventory = $this->doInventory($xml_source, true);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);

        //load computer and check entities_id 1
        $computer->getFromDB($computers_id);
        $this->integer($computer->fields['entities_id'])->isEqualTo(1);
    }

    public function testTransferWithLockedField()
    {
        $computer = new \Computer();
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $inventory = $this->doInventory($xml_source, true);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);
        //load computer
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        //test entities_id
        $this->integer($computer->fields['entities_id'])->isEqualTo(0);

        //transer to another entity
        $doTransfer = \Entity::getUsedConfig('transfers_strategy', $computer->fields['entities_id'], 'transfers_id', 0);
        $transfer = new \Transfer();
        $transfer->getFromDB($doTransfer);

        //update tranfer model to enable locked field
        $transfer->fields["lock_updated_fields"] = 1;

        $item_to_transfer = ["Computer" => [$computers_id => $computers_id]];
        $transfer->moveItems($item_to_transfer, 1, $transfer->fields);

        //reload computer
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        //test entities_id
        $this->integer($computer->fields['entities_id'])->isEqualTo(1);

        //one lock for entities_id
        $lockedfield = new \Lockedfield();
        $locks = $lockedfield->find(['itemtype' => 'Computer', 'items_id' => $computers_id, "field" => "entities_id"]);
        $this->integer(count($locks))->isIdenticalTo(1);
    }

    public function testTransferWithoutLockedField()
    {
        $computer = new \Computer();
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>5BCB-25C1BB60B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $inventory = $this->doInventory($xml_source, true);

        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);
        //load computer
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        //test entities_id
        $this->integer($computer->fields['entities_id'])->isEqualTo(0);

        //transer to another entity
        $doTransfer = \Entity::getUsedConfig('transfers_strategy', $computer->fields['entities_id'], 'transfers_id', 0);
        $transfer = new \Transfer();
        $transfer->getFromDB($doTransfer);

        //update tranfer model to disable locked field
        $transfer->fields["lock_updated_fields"] = 0;

        $item_to_transfer = ["Computer" => [$computers_id => $computers_id]];
        $transfer->moveItems($item_to_transfer, 1, $transfer->fields);

        //reload computer
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        //test entities_id
        $this->integer($computer->fields['entities_id'])->isEqualTo(1);

        $lockedfield = new \Lockedfield();
        $locks = $lockedfield->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($locks))->isIdenticalTo(0);
    }

    public function testNetworkNames()
    {
        global $DB;
        $json_str = file_get_contents(self::INV_FIXTURES . 'computer_1.json');
        $json = json_decode($json_str);

        //add a new port
        $newport = new \stdClass();
        $newport->description = 'any name incorrect for netname';
        $newport->type = 'ethernet';
        $newport->ipaddress = '192.168.101.101';

        $json->content->networks[] = $newport;

        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['itemtype']->isIdenticalTo('Computer');

        //check created computer
        $computers_id = $agent['items_id'];

        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        //check last_boot came from "operatingsystem->boot_time" node.
        $this->string($computer->fields['last_boot'])->isIdenticalTo('2020-06-09 07:58:08');

        $it = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM' => \NetworkPort::getTable(),
            'WHERE' => [
                'itemtype' => 'Computer',
                'items_id' => $computers_id
            ]
        ]);
        $this->integer(count($it))->isIdenticalTo(6);

        foreach ($it as $port) {
            if ($port['name'] === 'virbr0-nic') {
                //no networkname for this one.
                continue;
            }

            $netname_it = $DB->request([
                'SELECT' => ['id', 'name'],
                'FROM' => \NetworkName::getTable(),
                'WHERE' => [
                    'itemtype' => 'NetworkPort',
                    'items_id' => $port['id']
                ]
            ]);

            $this->integer(count($netname_it))->isIdenticalTo(1);

            $netname = $netname_it->current();
            $this->array($netname)
                ->string['name']->isIdenticalTo(\Toolbox::slugify($port['name']));
        }
    }

    public function testNetworkCards()
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
      <IPADDRESS>172.28.211.63</IPADDRESS>
      <IPDHCP>172.28.200.22</IPDHCP>
      <IPGATEWAY>172.28.211.1</IPGATEWAY>
      <IPMASK>255.255.255.0</IPMASK>
      <IPSUBNET>172.28.211.0</IPSUBNET>
      <MACADDR>08:00:27:16:9C:60</MACADDR>
      <PCIID>8086:100E:001E:8086</PCIID>
      <SPEED>1000</SPEED>
      <STATUS>Up</STATUS>
      <VIRTUALDEV>0</VIRTUALDEV>
    </NETWORKS>
    <NETWORKS>
      <DESCRIPTION>Carte Intel(R) PRO/1000 MT pour station de travail</DESCRIPTION>
      <IPADDRESS6>fe80::68bf:3ee8:f221:588d</IPADDRESS6>
      <IPMASK6>ffff:ffff:ffff:ffff::</IPMASK6>
      <IPSUBNET6>fe80::</IPSUBNET6>
      <MACADDR>08:00:27:16:9C:60</MACADDR>
      <PCIID>8086:100E:001E:8086</PCIID>
      <SPEED>1000</SPEED>
      <STATUS>Up</STATUS>
      <VIRTUALDEV>0</VIRTUALDEV>
    </NETWORKS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>";

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $this->doInventory($json);

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

        //check created network cards
        $networkcards = $DB->request([
            'FROM' => \NetworkPort::getTable(),
            'WHERE' => [
                'itemtype' => \Computer::getType(),
                'items_id' => $computers_id
            ]
        ]);
        $this->integer(count($networkcards))->isIdenticalTo(1);

        $item_networkcard = $DB->request([
            'FROM' => \Item_DeviceNetworkCard::getTable(),
            'WHERE' => [
                'itemtype' => \Computer::getType(),
                'items_id' => $computers_id
            ]
        ]);
        $this->integer(count($item_networkcard))->isIdenticalTo(1);
    }

    public function testAssetTag()
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
      <ASSETTAG>other_serial</ASSETTAG>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>";

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $this->doInventory($json);

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

        $this->string($computer->fields['otherserial'])->isIdenticalTo('other_serial');
    }

    public function testTransferNotAllowed()
    {
        global $DB;
        $computer = new \Computer();
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $inventory = $this->doInventory($xml_source, true);

        //check created agent itemtype / deviceid / entities_id
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps.teclib.infra-2018-10-03-08-42-36')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->integer['entities_id']->isIdenticalTo(0); //root entity


        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);
        //load / test computer entities_id root
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        $this->integer($computer->fields['entities_id'])->isEqualTo(0);


        //transfer to another entity
        $doTransfer = \Entity::getUsedConfig('transfers_strategy', $computer->fields['entities_id'], 'transfers_id', 0);
        $transfer = new \Transfer();
        $transfer->getFromDB($doTransfer);

        $item_to_transfer = ["Computer" => [$computers_id => $computers_id]];
        $transfer->moveItems($item_to_transfer, 1, $transfer->fields);

        //reload / test computer entities_id
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        $this->integer($computer->fields['entities_id'])->isEqualTo(1); //another entity
        //reload / test agent entities_id
        $reloaded_agent = new \Agent();
        $reloaded_agent->getFromDB($agent['id']);
        $this->integer($reloaded_agent->fields['entities_id'])->isEqualTo(0); //always root (only pc is transfered)


        //prohibit the transfer from this entity
        $entity = new \Entity();
        $entity->getFromDB(1);
        $this->boolean($entity->update([
            "id" => $entity->fields['id'],
            "transfers_id" => 0, //no transfert
        ]))->isTrue();

        //redo inventory
        $inventory = $this->doInventory($xml_source, true);

        //reload / test computer entities_id
        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        $this->integer($computer->fields['entities_id'])->isEqualTo(1); //another entity
        //reload / test agent entities_id
        $reloaded_agent = new \Agent();
        $reloaded_agent->getFromDB($agent['id']);
        $this->integer($reloaded_agent->fields['entities_id'])->isEqualTo(1); //another entity
    }

    public function testTransferNotAllowedForSoftware()
    {
        global $DB;
        $computer = new \Computer();
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
          <SOFTWARES>
          <ARCH>i586</ARCH>
          <FROM>registry</FROM>
          <GUID>Office15.STANDARD</GUID>
          <INSTALLDATE>18/08/2020</INSTALLDATE>
          <NAME>Microsoft Office Standard 2013</NAME>
          <NO_REMOVE>0</NO_REMOVE>
          <PUBLISHER>Microsoft Corporation</PUBLISHER>
          <SYSTEM_CATEGORY>application</SYSTEM_CATEGORY>
          <UNINSTALL_STRING>&quot;C:\Program Files (x86)\Common Files\Microsoft Shared\OFFICE15\Office Setup Controller\setup.exe&quot; /uninstall STANDARD /dll OSETUP.DLL</UNINSTALL_STRING>
          <VERSION>15.0.4569.1506</VERSION>
        </SOFTWARES>
        </CONTENT>
        <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $inventory = $this->doInventory($xml_source, true);

        //check created agent itemtype / deviceid / entities_id
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps.teclib.infra-2018-10-03-08-42-36')
            ->string['itemtype']->isIdenticalTo('Computer')
            ->integer['entities_id']->isIdenticalTo(0); //root entity


        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);
        //load / test computer entities_id root
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        $this->integer($computer->fields['entities_id'])->isEqualTo(0);

        //load / test software entities_id root
        $item_software = new \Item_SoftwareVersion();
        $item_softwares = $item_software->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($item_softwares))->isIdenticalTo(1);
        $this->integer(reset($item_softwares)['entities_id'])->isEqualTo(0);
        //load softwareversion entities_id root
        $softwareversion = new \SoftwareVersion();
        $this->boolean($softwareversion->getFromDB(reset($item_softwares)['softwareversions_id']))->isTrue();
        $this->integer($softwareversion->fields['entities_id'])->isEqualTo(0);
        //load software entities_id root
        $software = new \Software();
        $this->boolean($software->getFromDB($softwareversion->fields['softwares_id']))->isTrue();
        $this->integer($software->fields['entities_id'])->isEqualTo(0);



        //transfer to another entity
        $transfer = new \Transfer();
        $transfer->getFromDB(1);

        $item_to_transfer = ["Computer" => [$computers_id => $computers_id]];
        $transfer->moveItems($item_to_transfer, 1, $transfer->fields);

        //reload / test computer entities_id sub
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        $this->integer($computer->fields['entities_id'])->isEqualTo(1); //another entity
        //reload / test agent entities_id
        $reloaded_agent = new \Agent();
        $reloaded_agent->getFromDB($agent['id']);
        $this->integer($reloaded_agent->fields['entities_id'])->isEqualTo(0); //always root (only pc is transfered)

        //reload / test software entities_id sub
        $item_software = new \Item_SoftwareVersion();
        $item_softwares = $item_software->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($item_softwares))->isIdenticalTo(1);
        $this->integer(reset($item_softwares)['entities_id'])->isEqualTo(1);
        //load sofware version entities_id sub
        $softwareversion = new \SoftwareVersion();
        $this->boolean($softwareversion->getFromDB(reset($item_softwares)['softwareversions_id']))->isTrue();
        $this->integer($softwareversion->fields['entities_id'])->isEqualTo(1);
        //load software entities_id sub
        $software = new \Software();
        $this->boolean($software->getFromDB($softwareversion->fields['softwares_id']))->isTrue();
        $this->integer($software->fields['entities_id'])->isEqualTo(1);

        //prohibit the transfer from this entity
        $entity = new \Entity();
        $entity->getFromDB(1);
        $this->boolean($entity->update([
            "id" => $entity->fields['id'],
            "transfers_strategy" => 0, //no transfert for computer
            "entities_strategy_software" => 0, //no transfert for software
        ]))->isTrue();

        //redo inventory
        $inventory = $this->doInventory($xml_source, true);

        //reload / test computer entities_id
        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        $this->integer($computer->fields['entities_id'])->isEqualTo(1); //another entity
        //reload / test agent entities_id
        $reloaded_agent = new \Agent();
        $reloaded_agent->getFromDB($agent['id']);
        $this->integer($reloaded_agent->fields['entities_id'])->isEqualTo(1); //another entity

        //reload / test software entities_id sub
        $item_software = new \Item_SoftwareVersion();
        $item_softwares = $item_software->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($item_softwares))->isIdenticalTo(1);
        $this->integer(reset($item_softwares)['entities_id'])->isEqualTo(1);
        //load sofware version entities_id sub
        $softwareversion = new \SoftwareVersion();
        $this->boolean($softwareversion->getFromDB(reset($item_softwares)['softwareversions_id']))->isTrue();
        $this->integer($softwareversion->fields['entities_id'])->isEqualTo(1);
        //load software entities_id sub
        $software = new \Software();
        $this->boolean($software->getFromDB($softwareversion->fields['softwares_id']))->isTrue();
        $this->integer($software->fields['entities_id'])->isEqualTo(1);
    }

    public function testWorkgroup()
    {
        global $DB;
        $json_str = file_get_contents(self::INV_FIXTURES . 'computer_1.json');
        $json = json_decode($json_str);

        //add workgroup
        $json->content->hardware->workgroup = "workgroup'ed";

        $this->doInventory($json);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps-2018-07-09-09-07-13')
            ->string['itemtype']->isIdenticalTo('Computer');

        //check created computer
        $computers_id = $agent['items_id'];

        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        //check domain has been created
        $domain = new \Domain();
        $this->boolean($domain->getFromDBByCrit(['name' => addslashes("workgroup'ed")]))->isTrue();

        //check relation has been created
        $domain_item = new \Domain_Item();
        $this->boolean($domain_item->getFromDBByCrit(['domains_id' => $domain->fields['id']]))->isTrue();

        $this->string($domain_item->fields['itemtype'])->isIdenticalTo('Computer');
        $this->integer($domain_item->fields['items_id'])->isIdenticalTo($computers_id);
        $this->integer($domain_item->fields['domainrelations_id'])->isIdenticalTo(\DomainRelation::BELONGS);

        $json_str = file_get_contents(self::INV_FIXTURES . 'computer_1.json');
        $json = json_decode($json_str);

        //add another workgroup
        $json->content->hardware->workgroup = "workgroup'ed another time";
        $this->doInventory($json);

        //check domain has been created
        $first_id = $domain->fields['id'];
        $domain = new \Domain();
        $this->boolean($domain->getFromDBByCrit(['name' => addslashes("workgroup'ed another time")]))->isTrue();

        //check relation has been created - and there is only one remaining
        $domain_item = new \Domain_Item();
        $this->boolean($domain_item->getFromDBByCrit(['domains_id' => $domain->fields['id']]))->isTrue();
        $this->boolean($domain_item->getFromDBByCrit(['domains_id' => $first_id]))->isTrue();
        $this->boolean($domain_item->getFromDBByCrit(['domains_id' => $first_id, 'is_deleted' => 1]))->isTrue();

        //check if one has been added non dynamic
        $ndyn_domain = new \Domain();
        $this->integer(
            $ndyn_domain->add([
                'name' => 'not dynamic',
            ])
        )->isGreaterThan(0);

        $ndyn_item = new \Domain_Item();
        $this->integer(
            $ndyn_item->add([
                'domains_id' => $ndyn_domain->fields['id'],
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'is_dynamic' => 0,
            ])
        )->isGreaterThan(0);

        $json_str = file_get_contents(self::INV_FIXTURES . 'computer_1.json');
        $json = json_decode($json_str);

        //add another workgroup
        $json->content->hardware->workgroup = "workgroup'ed another time";
        $this->doInventory($json);

        //check domain has been created
        $first_id = $domain->fields['id'];
        $domain = new \Domain();
        $this->boolean($domain->getFromDBByCrit(['name' => addslashes("workgroup'ed another time")]))->isTrue();

        //check relation is still present - and non dynamic one as well
        $domain_item = new \Domain_Item();
        $this->boolean($domain_item->getFromDBByCrit(['domains_id' => $domain->fields['id']]))->isTrue();
        $this->boolean($domain_item->getFromDBByCrit(['domains_id' => $ndyn_domain->fields['id']]))->isTrue();
    }

    public function testEntityGlobalLockedField()
    {
        $computer = new \Computer();
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>glpixps</NAME>
            <UUID>5BCB-25C1BB60B18F-5404A6A534C4</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>640HP72</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $lockedfield = new \Lockedfield();
        $this->integer($lockedfield->add(['itemtype' => 'Computer', 'items_id' => 0, 'field' => 'entities_id', 'is_global' => 1 ]))->isGreaterThan(0);

        $inventory = $this->doInventory($xml_source, true);
        $computers_id = $inventory->getItem()->fields['id'];
        $this->integer($computers_id)->isGreaterThan(0);
        //load computer
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        //test entities_id
        $this->integer($computer->fields['entities_id'])->isEqualTo(0);

        $lockedfield = new \Lockedfield();
        $locks = $lockedfield->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($locks))->isIdenticalTo(0);
    }
}
