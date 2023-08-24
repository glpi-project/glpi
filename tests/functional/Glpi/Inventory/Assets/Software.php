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

use SoftwareVersion;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/computer.class.php */

class Software extends AbstractInventoryAsset
{
    protected function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>GNU Image Manipulation Program</COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/09/2018</INSTALLDATE>
      <NAME>gimp</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>Application</SYSTEM_CATEGORY>
      <VERSION>2.8.22-7.fc28</VERSION>
    </SOFTWARES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"arch": "x86_64", "comments": "GNU Image Manipulation Program", "filesize": 67382735, "from": "rpm", "name": "gimp", "publisher": "Fedora Project", "system_category": "Application", "version": "2.8.22-7.fc28", "install_date": "2018-09-03", "manufacturers_id": "Fedora Project", "comment": "GNU Image Manipulation Program", "_system_category": "Application", "operatingsystems_id": 0, "entities_id": 0, "softwarecategories_id": "Application", "is_template_item": 0, "is_deleted_item": 0, "is_recursive": 0, "date_install": "2018-09-03"}'
            ],
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <OPERATINGSYSTEM>
      <ARCH>x86_64</ARCH>
      <BOOT_TIME>2017-02-20 08:11:53</BOOT_TIME>
      <FULL_NAME>Fedora release 25 (Twenty Five)</FULL_NAME>
      <HOSTID>007f0100</HOSTID>
      <KERNEL_NAME>linux</KERNEL_NAME>
      <KERNEL_VERSION>4.9.9-200.fc25.x86_64</KERNEL_VERSION>
      <NAME>Fedora</NAME>
      <VERSION>25</VERSION>
    </OPERATINGSYSTEM>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>GNU Image Manipulation Program</COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/09/2018</INSTALLDATE>
      <NAME>gimp</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>System Component</SYSTEM_CATEGORY>
      <VERSION>2.8.22-7.fc28</VERSION>
    </SOFTWARES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"arch": "x86_64", "comments": "GNU Image Manipulation Program", "filesize": 67382735, "from": "rpm", "name": "gimp", "publisher": "Fedora Project", "system_category": "System Component", "version": "2.8.22-7.fc28", "install_date": "2018-09-03", "manufacturers_id": "Fedora Project", "comment": "GNU Image Manipulation Program", "_system_category": "System Component", "operatingsystems_id": 0, "entities_id": 0, "softwarecategories_id": "System Component", "is_template_item": 0, "is_deleted_item": 0, "is_recursive": 0, "date_install": "2018-09-03"}'
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
        $asset = new \Glpi\Inventory\Asset\Software($computer, $json->content->softwares);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();

        //Manufacturer has been imported into db...
        $expected = json_decode($expected);

        $this->object($result[0])->isEqualTo($expected);
    }

    public function testHandle()
    {
        $this->login();
        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no software linked to this computer
        $sov = new \Item_SoftwareVersion();
        $this->boolean($sov->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A software version is already linked to computer!');

        //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Software($computer, $json->content->softwares);

        $extra_data = (array)$json->content;

        $asset->setExtraData($extra_data);
        $result = $asset->prepare();
        $expected = json_decode($expected['expected']);

        $this->object($result[0])->isEqualTo($expected);

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->boolean($sov->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('A software version has not been linked to computer!');

        $version = new \SoftwareVersion();
        $this->boolean($version->getFromDB($sov->fields['softwareversions_id']))->isTrue();
        $this->integer($version->fields['operatingsystems_id'])->isIdenticalTo(0); // no OS from $this->assetProvider()[0];

        //convert data
        $expected = $this->assetProvider()[1];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Software($computer, $json->content->softwares);
        $osasset = new \Glpi\Inventory\Asset\OperatingSystem($computer, (array)$json->content->operatingsystem);
        $osasset->prepare();
        //handle
        $osasset->handleLinks();
        $osasset->handle();

        $extra_data = (array)$json->content;
        $extra_data['\Glpi\Inventory\Asset\OperatingSystem'] = $osasset;

        $asset->setExtraData($extra_data);
        $result = $asset->prepare();
        $expected = json_decode($expected['expected']);
        $this->object($result[0])->isEqualTo($expected);

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->boolean($sov->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer', ['NOT' => ['date_install' => null]]]))
         ->isTrue('A software version has not been linked to computer!');

        $this->integer($sov->fields['softwareversions_id'])->isNotEqualTo($version->fields['id']);

        $ios = new \Item_OperatingSystem();
        $this->boolean($ios->getFromDBByCrit([
            "itemtype" => 'Computer',
            "items_id" => $computer->fields['id']
        ]))->isTrue();

        $version = new \SoftwareVersion();
        $this->boolean($version->getFromDB($sov->fields['softwareversions_id']))->isTrue();
        $this->integer($version->fields['operatingsystems_id'])->isEqualTo($ios->fields['operatingsystems_id']); //check linked OS from SoftwareVersion

        //new computer with same software
        global $DB;
        $soft_reference = $DB->request(\Software::getTable());
        $this->integer(count($soft_reference))->isIdenticalTo(5);

        $computer2 = getItemByTypeName('Computer', '_test_pc02');
       //first, check there are no software linked to this computer
        $this->boolean($sov->getFromDbByCrit(['items_id' => $computer2->fields['id'], 'itemtype' => 'Computer']))
         ->isFalse('A software version is already linked to computer!');

        $asset = new \Glpi\Inventory\Asset\Software($computer2, $json->content->softwares);
        $osasset = new \Glpi\Inventory\Asset\OperatingSystem($computer2, (array)$json->content->operatingsystem);
        $osasset->prepare();
        //handle
        $osasset->handleLinks();
        $osasset->handle();

        $extra_data = (array)$json->content;
        $extra_data['\Glpi\Inventory\Asset\OperatingSystem'] = $osasset;

        $asset->setExtraData($extra_data);
        $result = $asset->prepare();

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->boolean($sov->getFromDbByCrit(['items_id' => $computer2->fields['id'], 'itemtype' => 'Computer', ['NOT' => ['date_install' => null]]]))
         ->isTrue('A software version has not been linked to computer!');

        $this->integer(count($DB->request(\Software::getTable())))->isIdenticalTo(count($soft_reference));
    }

    public function testInventoryUpdate()
    {
        global $DB;
        $computer = new \Computer();
        $soft = new \Software();
        $version = new \SoftwareVersion();
        $item_version = new \Item_SoftwareVersion();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>GNU Image Manipulation Program</COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/10/2021</INSTALLDATE>
      <NAME>gimp</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>Application</SYSTEM_CATEGORY>
      <VERSION>2.10.28-1.fc34</VERSION>
    </SOFTWARES>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>Command-line interface for PHP</COMMENTS>
      <FILESIZE>25438052</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>25/11/2021</INSTALLDATE>
      <NAME>php-cli</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>Development/Languages</SYSTEM_CATEGORY>
      <VERSION>7.4.25-1.fc34</VERSION>
    </SOFTWARES>
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

        //software import behave differently: non-dynamic are not handled at all.
        //create manually a computer
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        //computer inventory knows only "gimp" and "php-cli" software
        $this->doInventory($xml_source, true);

       //we have 2 software & versions
        $softs = $soft->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($softs))->isIdenticalTo(2);
        $versions = $version->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($versions))->isIdenticalTo(2);

        //check that sofware exist with softwarecategories
        $criteria = [
            'FROM' => \Software::getTable(),
            'LEFT JOIN' => [
                \SoftwareCategory::getTable() => [
                    'ON' => [
                        \Software::getTable() => 'softwarecategories_id',
                        \SoftwareCategory::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                \Software::getTable() . ".name" => "gimp",
                \SoftwareCategory::getTable() . ".name" => "Application"
            ]
        ];

        $iterator = $DB->request($criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);

        $criteria = [
            'FROM' => \Software::getTable(),
            'LEFT JOIN' => [
                \SoftwareCategory::getTable() => [
                    'ON' => [
                        \Software::getTable() => 'softwarecategories_id',
                        \SoftwareCategory::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                \Software::getTable() . ".name" => "php-cli",
                \SoftwareCategory::getTable() . ".name" => "Development/Languages"
            ]
        ];

        $iterator = $DB->request($criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);

        //we have 2 softwareversion items linked to the computer
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($item_versions))->isIdenticalTo(2);

        //software present in the inventory source are dynamic
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($item_versions))->isIdenticalTo(2);

        //Redo inventory, but with removed "php-cli" software and change softwareCategories
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>GNU Image Manipulation Program</COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/10/2021</INSTALLDATE>
      <NAME>gimp</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>Web App</SYSTEM_CATEGORY>
      <VERSION>2.10.28-1.fc34</VERSION>
    </SOFTWARES>
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

        //we still have 2 software & versions
        $softs = $soft->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($softs))->isIdenticalTo(2);
        $versions = $version->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($versions))->isIdenticalTo(2);

        $categories_iterator = $DB->request(['FROM' => \SoftwareCategory::getTable()]);
        $this->integer(count($categories_iterator))->isIdenticalTo(4);

        //check that software still exist but with different softwarecategories
        $criteria = [
            'FROM' => \Software::getTable(),
            'LEFT JOIN' => [
                \SoftwareCategory::getTable() => [
                    'ON' => [
                        \Software::getTable() => 'softwarecategories_id',
                        \SoftwareCategory::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                \Software::getTable() . ".name" => "gimp",
                \SoftwareCategory::getTable() . ".name" => "Web App"
            ]
        ];
        $iterator = $DB->request($criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);

        //we now have 1 softwareversion items linked to the computer
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($item_versions))->isIdenticalTo(1);

        //software present in the inventory source is still dynamic
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($item_versions))->isIdenticalTo(1);
    }


    public function testSoftwareEntity()
    {
        $this->login();
        $entity = new \Entity();
        $entity_1_id = $entity->add([
            'name' => '_test_entity_1',
            'entities_id' => 0,
            'entities_id_software' => \Entity::CONFIG_PARENT,
            'tag' => 'testtag_1',
        ]);
        $entity_2_id = $entity->add([
            'name' => '_test_entity_2',
            'entities_id' => $entity_1_id,
            'entities_id_software' => \Entity::CONFIG_NEVER,
            'tag' => 'testtag_2',
        ]);

        $rule         = new \RuleImportEntity();
        $rulecriteria = new \RuleCriteria();
        $ruleaction   = new \RuleAction();

        $rules_id = $rule->add([
            'is_active'    => 1,
            'name'         => '_test_affect_entity_by_tag',
            'match'        => 'AND',
            'sub_type'     => \RuleImportEntity::class,
            'is_recursive' => 1,
            'ranking'      => 1,
        ]);
        $this->integer((int) $rules_id)->isGreaterThan(0);

        $this->integer((int) $rulecriteria->add([
            'rules_id'    => $rules_id,
            'criteria'  => "tag",
            'pattern'   => "/(.*)/",
            'condition' => \RuleImportEntity::REGEX_MATCH
        ]))->isGreaterThan(0);

        $this->integer((int) $ruleaction->add([
            'rules_id'    => $rules_id,
            'action_type' => 'regex_result',
            'field'       => '_affect_entity_by_tag',
            'value'       => 'testtag_2',
        ]))->isGreaterThan(0);

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS></COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/10/2021</INSTALLDATE>
      <NAME>test_software</NAME>
      <PUBLISHER>Publisher</PUBLISHER>
      <SYSTEM_CATEGORY>Unspecified</SYSTEM_CATEGORY>
      <VERSION>1.1</VERSION>
    </SOFTWARES>
    <HARDWARE>
      <NAME>pc_test_entity</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ssnexample</SSN>
    </BIOS>
    <VERSIONCLIENT>test-agent</VERSIONCLIENT>
    <ACCOUNTINFO>
      <KEYNAME>TAG</KEYNAME>
      <KEYVALUE>testtag_2</KEYVALUE>
    </ACCOUNTINFO>
  </CONTENT>
  <DEVICEID>pc_test_entity</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        $computer = new \Computer();
        $found_computers = $computer->find(['name' => "pc_test_entity"]);
        $this->integer(count($found_computers))->isIdenticalTo(1);
        $first_computer = array_pop($found_computers);
        $this->integer($first_computer['entities_id'])->isIdenticalTo($entity_2_id);

        $soft = new \Software();
        $softs = $soft->find(['name' => "test_software"]);
        $this->integer(count($softs))->isIdenticalTo(1);
        $first_soft = array_pop($softs);
        $this->integer($first_soft['entities_id'])->isIdenticalTo($entity_2_id);
        $this->integer($first_soft['is_recursive'])->isIdenticalTo(0);
    }

    public function testSoftwareWithSpecialChar()
    {
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <SOFTWARES>
            <ARCH>x86_64</ARCH>
            <COMMENTS>Soft with special langage</COMMENTS>
            <FILESIZE>67382735</FILESIZE>
            <FROM>rpm</FROM>
            <INSTALLDATE>03/09/2018</INSTALLDATE>
            <NAME>ភាសាខ្មែរ កញ្ចប់បទពិសោធន៍ផ្ទៃក្នុង</NAME>
            <PUBLISHER>Other</PUBLISHER>
            <SYSTEM_CATEGORY>Application</SYSTEM_CATEGORY>
            <VERSION>1.1</VERSION>
          </SOFTWARES>
          <HARDWARE>
            <NAME>pc_test_entity</NAME>
          </HARDWARE>
          <BIOS>
            <SSN>ssnexample</SSN>
          </BIOS>
          <VERSIONCLIENT>test-agent</VERSIONCLIENT>
          <ACCOUNTINFO>
            <KEYNAME>TAG</KEYNAME>
            <KEYVALUE>testtag_2</KEYVALUE>
          </ACCOUNTINFO>
        </CONTENT>
        <DEVICEID>pc_test_entity</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $this->doInventory($xml_source, true);

        $computer = new \Computer();
        $found_computers = $computer->find(['name' => "pc_test_entity"]);
        $this->integer(count($found_computers))->isIdenticalTo(1);
        $first_computer = array_pop($found_computers);

        $soft = new \Software();
        $softs = $soft->find(['name' => "ភាសាខ្មែរ កញ្ចប់បទពិសោធន៍ផ្ទៃក្នុង"]);
        $this->integer(count($softs))->isIdenticalTo(1);

        $computer_softversion = new \Item_SoftwareVersion();
        $computer_softversions = $computer_softversion->find([
            'itemtype' => "Computer",
            'items_id' => $first_computer['id']
        ]);
        $this->integer(count($computer_softversions))->isIdenticalTo(1);
        $first_computer_soft = array_pop($computer_softversions);

        $version = new SoftwareVersion();
        $this->boolean($version->getFromDB($first_computer_soft['softwareversions_id']))->isTrue();
        $this->string($version->fields['name'])->isEqualTo("1.1");

        //update software version
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <SOFTWARES>
            <ARCH>x86_64</ARCH>
            <COMMENTS>Soft with special langage</COMMENTS>
            <FILESIZE>67382735</FILESIZE>
            <FROM>rpm</FROM>
            <INSTALLDATE>03/09/2018</INSTALLDATE>
            <NAME>ភាសាខ្មែរ កញ្ចប់បទពិសោធន៍ផ្ទៃក្នុង</NAME>
            <PUBLISHER>Other</PUBLISHER>
            <SYSTEM_CATEGORY>Application</SYSTEM_CATEGORY>
            <VERSION>1.4</VERSION>
          </SOFTWARES>
          <HARDWARE>
            <NAME>pc_test_entity</NAME>
          </HARDWARE>
          <BIOS>
            <SSN>ssnexample</SSN>
          </BIOS>
          <VERSIONCLIENT>test-agent</VERSIONCLIENT>
          <ACCOUNTINFO>
            <KEYNAME>TAG</KEYNAME>
            <KEYVALUE>testtag_2</KEYVALUE>
          </ACCOUNTINFO>
        </CONTENT>
        <DEVICEID>pc_test_entity</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        $this->doInventory($xml_source, true);

        $computer = new \Computer();
        $found_computers = $computer->find(['name' => "pc_test_entity"]);
        $this->integer(count($found_computers))->isIdenticalTo(1);
        $first_computer = array_pop($found_computers);

        $soft = new \Software();
        $softs = $soft->find(['name' => "ភាសាខ្មែរ កញ្ចប់បទពិសោធន៍ផ្ទៃក្នុង"]);
        $this->integer(count($softs))->isIdenticalTo(1);

        $computer_softversion = new \Item_SoftwareVersion();
        $computer_softversions = $computer_softversion->find([
            'itemtype' => "Computer",
            'items_id' => $first_computer['id']
        ]);
        $this->integer(count($computer_softversions))->isIdenticalTo(1);
        $first_computer_soft = array_pop($computer_softversions);

        $version = new SoftwareVersion();
        $this->boolean($version->getFromDB($first_computer_soft['softwareversions_id']))->isTrue();
        $this->string($version->fields['name'])->isEqualTo("1.4");
    }

    public function testSoftwareRuledictionnaryManufacturer()
    {
        $this->login();

        $rule         = new \RuleDictionnaryManufacturer();
        $rulecriteria = new \RuleCriteria();
        $ruleaction   = new \RuleAction();

        $rules_id = $rule->add([
            'is_active'    => 1,
            'name'         => 'Microsoft',
            'match'        => 'AND',
            'sub_type'     => \RuleDictionnaryManufacturer::class,
            'is_recursive' => 1,
            'ranking'      => 1,
        ]);
        $this->integer((int) $rules_id)->isGreaterThan(0);

        $this->integer((int) $rulecriteria->add([
            'rules_id'    => $rules_id,
            'criteria'  => "name",
            'pattern'   => "Microsoft",
            'condition' => \Rule::PATTERN_CONTAIN
        ]))->isGreaterThan(0);

        $this->integer((int) $ruleaction->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'name',
            'value'       => 'Personal_Publisher',
        ]))->isGreaterThan(0);

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS></COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/10/2021</INSTALLDATE>
      <NAME>test_software</NAME>
      <PUBLISHER>Microsoft</PUBLISHER>
      <SYSTEM_CATEGORY>Unspecified</SYSTEM_CATEGORY>
      <VERSION>1.1</VERSION>
    </SOFTWARES>
    <HARDWARE>
      <NAME>pc_test_entity</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ssnexample</SSN>
    </BIOS>
    <VERSIONCLIENT>test-agent</VERSIONCLIENT>
    <ACCOUNTINFO>
      <KEYNAME>TAG</KEYNAME>
      <KEYVALUE>testtag_2</KEYVALUE>
    </ACCOUNTINFO>
  </CONTENT>
  <DEVICEID>pc_test_entity</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        $computer = new \Computer();
        $found_computers = $computer->find(['name' => "pc_test_entity"]);
        $this->integer(count($found_computers))->isIdenticalTo(1);

        $soft = new \Software();
        $softs = $soft->find(['name' => "test_software"]);
        $this->integer(count($softs))->isIdenticalTo(1);
        $first_soft = array_pop($softs);

        $manufacturer = new \Manufacturer();
        $manufacturer->getFromDB($first_soft['manufacturers_id']);

        $this->string($manufacturer->fields['name'])->isEqualTo('Personal_Publisher');
    }

    public function testSoftwareRuledictionnarySoftware()
    {
        $this->login();

        $rule         = new \RuleDictionnarySoftware();
        $rulecriteria = new \RuleCriteria();
        $ruleaction   = new \RuleAction();

        $rules_id = $rule->add([
            'is_active'    => 1,
            'name'         => 'Apple',
            'match'        => 'AND',
            'sub_type'     => \RuleDictionnarySoftware::class,
            'is_recursive' => 1,
            'ranking'      => 1,
        ]);
        $this->integer((int) $rules_id)->isGreaterThan(0);

        $this->integer((int) $rulecriteria->add([
            'rules_id'    => $rules_id,
            'criteria'  => "manufacturer",
            'pattern'   => "Apple",
            'condition' => \Rule::PATTERN_CONTAIN
        ]))->isGreaterThan(0);

        $this->integer((int) $ruleaction->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'manufacturer',
            'value'       => 'Other_Publisher',
        ]))->isGreaterThan(0);


        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS></COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/10/2021</INSTALLDATE>
      <NAME>test_software</NAME>
      <PUBLISHER>Apple</PUBLISHER>
      <SYSTEM_CATEGORY>Unspecified</SYSTEM_CATEGORY>
      <VERSION>1.1</VERSION>
    </SOFTWARES>
    <HARDWARE>
      <NAME>pc_test_entity</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ssnexample</SSN>
    </BIOS>
    <VERSIONCLIENT>test-agent</VERSIONCLIENT>
    <ACCOUNTINFO>
      <KEYNAME>TAG</KEYNAME>
      <KEYVALUE>testtag_2</KEYVALUE>
    </ACCOUNTINFO>
  </CONTENT>
  <DEVICEID>pc_test_entity</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        $computer = new \Computer();
        $found_computers = $computer->find(['name' => "pc_test_entity"]);
        $this->integer(count($found_computers))->isIdenticalTo(1);

        $soft = new \Software();
        $softs = $soft->find(['name' => "test_software"]);
        $this->integer(count($softs))->isIdenticalTo(1);
        $first_soft = array_pop($softs);

        $manufacturer = new \Manufacturer();
        $manufacturer->getFromDB($first_soft['manufacturers_id']);

        $this->string($manufacturer->fields['name'])->isEqualTo('Other_Publisher');
    }

    protected function softwareProvider(): array
    {
        return [
            //To test FullCompareKey (with special chars on software name / manufacturer)
            '/tests/fixtures/inventories/softwares/01-test_software_with_special_chars_with_version.json',
            '/tests/fixtures/inventories/softwares/02-test_software_with_special_chars_with_version.json',
            //To test FullCompareKey without version (with special chars on software name / manufacturer)
            '/tests/fixtures/inventories/softwares/03-test_software_with_special_chars_and_without_version.json',
            // /To test FullCompareKey with version (with special chars on software name / manufacturer name / OS name / arch name)
            '/tests/fixtures/inventories/softwares/04-test_software_with_special_chars_and_with_version_and_os.json',
            // /To test FullCompareKey without version (with special chars on software name / manufacturer name / OS name / arch name)
            '/tests/fixtures/inventories/softwares/05-test_software_with_special_chars_and_without_version_and_os.json',
        ];
    }

    /**
     * @dataProvider softwareProvider
     */
    public function testSoftwareWithHtmlentites($path)
    {

        $json_source = json_decode(file_get_contents(GLPI_ROOT . $path));
        $this->doInventory($json_source);

        $computer = new \Computer();
        $found_computers = $computer->find(['name' => "pc_test"]);
        $this->integer(count($found_computers))->isIdenticalTo(1);
        $first_computer = array_pop($found_computers);

        //get Software / ItemSoftware
        $software = new \Software();
        $software_version = new \SoftwareVersion();
        $software_item = new \Item_SoftwareVersion();

        $software_items = $software_item->find(['itemtype' => "Computer", "items_id" => $first_computer['id']]);
        $this->integer(count($software_items))->isIdenticalTo(1);
        $first_software_items = array_pop($software_items);

        $software_versions = $software_version->find(['id' => $first_software_items['softwareversions_id']]);
        $this->integer(count($software_versions))->isIdenticalTo(1);
        $first_software_versions = array_pop($software_versions);

        $softwares = $software->find(['id' => $first_software_versions['softwares_id']]);
        $this->integer(count($softwares))->isIdenticalTo(1);
        $first_software = array_pop($softwares);


        //redo an inventory
        $json_source = json_decode(file_get_contents(GLPI_ROOT . $path));
        $this->doInventory($json_source);

        $computer = new \Computer();
        $found_computers = $computer->find(['name' => "pc_test"]);
        $this->integer(count($found_computers))->isIdenticalTo(1);
        $first_computer = array_pop($found_computers);


        $software_items = $software_item->find(['itemtype' => "Computer", "items_id" => $first_computer['id']]);
        $this->integer(count($software_items))->isIdenticalTo(1);
        $second_software_items = array_pop($software_items);

        $software_versions = $software_version->find(['id' => $second_software_items['softwareversions_id']]);
        $this->integer(count($software_versions))->isIdenticalTo(1);
        $second_software_versions = array_pop($software_versions);

        $softwares = $software->find(['id' => $second_software_versions['softwares_id']]);
        $this->integer(count($softwares))->isIdenticalTo(1);
        $second_software = array_pop($softwares);

        $this->integer($second_software_items['id'])->isIdenticalTo($first_software_items['id']);
        $this->integer($second_software_versions['id'])->isIdenticalTo($first_software_versions['id']);
        $this->integer($second_software['id'])->isIdenticalTo($first_software['id']);

        $computer->deleteByCriteria(['id' => $first_computer['id']]);
    }

    public function testDuplicatedSoft()
    {
        global $DB;
        $this->login();

        $computer = new \Computer();
        $soft = new \Software();
        $version = new \SoftwareVersion();
        $item_version = new \Item_SoftwareVersion();

        //inventory with a software name containing an unbreakable space
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>i586</ARCH>
      <FROM>registry</FROM>
      <GUID>Office15.STANDARD</GUID>
      <INSTALLDATE>18/08/2020</INSTALLDATE>
      <NAME>Microsoft Office Standard 2013</NAME>
      <NO_REMOVE>0</NO_REMOVE>
      <PUBLISHER>Microsoft Corporation</PUBLISHER>
      <SYSTEM_CATEGORY>application</SYSTEM_CATEGORY>
      <UNINSTALL_STRING>&quot;C:\Program Files (x86)\Common Files\Microsoft Shared\OFFICE15\Office Setup Controller\setup.exe&quot; /uninstall STANDARD /dll OSETUP.DLL</UNINSTALL_STRING>
      <VERSION>15.0.4569.1506</VERSION>
    </SOFTWARES>
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

        //create manually a computer
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $this->doInventory($xml_source, true);

        $manufacturer = new \Manufacturer();
        $this->boolean($manufacturer->getFromDBByCrit(['name' => 'Microsoft Corporation']))->isTrue();

        //we have 1 software & versions for Microsoft Office Standard - Microsoft Corporation
        $softs = $soft->find(['name' => 'Microsoft Office Standard 2013', 'manufacturers_id' => $manufacturer->fields['id']]);
        $this->integer(count($softs))->isIdenticalTo(1);

        //15.0.4569.1506
        $versions = $version->find(['name' => '15.0.4569.1506']);
        $this->integer(count($versions))->isIdenticalTo(1);

        $version_data = array_pop($versions);
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id']
        ]))->isTrue();

        //inventory with two same software: one with name containing an unbreakable space, the other with standard space
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
            <SOFTWARES>
              <ARCH>i586</ARCH>
              <FROM>registry</FROM>
              <GUID>Office15.STANDARD</GUID>
              <INSTALLDATE>18/08/2020</INSTALLDATE>
              <NAME>Microsoft Office Standard 2013</NAME>
              <NO_REMOVE>0</NO_REMOVE>
              <PUBLISHER>Microsoft Corporation</PUBLISHER>
              <SYSTEM_CATEGORY>application</SYSTEM_CATEGORY>
              <UNINSTALL_STRING>&quot;C:\Program Files (x86)\Common Files\Microsoft Shared\OFFICE15\Office Setup Controller\setup.exe&quot; /uninstall STANDARD /dll OSETUP.DLL</UNINSTALL_STRING>
              <VERSION>15.0.4569.1506</VERSION>
            </SOFTWARES>
            <SOFTWARES>
              <ARCH>i586</ARCH>
              <FROM>registry</FROM>
              <GUID>{90150000-0012-0000-0000-0000000FF1CE}</GUID>
              <INSTALLDATE>11/01/2023</INSTALLDATE>
              <NAME>Microsoft Office Standard 2013</NAME>
              <PUBLISHER>Microsoft Corporation</PUBLISHER>
              <SYSTEM_CATEGORY>system_component</SYSTEM_CATEGORY>
              <UNINSTALL_STRING>MsiExec.exe /X{90150000-0012-0000-0000-0000000FF1CE}</UNINSTALL_STRING>
              <VERSION>15.0.4569.1506</VERSION>
            </SOFTWARES>
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
    }

    public function testSameSoft()
    {
        global $DB;
        $this->login();

        $computer = new \Computer();
        $soft = new \Software();
        $version = new \SoftwareVersion();
        $item_version = new \Item_SoftwareVersion();

        //inventory with a software name containing an unbreakable space
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>i586</ARCH>
      <COMMENTS>A SmartNET contract is required for support - Cisco AnyConnect Secure Mobility Client.</COMMENTS>
      <FROM>registry</FROM>
      <GUID>{F4BACC43-70D3-4CCF-A0C6-89512F64CBB4}</GUID>
      <HELPLINK>http://www.cisco.com/TAC/</HELPLINK>
      <INSTALLDATE>12/01/2022</INSTALLDATE>
      <NAME>Cisco AnyConnect Secure Mobility Client</NAME>
      <PUBLISHER>Cisco Systems, Inc.</PUBLISHER>
      <SYSTEM_CATEGORY>system_component</SYSTEM_CATEGORY>
      <UNINSTALL_STRING>MsiExec.exe /X{F4BACC43-70D3-4CCF-A0C6-89512F64CBB4}</UNINSTALL_STRING>
      <URL_INFO_ABOUT>http://www.cisco.com</URL_INFO_ABOUT>
      <VERSION>4.10.01075</VERSION>
    </SOFTWARES>
    <SOFTWARES>
      <ARCH>i586</ARCH>
      <COMMENTS>A SmartNET contract is required for support - Cisco AnyConnect Secure Mobility Client.</COMMENTS>
      <FROM>registry</FROM>
      <GUID>Cisco AnyConnect Secure Mobility Client</GUID>
      <HELPLINK>http://www.cisco.com/TAC/</HELPLINK>
      <INSTALLDATE>12/01/2022</INSTALLDATE>
      <NAME>Cisco AnyConnect Secure Mobility Client </NAME>
      <PUBLISHER>Cisco Systems, Inc.</PUBLISHER>
      <SYSTEM_CATEGORY>application</SYSTEM_CATEGORY>
      <UNINSTALL_STRING>C:\Program Files (x86)\Cisco\Cisco AnyConnect Secure Mobility Client\Uninstall.exe -remove</UNINSTALL_STRING>
      <URL_INFO_ABOUT>http://www.cisco.com</URL_INFO_ABOUT>
      <VERSION>4.10.01075</VERSION>
    </SOFTWARES>
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

        //create manually a computer
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $this->doInventory($xml_source, true);

        //we have 1 software & versions for Cisco AnyConnect Secure Mobility Client
        $softs = $soft->find(['name' => 'Cisco AnyConnect Secure Mobility Client']);
        $this->integer(count($softs))->isIdenticalTo(1);

        //4.10.01075
        $versions = $version->find(['name' => '4.10.01075']);
        $this->integer(count($versions))->isIdenticalTo(1);

        $version_data = array_pop($versions);
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id']
        ]))->isTrue();

        //inventory with Cisco soft updated
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>i586</ARCH>
      <COMMENTS>A SmartNET contract is required for support - Cisco AnyConnect Secure Mobility Client.</COMMENTS>
      <FROM>registry</FROM>
      <GUID>{F4BACC43-70D3-4CCF-A0C6-89512F64CBB4}</GUID>
      <HELPLINK>http://www.cisco.com/TAC/</HELPLINK>
      <INSTALLDATE>14/02/2023</INSTALLDATE>
      <NAME>Cisco AnyConnect Secure Mobility Client</NAME>
      <PUBLISHER>Cisco Systems, Inc.</PUBLISHER>
      <SYSTEM_CATEGORY>system_component</SYSTEM_CATEGORY>
      <UNINSTALL_STRING>MsiExec.exe /X{F4BACC43-70D3-4CCF-A0C6-89512F64CBB4}</UNINSTALL_STRING>
      <URL_INFO_ABOUT>http://www.cisco.com</URL_INFO_ABOUT>
      <VERSION>4.10.06079</VERSION>
    </SOFTWARES>
    <SOFTWARES>
      <ARCH>i586</ARCH>
      <COMMENTS>A SmartNET contract is required for support - Cisco AnyConnect Secure Mobility Client.</COMMENTS>
      <FROM>registry</FROM>
      <GUID>Cisco AnyConnect Secure Mobility Client</GUID>
      <HELPLINK>http://www.cisco.com/TAC/</HELPLINK>
      <INSTALLDATE>14/02/2023</INSTALLDATE>
      <NAME>Cisco AnyConnect Secure Mobility Client </NAME>
      <PUBLISHER>Cisco Systems, Inc.</PUBLISHER>
      <SYSTEM_CATEGORY>application</SYSTEM_CATEGORY>
      <UNINSTALL_STRING>C:\Program Files (x86)\Cisco\Cisco AnyConnect Secure Mobility Client\Uninstall.exe -remove</UNINSTALL_STRING>
      <URL_INFO_ABOUT>http://www.cisco.com</URL_INFO_ABOUT>
      <VERSION>4.10.06079</VERSION>
    </SOFTWARES>
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

        //we have 1 software & versions for Cisco AnyConnect Secure Mobility Client
        $softs = $soft->find(['name' => 'Cisco AnyConnect Secure Mobility Client']);
        $this->integer(count($softs))->isIdenticalTo(1);

        //4.10.06079
        $versions = $version->find(['name' => '4.10.06079']);
        $this->integer(count($versions))->isIdenticalTo(1);

        $version_data = array_pop($versions);
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id']
        ]))->isTrue();
    }

    public function testSameSoftManufacturer()
    {
        global $DB;
        $this->login();

        $computer = new \Computer();
        $soft = new \Software();
        $version = new \SoftwareVersion();
        $item_version = new \Item_SoftwareVersion();

        //inventory with a software manufacturer name containing an unbreakable space
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <NAME>ÀVEVA Application Server 2020</NAME>
      <VERSION>20.0.000</VERSION>
      <PUBLISHER>﻿ÀVEVA Software, LLC</PUBLISHER>
    </SOFTWARES>
    <SOFTWARES>
      <NAME>ÀVEVA Application Server 2020</NAME>
      <VERSION>20.0.000</VERSION>
      <PUBLISHER>ÀVEVA Software, LLC</PUBLISHER>
      <INSTALLDATE>03/12/2020</INSTALLDATE>
    </SOFTWARES>
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

        //create manually a computer
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $this->doInventory($xml_source, true);

        //we have 1 software & versions for ÀVEVA Application Server 2020
        $softs = $soft->find(['name' => 'ÀVEVA Application Server 2020']);
        $this->integer(count($softs))->isIdenticalTo(1);

        //20.0.000
        $versions = $version->find(['name' => '20.0.000']);
        $this->integer(count($versions))->isIdenticalTo(1);

        $version_data = array_pop($versions);
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id']
        ]))->isTrue();

        //same inventory again
        $this->doInventory($xml_source, true);

        //we have 1 software & versions for ÀVEVA Application Server 2020
        $softs = $soft->find(['name' => 'ÀVEVA Application Server 2020']);
        $this->integer(count($softs))->isIdenticalTo(1);

        //20.0.000
        $versions = $version->find(['name' => '20.0.000']);
        $this->integer(count($versions))->isIdenticalTo(1);

        $version_data = array_pop($versions);
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id']
        ]))->isTrue();
    }

    public function testSameSoftManufacturer2()
    {
        global $DB;
        $this->login();

        $computer = new \Computer();
        $soft = new \Software();
        $version = new \SoftwareVersion();
        $item_version = new \Item_SoftwareVersion();

        //inventory with a software manufacturer name containing an unbreakable space
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <NAME>ÀVEVA Application Server 2020</NAME>
      <VERSION>20.0.000</VERSION>
      <PUBLISHER>TEST ﻿ÀVEVA Software, LLC</PUBLISHER>
    </SOFTWARES>
    <SOFTWARES>
      <NAME>ÀVEVA Application Server 2020</NAME>
      <VERSION>20.0.000</VERSION>
      <PUBLISHER>TEST ÀVEVA Software, LLC</PUBLISHER>
      <INSTALLDATE>03/12/2020</INSTALLDATE>
    </SOFTWARES>
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

        //create manually a computer
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $this->doInventory($xml_source, true);

        //we have 1 software & versions for ÀVEVA Application Server 2020
        $softs = $soft->find(['name' => 'ÀVEVA Application Server 2020']);
        $this->integer(count($softs))->isIdenticalTo(1);

        //20.0.000
        $versions = $version->find(['name' => '20.0.000']);
        $this->integer(count($versions))->isIdenticalTo(1);

        $version_data = array_pop($versions);
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id']
        ]))->isTrue();

        //same inventory again
        $this->doInventory($xml_source, true);

        //we have 1 software & versions for ÀVEVA Application Server 2020
        $softs = $soft->find(['name' => 'ÀVEVA Application Server 2020']);
        $this->integer(count($softs))->isIdenticalTo(1);

        //20.0.000
        $versions = $version->find(['name' => '20.0.000']);
        $this->integer(count($versions))->isIdenticalTo(1);

        $version_data = array_pop($versions);
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id']
        ]))->isTrue();
    }

    public function testManufacturerDifferentCase()
    {
        $computer = new \Computer();
        $soft = new \Software();
        $version = new \SoftwareVersion();
        $item_version = new \Item_SoftwareVersion();

        //inventory with a software manufacturer name with different cases
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <NAME>Realtek PCI-E Wireless LAN Driver</NAME>
      <VERSION>Drv_3.00.0015</VERSION>
      <PUBLISHER>REALTEK Semiconductor Corp.</PUBLISHER>
    </SOFTWARES>
    <SOFTWARES>
      <NAME>Realtek Card Reader</NAME>
      <VERSION>10.0.17134.31242</VERSION>
      <PUBLISHER>Realtek Semiconductor Corp.</PUBLISHER>
    </SOFTWARES>
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

        //create manually a computer
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $this->doInventory($xml_source, true);

        //check software has been created
        $this->boolean(
            $soft->getFromDBByCrit(['name' => 'Realtek Card Reader'])
        )->isTrue();
        $softwares_id = $soft->fields['id'];

        //check version has been created
        $this->boolean(
            $version->getFromDBByCrit(['name' => '10.0.17134.31242'])
        )->isTrue();
        $this->integer($version->fields['softwares_id'])->isIdenticalTo($softwares_id);
        $versions_id = $version->fields['id'];

        //check computer-softwareverison relation has been created
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id
        ]))->isTrue();
        $item_versions_id = $item_version->fields['id'];

        //import again
        $this->doInventory($xml_source, true);

        //check software is the same
        $this->boolean(
            $soft->getFromDBByCrit(['name' => 'Realtek Card Reader'])
        )->isTrue();
        $this->integer($softwares_id)->isIdenticalTo($soft->fields['id']);

        //check version is the same
        $this->boolean(
            $version->getFromDBByCrit(['name' => '10.0.17134.31242'])
        )->isTrue();
        $this->integer($versions_id)->isIdenticalTo($version->fields['id']);

        //check computer-softwareverison relation is the same
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id
        ]))->isTrue();
        $this->integer($item_versions_id)->isIdenticalTo($item_version->fields['id']);
    }

    public function testSoftDifferentCase()
    {
        $computer = new \Computer();
        $soft = new \Software();
        $version = new \SoftwareVersion();
        $item_version = new \Item_SoftwareVersion();

        //inventory with a software manufacturer name with different cases
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <NAME>texlive-HA-prosper</NAME>
      <VERSION>4.21</VERSION>
    </SOFTWARES>
    <SOFTWARES>
      <NAME>texlive-ha-prosper</NAME>
      <VERSION>4.21</VERSION>
    </SOFTWARES>
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

        //create manually a computer
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $this->doInventory($xml_source, true);

        //check software has been created
        $this->boolean(
            $soft->getFromDBByCrit(['name' => 'texlive-ha-prosper'])
        )->isTrue();
        $softwares_id = $soft->fields['id'];

        //check version has been created
        $this->boolean(
            $version->getFromDBByCrit(['name' => '4.21'])
        )->isTrue();
        $this->integer($version->fields['softwares_id'])->isIdenticalTo($softwares_id);
        $versions_id = $version->fields['id'];

        //check computer-softwareverison relation has been created
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id
        ]))->isTrue();
        $item_versions_id = $item_version->fields['id'];

        //import again
        $this->doInventory($xml_source, true);

        //check software is the same
        $this->boolean(
            $soft->getFromDBByCrit(['name' => 'texlive-ha-prosper'])
        )->isTrue();
        $this->integer($softwares_id)->isIdenticalTo($soft->fields['id']);

        //check version is the same
        $this->boolean(
            $version->getFromDBByCrit(['name' => '4.21'])
        )->isTrue();
        $this->integer($versions_id)->isIdenticalTo($version->fields['id']);

        //check computer-softwareverison relation is the same
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id
        ]))->isTrue();
        $this->integer($item_versions_id)->isIdenticalTo($item_version->fields['id']);
    }

    public function testManufacturerSpecialCharacters()
    {
        $computer = new \Computer();
        $soft = new \Software();
        $version = new \SoftwareVersion();
        $item_version = new \Item_SoftwareVersion();

        //inventory with a software manufacturer with a special character
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
      <SOFTWARES>
      <NAME>Any other software</NAME>
      <VERSION>2.0.0</VERSION>
      <PUBLISHER>Manufacture</PUBLISHER>
    </SOFTWARES>
    <SOFTWARES>
      <NAME>Any software</NAME>
      <VERSION>1.0.0</VERSION>
      <PUBLISHER>Manufacturé</PUBLISHER>
    </SOFTWARES>
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

        //create manually a computer
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $this->doInventory($xml_source, true);

        //check software has been created
        $this->boolean(
            $soft->getFromDBByCrit(['name' => 'Any software'])
        )->isTrue();
        $softwares_id = $soft->fields['id'];

        //check version has been created
        $this->boolean(
            $version->getFromDBByCrit(['name' => '1.0.0'])
        )->isTrue();
        $this->integer($version->fields['softwares_id'])->isIdenticalTo($softwares_id);
        $versions_id = $version->fields['id'];

        //check computer-softwareverison relation has been created
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id
        ]))->isTrue();
        $item_versions_id = $item_version->fields['id'];

        //import again
        $this->doInventory($xml_source, true);

        //check software is the same
        $this->boolean(
            $soft->getFromDBByCrit(['name' => 'Any software'])
        )->isTrue();
        $this->integer($softwares_id)->isIdenticalTo($soft->fields['id']);

        //check version is the same
        $this->boolean(
            $version->getFromDBByCrit(['name' => '1.0.0'])
        )->isTrue();
        $this->integer($versions_id)->isIdenticalTo($version->fields['id']);

        //check computer-softwareverison relation is the same
        $this->boolean($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id
        ]))->isTrue();
        $this->integer($item_versions_id)->isIdenticalTo($item_version->fields['id']);
    }
}
