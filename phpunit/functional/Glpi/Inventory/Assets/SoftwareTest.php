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

use Glpi\Inventory\Converter;
use PHPUnit\Framework\Attributes\DataProvider;
use SoftwareVersion;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/computer.class.php */

class SoftwareTest extends AbstractInventoryAsset
{
    public static function assetProvider(): array
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
                'expected'  => '{"arch": "x86_64", "comments": "GNU Image Manipulation Program", "filesize": 67382735, "from": "rpm", "name": "gimp", "publisher": "Fedora Project", "system_category": "Application", "version": "2.8.22-7.fc28", "install_date": "2018-09-03", "manufacturers_id": "Fedora Project", "comment": "GNU Image Manipulation Program", "_system_category": "Application", "operatingsystems_id": 0, "entities_id": 0, "softwarecategories_id": "Application", "is_template_item": 0, "is_deleted_item": 0, "is_recursive": 0, "date_install": "2018-09-03"}',
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
                'expected'  => '{"arch": "x86_64", "comments": "GNU Image Manipulation Program", "filesize": 67382735, "from": "rpm", "name": "gimp", "publisher": "Fedora Project", "system_category": "System Component", "version": "2.8.22-7.fc28", "install_date": "2018-09-03", "manufacturers_id": "Fedora Project", "comment": "GNU Image Manipulation Program", "_system_category": "System Component", "operatingsystems_id": 0, "entities_id": 0, "softwarecategories_id": "System Component", "is_template_item": 0, "is_deleted_item": 0, "is_recursive": 0, "date_install": "2018-09-03"}',
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
        $asset = new \Glpi\Inventory\Asset\Software($computer, $json->content->softwares);
        $asset->setExtraData((array) $json->content);
        $result = $asset->prepare();

        //Manufacturer has been imported into db...
        $expected = json_decode($expected);

        $this->assertEquals($expected, $result[0]);
    }

    public function testHandle()
    {
        $this->login();
        $computer = getItemByTypeName('Computer', '_test_pc01');

        //first, check there are no software linked to this computer
        $sov = new \Item_SoftwareVersion();
        $this->assertFalse(
            $sov->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'A software version is already linked to computer!'
        );

        //convert data
        $expected = $this::assetProvider()[0];

        $converter = new Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Software($computer, $json->content->softwares);

        $extra_data = (array) $json->content;

        $asset->setExtraData($extra_data);
        $result = $asset->prepare();
        $expected = json_decode($expected['expected']);

        $this->assertEquals($expected, $result[0]);

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $sov->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']),
            'A software version has not been linked to computer!'
        );

        $version = new SoftwareVersion();
        $this->assertTrue($version->getFromDB($sov->fields['softwareversions_id']));
        $this->assertSame(0, $version->fields['operatingsystems_id']); // no OS from $this::assetProvider()[0];

        //convert data
        $expected = $this::assetProvider()[1];

        $converter = new Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Software($computer, $json->content->softwares);
        $osasset = new \Glpi\Inventory\Asset\OperatingSystem($computer, (array) $json->content->operatingsystem);
        $osasset->prepare();
        //handle
        $osasset->handleLinks();
        $osasset->handle();

        $extra_data = (array) $json->content;
        $extra_data[\Glpi\Inventory\Asset\OperatingSystem::class] = $osasset;

        $asset->setExtraData($extra_data);
        $result = $asset->prepare();
        $expected = json_decode($expected['expected']);
        $this->assertEquals($expected, $result[0]);

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $sov->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer', ['NOT' => ['date_install' => null]]]),
            'A software version has not been linked to computer!'
        );

        $this->assertNotEquals($version->fields['id'], $sov->fields['softwareversions_id']);

        $ios = new \Item_OperatingSystem();
        $this->assertTrue($ios->getFromDBByCrit([
            "itemtype" => 'Computer',
            "items_id" => $computer->fields['id'],
        ]));

        $version = new SoftwareVersion();
        $this->assertTrue($version->getFromDB($sov->fields['softwareversions_id']));
        $this->assertSame($ios->fields['operatingsystems_id'], $version->fields['operatingsystems_id']); //check linked OS from SoftwareVersion

        //new computer with same software
        global $DB;
        $soft_reference = $DB->request(['FROM' => \Software::getTable()]);
        $this->assertCount(5, $soft_reference);

        $computer2 = getItemByTypeName('Computer', '_test_pc02');
        //first, check there are no software linked to this computer
        $this->assertFalse(
            $sov->getFromDbByCrit(['items_id' => $computer2->fields['id'], 'itemtype' => 'Computer']),
            'A software version is already linked to computer!'
        );

        $asset = new \Glpi\Inventory\Asset\Software($computer2, $json->content->softwares);
        $osasset = new \Glpi\Inventory\Asset\OperatingSystem($computer2, (array) $json->content->operatingsystem);
        $osasset->prepare();
        //handle
        $osasset->handleLinks();
        $osasset->handle();

        $extra_data = (array) $json->content;
        $extra_data[\Glpi\Inventory\Asset\OperatingSystem::class] = $osasset;

        $asset->setExtraData($extra_data);
        $result = $asset->prepare();

        //handle
        $asset->handleLinks();
        $asset->handle();
        $this->assertTrue(
            $sov->getFromDbByCrit(['items_id' => $computer2->fields['id'], 'itemtype' => 'Computer', ['NOT' => ['date_install' => null]]]),
            'A software version has not been linked to computer!'
        );

        $this->assertCount(count($soft_reference), $DB->request(['FROM' => \Software::getTable()]));
    }

    public function testInventoryUpdate()
    {
        global $DB;
        $computer = new \Computer();
        $soft = new \Software();
        $version = new SoftwareVersion();
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
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        //computer inventory knows only "gimp" and "php-cli" software
        $this->doInventory($xml_source, true);

        //we have 2 software & versions
        $softs = $soft->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(2, $softs);
        $versions = $version->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(2, $versions);

        //check that sofware exist with softwarecategories
        $criteria = [
            'FROM' => \Software::getTable(),
            'LEFT JOIN' => [
                \SoftwareCategory::getTable() => [
                    'ON' => [
                        \Software::getTable() => 'softwarecategories_id',
                        \SoftwareCategory::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                \Software::getTable() . ".name" => "gimp",
                \SoftwareCategory::getTable() . ".name" => "Application",
            ],
        ];

        $iterator = $DB->request($criteria);
        $this->assertCount(1, $iterator);

        $criteria = [
            'FROM' => \Software::getTable(),
            'LEFT JOIN' => [
                \SoftwareCategory::getTable() => [
                    'ON' => [
                        \Software::getTable() => 'softwarecategories_id',
                        \SoftwareCategory::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                \Software::getTable() . ".name" => "php-cli",
                \SoftwareCategory::getTable() . ".name" => "Development/Languages",
            ],
        ];

        $iterator = $DB->request($criteria);
        $this->assertCount(1, $iterator);

        //we have 2 softwareversion items linked to the computer
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(2, $item_versions);

        //software present in the inventory source are dynamic
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(2, $item_versions);

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
        $this->assertCount(2, $softs);
        $versions = $version->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->assertCount(2, $versions);

        $categories_iterator = $DB->request(['FROM' => \SoftwareCategory::getTable()]);
        $this->assertCount(4, $categories_iterator);

        //we now have 1 softwareversion items linked to the computer
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->assertCount(1, $item_versions);

        //software present in the inventory source is still dynamic
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->assertCount(1, $item_versions);
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
        $this->assertGreaterThan(0, (int) $rules_id);

        $this->assertGreaterThan(
            0,
            (int) $rulecriteria->add([
                'rules_id'    => $rules_id,
                'criteria'  => "tag",
                'pattern'   => "/(.*)/",
                'condition' => \RuleImportEntity::REGEX_MATCH,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $ruleaction->add([
                'rules_id'    => $rules_id,
                'action_type' => 'regex_result',
                'field'       => '_affect_entity_by_tag',
                'value'       => 'testtag_2',
            ])
        );

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
        $this->assertCount(1, $found_computers);
        $first_computer = array_pop($found_computers);
        $this->assertSame($entity_2_id, $first_computer['entities_id']);

        $soft = new \Software();
        $softs = $soft->find(['name' => "test_software"]);
        $this->assertCount(1, $softs);
        $first_soft = array_pop($softs);
        $this->assertSame($entity_2_id, $first_soft['entities_id']);
        $this->assertSame(0, $first_soft['is_recursive']);
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
        $this->assertCount(1, $found_computers);
        $first_computer = array_pop($found_computers);

        $soft = new \Software();
        $softs = $soft->find(['name' => "ភាសាខ្មែរ កញ្ចប់បទពិសោធន៍ផ្ទៃក្នុង"]);
        $this->assertCount(1, $softs);

        $computer_softversion = new \Item_SoftwareVersion();
        $computer_softversions = $computer_softversion->find([
            'itemtype' => "Computer",
            'items_id' => $first_computer['id'],
        ]);
        $this->assertCount(1, $computer_softversions);
        $first_computer_soft = array_pop($computer_softversions);

        $version = new SoftwareVersion();
        $this->assertTrue($version->getFromDB($first_computer_soft['softwareversions_id']));
        $this->assertEquals("1.1", $version->fields['name']);

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
        $this->assertCount(1, $found_computers);
        $first_computer = array_pop($found_computers);

        $soft = new \Software();
        $softs = $soft->find(['name' => "ភាសាខ្មែរ កញ្ចប់បទពិសោធន៍ផ្ទៃក្នុង"]);
        $this->assertCount(1, $softs);

        $computer_softversion = new \Item_SoftwareVersion();
        $computer_softversions = $computer_softversion->find([
            'itemtype' => "Computer",
            'items_id' => $first_computer['id'],
        ]);
        $this->assertCount(1, $computer_softversions);
        $first_computer_soft = array_pop($computer_softversions);

        $version = new SoftwareVersion();
        $this->assertTrue($version->getFromDB($first_computer_soft['softwareversions_id']));
        $this->assertEquals("1.4", $version->fields['name']);
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
        $this->assertGreaterThan(0, (int) $rules_id);

        $this->assertGreaterThan(
            0,
            (int) $rulecriteria->add([
                'rules_id'    => $rules_id,
                'criteria'  => "name",
                'pattern'   => "Microsoft",
                'condition' => \Rule::PATTERN_CONTAIN,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $ruleaction->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => 'name',
                'value'       => 'Personal_Publisher',
            ])
        );

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
        $this->assertCount(1, $found_computers);

        $soft = new \Software();
        $softs = $soft->find(['name' => "test_software"]);
        $this->assertCount(1, $softs);
        $first_soft = array_pop($softs);

        $manufacturer = new \Manufacturer();
        $manufacturer->getFromDB($first_soft['manufacturers_id']);

        $this->assertEquals('Personal_Publisher', $manufacturer->fields['name']);
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
        $this->assertGreaterThan(0, (int) $rules_id);

        $this->assertGreaterThan(
            0,
            (int) $rulecriteria->add([
                'rules_id'    => $rules_id,
                'criteria'  => "manufacturer",
                'pattern'   => "Apple",
                'condition' => \Rule::PATTERN_CONTAIN,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $ruleaction->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => 'manufacturer',
                'value'       => 'Other_Publisher',
            ])
        );

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
        $this->assertCount(1, $found_computers);

        $soft = new \Software();
        $softs = $soft->find(['name' => "test_software"]);
        $this->assertCount(1, $softs);
        $first_soft = array_pop($softs);

        $manufacturer = new \Manufacturer();
        $manufacturer->getFromDB($first_soft['manufacturers_id']);

        $this->assertEquals('Other_Publisher', $manufacturer->fields['name']);
    }

    public static function softwareProvider(): array
    {
        return [
            //To test FullCompareKey (with special chars on software name / manufacturer)
            ['01-test_software_with_special_chars_with_version.json'],
            ['02-test_software_with_special_chars_with_version.json'],
            //To test FullCompareKey without version (with special chars on software name / manufacturer)
            ['03-test_software_with_special_chars_and_without_version.json'],
            // /To test FullCompareKey with version (with special chars on software name / manufacturer name / OS name / arch name)
            ['04-test_software_with_special_chars_and_with_version_and_os.json'],
            // /To test FullCompareKey without version (with special chars on software name / manufacturer name / OS name / arch name)
            ['05-test_software_with_special_chars_and_without_version_and_os.json'],
        ];
    }

    #[DataProvider('softwareProvider')]
    public function testSoftwareWithHtmlentites($path)
    {
        $fixtures_path = FIXTURE_DIR . '/inventories/software/';
        $json_source = json_decode(file_get_contents($fixtures_path . $path));
        $this->doInventory($json_source);

        $computer = new \Computer();
        $found_computers = $computer->find(['name' => "pc_test"]);
        $this->assertCount(1, $found_computers);
        $first_computer = array_pop($found_computers);

        //get Software / ItemSoftware
        $software = new \Software();
        $software_version = new SoftwareVersion();
        $software_item = new \Item_SoftwareVersion();

        $software_items = $software_item->find(['itemtype' => "Computer", "items_id" => $first_computer['id']]);
        $this->assertCount(1, $software_items);
        $first_software_items = array_pop($software_items);

        $software_versions = $software_version->find(['id' => $first_software_items['softwareversions_id']]);
        $this->assertCount(1, $software_versions);
        $first_software_versions = array_pop($software_versions);

        $softwares = $software->find(['id' => $first_software_versions['softwares_id']]);
        $this->assertCount(1, $softwares);
        $first_software = array_pop($softwares);


        //redo an inventory
        $json_source = json_decode(file_get_contents($fixtures_path . $path));
        $this->doInventory($json_source);

        $computer = new \Computer();
        $found_computers = $computer->find(['name' => "pc_test"]);
        $this->assertCount(1, $found_computers);
        $first_computer = array_pop($found_computers);


        $software_items = $software_item->find(['itemtype' => "Computer", "items_id" => $first_computer['id']]);
        $this->assertCount(1, $software_items);
        $second_software_items = array_pop($software_items);

        $software_versions = $software_version->find(['id' => $second_software_items['softwareversions_id']]);
        $this->assertCount(1, $software_versions);
        $second_software_versions = array_pop($software_versions);

        $softwares = $software->find(['id' => $second_software_versions['softwares_id']]);
        $this->assertCount(1, $softwares);
        $second_software = array_pop($softwares);

        $this->assertSame($first_software_items['id'], $second_software_items['id']);
        $this->assertSame($first_software_versions['id'], $second_software_versions['id']);
        $this->assertSame($first_software['id'], $second_software['id']);

        $computer->deleteByCriteria(['id' => $first_computer['id']]);
    }

    public function testDuplicatedSoft()
    {
        global $DB;
        $this->login();

        $computer = new \Computer();
        $soft = new \Software();
        $version = new SoftwareVersion();
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
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $this->doInventory($xml_source, true);

        $manufacturer = new \Manufacturer();
        $this->assertTrue($manufacturer->getFromDBByCrit(['name' => 'Microsoft Corporation']));

        //we have 1 software & versions for Microsoft Office Standard - Microsoft Corporation
        $softs = $soft->find(['name' => 'Microsoft Office Standard 2013', 'manufacturers_id' => $manufacturer->fields['id']]);
        $this->assertCount(1, $softs);

        //15.0.4569.1506
        $versions = $version->find(['name' => '15.0.4569.1506']);
        $this->assertCount(1, $versions);

        $version_data = array_pop($versions);
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id'],
        ]));

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
        $version = new SoftwareVersion();
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
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $this->doInventory($xml_source, true);

        //we have 1 software & versions for Cisco AnyConnect Secure Mobility Client
        $softs = $soft->find(['name' => 'Cisco AnyConnect Secure Mobility Client']);
        $this->assertCount(1, $softs);

        //4.10.01075
        $versions = $version->find(['name' => '4.10.01075']);
        $this->assertCount(1, $versions);

        $version_data = array_pop($versions);
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id'],
        ]));

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
        $this->assertCount(1, $softs);

        //4.10.06079
        $versions = $version->find(['name' => '4.10.06079']);
        $this->assertCount(1, $versions);

        $version_data = array_pop($versions);
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id'],
        ]));
    }

    public function testSameSoftManufacturer()
    {
        global $DB;
        $this->login();

        $computer = new \Computer();
        $soft = new \Software();
        $version = new SoftwareVersion();
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
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $this->doInventory($xml_source, true);

        //we have 1 software & versions for ÀVEVA Application Server 2020
        $softs = $soft->find(['name' => 'ÀVEVA Application Server 2020']);
        $this->assertCount(1, $softs);

        //20.0.000
        $versions = $version->find(['name' => '20.0.000']);
        $this->assertCount(1, $versions);

        $version_data = array_pop($versions);
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id'],
        ]));

        //same inventory again
        $this->doInventory($xml_source, true);

        //we have 1 software & versions for ÀVEVA Application Server 2020
        $softs = $soft->find(['name' => 'ÀVEVA Application Server 2020']);
        $this->assertCount(1, $softs);

        //20.0.000
        $versions = $version->find(['name' => '20.0.000']);
        $this->assertCount(1, $versions);

        $version_data = array_pop($versions);
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id'],
        ]));
    }

    public function testSameSoftManufacturer2()
    {
        global $DB;
        $this->login();

        $computer = new \Computer();
        $soft = new \Software();
        $version = new SoftwareVersion();
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
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $this->doInventory($xml_source, true);

        //we have 1 software & versions for ÀVEVA Application Server 2020
        $softs = $soft->find(['name' => 'ÀVEVA Application Server 2020']);
        $this->assertCount(1, $softs);

        //20.0.000
        $versions = $version->find(['name' => '20.0.000']);
        $this->assertCount(1, $versions);

        $version_data = array_pop($versions);
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id'],
        ]));

        //same inventory again
        $this->doInventory($xml_source, true);

        //we have 1 software & versions for ÀVEVA Application Server 2020
        $softs = $soft->find(['name' => 'ÀVEVA Application Server 2020']);
        $this->assertCount(1, $softs);

        //20.0.000
        $versions = $version->find(['name' => '20.0.000']);
        $this->assertCount(1, $versions);

        $version_data = array_pop($versions);
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $version_data['id'],
        ]));
    }

    public function testManufacturerDifferentCase()
    {
        $computer = new \Computer();
        $soft = new \Software();
        $version = new SoftwareVersion();
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
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $this->doInventory($xml_source, true);

        //check software has been created
        $this->assertTrue(
            $soft->getFromDBByCrit(['name' => 'Realtek Card Reader'])
        );
        $softwares_id = $soft->fields['id'];

        //check version has been created
        $this->assertTrue(
            $version->getFromDBByCrit(['name' => '10.0.17134.31242'])
        );
        $this->assertSame($softwares_id, $version->fields['softwares_id']);
        $versions_id = $version->fields['id'];

        //check computer-softwareverison relation has been created
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id,
        ]));
        $item_versions_id = $item_version->fields['id'];

        //import again
        $this->doInventory($xml_source, true);

        //check software is the same
        $this->assertTrue(
            $soft->getFromDBByCrit(['name' => 'Realtek Card Reader'])
        );
        $this->assertSame($soft->fields['id'], $softwares_id);

        //check version is the same
        $this->assertTrue(
            $version->getFromDBByCrit(['name' => '10.0.17134.31242'])
        );
        $this->assertSame($version->fields['id'], $versions_id);

        //check computer-softwareverison relation is the same
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id,
        ]));
        $this->assertSame($item_version->fields['id'], $item_versions_id);
    }

    public function testSoftDifferentCase()
    {
        $computer = new \Computer();
        $soft = new \Software();
        $version = new SoftwareVersion();
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
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $this->doInventory($xml_source, true);

        //check software has been created
        $this->assertTrue(
            $soft->getFromDBByCrit(['name' => 'texlive-ha-prosper'])
        );
        $softwares_id = $soft->fields['id'];

        //check version has been created
        $this->assertTrue(
            $version->getFromDBByCrit(['name' => '4.21'])
        );
        $this->assertSame($softwares_id, $version->fields['softwares_id']);
        $versions_id = $version->fields['id'];

        //check computer-softwareverison relation has been created
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id,
        ]));
        $item_versions_id = $item_version->fields['id'];

        //import again
        $this->doInventory($xml_source, true);

        //check software is the same
        $this->assertTrue(
            $soft->getFromDBByCrit(['name' => 'texlive-ha-prosper'])
        );
        $this->assertSame($soft->fields['id'], $softwares_id);

        //check version is the same
        $this->assertTrue(
            $version->getFromDBByCrit(['name' => '4.21'])
        );
        $this->assertSame($version->fields['id'], $versions_id);

        //check computer-softwareverison relation is the same
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id,
        ]));
        $this->assertSame($item_version->fields['id'], $item_versions_id);
    }

    public function testManufacturerSpecialCharacters()
    {
        $computer = new \Computer();
        $soft = new \Software();
        $version = new SoftwareVersion();
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
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $this->doInventory($xml_source, true);

        //check software has been created
        $this->assertTrue(
            $soft->getFromDBByCrit(['name' => 'Any software'])
        );
        $softwares_id = $soft->fields['id'];

        //check version has been created
        $this->assertTrue(
            $version->getFromDBByCrit(['name' => '1.0.0'])
        );
        $this->assertSame($softwares_id, $version->fields['softwares_id']);
        $versions_id = $version->fields['id'];

        //check computer-softwareverison relation has been created
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id,
        ]));
        $item_versions_id = $item_version->fields['id'];

        //import again
        $this->doInventory($xml_source, true);

        //check software is the same
        $this->assertTrue(
            $soft->getFromDBByCrit(['name' => 'Any software'])
        );
        $this->assertSame($soft->fields['id'], $softwares_id);

        //check version is the same
        $this->assertTrue(
            $version->getFromDBByCrit(['name' => '1.0.0'])
        );
        $this->assertSame($version->fields['id'], $versions_id);

        //check computer-softwareverison relation is the same
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id,
        ]));
        $this->assertSame($item_version->fields['id'], $item_versions_id);
    }

    public function testDateInstallUpdate()
    {
        $computer = new \Computer();
        $soft = new \Software();
        $version = new SoftwareVersion();
        $item_version = new \Item_SoftwareVersion();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
      <SOFTWARES>
      <NAME>A great software</NAME>
      <VERSION>2.0.0</VERSION>
      <INSTALL_DATE>2014-03-04 16:12:35</INSTALL_DATE>
      <PUBLISHER>Manufacture</PUBLISHER>
    </SOFTWARES>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>sdfgdfg8dfg</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        //create manually a computer
        $computers_id = $computer->add([
            'name'   => 'pc003',
            'serial' => 'sdfgdfg8dfg',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $this->doInventory($xml_source, true);

        //check software has been created
        $this->assertTrue(
            $soft->getFromDBByCrit(['name' => 'A great software'])
        );
        $softwares_id = $soft->fields['id'];

        //check version has been created
        $this->assertTrue(
            $version->getFromDBByCrit(['name' => '2.0.0'])
        );
        $this->assertSame($softwares_id, $version->fields['softwares_id']);
        $versions_id = $version->fields['id'];

        //check computer-softwareverison relation has been created
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id,
            "date_install" => "2014-03-04",
        ]));
        $item_versions_id = $item_version->fields['id'];

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
      <SOFTWARES>
      <NAME>A great software</NAME>
      <VERSION>2.0.0</VERSION>
      <INSTALL_DATE>2024-03-04 16:12:35</INSTALL_DATE>
      <PUBLISHER>Manufacture</PUBLISHER>
    </SOFTWARES>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>sdfgdfg8dfg</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        //import again
        $this->doInventory($xml_source, true);

        //check software is the same
        $this->assertTrue(
            $soft->getFromDBByCrit(['name' => 'A great software'])
        );
        $this->assertSame($soft->fields['id'], $softwares_id);

        //check version is the same
        $this->assertTrue(
            $version->getFromDBByCrit(['name' => '2.0.0'])
        );
        $this->assertSame($version->fields['id'], $versions_id);

        //check computer-softwareverison relation has been updated (date_install)
        $this->assertTrue($item_version->getFromDBByCrit([
            "itemtype" => "Computer",
            "items_id" => $computers_id,
            "softwareversions_id" => $versions_id,
            "date_install" => "2024-03-04",
        ]));

        //check that no lock have been added
        $lock = new \Lockedfield();
        $this->assertFalse($lock->getFromDBByCrit([
            "itemtype" => \Item_SoftwareVersion::class,
            "items_id" => $item_version->fields['id'],
            "field" => "date_install",
        ]));
    }

    public function testSubCategoryDictionnary()
    {
        $this->login();

        $rule         = new \RuleSoftwareCategory();
        $rulecriteria = new \RuleCriteria();
        $ruleaction   = new \RuleAction();

        $category   = new \SoftwareCategory();
        $parent_categories_id = $category->add(['name' => 'Parent']);
        $categories_id = $category->add(['name' => 'Child', 'categories_id' => $parent_categories_id]);

        $rules_id = $rule->add([
            'is_active'    => 1,
            'name'         => 'Sub category',
            'match'        => 'AND',
            'sub_type'     => \RuleSoftwareCategory::class,
            'is_recursive' => 0,
            'ranking'      => 1,
        ]);
        $this->assertGreaterThan(0, (int) $rules_id);

        $this->assertGreaterThan(
            0,
            (int) $rulecriteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => 'firefox',
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $ruleaction->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => 'softwarecategories_id',
                'value'       => $categories_id,
            ])
        );

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>Mozilla Firefox Web browser</COMMENTS>
      <FILESIZE>258573684</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>23/12/2020</INSTALLDATE>
      <NAME>firefox</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>Unspecified</SYSTEM_CATEGORY>
      <VERSION>84.0-6.fc32</VERSION>
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
        $this->assertCount(1, $found_computers);

        $soft = new \Software();
        $softs = $soft->find(['name' => "firefox"]);
        $this->assertCount(1, $softs);
        $first_soft = array_pop($softs);

        $this->assertSame($categories_id, $first_soft['softwarecategories_id']);
    }

    public function testIsHelpdeskVisible()
    {
        global $CFG_GLPI;

        $this->login();

        $computer = new \Computer();
        $soft = new \Software();

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
      <NAME>pc012302</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>sd65f4sd6f4</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        //software import behave differently: non-dynamic are not handled at all.
        //create manually a computer
        $computers_id = $computer->add([
            'name'   => 'pc012302',
            'serial' => 'sd65f4sd6f4',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $computers_id);

        // set software to not be helpdesk visible
        $CFG_GLPI["default_software_helpdesk_visible"] = 0;
        $this->doInventory($xml_source, true);

        //check that software has been created and is_helpdesk_visible false
        $softs = $soft->find(['is_helpdesk_visible' => false, 'name' => 'gimp']);
        $this->assertCount(1, $softs);

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
          <CONTENT>
            <SOFTWARES>
              <ARCH>x86_64</ARCH>
              <COMMENTS>GNU Image Manipulation Program</COMMENTS>
              <FILESIZE>67382735</FILESIZE>
              <FROM>rpm</FROM>
              <INSTALLDATE>03/10/2021</INSTALLDATE>
              <NAME>other_soft</NAME>
              <PUBLISHER>Fedora Project</PUBLISHER>
              <SYSTEM_CATEGORY>Web App</SYSTEM_CATEGORY>
              <VERSION>2.10.28-1.fc34</VERSION>
            </SOFTWARES>
            <HARDWARE>
              <NAME>pc012302</NAME>
            </HARDWARE>
            <BIOS>
              <SSN>sd65f4sd6f4</SSN>
            </BIOS>
            <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
          </CONTENT>
          <DEVICEID>test-pc002</DEVICEID>
          <QUERY>INVENTORY</QUERY>
        </REQUEST>";

        // retry with default_software_helpdesk_visible = 1
        $CFG_GLPI["default_software_helpdesk_visible"] = 1;
        $this->doInventory($xml_source, true);

        //check that software has been created and is_helpdesk_visible false
        $softs = $soft->find(['is_helpdesk_visible' => true, 'name' => 'other_soft']);
        $this->assertCount(1, $softs);
    }

    public function testSoftLockedOSChange()
    {
        $json = <<<JSON
{
    "action": "inventory",
    "content": {
        "hardware": {
            "chassis_type": "Desktop",
            "name": "computer-os-change",
            "uuid": "3a82e620-d7da-11dd-ad0f-klhlhlhjjjvv"
        },
        "operatingsystem": {
            "arch": "x86_64",
            "full_name": "Fedora 39",
            "name": "Fedora",
            "version": "39"
        },
        "softwares": [
            {
                "arch": "x86_64",
                "from": "rpm",
                "install_date": "2025-02-19",
                "name": "Firefox",
                "version": "135.0.1"
            }
        ],
        "versionclient": "GLPI-Agent"
    },
    "deviceid": "computer-os-change",
    "itemtype": "Computer"
}
JSON;

        $this->login();
        $this->doInventory(json_decode($json));

        $computer = new \Computer();
        $this->assertTrue($computer->getFromDBByCrit(['name' => 'computer-os-change']));

        $computers_id = $computer->getID();

        $os = new \OperatingSystem();
        $os_item = $this->getMockBuilder(\Item_OperatingSystem::class)
            ->onlyMethods(['prepareInputForUpdate'])
            ->getMock();
        $os_item->method('prepareInputForUpdate')->willReturnCallback(
            function ($input) {
                return $input;
            }
        );
        $this->assertTrue($os_item->getFromDBByCrit(['itemtype' => \Computer::class, 'items_id' => $computers_id]));
        $this->assertTrue($os->getFromDB($os_item->fields['operatingsystems_id']));
        $this->assertSame('Fedora 39', $os->fields['name']);

        //check software versions count
        $isoft_version = new \Item_SoftwareVersion();
        $this->assertCount(
            2, //OS + one software
            $isoft_version->find(['itemtype' => \Computer::class, 'items_id' => $computers_id])
        );

        //check there is no locked field
        $lockedfields = new \Lockedfield();
        $this->assertEquals([], $lockedfields->find());

        //change OS by hand
        $new_osid = $os->add([
            'name' => 'Fedora 40',
        ]);
        $this->assertGreaterThan(0, $new_osid);
        $this->assertTrue(
            $os_item->update([
                'id' => $os_item->getID(),
                'operatingsystems_id' => $new_osid,
            ])
        );

        //check software versions count is still the same
        $this->assertCount(
            2, //OS + one software
            $isoft_version->find(['itemtype' => \Computer::class, 'items_id' => $computers_id])
        );

        //check Item_OperatingSystem has been locked
        $locks = $lockedfields->find();
        $this->assertCount(1, $locks);
        $lock = array_pop($locks);
        $this->assertSame('operatingsystems_id', $lock['field']);
        $this->assertTrue($lockedfields->getFromDB($lock['id']));

        //mocked class name is stored here.
        $this->assertNotEquals(\Item_OperatingSystem::class, $lock['itemtype']);
        $this->assertTrue( //fix itemtype
            $lockedfields->update([
                'id' => $lock['id'],
                'itemtype' => \Item_OperatingSystem::class,
            ])
        );

        //redo inventory
        $inventory = json_decode($json);
        $inventory->content->operatingsystem->full_name = 'Fedora 41';
        $inventory->content->operatingsystem->version = '41';
        $this->doInventory($inventory);

        $this->assertTrue($os_item->getFromDBByCrit(['itemtype' => \Computer::class, 'items_id' => $computers_id]));
        //ensure OS is the one defined by hand
        $this->assertSame($new_osid, $os_item->fields['operatingsystems_id']);

        //check software versions count is still the same
        $this->assertCount(
            2, //OS + one software
            $isoft_version->find(['itemtype' => \Computer::class, 'items_id' => $computers_id])
        );
    }

    public function testDuplicateSoftwareVersion()
    {
        $fixturesPath = FIXTURE_DIR . '/inventories/software/';

        $computer         = new \Computer();
        $software         = new \Software();
        $softwareVersion  = new SoftwareVersion();
        $itemSoftware     = new \Item_SoftwareVersion();

        // Step 1: Full inventory (software + OS) on computer ARN2032
        // This should create the software, version, and link it to the computer.
        $jsonSource = json_decode(file_get_contents($fixturesPath . "01_computer_full_inventory_soft_and_os.json"));
        $this->doInventory($jsonSource);

        $foundComputers = $computer->find(['name' => "ARN2032"]);
        $this->assertCount(1, $foundComputers);
        $firstComputer = array_pop($foundComputers);

        $softwares = $software->find(['name' => "Teclib Software Software"]);
        $this->assertCount(1, $softwares);
        $firstSoftware = array_pop($softwares);

        $versions = $softwareVersion->find(['name' => "2.20.4853.2486", 'softwares_id' => $firstSoftware['id']]);
        $this->assertCount(1, $versions);
        $version = array_pop($versions);

        $itemSoftwares = $itemSoftware->find([
            'itemtype'            => \Computer::class,
            'items_id'            => $firstComputer['id'],
            'softwareversions_id' => $version['id'],
        ]);
        $this->assertCount(1, $itemSoftwares);

        // Step 2: Full inventory (software + OS) on a second computer ARN2045
        // This should reuse the existing software version, not create a duplicate.
        $jsonSource = json_decode(file_get_contents($fixturesPath . "02_computer_full_inventory_soft_and_os.json"));
        $this->doInventory($jsonSource);

        $foundComputers = $computer->find(['name' => "ARN2045"]);
        $this->assertCount(1, $foundComputers);
        $secondComputer = array_pop($foundComputers);

        $softwares = $software->find(['name' => "Teclib Software Software"]);
        $this->assertCount(1, $softwares);
        $firstSoftware = array_pop($softwares);

        $versions = $softwareVersion->find(['name' => "2.20.4853.2486", 'softwares_id' => $firstSoftware['id']]);
        $this->assertCount(1, $versions);
        $version = array_pop($versions);

        $itemSoftwares = $itemSoftware->find([
            'itemtype'            => \Computer::class,
            'items_id'            => $secondComputer['id'],
            'softwareversions_id' => $version['id'],
        ]);
        $this->assertCount(1, $itemSoftwares);

        // Step 3: Partial inventory (software + OS) on a third computer ARN3045
        // This simulates partial inventory behavior where OS info is present, and should still reuse the same version.
        $jsonSource = json_decode(file_get_contents($fixturesPath . "03_computer_partial_inventory_with_os.json"));
        $this->doInventory($jsonSource);

        $foundComputers = $computer->find(['name' => "ARN3045"]);
        $this->assertCount(1, $foundComputers);
        $thirdComputer = array_pop($foundComputers);

        $softwares = $software->find(['name' => "Teclib Software Software"]);
        $this->assertCount(1, $softwares);
        $firstSoftware = array_pop($softwares);

        $versions = $softwareVersion->find(['name' => "2.20.4853.2486", 'softwares_id' => $firstSoftware['id']]);
        $this->assertCount(1, $versions);
        $version = array_pop($versions);

        $itemSoftwares = $itemSoftware->find([
            'itemtype'            => \Computer::class,
            'items_id'            => $thirdComputer['id'],
            'softwareversions_id' => $version['id'],
        ]);
        $this->assertCount(1, $itemSoftwares);

        // Step 4: Full inventory with OS only (no software) on ARN4058
        // This prepares the main asset with an OS for use in the next step (software will need to retrieve OS from main asset).
        $jsonSource = json_decode(file_get_contents($fixturesPath . "04_computer_full_inventory_with_os_without_soft.json"));
        $this->doInventory($jsonSource);

        $foundComputers = $computer->find(['name' => "ARN4058"]);
        $this->assertCount(1, $foundComputers);
        $fourthComputer = array_pop($foundComputers);

        $softwares = $software->find(['name' => "Teclib Software Software"]);
        $this->assertCount(1, $softwares);
        $firstSoftware = array_pop($softwares);

        // Step 5: Partial inventory with software only (no OS) on ARN4058
        // This simulates a case where prepare() must fetch the OS from the main asset to avoid creating a duplicate version.
        $jsonSource = json_decode(file_get_contents($fixturesPath . "05_computer_full_inventory_without_os_with_soft.json"));
        $this->doInventory($jsonSource);

        $foundComputers = $computer->find(['name' => "ARN4058"]);
        $this->assertCount(1, $foundComputers);
        $fourthComputer = array_pop($foundComputers);

        $softwares = $software->find(['name' => "Teclib Software Software"]);
        $this->assertCount(1, $softwares);
        $firstSoftware = array_pop($softwares);

        $versions = $softwareVersion->find(['name' => "2.20.4853.2486", 'softwares_id' => $firstSoftware['id']]);
        $this->assertCount(1, $versions);
        $version = array_pop($versions);

        $itemSoftwares = $itemSoftware->find([
            'itemtype'            => \Computer::class,
            'items_id'            => $fourthComputer['id'],
            'softwareversions_id' => $version['id'],
        ]);
        $this->assertCount(1, $itemSoftwares);

        // Step 6: Full inventory with software and without OS should never happen
    }

}
