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

namespace tests\units;

use DbTestCase;

/* Test for inc/ruleimportcomputer.class.php */

class RuleImportAsset extends DbTestCase
{
    private $items_id;
    private $itemtype;
    private $rules_id;
    private $ports_id;

    public function beforeTestMethod($method)
    {
        parent::beforeTestMethod($method);
       //reset local values
        $this->items_id = null;
        $this->itemtype = null;
        $this->rules_id = null;
        $this->ports_id = [];
    }

    public function rulepassed($items_id, $itemtype, $rules_id, $ports_id = [])
    {
        $this->items_id = (int)$items_id;
        $this->itemtype = $itemtype;
        $this->rules_id = (int)$rules_id;
        $this->ports_id = (array)$ports_id;
    }

    protected function enableRule($name)
    {
        global $DB;

        $DB->update(
            \RuleImportAssetCollection::getTable(),
            ['is_active' => 1],
            [
                'name'      => ['LIKE', "%$name%"],
                'is_active' => 0
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
    protected function addRule($name, array $criteria, array $action, $afterRuleName = null)
    {
        global $DB;

        $rule = new \RuleImportAsset();
        $rulecriteria = new \RuleCriteria();

        $input = [
            'is_active' => 1,
            'name'      => $name,
            'match'     => 'AND',
            'sub_type'  => 'RuleImportAsset',
        ];

        $ruleARN = $rule->find(['name' => $afterRuleName ?? 'Computer constraint (name)'], [], 1);
        $this->integer(count($ruleARN))->isIdenticalTo(1);
        $r = current($ruleARN);
        $this->boolean(
            $DB->update(
                'glpi_rules',
                [
                    'ranking' => new \QueryExpression($DB->quoteName('ranking') . ' + 2')
                ],
                [
                    'ranking'   => ['>', $r['ranking']],
                    'sub_type'  => 'RuleImportAsset'
                ]
            )
        )->isTrue();
        $input['ranking'] = ($r['ranking'] + 1);
        $rules_id = $rule->add($input);
        $this->integer($rules_id)->isGreaterThan(0);

       // Add criteria
        foreach ($criteria as $crit) {
            $input = [
                'rules_id'  => $rules_id,
                'criteria'  => $crit['criteria'],
                'pattern'   => $crit['pattern'],
                'condition' => $crit['condition'],
            ];
            $this->integer((int)$rulecriteria->add($input))->isGreaterThan(0);
        }

       // Add action
        $ruleaction = new \RuleAction();
        $input = [
            'rules_id'    => $rules_id,
            'action_type' => $action['action_type'],
            'field'       => $action['field'],
            'value'       => $action['value'],
        ];
        $this->integer((int)$ruleaction->add($input))->isGreaterThan(0);
    }

    public function testCreateComputerName()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer import (by name)");
        $this->integer($this->items_id)->isIdenticalTo(0);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
    }

    public function testUpdateComputerName()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'entities_id'  => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();

        $computers_id = (int)$computer->add($input);
        $this->integer($computers_id)->isGreaterThan(0);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer update (by name)");
        $this->integer($this->items_id)->isIdenticalTo($computers_id);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
    }

    public function testUpdateComputerDoubleName()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'entities_id'  => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();

        $computers_id = $computer->add($input + ['comment' => 'first computer']);
        $this->integer($computers_id)->isGreaterThan(0);

        $computers_id2 = $computer->add($input + ['comment'     => 'second computer']);
        $this->integer($computers_id2)->isGreaterThan(0);

        $this->integer($computers_id)->isNotEqualTo($computers_id2);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer update (by name)");
        $this->integer($this->items_id)->isIdenticalTo($computers_id);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
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
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer import (by serial + uuid)");
        $this->integer($this->items_id)->isIdenticalTo(0);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
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
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();

        $computers_id = (int)$computer->add([
            'entities_id' => 0,
            'name'        => 'pc-02',
            'uuid'     => '01391796-50A4-0246-955B-417652A8AF14',
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer import (by serial + uuid)");
        $this->integer($this->items_id)->isIdenticalTo(0);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
    }

    public function testUpdateComputerSerial_UUID()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'serial'   => '75F4BF',
            'uuid'     => '01391796-50A4-0246-955B-417652A8AF14',
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();

        $computers_id = (int)$computer->add($input);
        $this->integer($computers_id)->isGreaterThan(0);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer update (by serial + uuid)");
        $this->integer($this->items_id)->isIdenticalTo($computers_id);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
    }

    public function testCreateComputerMac()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'mac'      => ['d4:81:d7:7b:6c:21'],
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $this->enableRule('(by mac)');
        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer import (by mac)");
        $this->integer($this->items_id)->isIdenticalTo(0);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
    }

    public function testUpdateComputerMac()
    {
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'mac'      => ['d4:81:d7:7b:6c:21'],
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();
        $nport = new \NetworkPort();

        $computers_id = (int)$computer->add([
            'entities_id' => 0,
            'name'        => 'pc-02', // to be sure the name rule not works before mac rule
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $ports_id = (int)$nport->add([
            'instantiation_type' => 'NetworkPortEthernet',
            'itemtype'           => 'Computer',
            'items_id'           => $computers_id,
            'mac'                => 'd4:81:d7:7b:6c:21'
        ]);
        $this->integer($ports_id)->isGreaterThan(0);

        $this->enableRule('(by mac)');
        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer update (by mac)");
        $this->integer($this->items_id)->isIdenticalTo($computers_id);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
        $this->array($this->ports_id)->isEqualTo([$ports_id]);
    }

    /**
     * Create rules for Computer based on IP
     *
     * @return void
     */
    private function addComputerIPRules()
    {
       // Create rules
        $this->addRule(
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

        $this->addRule(
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
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer import (by ip)");
        $this->integer($this->items_id)->isIdenticalTo(0);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
    }

    public function testUpdateComputerIP()
    {
        $this->addComputerIPRules();
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'ip'       => ['192.168.0.10'],
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();
        $networkPort = new \NetworkPort();

        $computers_id = (int)$computer->add([
            'entities_id' => 0,
            'name'        => 'pc-02', // to be sure the name rule not works before mac rule
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $ports_id = (int)$networkPort->add([
            'instantiation_type' => 'NetworkPortEthernet',
            'itemtype'           => 'Computer',
            'items_id'           => $computers_id,
            'ip'                 => '192.168.0.10',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '192.168.0.10'
            ],
        ]);
        $this->integer($ports_id)->isGreaterThan(0);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer update (by ip)");
        $this->integer($this->items_id)->isIdenticalTo($computers_id);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
        $this->array($this->ports_id)->isEqualTo([$ports_id]);
    }


    /**
     * Create rules for Computer based on IP
     *
     * @return void
     */
    private function addComputerIPLinkOnlyRules()
    {
       // Create rules
        $this->addRule(
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
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer update (by ip)");
       //do not exists in database, so not imported.
        $this->variable($this->items_id)->isNull();
        $this->variable($this->itemtype)->isNull();
    }

    public function testUpdateComputerIPLinkOnly()
    {
        $this->addComputerIPLinkOnlyRules();
        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-01',
            'ip'       => ['192.168.0.10'],
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $computer = new \Computer();
        $networkPort = new \NetworkPort();

        $computers_id = (int)$computer->add([
            'entities_id' => 0,
            'name'        => 'pc-02', // to be sure the name rule not works before mac rule
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $ports_id = (int)$networkPort->add([
            'instantiation_type' => 'NetworkPortEthernet',
            'itemtype'           => 'Computer',
            'items_id'           => $computers_id,
            'ip'                 => '192.168.0.10',
            '_create_children'   => 1,
            'NetworkName_name'   => '',
            'NetworkName_fqdns_id' => 0,
            'NetworkName__ipaddresses' => [
                '-1' => '192.168.0.10'
            ],
        ]);
        $this->integer($ports_id)->isGreaterThan(0);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer update (by ip)");
        $this->integer($this->items_id)->isIdenticalTo($computers_id);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
        $this->array($this->ports_id)->isEqualTo([$ports_id]);
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
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();

        $DB->update(
            $ruleCollection->getTable(),
            [
                'is_active' => 0
            ],
            [
                'is_active' => 1,
            ]
        );

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_no_rule_matches');
        $this->integer((int)$data['_no_rule_matches'])->isIdenticalTo(1);
    }

    protected function refuseProvider()
    {
        return [
            [    // IP
                'rdata'   => ['ip' => '192.168.0.10'],
                'rname'  => 'Global constraint (name)'
            ], [ // IP+mac
                'rdata'   => ['mac' => 'd4:81:d7:7b:6c:21', 'ip' => '192.168.0.10'],
                'rname'  => 'Global constraint (name)'
            ], [ // IP+name
                'rdata'   => ['name' => 'pc-01', 'ip' => '192.168.0.10'],
                'rname'  => 'Global import denied'
            ], [ // IP+mac+name
                'rdata'   => ['name' => 'pc-01', 'mac' => 'd4:81:d7:7b:6c:21', 'ip' => '192.168.0.10'],
                'rname'  => 'Global import denied'
            ]
        ];
    }

    /**
     * With default rules, refuse import in theses cases
     *
     * @param array  $rdata  Rules data to use
     * @param string $rname  Expected rule name
     *
     * @dataProvider refuseProvider
     */
    public function testRefuseImport($rdata, $rname)
    {
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($rdata, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo($rname);
        $this->variable($this->items_id)->isNull();
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
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Global import (by mac+ifnumber)");
        $this->integer($this->items_id)->isIdenticalTo(0);
        $this->string($this->itemtype)->isIdenticalTo('Unmanaged'); //not handled yet...
        $this->array($this->ports_id)->isEmpty();
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
            'entities_id' => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $networkEquipment = new \NetworkEquipment();
        $networkPort = new \NetworkPort();

        $networkEquipments_id = (int)$networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'network-02',
        ]);
        $this->integer($networkEquipments_id)->isGreaterThan(0);

        $ports_id = (int)$networkPort->add([
            'mac'                => '00:1a:6c:9a:fc:99',
            'name'               => 'Gi0/1',
            'logical_number'     => '10101',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
        ]);
        $this->integer($ports_id)->isGreaterThan(0);

        $ports_id = (int)$networkPort->add([
            'mac'                => '00:1a:6c:9a:fc:99',
            'name'               => 'Gi0/2',
            'logical_number'     => '10102',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $networkEquipments_id,
            'itemtype'           => 'NetworkEquipment',
        ]);
        $this->integer($ports_id)->isGreaterThan(0);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Global update (by mac+ifnumber restricted port)");
        $this->integer($this->items_id)->isIdenticalTo($networkEquipments_id);
        $this->string($this->itemtype)->isIdenticalTo('NetworkEquipment');
        $this->array($this->ports_id)->isEqualTo([$ports_id]);
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

        $networkEquipments_id = (int)$networkEquipment->add([
            'entities_id' => 0,
            'name'        => 'network-02',
        ]);
        $this->integer($networkEquipments_id)->isGreaterThan(0);

        $ports_id_1 = (int)$networkPort->add([
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
                '-1' => '192.168.0.1'
            ],
            'ifdescr' => 'FastEthernet0/1',
        ]);
        $this->integer($ports_id_1)->isGreaterThan(0);

        $ports_id_2 = (int)$networkPort->add([
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
                '-1' => '192.168.0.2'
            ],
            'ifdescr' => 'FastEthernet0/2',
        ]);
        $this->integer($ports_id_2)->isGreaterThan(0);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Global update (by ip+ifdescr restricted port)");
        $this->integer($this->items_id)->isIdenticalTo($networkEquipments_id);
        $this->string($this->itemtype)->isIdenticalTo('NetworkEquipment');
        $this->array($this->ports_id)->isEqualTo([$ports_id_1]);

        $this->items_id = 0;
        $this->itemtype = "";
        $this->ports_id = [];
        $input = [
            'ifdescr' => 'FastEthernet0/1',
            'ip'      => '192.168.0.2',
            'entities_id' => 0
        ];
        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isNotEqualTo("Device update (by ip+ifdescr restricted port)");
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
        $this->integer($networkEquipments_id)->isGreaterThan(0);

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
                '-1' => '192.168.0.1'
            ],
            'ifdescr'         => 'FastEthernet0/1',
        ]);
        $this->integer($ports_id)->isGreaterThan(0);
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
                '-1' => '192.168.0.2'
            ],
            'ifdescr'         => 'FastEthernet0/2',
        ]);
        $this->integer($ports_id)->isGreaterThan(0);
        $a_portids[] = $ports_id;

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->integer((int)$data['_ruleid'])->isGreaterThan(0);

        $this->boolean($rule->getFromDB($data['_ruleid']))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo('Global update (by ip+ifdescr not restricted port)');
        $this->integer($this->items_id)->isIdenticalTo($networkEquipments_id);
        $this->string($this->itemtype)->isIdenticalTo('NetworkEquipment');
        $this->array($this->ports_id)->hasSize(count($a_portids));
        $this->array($this->ports_id)->containsValues($a_portids);
    }

    /**
     * Case have only the mac address (mac found on switches)
     */
    public function testSearchMac_nomoredata()
    {
        $input = [
            'mac' => 'd4:81:b4:5a:a6:19'
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();
        $printer = new \Printer();
        $networkPort = new \NetworkPort();

        $printers_id = (int)$printer->add([
            'entities_id' => 0,
            'name'        => 'network-02',
        ]);
        $this->integer($printers_id)->isGreaterThan(0);

        $ports_id_1 = (int)$networkPort->add([
            'mac'                => 'd4:81:b4:5a:a6:18',
            'name'               => 'Fa0/1',
            'logical_number'     => '10101',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $printers_id,
            'itemtype'           => 'Printer',
        ]);
        $this->integer($ports_id_1)->isGreaterThan(0);

        $ports_id_2 = (int)$networkPort->add([
            'mac'                => 'd4:81:b4:5a:a6:19',
            'name'               => 'Fa0/2',
            'logical_number'     => '10102',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $printers_id,
            'itemtype'           => 'Printer',
        ]);
        $this->integer($ports_id_2)->isGreaterThan(0);

        $ports_id_3 = (int)$networkPort->add([
            'mac'                => 'd4:81:b4:5a:a6:20',
            'name'               => 'Fa0/3',
            'logical_number'     => '10103',
            'instantiation_type' => 'NetworkPortEthernet',
            'items_id'           => $printers_id,
            'itemtype'           => 'Printer',
        ]);
        $this->integer($ports_id_3)->isGreaterThan(0);

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Update only mac address (mac on switch port)");
        $this->integer($this->items_id)->isIdenticalTo($printers_id);
        $this->string($this->itemtype)->isIdenticalTo('Printer');
        $this->array($this->ports_id)->isEqualTo([$ports_id_2]);
    }

    public function testGetTitle()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->getTitle())
            ->isIdenticalTo('Rules for import and link equipments');
    }

    public function testMaxActionsCount()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->integer($this->testedInstance->maxActionsCount())
            ->isIdenticalTo(1);
    }

    public function testGetCriteria()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->getCriterias())
            ->hasSize(23);
    }

    public function testGetActions()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->getActions())
            ->hasSize(2);
    }

    public function testGetRuleActionValues()
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->getRuleActionValues())
            ->hasSize(3);
    }

    protected function ruleactionProvider()
    {
        $known = \RuleImportAsset::getRuleActionValues();

        $values = [];
        foreach ($known as $k => $v) {
            $values[] = [
                'value'     => $k,
                'expected'  => $v
            ];
        }

        $values[] = [
            'value'     => 404,
            'expected'  => ''
        ];

        return $values;
    }

    /**
     * @dataProvider ruleactionProvider
     *
     * @param integer $value    Value to test
     * @param string  $expected Excpected result
     *
     * @return void
     */
    public function testDisplayAdditionRuleActionValue($value, $expected)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->displayAdditionRuleActionValue($value))
            ->isIdenticalTo($expected);
    }

    protected function moreCritProvider()
    {
        return [
            [
                'criterion' => 'entityrestrict',
                'expected'  => [
                    \RuleImportAsset::PATTERN_ENTITY_RESTRICT => 'Yes'
                ]
            ], [
                'criterion' => 'link_criteria_port',
                'expected'  => [
                    \RuleImportAsset::PATTERN_NETWORK_PORT_RESTRICT => 'Yes'
                ]
            ], [
                'criterion' => 'only_these_criteria',
                'expected'  => [
                    \RuleImportAsset::PATTERN_ONLY_CRITERIA_RULE => 'Yes'
                ]
            ], [
                'criterion' => 'any_other',
                'expected'  => [
                    \RuleImportAsset::PATTERN_FIND => 'is already present',
                    \RuleImportAsset::PATTERN_IS_EMPTY => 'is empty'
                ]
            ]
        ];
    }

    /**
     * @dataProvider moreCritProvider
     *
     * @param string $criterion Criterion to test
     * @param array $expected   Expected result
     *
     * @return void
     */
    public function testAddMoreCriteria($criterion, $expected)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->addMoreCriteria($criterion))
            ->isIdenticalTo($expected);
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
        $this->integer((int)$computer->add(\Toolbox::addslashes_deep($fields)))->isGreaterThan(0);

        $input = [
            'itemtype' => 'Computer',
            'name'     => 'pc-02',
            'serial'   => '75F4BFC',
            'uuid'     => '01391796-50A4-0246-955B-417652A8AF14',
            'entities_id' => 0
        ];

        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer update (by serial + uuid is empty in GLPI)");
        $this->string($this->itemtype)->isIdenticalTo('Computer');
    }

    /**
     * Create rules for update Computer based on computer name and TAG
     *
     * @return void
     */
    private function updateComputerAgentTagRules()
    {
       // Create rules
        $this->addRule(
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
        $computers_id = (int)$computer->add([
            'entities_id' => 0,
            'name'        => 'pc-11',
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        //create linked agent
        $agent = new \Agent();
        $agenttype = $DB->request(['FROM' => \AgentType::getTable(), 'WHERE' => ['name' => 'Core']])->current();
        $agents_id = (int)$agent->add([
            'deviceid' => 'my_specific_agent_deviceid',
            'tag' => 'my_specific_agent_tag',
            'itemtype' => "Computer",
            'agenttypes_id' => $agenttype['id'],
            'items_id' => $computers_id,
            'name'        => 'pc-11',
        ]);
        $this->integer($agents_id)->isGreaterThan(0);

        $input = [
            'itemtype'      => 'Computer',
            'name'          => 'pc-11',
            'tag'           => 'my_specific_agent_tag',
            'deviceid'     => 'my_specific_agent_deviceid',
            'ip'            => ['192.168.0.10'],
            'entities_id'   => 0
        ];
        $ruleCollection = new \RuleImportAssetCollection();
        $rule = new \RuleImportAsset();

        $data = $ruleCollection->processAllRules($input, [], ['class' => $this]);

        $this->array($data)->hasKey('_ruleid');
        $_rule_id = (int)$data['_ruleid'];
        $this->integer($_rule_id)->isGreaterThan(0);

        $this->boolean($rule->getFromDB($_rule_id))->isTrue();
        $this->string($rule->fields['name'])->isIdenticalTo("Computer update (by name and tag)");
        $this->integer($this->items_id)->isIdenticalTo($computers_id);
        $this->string($this->itemtype)->isIdenticalTo('Computer');
    }
}
