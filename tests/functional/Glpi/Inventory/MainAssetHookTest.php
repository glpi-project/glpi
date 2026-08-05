<?php

/**
 * Tests for the MainAsset hook in GLPI's inventory process.
 */

namespace tests\units\Glpi\Inventory;

use Glpi\Inventory\MainAsset\MainAsset;
use Glpi\Tests\InventoryTestCase;

class MainAssetHookTest extends InventoryTestCase
{
    private const HOOK_NAME = 'post_process_import_entity_rules';

    public function tearDown(): void
    {
        global $PLUGIN_HOOKS;

        unset($PLUGIN_HOOKS[self::HOOK_NAME]['tester']);

        parent::tearDown();
    }

    // Sanity/regression check: runs a normal inventory with no plugin listening to the hook, confirming the new Plugin::doHookFunction() call doesn't break the default flow (computer still gets created in the default entity 0).
    public function testInventoryStillWorksWithoutHookRegistered(): void
    {
        $computer = new \Computer();
        $xml_source = $this->getComputerXml('hooktest-none', '11111111-0000-0000-0000-000000000001');

        $inventory = $this->doInventory($xml_source, true);
        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        $this->assertTrue($computer->getFromDB($computers_id));
        $this->assertSame(0, $computer->fields['entities_id']);
    }

    // Verifies the hook is fired exactly once per asset and validates its contract: the callback receives a stdClass with mainAssetObj (the MainAsset instance being processed) and rulesTargetEntity (the array result from the entity rules engine).
    public function testHookIsCalledOnceWithExpectedParameters(): void
    {
        global $PLUGIN_HOOKS;

        $calls = [];
        $PLUGIN_HOOKS[self::HOOK_NAME]['tester'] = function ($params) use (&$calls) {
            $calls[] = $params;
            return $params;
        };

        $xml_source = $this->getComputerXml('hooktest-params', '11111111-0000-0000-0000-000000000002');
        $inventory = $this->doInventory($xml_source, true);
        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        $this->assertCount(1, $calls);

        $params = $calls[0];
        $this->assertInstanceOf(\stdClass::class, $params);
        $this->assertTrue(property_exists($params, 'mainAssetObj'));
        $this->assertTrue(property_exists($params, 'rulesTargetEntity'));

        $this->assertInstanceOf(MainAsset::class, $params->mainAssetObj);
        $this->assertSame('Computer', $params->mainAssetObj->getItem()->getType());

        $this->assertIsArray($params->rulesTargetEntity);
    }

    // Confirms a plugin can actually influence the outcome: the hook calls $params->mainAssetObj->setEntityID(...) to force a different entity, and the test checks the computer ends up created in that overridden entity instead of the rule-engine's original choice.
    public function testHookCanOverrideTargetEntity(): void
    {
        global $PLUGIN_HOOKS;

        $this->login();
        $entity = new \Entity();
        $entities_id_override = $entity->add([
            'name'        => 'Hook Override Entity',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $entities_id_override);
        $this->logout();

        $PLUGIN_HOOKS[self::HOOK_NAME]['tester'] = function ($params) use ($entities_id_override) {
            $params->mainAssetObj->setEntityID($entities_id_override);
            return $params;
        };

        $computer = new \Computer();
        $xml_source = $this->getComputerXml('hooktest-override', '11111111-0000-0000-0000-000000000003');
        $inventory = $this->doInventory($xml_source, true);
        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        $this->assertTrue($computer->getFromDB($computers_id));
        $this->assertSame($entities_id_override, $computer->fields['entities_id']);
    }

    // Sets up a real RuleImportEntity rule that assigns a specific entity, then uses a passthrough (non-mutating) hook to capture rulesTargetEntity. It checks that the captured data matches what the rule engine actually produced (entities_id, _ruleid), and that when the hook doesn't touch mainAssetObj, the rule engine's decision is preserved untouched in the final computer.
    public function testHookReceivesRuleEngineResultAndDoesNotAlterItWhenUnmodified(): void
    {
        global $PLUGIN_HOOKS;

        $this->login();
        $entity = new \Entity();
        $entities_id_rule = $entity->add([
            'name'        => 'Hook Rule Entity',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $entities_id_rule);

        $rule = new \Rule();
        $rules_id = $rule->add([
            'is_active' => 1,
            'name'      => 'entity rule for hook test',
            'match'     => 'AND',
            'sub_type'  => 'RuleImportEntity',
            'ranking'   => 1,
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $rulecriteria = new \RuleCriteria();
        $this->assertGreaterThan(0, $rulecriteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'name',
            'pattern'   => '/(.*)/',
            'condition' => \RuleImportEntity::REGEX_MATCH,
        ]));

        $ruleaction = new \RuleAction();
        $this->assertGreaterThan(0, $ruleaction->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'entities_id',
            'value'       => $entities_id_rule,
        ]));
        $this->logout();

        $captured = null;
        // Passthrough hook: it must not change the outcome of the rules engine.
        $PLUGIN_HOOKS[self::HOOK_NAME]['tester'] = function ($params) use (&$captured) {
            $captured = $params;
            return $params;
        };

        $computer = new \Computer();
        $xml_source = $this->getComputerXml('hooktest-rule', '11111111-0000-0000-0000-000000000004');
        $inventory = $this->doInventory($xml_source, true);
        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        $this->assertNotNull($captured);
        /** @var \stdClass $captured */
        $this->assertIsArray($captured->rulesTargetEntity);
        $this->assertEquals($entities_id_rule, $captured->rulesTargetEntity['entities_id']);
        $this->assertEquals($rules_id, $captured->rulesTargetEntity['_ruleid']);

        $this->assertTrue($computer->getFromDB($computers_id));
        $this->assertSame($entities_id_rule, $computer->fields['entities_id']);
    }

    // Confirms that when the hook overrides the target entity, that override is visible to rules engines running after the hook (RuleLocation here), not just to the final item. A RuleImportEntity rule first assigns the asset to $entities_id_rule; the hook then overrides it to $entities_id_override via setEntityID(). A RuleLocation rule imports a new Location named after the asset, scoped to whatever entity is in $input['entities_id'] at the time it runs. If the $input['entities_id'] = $this->entities_id; line after the hook call were removed, the location would incorrectly be imported into $entities_id_rule instead of $entities_id_override. This validates the $input['entities_id'] = $this->entities_id; line that re-reads the value after the hook runs.
    public function testHookOverrideIsVisibleToSubsequentLocationRule(): void
    {
        global $PLUGIN_HOOKS;

        $this->login();
        $entity = new \Entity();
        $entities_id_rule = $entity->add([
            'name'        => 'Hook Location Rule Entity',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $entities_id_rule);

        $entities_id_override = $entity->add([
            'name'        => 'Hook Location Override Entity',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $entities_id_override);

        $rule = new \Rule();
        $rules_id = $rule->add([
            'is_active' => 1,
            'name'      => 'entity rule for location hook test',
            'match'     => 'AND',
            'sub_type'  => 'RuleImportEntity',
            'ranking'   => 1,
        ]);
        $this->assertGreaterThan(0, $rules_id);

        $rulecriteria = new \RuleCriteria();
        $this->assertGreaterThan(0, $rulecriteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'name',
            'pattern'   => '/(.*)/',
            'condition' => \RuleImportEntity::REGEX_MATCH,
        ]));

        $ruleaction = new \RuleAction();
        $this->assertGreaterThan(0, $ruleaction->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'entities_id',
            'value'       => $entities_id_rule,
        ]));

        $location_rule = new \Rule();
        $location_rules_id = $location_rule->add([
            'is_active' => 1,
            'name'      => 'location rule for location hook test',
            'match'     => 'AND',
            'sub_type'  => 'RuleLocation',
            'ranking'   => 1,
        ]);
        $this->assertGreaterThan(0, $location_rules_id);

        $location_rulecriteria = new \RuleCriteria();
        $this->assertGreaterThan(0, $location_rulecriteria->add([
            'rules_id'  => $location_rules_id,
            'criteria'  => 'name',
            'pattern'   => '/(.*)/',
            'condition' => \RuleImportEntity::REGEX_MATCH,
        ]));

        $location_ruleaction = new \RuleAction();
        $this->assertGreaterThan(0, $location_ruleaction->add([
            'rules_id'    => $location_rules_id,
            'action_type' => 'regex_result',
            'field'       => 'locations_id',
            'value'       => '#0',
        ]));
        $this->logout();

        $PLUGIN_HOOKS[self::HOOK_NAME]['tester'] = function ($params) use ($entities_id_override) {
            $params->mainAssetObj->setEntityID($entities_id_override);
            return $params;
        };

        $computer = new \Computer();
        $computer_name = 'hooktest-location-override';
        $xml_source = $this->getComputerXml($computer_name, '11111111-0000-0000-0000-000000000005');
        $inventory = $this->doInventory($xml_source, true);
        $computers_id = $inventory->getItem()->fields['id'];
        $this->assertGreaterThan(0, $computers_id);

        $this->assertTrue($computer->getFromDB($computers_id));
        $this->assertSame($entities_id_override, $computer->fields['entities_id']);

        $location = new \Location();
        $this->assertTrue($location->getFromDbByCrit(['name' => $computer_name]));
        $this->assertSame($entities_id_override, $location->fields['entities_id']);
    }

    private function getComputerXml(string $name, string $uuid): string
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <REQUEST>
        <CONTENT>
          <HARDWARE>
            <NAME>{$name}</NAME>
            <UUID>{$uuid}</UUID>
          </HARDWARE>
          <BIOS>
            <MSN>{$name}-msn</MSN>
          </BIOS>
          <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
        </CONTENT>
        <DEVICEID>{$name}.teclib.infra-2018-10-03-08-42-36</DEVICEID>
        <QUERY>INVENTORY</QUERY>
        </REQUEST>";
    }
}
