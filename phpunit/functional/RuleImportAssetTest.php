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

namespace tests\units;

use DbTestCase;
use Glpi\DBAL\QueryExpression;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/ruleimportcomputer.class.php */

class RuleImportAssetTest extends DbTestCase
{
    private $items_id;
    private $itemtype;
    private $rules_id;
    private $ports_id;

    public function setUp(): void
    {
        parent::setUp();
        //reset local values
        $this->items_id = null;
        $this->itemtype = null;
        $this->rules_id = null;
        $this->ports_id = [];
    }

    public function rulepassed($items_id, $itemtype, $rules_id, $ports_id = [])
    {
        $this->items_id = (int) $items_id;
        $this->itemtype = $itemtype;
        $this->rules_id = (int) $rules_id;
        $this->ports_id = (array) $ports_id;
    }

    protected function enableRule($name)
    {
        global $DB;

        $DB->update(
            \RuleImportAssetCollection::getTable(),
            ['is_active' => 1],
            [
                'name'      => ['LIKE', "%$name%"],
                'is_active' => 0,
            ]
        );
    }

    /**
     * Adds a new rule
     *
     * @param string $name          New rule name
     * @param array  $criteria      Rule criteria
     * @param array  $actions       Rule actions
     * @param string $afterRuleName Rule name to insert after.
     *
     * @return void
     */
    protected function addAssetRule($name, array $criteria, array $action, $afterRuleName = null): int
    {
        global $DB;

        $rule = new \RuleImportAsset();

        $ruleARN = $rule->find(['name' => $afterRuleName ?? 'Computer constraint (name)'], [], 1);
        $this->assertCount(1, $ruleARN);
        $r = current($ruleARN);
        $this->assertTrue(
            $DB->update(
                'glpi_rules',
                [
                    'ranking' => new QueryExpression($DB->quoteName('ranking') . ' + 2'),
                ],
                [
                    'ranking'   => ['>', $r['ranking']],
                    'sub_type'  => \RuleImportAsset::class,
                ]
            )
        );

        return parent::addRule(\RuleImportAsset::class, $name, $criteria, $action, $r['ranking'] + 1);
    }

    public function testCreateComputerName()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer import (by name)", $rule->fields['name']);
        $this->assertSame(0, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
    }

    public function testUpdateComputerName()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'entities_id'  => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();

        $computers_id = (int) $computer->add($input);
        $this->assertGreaterThan(0, $computers_id);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer update (by name)", $rule->fields['name']);
        $this->assertSame($computers_id, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
    }

    public function testUpdateComputerDoubleName()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'entities_id'  => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();

        $computers_id = $computer->add($input + ['comment' => 'first computer']);
        $this->assertGreaterThan(0, $computers_id);

        $computers_id2 = $computer->add($input + ['comment'     => 'second computer']);
        $this->assertGreaterThan(0, $computers_id2);

        $this->assertNotEquals($computers_id2, $computers_id);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer update (by name)", $rule->fields['name']);
        $this->assertSame($computers_id, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
    }

    /**
     * case 1 :
     *   no computer in DB
     */
    public function testCreateComputerSerial_UUID_case1()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'serial'   => '75F4BF',
            'uuid'     => '01391796-50A4-0246-955B-417652A8AF14',
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer import (by serial + uuid)", $rule->fields['name']);
        $this->assertSame(0, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
    }

    /**
     * case 2 :
     *   computer in DB with this UUID and another name
     */
    public function testCreateComputerSerial_UUID_case2()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'serial'   => '75F4BF',
            'uuid'     => '01391796-50A4-0246-955B-417652A8AF14',
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();

        $computers_id = (int) $computer->add([
            'entities_id' => 0,
            'name'        => 'pc-02',
            'uuid'     => '01391796-50A4-0246-955B-417652A8AF14',
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer import (by serial + uuid)", $rule->fields['name']);
        $this->assertSame(0, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
    }

    public function testUpdateComputerSerial_UUID()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'serial'   => '75F4BF',
            'uuid'     => '01391796-50A4-0246-955B-417652A8AF14',
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();

        $computers_id = (int) $computer->add($input);
        $this->assertGreaterThan(0, $computers_id);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer update (by serial + uuid)", $rule->fields['name']);
        $this->assertSame($computers_id, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
    }

    public function testCreateComputerMac()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'mac'      => ['d4:81:d7:7b:6c:21'],
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $this->enableRule('(by mac)');
        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer import (by mac)", $rule->fields['name']);
        $this->assertSame(0, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
    }

    public function testUpdateComputerMac()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'mac'      => ['d4:81:d7:7b:6c:21'],
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();
        $nport = new \NetworkPort();

        $computers_id = (int) $computer->add([
            'entities_id' => 0,
            'name'        => 'pc-02', // to be sure the name rule not works before mac rule
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $ports_id = (int) $nport->add([
            'instantiation_type' => 'NetworkPortEthernet',
            'itemtype'           => 'Computer',
            'items_id'           => $computers_id,
            'mac'                => 'd4:81:d7:7b:6c:21',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        $this->enableRule('(by mac)');
        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer update (by mac)", $rule->fields['name']);
        $this->assertSame($computers_id, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
        $this->assertEquals([$ports_id], $this->ports_id);
    }

    /**
     * Create rules for Computer based on IP
     *
     * @return void
     */
    private function addComputerIPRules()
    {
        // Create rules
        $this->addAssetRule(
            "Computer update (by ip)",
            [
                [
                    'condition' => 0,
                    'criteria'  => 'itemtype',
                    'pattern'   => 'Computer',
                ],
                [
                    'condition' => \RuleImportAsset::PATTERN_FIND,
                    'criteria'  => 'ip',
                    'pattern'   => '1',
                ],
                [
                    'condition' => \RuleImportAsset::PATTERN_EXISTS,
                    'criteria'  => 'ip',
                    'pattern'   => '1',
                ],
            ],
            [
                'action_type' => 'assign',
                'field'       => '_inventory',
                'value'       => \RuleImportAsset::RULE_ACTION_LINK_OR_IMPORT,
            ],
            "Computer update (by mac)"
        );

        $this->addAssetRule(
            "Computer import (by ip)",
            [
                [
                    'condition' => 0,
                    'criteria'  => 'itemtype',
                    'pattern'   => 'Computer',
                ],
                [
                    'condition' => \RuleImportAsset::PATTERN_EXISTS,
                    'criteria'  => 'ip',
                    'pattern'   => '1',
                ],
            ],
            [
                'action_type' => 'assign',
                'field'       => '_inventory',
                'value'       => \RuleImportAsset::RULE_ACTION_LINK_OR_IMPORT,
            ],
            "Computer import (by mac)"
        );
    }

    public function testCreateComputerIP()
    {
        $this->addComputerIPRules();
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'ip'       => ['192.168.0.10'],
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer import (by ip)", $rule->fields['name']);
        $this->assertSame(0, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
    }

    public function testUpdateComputerIP()
    {
        $this->addComputerIPRules();
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'ip'       => ['192.168.0.10'],
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();
        $networkPort = new \NetworkPort();

        $computers_id = (int) $computer->add([
            'entities_id' => 0,
            'name'        => 'pc-02', // to be sure the name rule not works before mac rule
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $ports_id = (int) $networkPort->add([
            'instantiation_type' => 'NetworkPortEthernet',
            'itemtype'           => 'Computer',
            'items_id'           => $computers_id,
            'ip'                 => '192.168.0.10',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '192.168.0.10',
            ],
        ]);
        $this->assertGreaterThan(0, $ports_id);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer update (by ip)", $rule->fields['name']);
        $this->assertSame($computers_id, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
        $this->assertEquals([$ports_id], $this->ports_id);
    }


    /**
     * Create rules for Computer based on IP
     *
     * @return void
     */
    private function addComputerIPLinkOnlyRules()
    {
        // Create rules
        $this->addAssetRule(
            "Computer update (by ip)",
            [
                [
                    'condition' => 0,
                    'criteria'  => 'itemtype',
                    'pattern'   => 'Computer',
                ],
                [
                    'condition' => \RuleImportAsset::PATTERN_FIND,
                    'criteria'  => 'ip',
                    'pattern'   => '1',
                ],
                [
                    'condition' => \RuleImportAsset::PATTERN_EXISTS,
                    'criteria'  => 'ip',
                    'pattern'   => '1',
                ],
            ],
            [
                'action_type' => 'assign',
                'field'       => '_inventory',
                'value'       => \RuleImportAsset::RULE_ACTION_LINK_OR_NO_IMPORT,
            ],
            "Computer update (by mac)"
        );
    }

    public function testCreateComputerIPLinkOnly()
    {
        $this->addComputerIPLinkOnlyRules();
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'ip'       => ['192.168.0.10'],
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer update (by ip)", $rule->fields['name']);
        //do not exists in database, so not imported.
        $this->assertNull($this->items_id);
        $this->assertNull($this->itemtype);
    }

    public function testUpdateComputerIPLinkOnly()
    {
        $this->addComputerIPLinkOnlyRules();
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'ip'       => ['192.168.0.10'],
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();
        $networkPort = new \NetworkPort();

        $computers_id = (int) $computer->add([
            'entities_id' => 0,
            'name'        => 'pc-02', // to be sure the name rule not works before mac rule
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $ports_id = (int) $networkPort->add([
            'instantiation_type' => 'NetworkPortEthernet',
            'itemtype'           => 'Computer',
            'items_id'           => $computers_id,
            'ip'                 => '192.168.0.10',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '192.168.0.10',
            ],
        ]);
        $this->assertGreaterThan(0, $ports_id);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer update (by ip)", $rule->fields['name']);
        $this->assertSame($computers_id, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
        $this->assertEquals([$ports_id], $this->ports_id);
    }

    /**
     * Case when all rules are disabled
     */
    public function testCreateComputerNoRules()
    {
        global $DB;

        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();

        $DB->update(
            $ruleCollection->getTable(),
            [
                'is_active' => 0,
            ],
            [
                'is_active' => 1,
            ]
        );

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_no_rule_matches', $data);
        $this->assertEquals(1, $data['_no_rule_matches']);
    }

    public static function refuseProvider()
    {
        return [
            [    // IP
                'rdata'   => ['ip' => '192.168.0.10'],
                'rname'  => 'Global constraint (name)',
            ], [ // IP+mac
                'rdata'   => ['mac' => 'd4:81:d7:7b:6c:21', 'ip' => '192.168.0.10'],
                'rname'  => 'Global constraint (name)',
            ], [ // IP+name
                'rdata'   => ['name' => 'pc-01', 'ip' => '192.168.0.10'],
                'rname'  => 'Global import denied',
            ], [ // IP+mac+name
                'rdata'   => ['name' => 'pc-01', 'mac' => 'd4:81:d7:7b:6c:21', 'ip' => '192.168.0.10'],
                'rname'  => 'Global import denied',
            ],
        ];
    }

    /**
     * With default rules, refuse import in theses cases
     *
     * @param array  $rdata  Rules data to use
     * @param string $rname  Expected rule name
     */
    #[DataProvider('refuseProvider')]
    public function testRefuseImport($rdata, $rname)
    {
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($rdata, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame($rname, $rule->fields['name']);
        $this->assertNull($this->items_id);
    }

    /**
     * Search device based on MAC + ifnumber (logicial number)
     */
    public function testCreateMacIfnumber()
    {
        $input = [
            'ifnumber' => '10102',
            'mac'      => '00:1a:6c:9a:fc:99',
            'name'     => 'network-01',
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Global import (by mac+ifnumber)", $rule->fields['name']);
        $this->assertSame(0, $this->items_id);
        $this->assertSame('Unmanaged', $this->itemtype); //not handled yet...
        $this->assertEmpty($this->ports_id);
    }

    /**
     * Search device based on MAC + ifnumber (logicial number)
     */
    public function testUpdateMacIfnumber()
    {
        $input = [
            'ifnumber' => '10102',
            'mac'      => '00:1a:6c:9a:fc:99',
            'name'     => 'network-01',
            'entities_id' => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $networkEquipment = new \NetworkEquipment();
        $networkPort = new \NetworkPort();

        $networkEquipments_id = (int) $networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'network-02',
        ]);
        $this->assertGreaterThan(0, $networkEquipments_id);

        $ports_id = (int) $networkPort->add([
            'mac'                => '00:1a:6c:9a:fc:99',
            'name'               => 'Gi0/1',
            'logical_number'     => '10101',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        $ports_id = (int) $networkPort->add([
            'mac'                => '00:1a:6c:9a:fc:99',
            'name'               => 'Gi0/2',
            'logical_number'     => '10102',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
        ]);
        $this->assertGreaterThan(0, $ports_id);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Global update (by mac+ifnumber restricted port)", $rule->fields['name']);
        $this->assertSame($networkEquipments_id, $this->items_id);
        $this->assertSame('NetworkEquipment', $this->itemtype);
        $this->assertEquals([$ports_id], $this->ports_id);
    }

    //Above commented tests are related to SNMP inventory
    /**
     * Search device based on IP + ifdescr restricted on same port
     */
    public function testUpdateIPIfdescrRestrictport()
    {
        $input = [
            'ifdescr' => 'FastEthernet0/1',
            'ip'      => '192.168.0.1',
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $networkEquipment = new \NetworkEquipment();
        $networkPort = new \NetworkPort();

        $networkEquipments_id = (int) $networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'network-02',
        ]);
        $this->assertGreaterThan(0, $networkEquipments_id);

        $ports_id_1 = (int) $networkPort->add([
            'mac'                => '00:1a:6c:9a:fc:99',
            'name'               => 'Fa0/1',
            'logical_number'     => '10101',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ip'                 => '192.168.0.1',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '192.168.0.1',
            ],
            'ifdescr' => 'FastEthernet0/1',
        ]);
        $this->assertGreaterThan(0, $ports_id_1);

        $ports_id_2 = (int) $networkPort->add([
            'mac'                => '00:1a:6c:9a:fc:98',
            'name'               => 'Fa0/2',
            'logical_number'     => '10102',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ip'                 => '192.168.0.2',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '192.168.0.2',
            ],
            'ifdescr' => 'FastEthernet0/2',
        ]);
        $this->assertGreaterThan(0, $ports_id_2);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Global update (by ip+ifdescr restricted port)", $rule->fields['name']);
        $this->assertSame($networkEquipments_id, $this->items_id);
        $this->assertSame('NetworkEquipment', $this->itemtype);
        $this->assertEquals([$ports_id_1], $this->ports_id);

        $this->items_id = 0;
        $this->itemtype = "";
        $this->ports_id = [];
        $input = [
            'ifdescr' => 'FastEthernet0/1',
            'ip'      => '192.168.0.2',
            'entities_id' => 0,
        ];
        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertNotEquals(
            "Device update (by ip+ifdescr restricted port)",
            $rule->fields['name']
        );
    }

    /**
     * Search device based on IP + ifdescr not restricted on same port
     */
    public function testUpdateIPIfdescrNotRestrictport()
    {
        $input = [
            'ifdescr' => 'FastEthernet0/1',
            'ip'      => '192.168.0.2',
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $networkEquipment = new \NetworkEquipment();
        $networkPort = new \NetworkPort();

        $networkEquipments_id = $networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'network-02',
        ]);
        $this->assertGreaterThan(0, $networkEquipments_id);

        $a_portids = [];
        $ports_id = $networkPort->add([
            'mac'                => '00:1a:6c:9a:fc:99',
            'name'               => 'Fa0/1',
            'logical_number'     => '10101',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ip'                 => '192.168.0.1',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '192.168.0.1',
            ],
            'ifdescr'         => 'FastEthernet0/1',
        ]);
        $this->assertGreaterThan(0, $ports_id);
        $a_portids[] = $ports_id;

        $ports_id = $networkPort->add([
            'mac'                => '00:1a:6c:9a:fc:98',
            'name'               => 'Fa0/2',
            'logical_number'     => '10102',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
            'ip'                 => '192.168.0.2',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '192.168.0.2',
            ],
            'ifdescr'         => 'FastEthernet0/2',
        ]);
        $this->assertGreaterThan(0, $ports_id);
        $a_portids[] = $ports_id;

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertGreaterThan(0, (int) $data['_ruleid']);

        $this->assertTrue($rule->getFromDB($data['_ruleid']));
        $this->assertSame('Global update (by ip+ifdescr not restricted port)', $rule->fields['name']);
        $this->assertSame($networkEquipments_id, $this->items_id);
        $this->assertSame('NetworkEquipment', $this->itemtype);
        $this->assertCount(count($a_portids), $this->ports_id);
        $this->assertEqualsCanonicalizing($a_portids, $this->ports_id);
    }

    /**
     * Case have only the mac address (mac found on switches)
     */
    public function testSearchMac_nomoredata()
    {
        $input = [
            'mac' => 'd4:81:b4:5a:a6:19',
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $printer = new \Printer();
        $networkPort = new \NetworkPort();

        $printers_id = (int) $printer->add([
            'entities_id' => 0,
            'name'        => 'network-02',
        ]);
        $this->assertGreaterThan(0, $printers_id);

        $ports_id_1 = (int) $networkPort->add([
            'mac'                => 'd4:81:b4:5a:a6:18',
            'name'               => 'Fa0/1',
            'logical_number'     => '10101',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $printers_id,
            'itemtype'           => 'Printer',
        ]);
        $this->assertGreaterThan(0, $ports_id_1);

        $ports_id_2 = (int) $networkPort->add([
            'mac'                => 'd4:81:b4:5a:a6:19',
            'name'               => 'Fa0/2',
            'logical_number'     => '10102',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $printers_id,
            'itemtype'           => 'Printer',
        ]);
        $this->assertGreaterThan(0, $ports_id_2);

        $ports_id_3 = (int) $networkPort->add([
            'mac'                => 'd4:81:b4:5a:a6:20',
            'name'               => 'Fa0/3',
            'logical_number'     => '10103',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $printers_id,
            'itemtype'           => 'Printer',
        ]);
        $this->assertGreaterThan(0, $ports_id_3);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Update only mac address (mac on switch port)", $rule->fields['name']);
        $this->assertSame($printers_id, $this->items_id);
        $this->assertSame('Printer', $this->itemtype);
        $this->assertEquals([$ports_id_2], $this->ports_id);
    }

    public function testGetTitle()
    {
        $instance = new \RuleImportAsset();
        $this->assertSame('Rules for import and link equipments', $instance->getTitle());
    }

    public function testMaxActionsCount()
    {
        $instance = new \RuleImportAsset();
        $this->assertSame(2, $instance->maxActionsCount());
    }

    public function testGetCriteria()
    {
        $instance = new \RuleImportAsset();
        $this->assertSame(24, count($instance->getCriterias()));
    }

    public function testGetActions()
    {
        $instance = new \RuleImportAsset();
        $this->assertSame(2, count($instance->getActions()));
    }

    public function testGetRuleActionValues()
    {
        $instance = new \RuleImportAsset();
        $this->assertSame(3, count($instance->getRuleActionValues()));
    }

    public static function ruleactionProvider()
    {
        $known = \RuleImportAsset::getRuleActionValues();

        $values = [];
        foreach ($known as $k => $v) {
            $values[] = [
                'value'     => $k,
                'expected'  => $v,
            ];
        }

        $values[] = [
            'value'     => 404,
            'expected'  => '',
        ];

        return $values;
    }

    /**
     * @param integer $value    Value to test
     * @param string  $expected Excpected result
     *
     * @return void
     */
    #[DataProvider('ruleactionProvider')]
    public function testDisplayAdditionRuleActionValue($value, $expected)
    {
        $instance = new \RuleImportAsset();
        $this->assertSame($expected, $instance->displayAdditionRuleActionValue($value));
    }

    public static function moreCritProvider()
    {
        return [
            [
                'criterion' => 'entityrestrict',
                'expected'  => [
                    \RuleImportAsset::PATTERN_ENTITY_RESTRICT => 'Yes',
                ],
            ], [
                'criterion' => 'link_criteria_port',
                'expected'  => [
                    \RuleImportAsset::PATTERN_NETWORK_PORT_RESTRICT => 'Yes',
                ],
            ], [
                'criterion' => 'only_these_criteria',
                'expected'  => [
                    \RuleImportAsset::PATTERN_ONLY_CRITERIA_RULE => 'Yes',
                ],
            ], [
                'criterion' => 'any_other',
                'expected'  => [
                    \RuleImportAsset::PATTERN_FIND => 'is already present',
                    \RuleImportAsset::PATTERN_IS_EMPTY => 'is empty',
                ],
            ],
        ];
    }

    /**
     * @param string $criterion Criterion to test
     * @param array $expected   Expected result
     *
     * @return void
     */
    #[DataProvider('moreCritProvider')]
    public function testAddMoreCriteria($criterion, $expected)
    {
        $instance = new \RuleImportAsset();
        $this->assertSame($expected, $instance->addMoreCriteria($criterion));
    }

    public function testCreateComputerSerial_emptyUUID()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $fields   = $computer->fields;
        unset($fields['id']);
        unset($fields['date_creation']);
        unset($fields['date_mod']);
        $fields['name'] = $this->getUniqueString();
        $fields['serial'] = '75F4BFC';
        $this->assertGreaterThan(0, (int) $computer->add($fields));

        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-02',
            'serial'   => '75F4BFC',
            'uuid'     => '01391796-50A4-0246-955B-417652A8AF14',
            'entities_id' => 0,
        ];

        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer update (by serial + uuid is empty in GLPI)", $rule->fields['name']);
        $this->assertSame('Computer', $this->itemtype);
    }

    /**
     * Create rules for update Computer based on computer name and TAG
     *
     * @return void
     */
    private function updateComputerAgentTagRules()
    {
        // Create rules
        $this->addAssetRule(
            "Computer update (by name and tag)",
            [
                [
                    'condition' => 0,
                    'criteria'  => 'itemtype',
                    'pattern'   => 'Computer',
                ],
                [
                    'condition' => \RuleImportAsset::PATTERN_FIND,
                    'criteria'  => 'name',
                    'pattern'   => '1',
                ],
                [
                    'condition' => \RuleImportAsset::PATTERN_EXISTS,
                    'criteria'  => 'name',
                    'pattern'   => '1',
                ],
                [
                    'condition' => \RuleImportAsset::PATTERN_FIND,
                    'criteria'  => 'tag',
                    'pattern'   => '1',
                ],
                [
                    'condition' => \RuleImportAsset::PATTERN_EXISTS,
                    'criteria'  => 'tag',
                    'pattern'   => '1',
                ],
            ],
            [
                'action_type' => 'assign',
                'field'       => '_inventory',
                'value'       => \RuleImportAsset::RULE_ACTION_LINK_OR_IMPORT,
            ],
            "Computer constraint (name)"
        );
    }

    public function testUpdateComputerByNameAndTag()
    {
        global $DB;

        $this->updateComputerAgentTagRules();

        //create computer
        $computer = new \Computer();
        $computers_id = (int) $computer->add([
            'entities_id' => 0,
            'name'        => 'pc-11',
        ]);
        $this->assertGreaterThan(0, $computers_id);

        //create linked agent
        $agent = new \Agent();
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents_id = (int) $agent->add([
            'deviceid' => 'my_specific_agent_deviceid',
            'tag' => 'my_specific_agent_tag',
            'itemtype' => "Computer",
            'agenttypes_id' => $agenttype['id'],
            'items_id' => $computers_id,
            'name'        => 'pc-11',
        ]);
        $this->assertGreaterThan(0, $agents_id);

        $input = [
            'itemtype'      => 'Computer',
            'name'          => 'pc-11',
            'tag'           => 'my_specific_agent_tag',
            'deviceid'     => 'my_specific_agent_deviceid',
            'ip'            => ['192.168.0.10'],
            'entities_id'   => 0,
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->assertArrayHasKey('_ruleid', $data);
        $_rule_id = (int) $data['_ruleid'];
        $this->assertGreaterThan(0, $_rule_id);

        $this->assertTrue($rule->getFromDB($_rule_id));
        $this->assertSame("Computer update (by name and tag)", $rule->fields['name']);
        $this->assertSame($computers_id, $this->items_id);
        $this->assertSame('Computer', $this->itemtype);
    }

    public function testReconciliateWithAssetTemplate()
    {

        $computer = new \Computer();
        $ruleCollection = new \RuleImportAssetCollection();

        //create 'legacy' computer
        $computers_id = (int) $computer->add([
            'entities_id' => 0,
            'name'        => 'pc-11',
            'serial'      => '12345-65487-98765-45645',
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $input = [
            'itemtype'      => 'Computer',
            'name'          => 'pc-11',
            'serial'        => '12345-65487-98765-45645',
            'entities_id'   => 0,
        ];

        // execute rule engine
        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);
        // check tha rule engine found the computer
        $this->assertSame($computers_id, $data["found_inventories"][0]);

        // update computer to mark as template
        $this->assertTrue($computer->update([
            'id'            => $computers_id,
            'is_template'   => 1,
        ]));

        // execute rule engine
        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);
        // check tha rule engine not found any computer
        $this->assertSame(0, $data["found_inventories"][0]);
    }
}
